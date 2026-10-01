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
            // Exclude the POS walk-in account — kiosk is not a real user
            ->where('email', '!=', 'pos@koop.school')
            ->with(['students' => fn ($query) => $query
                ->select(['id', 'user_id', 'name', 'class', 'is_active'])
                ->where('is_active', true)
                ->orderBy('name')])
            ->with(['orders' => fn ($query) => $query
                ->select(['id', 'user_id', 'student_id', 'order_number', 'status', 'payment_status', 'total_amount', 'created_at'])
                ->latest()
                ->with(['student:id,name,class', 'terminalPayments:id,order_id,payment_method'])])
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

        $userStats = Cache::remember('users.stats.v2', 120, function () {
            $row = User::query()
                ->toBase()
                // Exclude the POS walk-in account from stats
                ->where('email', '!=', 'pos@koop.school')
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
                ->first();

            return [
                'total' => (int) ($row->total ?? 0),
                'verified' => (int) ($row->verified ?? 0),
                'unverified' => (int) ($row->unverified ?? 0),
                'with_orders' => (int) ($row->with_orders ?? 0),
            ];
        });

        $stats = [
            'total' => (int) $userStats['total'],
            'verified' => (int) $userStats['verified'],
            'unverified' => (int) $userStats['unverified'],
            'with_orders' => (int) $userStats['with_orders'],
        ];

        $perPage = 20;
        $page = max(1, (int) $request->input('page', 1));

        if ($request->search || $request->filled('status')) {
            $users = $query->latest()->paginate($perPage)->withQueryString();
        } else {
            // Fast paginator: skip duplicate count query by reusing cached total
            $total = (int) ($stats['total'] ?? 0);
            $items = $query->latest()->forPage($page, $perPage)->get();
            $users = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('users', compact('users', 'stats'));
    }

    public function show(User $customer)
    {
        return redirect()->route('users.index', ['customer' => $customer->id]);
    }
}
