<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductActivityLog;
use App\Models\ProductSize;
use App\Models\StockPurchase;
use App\Models\StockPurchaseItem;
use App\Models\Supplier;
use App\Services\SupabaseStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplierController extends Controller
{
    private const IMAGE_BUCKET = 'product-images';

    public function __construct(private readonly SupabaseStorage $storage) {}

    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request)
    {
        // 1. Overview statistics (cached as pure primitive array)
        $supplierStats = Cache::remember('suppliers.stats_summary_v2', 120, function () {
            return [
                'total_suppliers' => (int) Supplier::count(),
                'total_purchases' => (int) StockPurchase::count(),
                'total_spent'     => (float) StockPurchase::sum('total_amount'),
            ];
        });

        // 2. Cache report modal HTML (eliminates 7 heavy queries on normal page views)
        $reportModalHtml = Cache::remember('suppliers.report_modal_html_v2', 120, function () use ($supplierStats) {
            $allSuppliersReport = Supplier::with([
                'stockPurchases' => function ($q) {
                    $q->with(['items.product.sizes', 'items.product.category'])->latest('purchase_date');
                },
            ])->orderBy('company_name')->get();

            $supplierActivityLogs = ProductActivityLog::whereIn('entity_type', ['supplier', 'stock_purchase'])
                ->latest()
                ->take(50)
                ->get();

            return view('partials.supplier-report-modal', compact('allSuppliersReport', 'supplierStats', 'supplierActivityLogs'))->render();
        });

        // 3. Fast cached form data for drawer
        $drawerData = $this->drawerFormData();

        // 4. Fast Paginated suppliers list for the main table (only 15 records)
        $query = Supplier::query()->with([
            'stockPurchases' => function ($q) {
                $q->with('items')->latest();
            }
        ]);

        $perPage = 15;
        $page = max(1, (int) $request->input('page', 1));

        if ($search = $request->input('search')) {
            $query->where('company_name', 'like', "%{$search}%")
                  ->orWhere('supplier_id', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");

            $suppliers = $query->latest()->paginate($perPage)->withQueryString();
        } else {
            // Fast paginator: skip duplicate count query by reusing cached total
            $total = (int) ($supplierStats['total_suppliers'] ?? Supplier::count());
            $items = $query->latest()->forPage($page, $perPage)->get();
            $suppliers = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('suppliers', array_merge(compact('suppliers', 'supplierStats', 'reportModalHtml'), $drawerData));
    }

    /**
     * Shared form data for the Combined Supplier & Purchase Drawer.
     */
    private function drawerFormData(): array
    {
        $spCategories = Cache::remember('suppliers.drawer_categories_v1', 300, function () {
            return Category::where('is_active', true)->orderBy('name')->get(['id', 'name'])->toArray();
        });

        $spSuppliers = Cache::remember('suppliers.drawer_suppliers_v1', 120, function () {
            return Supplier::orderBy('company_name')->get(['id', 'company_name', 'supplier_id', 'contact_person', 'phone', 'email', 'address'])->toArray();
        });

        return [
            'spCategories' => $spCategories,
            'spSuppliers'  => $spSuppliers,
        ];
    }

    /**
     * Store a new supplier and record its stock purchase together in one flow.
     */
    public function storeWithPurchase(Request $request)
    {
        $validated = $request->validate([
            // Supplier details (new or existing)
            'supplier_mode'        => 'required|in:new,existing',
            'existing_supplier_id' => 'required_if:supplier_mode,existing|nullable|exists:suppliers,id',
            'company_name'         => 'required_if:supplier_mode,new|nullable|string|max:255',
            'contact_person'       => 'nullable|string|max:255',
            'email'                => 'nullable|email|max:255',
            'phone'                => 'nullable|string|max:50',
            'address'              => 'nullable|string|max:1000',

            // Purchase details
            'purchase_date'        => 'required|date',
            'notes'                => 'nullable|string|max:1000',

            // Purchase items:
            'items'                       => 'required|array|min:1',
            'items.*.product_id'          => 'nullable',
            'items.*.name'                => 'required|string|max:255',
            'items.*.category_id'         => 'nullable|exists:categories,id',
            'items.*.sku'                 => 'nullable|string|max:100',
            'items.*.description'         => 'nullable|string|max:1000',
            'items.*.quantity'            => 'required|integer|min:1',
            'items.*.purchase_price'      => 'required|numeric|min:0.01',
            'items.*.selling_price'       => 'required|numeric|min:0.01',
            'items.*.min_stock_level'     => 'nullable|integer|min:0',
            'items.*.is_active'           => 'nullable|boolean',
            'items.*.image'               => 'nullable|image|max:2048',
            'items.*.has_sizes'           => 'nullable|boolean',
            'items.*.sizes'               => 'nullable|array',
            'items.*.sizes.*'             => 'string|in:'.implode(',', ProductSize::AVAILABLE_SIZES),
            'items.*.size_stock'          => 'nullable|array',
            'items.*.size_stock.*'        => 'nullable|integer|min:0',
            'items.*.size_price'          => 'nullable|array',
            'items.*.size_price.*'        => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $validated) {
            // 1. Resolve or create Supplier
            if ($validated['supplier_mode'] === 'existing' && !empty($validated['existing_supplier_id'])) {
                $supplier = Supplier::findOrFail($validated['existing_supplier_id']);
                $updateFields = array_filter([
                    'contact_person' => $validated['contact_person'] ?? null,
                    'email'          => $validated['email'] ?? null,
                    'phone'          => $validated['phone'] ?? null,
                    'address'        => $validated['address'] ?? null,
                ], fn ($v) => !is_null($v) && $v !== '');
                if (!empty($updateFields)) {
                    $supplier->update($updateFields);
                }
            } else {
                $supplier = Supplier::create([
                    'company_name'   => $validated['company_name'],
                    'contact_person' => $validated['contact_person'] ?? null,
                    'email'          => $validated['email'] ?? null,
                    'phone'          => $validated['phone'] ?? null,
                    'address'        => $validated['address'] ?? null,
                ]);

                ProductActivityLog::log(
                    'created',
                    'supplier',
                    $supplier->id,
                    $supplier->company_name,
                    "Added new supplier {$supplier->company_name} (ID: {$supplier->supplier_id})"
                );
            }

            // 2. Calculate grand total amount
            $grandTotal = 0;
            foreach ($validated['items'] as $item) {
                $hasSizes = !empty($item['has_sizes']) && !empty($item['sizes']);
                $qty = (int) $item['quantity'];
                if ($hasSizes) {
                    $sum = 0;
                    foreach ($item['sizes'] as $s) {
                        $sum += (int) ($item['size_stock'][$s] ?? 0);
                    }
                    if ($sum > 0) {
                        $qty = $sum;
                    }
                }
                $grandTotal += round($qty * $item['purchase_price'], 2);
            }

            // 3. Create StockPurchase record (status: received so stock is immediately active in store)
            $stockPurchase = StockPurchase::create([
                'supplier_id'   => $supplier->id,
                'purchase_date' => $validated['purchase_date'],
                'total_amount'  => $grandTotal,
                'status'        => 'received',
                'notes'         => $validated['notes'] ?? null,
                'created_by'    => auth()->id(),
            ]);

            // 4. Process each item: create/update product, stock, sizes, and purchase item
            foreach ($validated['items'] as $index => $itemData) {
                $hasSizes = !empty($itemData['has_sizes']) && !empty($itemData['sizes']);
                $sizeData = [];
                $totalSizeQty = 0;

                if ($hasSizes) {
                    foreach ($itemData['sizes'] as $size) {
                        $stock = isset($itemData['size_stock'][$size]) ? (int) $itemData['size_stock'][$size] : 0;
                        $price = isset($itemData['size_price'][$size]) && $itemData['size_price'][$size] !== ''
                            ? (float) $itemData['size_price'][$size]
                            : (float) $itemData['selling_price'];
                        $sizeData[$size] = [
                            'stock' => $stock,
                            'price' => $price,
                        ];
                        $totalSizeQty += $stock;
                    }
                    if ($totalSizeQty > 0) {
                        $quantity = $totalSizeQty;
                    } else {
                        $quantity = (int) $itemData['quantity'];
                    }
                } else {
                    $quantity = (int) $itemData['quantity'];
                }

                $itemSubtotal = round($quantity * $itemData['purchase_price'], 2);
                $minStock     = isset($itemData['min_stock_level']) ? (int) $itemData['min_stock_level'] : 5;
                $isActive     = isset($itemData['is_active']) ? (bool) $itemData['is_active'] : true;

                // Handle image upload if provided
                $imagePath = null;
                if ($request->hasFile("items.{$index}.image")) {
                    $imagePath = $this->storage->uploadImage(
                        $request->file("items.{$index}.image"),
                        self::IMAGE_BUCKET,
                        'products'
                    );
                }

                // Check if existing product selected
                $product = null;
                if (!empty($itemData['product_id'])) {
                    $product = Product::find($itemData['product_id']);
                }

                if (! $product) {
                    $sku = !empty($itemData['sku']) ? trim($itemData['sku']) : 'PRD-' . strtoupper(Str::random(8));

                    // Create new Product in store and database
                    $product = Product::create([
                        'supplier_id'       => $supplier->id,
                        'category_id'       => !empty($itemData['category_id']) ? $itemData['category_id'] : null,
                        'name'              => $itemData['name'],
                        'sku'               => $sku,
                        'description'       => $itemData['description'] ?? null,
                        'price'             => $itemData['selling_price'],
                        'cost_price'        => $itemData['purchase_price'],
                        'unit'              => 'pcs',
                        'purchase_unit'     => 'pcs',
                        'units_per_carton'  => 1,
                        'stock_quantity'    => 0, // incremented below
                        'min_stock_level'   => $minStock,
                        'is_active'         => $isActive,
                        'image'             => $imagePath,
                    ]);

                    ProductActivityLog::log(
                        'created',
                        'product',
                        $product->id,
                        $product->name,
                        "Created new product from supplier intake: {$product->name} (SKU: {$product->sku})"
                    );
                } else {
                    // Update existing product pricing and supplier info
                    $product->update([
                        'supplier_id'      => $supplier->id,
                        'cost_price'       => $itemData['purchase_price'],
                        'price'            => $itemData['selling_price'],
                        'is_active'        => $isActive,
                        'image'            => $imagePath ?: $product->image,
                    ]);
                }

                // Stock update: increment stock quantity in store and DB
                $stockBefore = $product->stock_quantity;
                $product->increment('stock_quantity', $quantity);
                $stockAfter = $product->fresh()->stock_quantity;

                // Sync or increment sizes if sizes enabled
                if ($hasSizes && !empty($sizeData)) {
                    foreach ($sizeData as $size => $sInfo) {
                        $existingSize = $product->sizes()->where('size', $size)->first();
                        if ($existingSize) {
                            $existingSize->increment('stock_quantity', $sInfo['stock'], ['price' => $sInfo['price']]);
                        } else {
                            $product->sizes()->create([
                                'size'           => $size,
                                'stock_quantity' => $sInfo['stock'],
                                'price'          => $sInfo['price'],
                            ]);
                        }
                    }
                }

                // Save stock purchase item record with item_image, item_name and item_sku
                $stockPurchase->items()->create([
                    'product_id'         => $product->id,
                    'item_name'          => $product->name,
                    'item_sku'           => $product->sku,
                    'item_image'         => $imagePath ?: $product->image,
                    'quantity'           => $quantity,
                    'purchase_unit'      => 'pcs',
                    'units_per_purchase' => 1,
                    'purchase_price'     => $itemData['purchase_price'],
                    'selling_price'      => $itemData['selling_price'],
                    'subtotal'           => $itemSubtotal,
                ]);

                // Record Inventory Transaction
                InventoryTransaction::create([
                    'product_id'     => $product->id,
                    'admin_id'       => auth()->id(),
                    'type'           => 'In',
                    'quantity'       => $quantity,
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stockAfter,
                    'reference_type' => 'StockPurchase',
                    'reference_id'   => $stockPurchase->id,
                    'notes'          => "Purchased {$quantity} units of {$product->name} from {$supplier->company_name}. PO #{$stockPurchase->id}",
                ]);

                ProductActivityLog::log(
                    'created',
                    'stock_purchase',
                    $stockPurchase->id,
                    $product->name,
                    "Intake {$quantity} units of {$product->name} from {$supplier->company_name}. Subtotal: RM " . number_format($itemSubtotal, 2)
                );
            }
        });

        Cache::forget('suppliers.stats_summary_v2');
        Cache::forget('suppliers.report_modal_html_v2');
        Cache::forget('suppliers.drawer_suppliers_v1');
        Cache::forget('products.stats_summary_v2');

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier and stock purchase recorded successfully. Products and inventory updated!');
    }

    /**
     * Store a newly created standalone supplier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'address'        => 'nullable|string|max:1000',
        ]);

        $supplier = Supplier::create($validated);

        ProductActivityLog::log(
            'created',
            'supplier',
            $supplier->id,
            $supplier->company_name,
            "Added supplier: {$supplier->company_name} (ID: {$supplier->supplier_id})"
        );

        Cache::forget('suppliers.stats_summary_v2');
        Cache::forget('suppliers.report_modal_html_v2');
        Cache::forget('suppliers.drawer_suppliers_v1');

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'address'        => 'nullable|string|max:1000',
        ]);

        $supplier->update($validated);

        ProductActivityLog::log(
            'updated',
            'supplier',
            $supplier->id,
            $supplier->company_name,
            "Updated contact details for supplier: {$supplier->company_name}"
        );

        Cache::forget('suppliers.stats_summary_v2');
        Cache::forget('suppliers.report_modal_html_v2');
        Cache::forget('suppliers.drawer_suppliers_v1');

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier)
    {
        DB::transaction(function () use ($supplier) {
            // Unlink any products referencing this supplier so products stay intact
            Product::where('supplier_id', $supplier->id)->update(['supplier_id' => null]);

            // Delete associated stock purchases and purchase orders
            $supplier->stockPurchases()->delete();
            $supplier->purchaseOrders()->delete();

            $name = $supplier->company_name;
            $id = $supplier->id;
            $code = $supplier->supplier_id;

            $supplier->delete();

            ProductActivityLog::log(
                'deleted',
                'supplier',
                $id,
                $name,
                "Deleted supplier {$name} ({$code})"
            );
        });

        Cache::forget('suppliers.stats_summary_v2');
        Cache::forget('suppliers.report_modal_html_v2');
        Cache::forget('suppliers.drawer_suppliers_v1');
        Cache::forget('products.stats_summary_v2');

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    /**
     * Export supplier and purchase report as a formatted PDF document.
     */
    public function exportPdf(Request $request)
    {
        $withActions = $request->boolean('with_actions', true);
        $supplierId  = $request->input('supplier_id');

        $selectedSupplier = null;
        if (!empty($supplierId)) {
            $selectedSupplier = Supplier::with([
                'stockPurchases' => function ($q) {
                    $q->with(['items.product.category', 'items.product.sizes'])->latest();
                }
            ])->find($supplierId);
        }

        if ($selectedSupplier) {
            $totalPurchases = $selectedSupplier->stockPurchases->count();
            $totalSpent     = (float) $selectedSupplier->stockPurchases->sum('total_amount');
            $totalUnits     = 0;
            foreach ($selectedSupplier->stockPurchases as $purchase) {
                $totalUnits += $purchase->items->sum('quantity');
            }

            $stats = [
                'total_suppliers' => 1,
                'total_purchases' => $totalPurchases,
                'total_spent'     => $totalSpent,
                'total_units'     => $totalUnits,
            ];

            $suppliers = collect([$selectedSupplier]);

            $activityLogs = $withActions
                ? ProductActivityLog::where(function ($q) use ($selectedSupplier) {
                    $q->where(function ($sq) use ($selectedSupplier) {
                        $sq->where('entity_type', 'supplier')
                           ->where(function ($ssq) use ($selectedSupplier) {
                               $ssq->where('entity_id', $selectedSupplier->id)
                                   ->orWhere('entity_name', $selectedSupplier->company_name);
                           });
                    })->orWhere(function ($sq) use ($selectedSupplier) {
                        $sq->where('entity_type', 'stock_purchase')
                           ->where('details', 'like', "%{$selectedSupplier->company_name}%");
                    });
                })->latest()->take(50)->get()
                : collect();

            $filename = 'supplier-audit-' . \Illuminate\Support\Str::slug($selectedSupplier->company_name) . '-' . date('Y-m-d') . '.pdf';
        } else {
            $suppliers = Supplier::with(['stockPurchases.items.product.category', 'stockPurchases.items.product.sizes'])->orderBy('company_name')->get();

            $totalUnits = 0;
            foreach ($suppliers as $s) {
                foreach ($s->stockPurchases as $p) {
                    $totalUnits += $p->items->sum('quantity');
                }
            }

            $stats = [
                'total_suppliers' => $suppliers->count(),
                'total_purchases' => StockPurchase::count(),
                'total_spent'     => (float) StockPurchase::sum('total_amount'),
                'total_units'     => $totalUnits,
            ];

            $activityLogs = $withActions
                ? ProductActivityLog::whereIn('entity_type', ['supplier', 'stock_purchase'])->latest()->take(100)->get()
                : collect();

            $filename = 'suppliers-purchases-report-' . date('Y-m-d') . '.pdf';
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.supplier-pdf', compact('suppliers', 'selectedSupplier', 'stats', 'activityLogs', 'withActions'))
            ->setPaper('a4', 'landscape');

        if ($request->boolean('stream')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Export full supplier and purchase summary report as CSV.
     */
    public function exportReport(Request $request)
    {
        $supplierId = $request->input('supplier_id');
        $query = Supplier::with(['stockPurchases.items.product'])->orderBy('company_name');
        if (!empty($supplierId)) {
            $query->where('id', $supplierId);
        }
        $suppliers = $query->get();

        $filename = (!empty($supplierId) && $suppliers->isNotEmpty())
            ? 'supplier-audit-' . \Illuminate\Support\Str::slug($suppliers->first()->company_name) . '-' . date('Y-m-d') . '.csv'
            : 'suppliers-purchases-report-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($suppliers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Supplier ID',
                'Company Name',
                'Contact Person',
                'Phone',
                'Email',
                'Address',
                'Total Purchase Orders',
                'Total Amount Spent (RM)',
                'Latest Purchase Date',
                'Date Added',
            ], ',', '"', '\\');

            foreach ($suppliers as $s) {
                $totalSpent = $s->stockPurchases->sum('total_amount');
                $latestPurchase = $s->stockPurchases->sortByDesc('purchase_date')->first();
                $latestDate = $latestPurchase ? $latestPurchase->purchase_date->format('Y-m-d') : 'None';

                fputcsv($file, [
                    $s->supplier_id,
                    $s->company_name,
                    $s->contact_person ?: '—',
                    $s->phone ?: '—',
                    $s->email ?: '—',
                    $s->address ?: '—',
                    $s->stockPurchases->count(),
                    number_format($totalSpent, 2),
                    $latestDate,
                    $s->created_at->format('Y-m-d'),
                ], ',', '"', '\\');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
