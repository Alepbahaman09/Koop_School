<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:Cash,NFC Card',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $order = Order::query()
                ->when($request->user(), fn ($query, $user) => $query->where('user_id', $user->id))
                ->lockForUpdate()
                ->find($request->order_id);
            if (! $order) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            $paymentReference = 'PAY-'.date('YmdHis').'-'.str_pad($order->id, 6, '0', STR_PAD_LEFT);

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_reference' => $paymentReference,
                'payment_method' => $request->payment_method,
                'amount' => $request->amount,
                'status' => 'Completed',
                'paid_at' => now(),
                'notes' => $request->notes,
            ]);

            $totalPaid = Payment::where('order_id', $order->id)->where('status', 'Completed')->sum('amount');
            $paymentStatus = $totalPaid >= $order->total_amount ? 'Paid' : 'Partial';

            $order->update([
                'status' => Order::STATUS_PROCESSING,
                'payment_status' => $paymentStatus,
            ]);

            DB::commit();

            $this->bustSalesCaches();

            return response()->json(['success' => true, 'data' => $payment], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Payment creation failed', 'error' => $e->getMessage()], 500);
        }
    }

    private function bustSalesCaches(): void
    {
        Cache::forget('dashboard.order_totals.current_statuses');
        Cache::forget('dashboard.recent_payments');
        Cache::forget('dashboard.customer_totals');
        Cache::forget('dashboard.product_totals');

        foreach ([7, 30, 90, 365] as $days) {
            Cache::forget("analytics.index.top_items.{$days}");
        }

        Cache::forget('finance.index.'.now()->format('Y-m'));
        Cache::forget('finance.index.'.now()->subMonthNoOverflow()->format('Y-m'));

        $today = now()->toDateString();
        for ($i = 0; $i <= 30; $i++) {
            $rangeStart = now()->subDays($i + 9)->toDateString();
            Cache::forget("dashboard.sales_by_date.{$rangeStart}.{$today}");
        }
    }

    public function index(Request $request, $orderId)
    {
        $order = Order::query()
            ->when($request->user(), fn ($query, $user) => $query->where('user_id', $user->id))
            ->find($orderId);
        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $payments = Payment::where('order_id', $order->id)->get();

        return response()->json(['success' => true, 'data' => $payments]);
    }
}
