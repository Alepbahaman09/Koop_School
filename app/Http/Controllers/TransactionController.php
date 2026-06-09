<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['order.customer']);

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

        $payments = $query->latest()->paginate(20);

        return view('transactions.index', compact('payments'));
    }
}
