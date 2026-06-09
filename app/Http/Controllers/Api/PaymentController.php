<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:Cash,Card,Online Banking,E-Wallet,Cheque',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $order = Order::find($request->order_id);

            $paymentReference = 'PAY-' . date('YmdHis') . '-' . str_pad($order->id, 6, '0', STR_PAD_LEFT);

            $payment = Payment::create([
                'order_id' => $request->order_id,
                'payment_reference' => $paymentReference,
                'payment_method' => $request->payment_method,
                'amount' => $request->amount,
                'status' => 'Completed',
                'paid_at' => now(),
                'notes' => $request->notes
            ]);

            $totalPaid = Payment::where('order_id', $order->id)->where('status', 'Completed')->sum('amount');

            if ($totalPaid >= $order->total_amount) {
                $order->update(['payment_status' => 'Paid']);
            } elseif ($totalPaid > 0) {
                $order->update(['payment_status' => 'Partial']);
            }

            DB::commit();

            return response()->json(['success' => true, 'data' => $payment], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Payment creation failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function index($orderId)
    {
        $payments = Payment::where('order_id', $orderId)->get();
        return response()->json(['success' => true, 'data' => $payments]);
    }
}
