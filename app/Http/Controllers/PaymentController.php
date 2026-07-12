<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Card;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
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

        // Cards for NFC simulator
        $cards = Card::where('is_frozen', false)->get();

        return view('payment.terminal', compact('order', 'waitingQueue', 'cards'));
    }

    public function checkout(Order $order)
    {
        // Ensure the card for simulation/demo exists
        $card = Card::where('card_uid', '05EE7BCA')->first();
        if (!$card) {
            // Find a valid user to link the card to
            $user = \App\Models\User::first();
            Card::create([
                'user_id' => $user ? $user->id : 1,
                'card_uid' => '05EE7BCA',
                'owner' => 'Ali Bin Abu',
                'balance' => 32.00,
                'is_frozen' => false,
            ]);
        } else if ($card->owner !== 'Ali Bin Abu') {
            $card->update(['owner' => 'Ali Bin Abu']);
        }

        // Get list of cards for simulation helper dropdown
        $cards = Card::where('is_frozen', false)->get();

        return view('payment.checkout', compact('order', 'cards'));
    }

    public function processNfcPayment(Request $request, Order $order)
    {
        $request->validate([
            'card_uid' => 'required|string',
        ]);

        $card = Card::where('card_uid', $request->card_uid)->first();

        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'NFC Card not found.',
            ], 404);
        }

        if ($card->is_frozen) {
            return response()->json([
                'success' => false,
                'message' => 'NFC Card is frozen.',
            ], 400);
        }

        if ($card->balance < $order->total_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance. Available: RM ' . number_format($card->balance, 2),
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Deduct card balance
            $card->decrement('balance', $order->total_amount);
            $card->update(['last_used_at' => now()]);

            // Create payment reference
            $paymentReference = 'PAY-NFC-' . date('YmdHis') . '-' . str_pad($order->id, 6, '0', STR_PAD_LEFT);

            // Create payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_reference' => $paymentReference,
                'payment_method' => 'Card',
                'amount' => $order->total_amount,
                'status' => 'Completed',
                'paid_at' => now(),
                'notes' => 'NFC card payment. Card: ' . $card->card_uid,
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
                'notes' => 'Paid via NFC Card ' . $card->card_uid,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'student' => $card->owner,
                'remaining_balance' => number_format($card->balance, 2),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function processCashPayment(Order $order)
    {
        try {
            DB::beginTransaction();

            // Create payment reference
            $paymentReference = 'PAY-CASH-' . date('YmdHis') . '-' . str_pad($order->id, 6, '0', STR_PAD_LEFT);

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

            return response()->json([
                'success' => true,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
