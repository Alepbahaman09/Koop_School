<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\StockPurchase;
use App\Models\StockPurchaseItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockPurchaseController extends Controller
{
    /**
     * Valid preset purchase units.
     */
    const PURCHASE_UNITS = ['Unit', 'Carton', 'Pack', 'Box', 'Dozen', 'Bottle', 'Bag'];

    /**
     * Display a listing of stock purchases.
     */
    public function index(Request $request)
    {
        $query = StockPurchase::with(['supplier', 'creator']);

        if ($search = $request->input('search')) {
            $query->whereHas('supplier', function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%");
            });
        }

        $stockPurchases = $query->latest()->paginate(15)->withQueryString();

        return view('stock_purchases.index', compact('stockPurchases'));
    }

    /**
     * Show form for creating a new stock purchase.
     */
    public function create()
    {
        $suppliers     = Supplier::where('status', 'active')->orderBy('company_name')->get();
        $products      = Product::orderBy('name')->get();
        $categories    = Category::orderBy('name')->get();
        $purchaseUnits = self::PURCHASE_UNITS;

        return view('stock_purchases.create', compact('suppliers', 'products', 'categories', 'purchaseUnits'));
    }

    /**
     * Store a newly created stock purchase.
     * Always saved as "pending" — stock is NOT updated until admin marks as received.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'notes'         => 'nullable|string|max:1000',
            'items'         => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:products,id',
            'items.*.quantity'          => 'required|integer|min:1',
            'items.*.purchase_unit'     => 'required|in:' . implode(',', self::PURCHASE_UNITS),
            'items.*.units_per_purchase'=> 'required|integer|min:1',
            'items.*.purchase_price'    => 'required|numeric|min:0.01',
            'items.*.selling_price'     => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($validated) {
            $totalAmount = 0;
            $itemsData   = [];

            foreach ($validated['items'] as $item) {
                $subtotal = round($item['quantity'] * $item['purchase_price'], 2);
                $totalAmount += $subtotal;
                $itemsData[] = [
                    'product_id'         => $item['product_id'],
                    'quantity'           => $item['quantity'],
                    'purchase_unit'      => $item['purchase_unit'],
                    'units_per_purchase' => $item['units_per_purchase'],
                    'purchase_price'     => $item['purchase_price'],
                    'selling_price'      => $item['selling_price'],
                    'subtotal'           => $subtotal,
                ];
            }

            // Always create as "pending" — admin must confirm receipt to update stock
            $stockPurchase = StockPurchase::create([
                'supplier_id'   => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'total_amount'  => $totalAmount,
                'status'        => 'pending',
                'notes'         => $validated['notes'] ?? null,
                'created_by'    => auth()->id(),
            ]);

            // Save items, update product prices and remember bulk unit defaults
            foreach ($itemsData as $item) {
                $stockPurchase->items()->create($item);

                Product::where('id', $item['product_id'])->update([
                    'price'          => $item['selling_price'],
                    'cost_price'     => $item['purchase_price'],
                    'purchase_unit'  => $item['purchase_unit'],
                    'units_per_carton' => $item['units_per_purchase'],
                ]);
            }
        });

        return redirect()->route('stock-purchases.index')
            ->with('success', 'Stock purchase recorded as pending. Mark as Received in the table to update inventory.');
    }

    /**
     * Display the specified stock purchase details.
     */
    public function show(StockPurchase $stockPurchase)
    {
        $stockPurchase->load(['supplier', 'creator', 'items.product']);

        return view('stock_purchases.show', compact('stockPurchase'));
    }

    /**
     * Admin marks a pending stock purchase as received.
     * Stock incremented by quantity × units_per_purchase for each item.
     */
    public function markAsReceived(StockPurchase $stockPurchase)
    {
        if ($stockPurchase->status === 'received') {
            return redirect()->route('stock-purchases.index')
                ->with('error', 'This stock purchase has already been marked as received.');
        }

        DB::transaction(function () use ($stockPurchase) {
            $stockPurchase->load('items');

            foreach ($stockPurchase->items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item->product_id);
                $stockBefore = $product->stock_quantity;

                // Stock added = cartons ordered × pcs per carton
                $stockAdded = $item->quantity * $item->units_per_purchase;

                $product->increment('stock_quantity', $stockAdded);
                $stockAfter = $product->fresh()->stock_quantity;

                InventoryTransaction::create([
                    'product_id'     => $product->id,
                    'admin_id'       => auth()->id(),
                    'type'           => 'In',
                    'quantity'       => $stockAdded,
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stockAfter,
                    'reference_type' => 'StockPurchase',
                    'reference_id'   => $stockPurchase->id,
                    'notes'          => "Received {$item->quantity} {$item->purchase_unit}(s) × {$item->units_per_purchase} = {$stockAdded} units. Purchase #" . $stockPurchase->id,
                ]);
            }

            $stockPurchase->update(['status' => 'received']);
        });

        return redirect()->route('stock-purchases.index')
            ->with('success', 'Stock purchase marked as received and inventory updated.');
    }

    /**
     * AJAX endpoint to create a new product inline from the purchase form.
     */
    public function storeProductInline(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:products,name',
            'category_id' => 'required|exists:categories,id',
            'unit'        => 'required|string|max:50',
            'price'       => 'required|numeric|min:0.01',
            'sku'         => 'nullable|string|max:100|unique:products,sku',
        ]);

        if (empty($validated['sku'])) {
            $validated['sku'] = 'PRD-' . strtoupper(Str::random(8));
        }

        $product = Product::create([
            'name'            => $validated['name'],
            'category_id'     => $validated['category_id'],
            'unit'            => $validated['unit'],
            'purchase_unit'   => 'Unit',
            'units_per_carton'=> 1,
            'price'           => $validated['price'],
            'cost_price'      => 0,
            'sku'             => $validated['sku'],
            'stock_quantity'  => 0,
            'min_stock_level' => 5,
        ]);

        return response()->json([
            'success' => true,
            'product' => $product,
        ]);
    }
}
