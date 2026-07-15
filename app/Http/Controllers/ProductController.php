<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductSize;
use App\Services\SupabaseStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    private const IMAGE_BUCKET = 'product-images';

    public function __construct(private readonly SupabaseStorage $storage) {}

    public function index(Request $request)
    {
        $query = Product::query()
            ->select(['id', 'category_id', 'sku', 'name', 'description', 'price', 'stock_quantity', 'min_stock_level', 'image', 'created_at'])
            ->with(['category:id,name', 'sizes:id,product_id,size,stock_quantity']);

        if ($request->filled('search')) {
            $search = (string) $request->string('search')->trim();
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }

        if ($request->filled('stock')) {
            match ($request->stock) {
                'low' => $query->where('stock_quantity', '>', 0)
                    ->whereColumn('stock_quantity', '<=', 'min_stock_level'),
                'out' => $query->where('stock_quantity', 0),
                default => null,
            };
        }

        $products = $query->latest()->paginate(20)->withQueryString();
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $allCategories = Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();
        $categoryStats = [
            'total' => $allCategories->count(),
            'active' => $allCategories->where('is_active', true)->count(),
            'inactive' => $allCategories->where('is_active', false)->count(),
            'products' => $allCategories->sum('products_count'),
        ];
        $productStats = (array) Product::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COUNT(*) as total,
                COUNT(CASE WHEN stock_quantity > 0 AND stock_quantity <= min_stock_level THEN 1 END) as low,
                COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out,
                COALESCE(SUM(price * stock_quantity), 0) as inventory_value
                SQL
            )
            ->first();

        $stats = [
            'total' => (int) $productStats['total'],
            'low' => (int) $productStats['low'],
            'out' => (int) $productStats['out'],
            'inventory_value' => (float) $productStats['inventory_value'],
        ];

        $availableSizes = ProductSize::AVAILABLE_SIZES;

        return view('products', compact('products', 'categories', 'allCategories', 'categoryStats', 'stats', 'availableSizes'));
    }

    public function create()
    {
        return redirect()->route('products.index', ['create' => 1]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);
        $sizeStocks = $validated['size_stocks'];
        unset($validated['size_stocks']);

        if ($request->hasFile('image')) {
            $validated['image'] = $this->storage->uploadImage(
                $request->file('image'),
                self::IMAGE_BUCKET,
                'products',
            );
        }

        DB::transaction(function () use ($validated, $sizeStocks) {
            $product = Product::create($validated);
            $this->syncSizeStocks($product, $sizeStocks);

            if ($product->stock_quantity > 0) {
                InventoryTransaction::create([
                    'product_id' => $product->id,
                    'admin_id' => auth()->id(),
                    'type' => 'In',
                    'quantity' => $product->stock_quantity,
                    'stock_before' => 0,
                    'stock_after' => $product->stock_quantity,
                    'notes' => 'Opening stock',
                ]);
            }
        });

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        return redirect()->route('products.index', ['edit' => $product->id]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProduct($request, $product);
        $sizeStocks = $validated['size_stocks'];
        unset($validated['size_stocks']);

        $oldImage = $product->image;
        $oldStock = $product->stock_quantity;

        if ($request->hasFile('image')) {
            $validated['image'] = $this->storage->uploadImage(
                $request->file('image'),
                self::IMAGE_BUCKET,
                'products',
            );
        }

        DB::transaction(function () use ($product, $validated, $oldStock, $sizeStocks) {
            $product->update($validated);
            $this->syncSizeStocks($product, $sizeStocks);

            if ($oldStock !== $product->stock_quantity) {
                InventoryTransaction::create([
                    'product_id' => $product->id,
                    'admin_id' => auth()->id(),
                    'type' => 'Adjustment',
                    'quantity' => abs($product->stock_quantity - $oldStock),
                    'stock_before' => $oldStock,
                    'stock_after' => $product->stock_quantity,
                    'notes' => 'Stock adjusted from product page',
                ]);
            }
        });

        if ($request->hasFile('image') && $oldImage) {
            $this->deleteImage($oldImage);
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            $this->deleteImage($product->image);
        }

        $product->delete();

        return to_route('products.index')->with('success', 'Product deleted successfully.');
    }

    private function deleteImage(string $image): void
    {
        if (str_contains($image, '/storage/v1/object/public/'.self::IMAGE_BUCKET.'/')) {
            $this->storage->deletePublicFile($image, self::IMAGE_BUCKET);

            return;
        }

        // Remove images created by the previous local-storage implementation.
        if (! str_starts_with($image, 'http://') && ! str_starts_with($image, 'https://')) {
            Storage::disk('public')->delete($image);
        }
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'required_unless:has_sizes,1', 'integer', 'min:0'],
            'min_stock_level' => ['required', 'integer', 'min:0'],
            'has_sizes' => ['nullable', 'boolean'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['string', 'in:'.implode(',', ProductSize::AVAILABLE_SIZES)],
            'size_stock' => ['nullable', 'array'],
            'size_stock.*' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $sizeStocks = [];

        if ($request->boolean('has_sizes')) {
            $selectedSizes = array_values(array_unique($validated['sizes'] ?? []));

            if ($selectedSizes === []) {
                throw ValidationException::withMessages([
                    'sizes' => 'Select at least one available size.',
                ]);
            }

            foreach ($selectedSizes as $size) {
                $stock = $validated['size_stock'][$size] ?? null;

                if ($stock === null || $stock === '') {
                    throw ValidationException::withMessages([
                        "size_stock.$size" => "Enter the stock quantity for size $size.",
                    ]);
                }

                $sizeStocks[$size] = (int) $stock;
            }

            $validated['stock_quantity'] = array_sum($sizeStocks);
        } else {
            $validated['stock_quantity'] = (int) $validated['stock_quantity'];
        }

        unset($validated['has_sizes'], $validated['sizes'], $validated['size_stock']);
        $validated['size_stocks'] = $sizeStocks;

        return $validated;
    }

    private function syncSizeStocks(Product $product, array $sizeStocks): void
    {
        if ($sizeStocks === []) {
            $product->sizes()->delete();

            return;
        }

        $product->sizes()->whereNotIn('size', array_keys($sizeStocks))->delete();

        foreach ($sizeStocks as $size => $stockQuantity) {
            $product->sizes()->updateOrCreate(
                ['size' => $size],
                ['stock_quantity' => $stockQuantity],
            );
        }
    }
}
