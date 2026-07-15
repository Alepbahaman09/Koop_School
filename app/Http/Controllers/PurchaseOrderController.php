<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'admin']);

        if ($search = $request->input('search')) {
            $query->where('po_number', 'like', "%{$search}%")
                ->orWhereHas('supplier', function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%");
                });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $purchaseOrders = $query->latest()->paginate(15)->withQueryString();

        return view('purchase_orders.index', compact('purchaseOrders'));
    }

    /**
     * Show the form for creating a new purchase order.
     */
    public function create()
    {
        $suppliers = Supplier::where('status', 'active')->orderBy('company_name')->get();
        $products  = Product::orderBy('name')->get();

        // Auto-generate PO number
        $lastPo = PurchaseOrder::orderByDesc('id')->first();
        $nextId = $lastPo ? $lastPo->id + 1 : 1;
        $poNumber = 'PO-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        return view('purchase_orders.create', compact('suppliers', 'products', 'poNumber'));
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'po_number'   => 'required|string|unique:purchase_orders,po_number',
            'order_date'  => 'required|date',
            'notes'       => 'nullable|string|max:1000',
            'items'       => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost'        => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($validated) {
            $totalAmount = 0;
            $itemsData   = [];

            foreach ($validated['items'] as $item) {
                $subtotal = round($item['quantity_ordered'] * $item['unit_cost'], 2);
                $totalAmount += $subtotal;
                $itemsData[] = [
                    'product_id'       => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_cost'        => $item['unit_cost'],
                    'subtotal'         => $subtotal,
                ];
            }

            $purchaseOrder = PurchaseOrder::create([
                'supplier_id'  => $validated['supplier_id'],
                'po_number'    => $validated['po_number'],
                'order_date'   => $validated['order_date'],
                'notes'        => $validated['notes'] ?? null,
                'status'       => 'pending',
                'total_amount' => $totalAmount,
                'admin_id'     => auth()->id(),
            ]);

            foreach ($itemsData as $item) {
                $purchaseOrder->items()->create($item);
            }
        });

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order created successfully.');
    }

    /**
     * Display the specified purchase order details.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'admin', 'items.product', 'goodsReceipts.receiptItems.product']);

        return view('purchase_orders.show', compact('purchaseOrder'));
    }

    /**
     * Record a new goods receipt directly from the PO details table and adjust inventory.
     */
    public function receiveGoods(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'receive_date' => 'required|date',
            'received_by'  => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:1000',
            'items'        => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.qty_to_receive'   => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $purchaseOrder) {
            // Create the Goods Receipt
            $goodsReceipt = GoodsReceipt::create([
                'purchase_order_id' => $purchaseOrder->id,
                'receive_date'      => $validated['receive_date'],
                'received_by'       => $validated['received_by'] ?? auth()->user()->name ?? 'Admin',
                'notes'             => $validated['notes'] ?? null,
            ]);

            $anyReceived = false;

            foreach ($validated['items'] as $item) {
                $productId = $item['product_id'];
                $qtyToReceive = (int)$item['qty_to_receive'];

                if ($qtyToReceive <= 0) {
                    continue;
                }

                $anyReceived = true;

                // 1. Create Goods Receipt Item
                GoodsReceiptItem::create([
                    'goods_receipt_id'  => $goodsReceipt->id,
                    'product_id'        => $productId,
                    'quantity_received' => $qtyToReceive,
                ]);

                // 2. Lock & Increment Product Stock
                $product = Product::lockForUpdate()->findOrFail($productId);
                $stockBefore = $product->stock_quantity;
                $product->increment('stock_quantity', $qtyToReceive);
                $stockAfter = $product->stock_quantity;

                // 3. Log Inventory Transaction
                InventoryTransaction::create([
                    'product_id'     => $product->id,
                    'admin_id'       => auth()->id(),
                    'type'           => 'In',
                    'quantity'       => $qtyToReceive,
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stockAfter,
                    'reference_type' => 'GoodsReceipt',
                    'reference_id'   => $goodsReceipt->id,
                    'notes'          => "Goods received from PO: {$purchaseOrder->po_number}",
                ]);

                // 4. Update quantity_received in PO Item
                $poItem = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                    ->where('product_id', $productId)
                    ->first();
                if ($poItem) {
                    $poItem->increment('quantity_received', $qtyToReceive);
                }
            }

            // If no items were actually received, rollback and raise exception
            if (!$anyReceived) {
                throw new \Exception("You must enter a quantity greater than 0 for at least one item.");
            }

            // 5. Re-evaluate PO Status
            $purchaseOrder->refresh();
            $allReceived = true;
            $anyPartialReceived = false;

            foreach ($purchaseOrder->items as $poItem) {
                if ($poItem->quantity_received < $poItem->quantity_ordered) {
                    $allReceived = false;
                }
                if ($poItem->quantity_received > 0) {
                    $anyPartialReceived = true;
                }
            }

            $newStatus = 'pending';
            if ($allReceived) {
                $newStatus = 'received';
            } elseif ($anyPartialReceived) {
                $newStatus = 'partially_received';
            }

            $purchaseOrder->update(['status' => $newStatus]);
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Goods receipt recorded and inventory updated successfully.');
    }
}
