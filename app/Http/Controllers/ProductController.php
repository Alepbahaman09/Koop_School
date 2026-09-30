<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductActivityLog;
use App\Models\ProductSize;
use App\Models\Supplier;
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
            ->select([
                'id', 'category_id', 'supplier_id', 'sku', 'name', 'description',
                'price', 'cost_price', 'unit', 'purchase_unit', 'units_per_carton',
                'stock_quantity', 'min_stock_level', 'image', 'is_active', 'created_at',
            ])
            ->with(['category:id,name', 'sizes:id,product_id,size,stock_quantity', 'supplier:id,company_name']);

        // Search by product name or SKU
        if ($request->filled('search')) {
            $search = (string) $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Category filter: handles specific category id or 'uncategorized'
        if ($request->filled('category')) {
            if ($request->category === 'uncategorized') {
                $query->whereNull('category_id');
            } else {
                $query->where('category_id', $request->integer('category'));
            }
        }

        // Supplier filter
        if ($request->filled('supplier')) {
            $query->where('supplier_id', $request->integer('supplier'));
        }

        // Stock & status filter (low, out, hidden, visible)
        if ($request->filled('stock')) {
            match ($request->stock) {
                'low' => $query->where('stock_quantity', '>', 0)
                    ->whereColumn('stock_quantity', '<=', 'min_stock_level'),
                'out' => $query->where('stock_quantity', 0),
                'hidden' => $query->where('is_active', false),
                'visible', 'published' => $query->where('is_active', true),
                default => null,
            };
        }

        $products = $query->latest()->paginate(20)->withQueryString();

        // Active categories for adding/editing products
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // All categories with their product counts for the category manager & filter
        $allCategories = Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $uncategorizedCount = Product::whereNull('category_id')->count();

        $categoryStats = [
            'total' => $allCategories->count(),
            'active' => $allCategories->where('is_active', true)->count(),
            'inactive' => $allCategories->where('is_active', false)->count(),
            'products' => $allCategories->sum('products_count'),
            'uncategorized' => $uncategorizedCount,
        ];

        // 4 top stats: Total Products, Low Stock, Out of Stock, Hidden Products
        $productStats = (array) Product::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COUNT(*) as total,
                COUNT(CASE WHEN stock_quantity > 0 AND stock_quantity <= min_stock_level THEN 1 END) as low,
                COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out,
                COUNT(CASE WHEN is_active = false THEN 1 END) as hidden,
                COALESCE(SUM(price * stock_quantity), 0) as inventory_value,
                COALESCE(SUM(stock_quantity), 0) as total_units
                SQL
            )
            ->first();

        $stats = [
            'total' => (int) ($productStats['total'] ?? 0),
            'low' => (int) ($productStats['low'] ?? 0),
            'out' => (int) ($productStats['out'] ?? 0),
            'hidden' => (int) ($productStats['hidden'] ?? 0),
            'inventory_value' => (float) ($productStats['inventory_value'] ?? 0),
            'total_units' => (int) ($productStats['total_units'] ?? 0),
        ];

        $availableSizes = ProductSize::AVAILABLE_SIZES;
        $suppliers = Supplier::orderBy('company_name')->get(['id', 'company_name']);

        // Recent activity audit logs for the report trace modal
        $activityLogs = ProductActivityLog::latest()->take(60)->get();

        // Full products list for the printable/exportable report table
        $reportProducts = Product::with(['category:id,name', 'supplier:id,company_name'])
            ->orderBy('name')
            ->get();

        return view('products', compact(
            'products',
            'categories',
            'allCategories',
            'categoryStats',
            'stats',
            'availableSizes',
            'suppliers',
            'uncategorizedCount',
            'activityLogs',
            'reportProducts'
        ));
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
                    'notes' => 'Opening stock recorded',
                ]);
            }

            ProductActivityLog::log(
                'created',
                'product',
                $product->id,
                $product->name,
                "Created product SKU: {$product->sku}, Price: RM " . number_format($product->price, 2) . ", Initial Stock: {$product->stock_quantity}, App Status: " . ($product->is_active ? 'Published' : 'Hidden')
            );
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
        $oldPrice = $product->price;
        $oldActive = $product->is_active;

        if ($request->hasFile('image')) {
            $validated['image'] = $this->storage->uploadImage(
                $request->file('image'),
                self::IMAGE_BUCKET,
                'products',
            );
        }

        DB::transaction(function () use ($product, $validated, $oldStock, $oldPrice, $oldActive, $sizeStocks) {
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
                    'notes' => 'Stock adjusted manually from product page',
                ]);
            }

            $changes = [];
            if ($oldStock !== $product->stock_quantity) {
                $changes[] = "Stock changed: {$oldStock} -> {$product->stock_quantity}";
            }
            if ((float) $oldPrice !== (float) $product->price) {
                $changes[] = "Price: RM {$oldPrice} -> RM {$product->price}";
            }
            if ($oldActive !== $product->is_active) {
                $changes[] = "App Status: " . ($product->is_active ? 'Published to App' : 'Hidden from App');
            }

            $detailText = count($changes) > 0 ? implode(', ', $changes) : 'Product details updated';

            ProductActivityLog::log(
                'updated',
                'product',
                $product->id,
                $product->name,
                $detailText
            );
        });

        if ($request->hasFile('image') && $oldImage) {
            $this->deleteImage($oldImage);
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $productName = $product->name;
        $productId = $product->id;
        $sku = $product->sku;

        // Ensure purchase history has the product image, name and sku stored before product deletion
        \App\Models\StockPurchaseItem::where('product_id', $product->id)->each(function ($item) use ($product) {
            $updates = [];
            if (! $item->item_name) $updates['item_name'] = $product->name;
            if (! $item->item_sku)  $updates['item_sku']  = $product->sku;
            if (! $item->item_image && $product->image) $updates['item_image'] = $product->image;
            if (! empty($updates)) {
                $item->update($updates);
            }
        });

        // Keep the image in storage if referenced in historical purchase records
        $hasPurchaseHistory = \App\Models\StockPurchaseItem::where('product_id', $product->id)
            ->orWhere('item_image', $product->image)
            ->exists();

        if ($product->image && ! $hasPurchaseHistory) {
            $this->deleteImage($product->image);
        }

        $product->sizes()->delete();
        $product->delete();

        ProductActivityLog::log(
            'deleted',
            'product',
            $productId,
            $productName,
            "Permanently deleted product '{$productName}' (SKU: {$sku})"
        );

        return to_route('products.index')->with('success', 'Product deleted successfully.');
    }

    /**
     * Export full product report as a formatted PDF document.
     */
    public function exportPdf(Request $request)
    {
        $withActions = $request->boolean('with_actions', true);
        $products = Product::with(['category', 'supplier'])->orderBy('name')->get();

        $stats = [
            'total' => $products->count(),
            'total_units' => $products->sum('stock_quantity'),
            'inventory_value' => (float) $products->sum(fn ($p) => $p->price * $p->stock_quantity),
            'low_stock' => $products->filter(fn ($p) => $p->stock_quantity > 0 && $p->stock_quantity <= $p->min_stock_level)->count(),
            'out_of_stock' => $products->filter(fn ($p) => $p->stock_quantity === 0)->count(),
            'hidden' => $products->filter(fn ($p) => ! $p->is_active)->count(),
        ];

        $activityLogs = $withActions
            ? ProductActivityLog::whereIn('entity_type', ['product', 'category'])->latest()->take(100)->get()
            : collect();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.product-pdf', compact('products', 'stats', 'activityLogs', 'withActions'))
            ->setPaper('a4', 'landscape');

        if ($request->boolean('stream')) {
            return $pdf->stream('product-catalogue-report-' . date('Y-m-d') . '.pdf');
        }

        return $pdf->download('product-catalogue-report-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export full product report as CSV.
     */
    public function exportReport()
    {
        $products = Product::with(['category', 'supplier'])->orderBy('name')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product-catalogue-report-' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Product ID', 'SKU', 'Product Name', 'Category', 'Supplier', 'Selling Price (RM)', 'Cost Price (RM)', 'Stock Quantity', 'Unit', 'Stock Status', 'App Status', 'Total Value (RM)'], ',', '"', '\\');

            foreach ($products as $p) {
                $stockStatus = $p->stock_quantity === 0 ? 'Out of Stock' : ($p->stock_quantity <= $p->min_stock_level ? 'Low Stock' : 'In Stock');
                $appStatus = $p->is_active ? 'Published' : 'Hidden';
                $totalVal = round($p->price * $p->stock_quantity, 2);

                fputcsv($file, [
                    $p->id,
                    $p->sku,
                    $p->name,
                    $p->category?->name ?? 'Uncategorised',
                    $p->supplier?->company_name ?? '—',
                    number_format($p->price, 2),
                    number_format($p->cost_price ?? 0, 2),
                    $p->stock_quantity,
                    $p->unit ?? 'pcs',
                    $stockStatus,
                    $appStatus,
                    number_format($totalVal, 2),
                ], ',', '"', '\\');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function deleteImage(string $image): void
    {
        if (str_contains($image, '/storage/v1/object/public/'.self::IMAGE_BUCKET.'/')) {
            $this->storage->deletePublicFile($image, self::IMAGE_BUCKET);

            return;
        }

        if (! str_starts_with($image, 'http://') && ! str_starts_with($image, 'https://')) {
            Storage::disk('public')->delete($image);
        }
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'stock_quantity' => ['nullable', 'required_unless:has_sizes,1', 'integer', 'min:0'],
            'min_stock_level' => ['required', 'integer', 'min:0'],
            'has_sizes' => ['nullable', 'boolean'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['string', 'in:'.implode(',', ProductSize::AVAILABLE_SIZES)],
            'size_stock' => ['nullable', 'array'],
            'size_stock.*' => ['nullable', 'integer', 'min:0'],
            'size_price' => ['nullable', 'array'],
            'size_price.*' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['unit'] = !empty($validated['unit']) ? $validated['unit'] : ($product?->unit ?? 'pcs');

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

                $price = !empty($validated['size_price'][$size])
                    ? (float) $validated['size_price'][$size]
                    : (float) $validated['price'];

                $sizeStocks[$size] = [
                    'stock' => (int) $stock,
                    'price' => $price,
                ];
            }

            $validated['stock_quantity'] = array_sum(array_column($sizeStocks, 'stock'));
        } else {
            $validated['stock_quantity'] = (int) ($validated['stock_quantity'] ?? 0);
        }

        unset($validated['has_sizes'], $validated['sizes'], $validated['size_stock'], $validated['size_price']);
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

        foreach ($sizeStocks as $size => $info) {
            $stockQuantity = is_array($info) ? ($info['stock'] ?? 0) : (int) $info;
            $price = is_array($info) ? ($info['price'] ?? $product->price) : $product->price;

            $product->sizes()->updateOrCreate(
                ['size' => $size],
                [
                    'stock_quantity' => $stockQuantity,
                    'price'          => $price,
                ],
            );
        }
    }
}
