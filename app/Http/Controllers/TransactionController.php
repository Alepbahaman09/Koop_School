<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::query()
            ->select(['id', 'order_id', 'payment_reference', 'payment_method', 'amount', 'status', 'paid_at', 'created_at'])
            ->with([
                'order:id,order_number,customer_id,total_amount,payment_status',
                'order.customer:id,parent_name,student_name',
            ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('payment_reference', 'like', '%' . $request->search . '%')
                  ->orWhereHas('order', function($q2) use ($request) {
                      $q2->where('order_number', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        return view('transactions.index', compact('payments'));
    }
}
