<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $orderId = $request->query('order_id');

        // All unpaid/partial orders, oldest first (FIFO queue)
        $queue = Order::whereIn('payment_status', ['Unpaid', 'Partial'])
            ->with([
                'customer:id,student_id,student_name,parent_name,class',
                'orderItems:id,order_id,product_id,quantity,unit_price,subtotal',
                'orderItems.product:id,name',
            ])
            ->oldest()
            ->take(20)
            ->get();

        // Active order: explicit pick or head of queue
        if ($orderId) {
            $order = $queue->firstWhere('id', (int) $orderId)
                       ?? Order::with(['customer', 'orderItems.product'])->find($orderId);
        } else {
            $order = $queue->first();
        }

        // Queue excluding the active order
        $waitingQueue = $order ? $queue->where('id', '!=', $order->id)->values() : $queue;

        // POS: active products with their category, ordered by category then name
        $products = Product::where('is_active', true)
            ->with('category:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category' => $p->category?->name ?? 'Others',
                'price' => (float) $p->price,
                'stock' => (int) $p->stock_quantity,
                'image' => $p->image_url,
            ]);

        // Unique category names that actually have active products
        $categories = $products->pluck('category')->unique()->sort()->values();

        return view('payment.terminal', compact('order', 'waitingQueue', 'products', 'categories'));
    }

    public function checkout(Order $order)
    {
        $cards = Card::where('is_frozen', false)->get();

        return view('payment.checkout', compact('order', 'cards'));
    }

    public function processNfcPayment(Request $request, Order $order)
    {
        $request->validate([
            'card_uid' => 'required|string|max:128',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->payment_status === 'Paid') {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been paid.',
                ], 422);
            }

            $paidAmount = (float) Payment::where('order_id', $order->id)
                ->where('status', 'Completed')
                ->sum('amount');
            $amountDue = round(max(0, (float) $order->total_amount - $paidAmount), 2);

            if ($amountDue <= 0) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'This order has no outstanding balance.',
                ], 422);
            }

            $card = Card::where('card_uid', $request->card_uid)
                ->lockForUpdate()
                ->first();

            if (! $card) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'NFC Card not found.',
                ], 404);
            }

            if ($card->is_frozen) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'NFC Card is frozen.',
                ], 422);
            }

            if ((float) $card->balance < $amountDue) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Available: RM '.number_format($card->balance, 2),
                ], 422);
            }

            // Deduct card balance
            $card->decrement('balance', $amountDue);
            $card->update(['last_used_at' => now()]);

            // Create payment reference
            $paymentReference = 'PAY-NFC-'.date('YmdHis').'-'.str_pad($order->id, 6, '0', STR_PAD_LEFT);

            // Create payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_reference' => $paymentReference,
                'payment_method' => 'Card',
                'amount' => $amountDue,
                'status' => 'Completed',
                'paid_at' => now(),
                'notes' => 'NFC card payment. Card: '.$card->card_uid,
            ]);

            // Update order payment status and order status
            $order->update([
                'payment_status' => 'Paid',
                'status' => 'Completed',
            ]);

            // Track order status history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'admin_id' => auth()->id(),
                'status' => 'Completed',
                'notes' => 'Paid via NFC Card '.$card->card_uid,
            ]);

            DB::commit();

            $this->bustSalesCaches();

            return response()->json([
                'success' => true,
                'student' => $card->owner,
                'remaining_balance' => number_format($card->fresh()->balance, 2),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Payment could not be completed. Please retry.',
            ], 500);
        }
    }

    public function processCashPayment(Order $order)
    {
        try {
            DB::beginTransaction();

            // Create payment reference
            $paymentReference = 'PAY-CASH-'.date('YmdHis').'-'.str_pad($order->id, 6, '0', STR_PAD_LEFT);

            // Create payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_reference' => $paymentReference,
                'payment_method' => 'Cash',
                'amount' => $order->total_amount,
                'status' => 'Completed',
                'paid_at' => now(),
                'notes' => 'Cash payment recorded by cashier.',
            ]);

            // Update order payment status and order status
            $order->update([
                'payment_status' => 'Paid',
                'status' => 'Completed',
            ]);

            // Track order status history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'admin_id' => auth()->id(),
                'status' => 'Completed',
                'notes' => 'Paid via Cash',
            ]);

            DB::commit();

            $this->bustSalesCaches();

            return response()->json([
                'success' => true,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: '.$e->getMessage(),
            ], 500);
        }
    }

    private function bustSalesCaches(): void
    {
        // Dashboard
        Cache::forget('dashboard.order_totals.current_statuses');
        Cache::forget('dashboard.recent_payments');
        Cache::forget('dashboard.customer_totals');
        Cache::forget('dashboard.product_totals');

        // Analytics (all period variants)
        foreach ([7, 30, 90, 365] as $days) {
            Cache::forget("analytics.index.top_items.{$days}");
        }

        // Finance (current + previous month)
        Cache::forget('finance.index.'.now()->format('Y-m'));
        Cache::forget('finance.index.'.now()->subMonthNoOverflow()->format('Y-m'));

        // Dashboard sales-by-date (bust any recent range keys)
        $today = now()->toDateString();
        for ($i = 0; $i <= 30; $i++) {
            $rangeStart = now()->subDays($i + 9)->toDateString();
            Cache::forget("dashboard.sales_by_date.{$rangeStart}.{$today}");
        }
    }
}
