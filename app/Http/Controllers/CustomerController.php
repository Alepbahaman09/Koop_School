<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()
            ->select([
                'id',
                'parent_name',
                'email',
                'phone',
                'is_active',
                'created_at',
            ])
            ->with(['orders' => fn ($query) => $query
                ->select(['id', 'customer_id', 'order_number', 'status', 'payment_status', 'total_amount', 'created_at'])
                ->latest()
                ->limit(8)])
            ->withCount('orders')
            ->withSum(['orders as total_spent' => function ($query) {
                $query->where('payment_status', 'Paid');
            }], 'total_amount');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('parent_name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $customers = $query->latest()->paginate(20)->withQueryString();
        $customerStats = Cache::remember('customers.stats', 30, fn () => (array) Customer::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COUNT(*) as total,
                COUNT(CASE WHEN is_active = true THEN 1 END) as active,
                COUNT(CASE WHEN is_active = false THEN 1 END) as inactive,
                COUNT(CASE WHEN EXISTS (
                    SELECT 1 FROM orders WHERE orders.customer_id = customers.id
                ) THEN 1 END) as with_orders
                SQL
            )
            ->first());

        $stats = [
            'total' => (int) $customerStats['total'],
            'active' => (int) $customerStats['active'],
            'inactive' => (int) $customerStats['inactive'],
            'with_orders' => (int) $customerStats['with_orders'],
        ];

        return view('users', compact('customers', 'stats'));
    }

    public function show(Customer $customer)
    {
        return redirect()->route('users.index', ['customer' => $customer->id]);
    }
}
