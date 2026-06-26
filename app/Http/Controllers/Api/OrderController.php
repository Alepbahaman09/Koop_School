<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $authUser = $request->user();
        if ($authUser && $request->filled('user_id') && (int) $request->user_id !== $authUser->id) {
            return response()->json(['success' => false, 'message' => 'Orders must belong to the authenticated user.'], 403);
        }

        $userId = $authUser?->id ?? $request->input('user_id');
        $validator = Validator::make($request->all(), [
            'customer_id' => [$authUser ? 'nullable' : 'required', 'exists:customers,id'],
            'user_id' => [$authUser ? 'nullable' : 'required', 'exists:users,id'],
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|distinct|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'tax' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'mobile_reference' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('orders', 'mobile_reference')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'User is required.'], 422);
        }

        DB::beginTransaction();
        try {
            $customerId = $request->customer_id;
            if ($authUser) {
                $customerId = Customer::firstOrCreate(['email' => $authUser->email], [
                    'student_id' => 'APP-'.$authUser->id,
                    'parent_name' => $authUser->username ?: $authUser->name,
                    'student_name' => $authUser->username ?: $authUser->name,
                    'phone' => $authUser->phone_number ?: '-',
                    'class' => '-',
                    'address' => '-',
                ])->id;
            }
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
                'mobile_reference' => $request->mobile_reference,
                'customer_id' => $customerId,
                'user_id' => $userId,
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
                    'user_id' => $userId,
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
            AdminNotification::forOrder($order, 'mobile_api');

            return response()->json(['success' => true, 'data' => $order], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Order creation failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        $orders = Order::with(['customer', 'orderItems.product'])
            ->when($request->user(), fn ($query, $user) => $query->where('user_id', $user->id))
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function show(Request $request, $id)
    {
        $order = Order::with(['customer', 'orderItems.product', 'payments', 'statusHistory.user'])
            ->when($request->user(), fn ($query, $user) => $query->where('user_id', $user->id))
            ->find($id);

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

        $order = Order::query()
            ->when($request->user(), fn ($query, $user) => $query->where('user_id', $user->id))
            ->find($id);
        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $order->update(['status' => $request->status]);

        return response()->json(['success' => true, 'data' => $order]);
    }
}
