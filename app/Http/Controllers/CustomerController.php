<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with(['orders' => fn ($query) => $query->latest()->limit(8)])
            ->withCount('orders')
            ->withSum(['orders as total_spent' => function ($query) {
                $query->where('payment_status', 'Paid');
            }], 'total_amount');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('parent_name', 'like', '%'.$request->search.'%')
                    ->orWhere('student_name', 'like', '%'.$request->search.'%')
                    ->orWhere('student_id', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $customers = $query->latest()->paginate(20)->withQueryString();
        $stats = [
            'total' => Customer::count(),
            'active' => Customer::where('is_active', true)->count(),
            'inactive' => Customer::where('is_active', false)->count(),
            'with_orders' => Customer::has('orders')->count(),
        ];

        return view('users', compact('customers', 'stats'));
    }

    public function show(Customer $customer)
    {
        return redirect()->route('users.index', ['customer' => $customer->id]);
    }
}
