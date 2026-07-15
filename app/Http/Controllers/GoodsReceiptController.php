<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    /**
     * Display a listing of goods receipts.
     */
    public function index(Request $request)
    {
        $query = GoodsReceipt::with(['purchaseOrder.supplier', 'receiptItems.product']);

        if ($search = $request->input('search')) {
            $query->whereHas('purchaseOrder', function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('company_name', 'like', "%{$search}%");
                    });
            });
        }

        $goodsReceipts = $query->latest()->paginate(15)->withQueryString();

        return view('goods_receipts.index', compact('goodsReceipts'));
    }

    /**
     * Show the form for creating a new goods receipt.
     */
    public function create(Request $request)
    {
        $purchaseOrders = PurchaseOrder::whereIn('status', ['pending', 'partially_received'])
            ->with(['supplier', 'items.product'])
            ->orderBy('po_number')
            ->get();

        $selectedPo = null;
        if ($request->filled('purchase_order_id')) {
            $selectedPo = PurchaseOrder::with(['supplier', 'items.product'])->find($request->input('purchase_order_id'));
        }

        return view('goods_receipts.create', compact('purchaseOrders', 'selectedPo'));
    }

    /**
     * Store a newly created goods receipt.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'receive_date'      => 'required|date',
            'received_by'       => 'nullable|string|max:255',
            'notes'             => 'nullable|string|max:1000',
            'items'             => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.quantity_received' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $purchaseOrder = PurchaseOrder::with('items')->findOrFail($validated['purchase_order_id']);

            $goodsReceipt = GoodsReceipt::create([
                'purchase_order_id' => $validated['purchase_order_id'],
                'receive_date'      => $validated['receive_date'],
                'received_by'       => $validated['received_by'] ?? auth()->user()->name ?? 'Admin',
                'notes'             => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $productId = $item['product_id'];
                $qtyReceived = (int)$item['quantity_received'];

                if ($qtyReceived <= 0) {
                    continue;
                }

                // 1. Create Receipt Item
                GoodsReceiptItem::create([
                    'goods_receipt_id'  => $goodsReceipt->id,
                    'product_id'        => $productId,
                    'quantity_received' => $qtyReceived,
                ]);

                // 2. Lock & update Product stock
                $product = Product::lockForUpdate()->findOrFail($productId);
                $stockBefore = $product->stock_quantity;
                $product->increment('stock_quantity', $qtyReceived);
                $stockAfter = $product->stock_quantity;

                // 3. Log Inventory Transaction
                InventoryTransaction::create([
                    'product_id'     => $product->id,
                    'admin_id'       => auth()->id(),
                    'type'           => 'In',
                    'quantity'       => $qtyReceived,
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stockAfter,
                    'reference_type' => 'GoodsReceipt',
                    'reference_id'   => $goodsReceipt->id,
                    'notes'          => "Goods received from PO: {$purchaseOrder->po_number}",
                ]);

                // 4. Update the quantity_received in PO Item
                $poItem = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                    ->where('product_id', $product->id)
                    ->first();
                if ($poItem) {
                    $poItem->increment('quantity_received', $qtyReceived);
                }
            }

            // 5. Re-evaluate PO Status
            $purchaseOrder->refresh();
            $allReceived = true;
            $anyReceived = false;

            foreach ($purchaseOrder->items as $poItem) {
                if ($poItem->quantity_received < $poItem->quantity_ordered) {
                    $allReceived = false;
                }
                if ($poItem->quantity_received > 0) {
                    $anyReceived = true;
                }
            }

            $newStatus = 'pending';
            if ($allReceived) {
                $newStatus = 'received';
            } elseif ($anyReceived) {
                $newStatus = 'partially_received';
            }

            $purchaseOrder->update(['status' => $newStatus]);
        });

        return redirect()->route('goods-receipts.index')
            ->with('success', 'Goods receipt saved and inventory updated successfully.');
    }

    /**
     * Display details of a specific goods receipt.
     */
    public function show(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load(['purchaseOrder.supplier', 'receiptItems.product']);

        return view('goods_receipts.show', compact('goodsReceipt'));
    }

    /**
     * Show form for editing an existing goods receipt.
     */
    public function edit(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load(['purchaseOrder.supplier', 'receiptItems.product']);

        return view('goods_receipts.edit', compact('goodsReceipt'));
    }

    /**
     * Update an existing goods receipt and adjust product stocks/PO status.
     */
    public function update(Request $request, GoodsReceipt $goodsReceipt)
    {
        $validated = $request->validate([
            'receive_date' => 'required|date',
            'received_by'  => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:1000',
            'items'        => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:products,id',
            'items.*.quantity_received' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $goodsReceipt) {
            $purchaseOrder = PurchaseOrder::with('items')->findOrFail($goodsReceipt->purchase_order_id);

            // Update Metadata
            $goodsReceipt->update([
                'receive_date' => $validated['receive_date'],
                'received_by'  => $validated['received_by'] ?? auth()->user()->name ?? 'Admin',
                'notes'        => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $productId = $item['product_id'];
                $newQty = (int)$item['quantity_received'];

                $receiptItem = GoodsReceiptItem::where('goods_receipt_id', $goodsReceipt->id)
                    ->where('product_id', $productId)
                    ->first();

                // If receipt item doesn't exist, create it if qty > 0
                $oldQty = $receiptItem ? (int)$receiptItem->quantity_received : 0;
                $difference = $newQty - $oldQty;

                if ($difference === 0) {
                    continue;
                }

                if ($receiptItem) {
                    $receiptItem->update(['quantity_received' => $newQty]);
                } else {
                    GoodsReceiptItem::create([
                        'goods_receipt_id'  => $goodsReceipt->id,
                        'product_id'        => $productId,
                        'quantity_received' => $newQty,
                    ]);
                }

                // Adjust Product stock
                $product = Product::lockForUpdate()->findOrFail($productId);
                $stockBefore = $product->stock_quantity;
                $product->increment('stock_quantity', $difference);
                $stockAfter = $product->stock_quantity;

                // Log Inventory Transaction
                InventoryTransaction::create([
                    'product_id'     => $product->id,
                    'admin_id'       => auth()->id(),
                    'type'           => $difference > 0 ? 'In' : 'Out',
                    'quantity'       => abs($difference),
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stockAfter,
                    'reference_type' => 'GoodsReceipt',
                    'reference_id'   => $goodsReceipt->id,
                    'notes'          => "Goods receipt adjustment for PO: {$purchaseOrder->po_number}",
                ]);

                // Adjust quantity_received in PO Item
                $poItem = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                    ->where('product_id', $productId)
                    ->first();
                if ($poItem) {
                    $poItem->increment('quantity_received', $difference);
                }
            }

            // Re-evaluate PO Status
            $purchaseOrder->refresh();
            $allReceived = true;
            $anyReceived = false;

            foreach ($purchaseOrder->items as $poItem) {
                if ($poItem->quantity_received < $poItem->quantity_ordered) {
                    $allReceived = false;
                }
                if ($poItem->quantity_received > 0) {
                    $anyReceived = true;
                }
            }

            $newStatus = 'pending';
            if ($allReceived) {
                $newStatus = 'received';
            } elseif ($anyReceived) {
                $newStatus = 'partially_received';
            }

            $purchaseOrder->update(['status' => $newStatus]);
        });

        return redirect()->route('goods-receipts.show', $goodsReceipt)
            ->with('success', 'Goods receipt and stock quantities updated successfully.');
    }
}
