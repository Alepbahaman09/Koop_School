<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|distinct|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'tax' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $products = [];

            foreach ($request->items as $item) {
                $product = Product::where('is_active', true)->lockForUpdate()->find($item['product_id']);

                if (! $product) {
                    DB::rollBack();

                    return response()->json(['success' => false, 'message' => 'Product is not available'], 422);
                }

                if ($product->stock_quantity < $item['quantity']) {
                    DB::rollBack();

                    return response()->json(['success' => false, 'message' => "{$product->name} does not have enough stock"], 422);
                }

                $products[$item['product_id']] = $product;
                $subtotal += $product->price * $item['quantity'];
            }

            $tax = $request->tax ?? 0;
            $discount = $request->discount ?? 0;
            $totalAmount = $subtotal + $tax - $discount;

            $orderNumber = 'KS-'.date('Ymd').'-'.str_pad(Order::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $request->customer_id,
                'user_id' => $request->user_id,
                'status' => 'Pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'payment_status' => 'Unpaid',
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $product = $products[$item['product_id']];
                $stockBefore = $product->stock_quantity;
                $stockAfter = $stockBefore - $item['quantity'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $product->price * $item['quantity'],
                ]);

                $product->update(['stock_quantity' => $stockAfter]);

                InventoryTransaction::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user_id,
                    'type' => 'Out',
                    'quantity' => $item['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'notes' => 'Stock reduced by mobile order',
                ]);
            }

            DB::commit();

            $order->load(['customer', 'orderItems.product']);

            return response()->json(['success' => true, 'data' => $order], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Order creation failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $orders = Order::with(['customer', 'orderItems.product'])->latest()->get();

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function show($id)
    {
        $order = Order::with(['customer', 'orderItems.product', 'payments', 'statusHistory.user'])->find($id);

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Pending,Processing,Packed,Ready,Completed,Cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $order = Order::find($id);
        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $order->update(['status' => $request->status]);

        return response()->json(['success' => true, 'data' => $order]);
    }
}
