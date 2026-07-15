<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\SupabaseStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    private const IMAGE_BUCKET = 'product-images';

    public function __construct(private readonly SupabaseStorage $storage) {}

    public function index(Request $request)
    {
        $query = Product::query()
            ->select(['id', 'category_id', 'sku', 'name', 'price', 'stock_quantity', 'min_stock_level', 'image', 'created_at'])
            ->with('category:id,name');

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

        return view('products', compact('products', 'categories', 'allCategories', 'categoryStats', 'stats'));
    }

    public function create()
    {
        return redirect()->route('products.index', ['create' => 1]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'    => 'required|exists:categories,id',
            'sku'            => 'required|unique:products,sku',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level'=> 'required|integer|min:0',
            'image'          => 'nullable|image|max:2048',
            'sizes'          => 'nullable|array',
            'sizes.*'        => 'in:S,M,L,XL',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $this->storage->uploadImage(
                $request->file('image'),
                self::IMAGE_BUCKET,
                'products',
            );
        }

        DB::transaction(function () use ($validated) {
            $product = Product::create($validated);

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
        $validated = $request->validate([
            'category_id'    => 'required|exists:categories,id',
            'sku'            => 'required|unique:products,sku,'.$product->id,
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level'=> 'required|integer|min:0',
            'image'          => 'nullable|image|max:2048',
            'sizes'          => 'nullable|array',
            'sizes.*'        => 'in:S,M,L,XL',
        ]);

        // If no sizes were checked, explicitly set to null so existing data is cleared.
        if (empty($validated['sizes'])) {
            $validated['sizes'] = null;
        }

        $oldImage = $product->image;
        $oldStock = $product->stock_quantity;

        if ($request->hasFile('image')) {
            $validated['image'] = $this->storage->uploadImage(
                $request->file('image'),
                self::IMAGE_BUCKET,
                'products',
            );
        }

        DB::transaction(function () use ($product, $validated, $oldStock) {
            $product->update($validated);

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
}
