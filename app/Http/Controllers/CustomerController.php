<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        DB::transaction(function () {
            $users = User::query()
                ->select(['id', 'email', 'username', 'phone_number'])
                ->get();

            foreach ($users as $user) {
                Customer::updateOrCreate(
                    ['email' => $user->email],
                    [
                        'student_id' => 'APP-'.$user->id,
                        'parent_name' => $user->username ?? $user->name,
                        'student_name' => $user->username ?? $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone_number ?? '-',
                        'class' => '-',
                        'address' => '-',
                        'is_active' => true,
                    ]
                );
            }
        });

        $query = Customer::query()
            ->select([
                'id',
                'student_id',
                'parent_name',
                'student_name',
                'email',
                'phone',
                'class',
                'address',
                'latitude',
                'longitude',
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
                    ->orWhere('student_name', 'like', '%'.$request->search.'%')
                    ->orWhere('student_id', 'like', '%'.$request->search.'%')
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
