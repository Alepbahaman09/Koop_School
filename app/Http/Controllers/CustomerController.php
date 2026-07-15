<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->select([
                'id',
                'name',
                'username',
                'email',
                'phone_number',
                'email_verified_at',
                'created_at',
            ])
            ->with(['students' => fn ($query) => $query
                ->select(['id', 'user_id', 'name', 'class', 'is_active'])
                ->where('is_active', true)
                ->orderBy('name')])
            ->with(['orders' => fn ($query) => $query
                ->select(['id', 'user_id', 'student_id', 'order_number', 'status', 'payment_status', 'total_amount', 'created_at'])
                ->latest()
                ->limit(8)
                ->with('student:id,name,class')])
            ->withCount('orders')
            ->withSum(['orders as total_spent' => function ($query) {
                $query->where('payment_status', 'Paid');
            }], 'total_amount');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('username', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%')
                    ->orWhereHas('students', function ($studentQuery) use ($request) {
                        $studentQuery->where('name', 'like', '%'.$request->search.'%')
                            ->orWhere('class', 'like', '%'.$request->search.'%');
                    });
            });
        }

        if ($request->filled('status')) {
            $request->status === 'verified'
                ? $query->whereNotNull('email_verified_at')
                : $query->whereNull('email_verified_at');
        }

        $users = $query->latest()->paginate(20)->withQueryString();
        $userStats = Cache::remember('users.stats', 30, fn () => (array) User::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COUNT(*) as total,
                COUNT(CASE WHEN email_verified_at IS NOT NULL THEN 1 END) as verified,
                COUNT(CASE WHEN email_verified_at IS NULL THEN 1 END) as unverified,
                COUNT(CASE WHEN EXISTS (
                    SELECT 1 FROM orders WHERE orders.user_id = users.id
                ) THEN 1 END) as with_orders
                SQL
            )
            ->first());

        $stats = [
            'total' => (int) $userStats['total'],
            'verified' => (int) $userStats['verified'],
            'unverified' => (int) $userStats['unverified'],
            'with_orders' => (int) $userStats['with_orders'],
        ];

        return view('users', compact('users', 'stats'));
    }

    public function show(User $customer)
    {
        return redirect()->route('users.index', ['customer' => $customer->id]);
    }
}
