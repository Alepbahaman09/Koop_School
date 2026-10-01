<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()
            ->select(['id', 'order_number', 'customer_id', 'user_id', 'student_id', 'status', 'total_amount', 'payment_status', 'created_at'])
            ->with([
                'customer:id,parent_name,student_name,class,phone,email,address',
                'user:id,name,username,email,phone_number',
                'student:id,name,class',
                'orderItems:id,order_id,product_id,size,quantity,unit_price,subtotal',
                'orderItems.product:id,name',
                'statusHistory' => function ($q) {
                    $q->select(['id', 'order_id', 'admin_id', 'status', 'created_at'])
                        ->latest()
                        ->with(['admin:id,name']);
                },
                // Load terminal payments so we can tell if the order came from the kiosk POS
                'terminalPayments' => function ($q) {
                    $q->select(['id', 'order_id', 'user_id', 'card_id', 'payment_method'])
                        ->with(['user:id,name,username', 'card:id,owner']);
                },
                // Load regular (app/online) payments to identify the payment method
                'payments:id,order_id,payment_method',
            ])
            ->withSum(['payments as completed_payments_total' => fn ($q) => $q->where('status', 'Completed')], 'amount');

        // Filter by order status
        if (in_array($request->status, Order::STATUSES, true)) {
            $query->where('status', $request->status);
        }

        // Filter by payment method: Cash, NFC, or Online
        $paymentMethodFilter = $request->input('payment_method');
        if ($paymentMethodFilter === 'Cash') {
            // Cash means the order has a terminal payment with payment_method = 'Cash'
            $query->whereHas('terminalPayments', function ($q) {
                $q->where('payment_method', 'Cash');
            });
        } elseif ($paymentMethodFilter === 'NFC') {
            // NFC means the order has a terminal payment with payment_method = 'NFC Card'
            $query->whereHas('terminalPayments', function ($q) {
                $q->where('payment_method', 'NFC Card');
            });
        } elseif ($paymentMethodFilter === 'Online') {
            // Online means the order has a regular payment but NO terminal payment
            $query->whereHas('payments')
                ->whereDoesntHave('terminalPayments');
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', '%'.$request->search.'%')
                    ->orWhereHas('customer', function ($q2) use ($request) {
                        $q2->where('parent_name', 'like', '%'.$request->search.'%')
                            ->orWhere('student_name', 'like', '%'.$request->search.'%');
                    })
                    ->orWhereHas('user', function ($userQuery) use ($request) {
                        $userQuery->where('name', 'like', '%'.$request->search.'%')
                            ->orWhere('username', 'like', '%'.$request->search.'%');
                    })
                    ->orWhereHas('student', function ($studentQuery) use ($request) {
                        $studentQuery->where('name', 'like', '%'.$request->search.'%')
                            ->orWhere('class', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $stats = $this->orderStats();
        $statuses = Order::STATUSES;
        $paymentMethods = ['Cash', 'NFC', 'Online'];

        $perPage = 20;
        $page = max(1, (int) $request->input('page', 1));

        if ($request->search || in_array($request->status, Order::STATUSES, true) || $paymentMethodFilter) {
            $orders = $query->latest()->paginate($perPage)->withQueryString();
        } else {
            // Fast paginator: skip redundant count query by reusing cached total
            $total = (int) ($stats['total'] ?? 0);
            $items = $query->latest()->forPage($page, $perPage)->get();
            $orders = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('orders', compact('orders', 'stats', 'statuses', 'paymentMethods'));
    }

    public function snapshot(Request $request)
    {
        $orderIds = collect(explode(',', (string) $request->query('order_ids')))
            ->filter(fn ($id) => ctype_digit($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(100);

        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->get(['id', 'status', 'payment_status'])
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
            ]);

        return response()->json([
            'stats' => $this->orderStats(),
            'orders' => $orders,
        ]);
    }

    private function orderStats(): array
    {
        return Cache::remember('orders.stats.v2', 120, function () {
            $totals = (array) Order::query()
                ->toBase()
                ->selectRaw(
                    <<<'SQL'
                    COUNT(*) as total,
                    COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as in_progress,
                    COUNT(CASE WHEN status = ? THEN 1 END) as completed,
                    COUNT(CASE WHEN status = ? THEN 1 END) as cancelled
                    SQL,
                    [
                        Order::STATUS_PROCESSING,
                        Order::STATUS_READY,
                        Order::STATUS_COMPLETED,
                        Order::STATUS_CANCELLED,
                    ]
                )
                ->first();

            return [
                'total' => (int) $totals['total'],
                'in_progress' => (int) $totals['in_progress'],
                'completed' => (int) $totals['completed'],
                'cancelled' => (int) $totals['cancelled'],
            ];
        });
    }

    private function clearOrderCaches(): void
    {
        Cache::forget('orders.stats.v2');
        Cache::forget('dashboard.order_totals.v4');
        Cache::forget('dashboard.order_totals.v3');
        Cache::forget('dashboard.latest_orders.v1');
        Cache::forget('dashboard.available_years_v1');
        foreach ([7, 30, 90, 365, 0] as $d) {
            Cache::forget("financial.report_data.{$d}.0");
            Cache::forget("financial.report_data.{$d}.1");
        }
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
            'notes' => 'nullable|string',
        ]);

        try {
            $history = DB::transaction(function () use ($order, $validated) {
                if ($order->status === $validated['status']) {
                    return null;
                }

                $order->update(['status' => $validated['status']]);

                return OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'admin_id' => auth()->id(),
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                ]);
            });
            $order->refresh();

            $this->clearOrderCaches();

            $message = $history
                ? 'Order status updated to '.$order->status.'.'
                : 'Order is already '.$order->status.'.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'order' => [
                        'id' => $order->id,
                        'status' => $order->status,
                    ],
                    'history' => $history ? [
                        'status' => $history->status,
                        'updated_by' => auth()->user()->name,
                        'updated_at' => $history->created_at->diffForHumans(),
                    ] : null,
                    'stats' => $this->orderStats(),
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Failed to update order status.'], 500);
            }

            return back()->with('error', 'Failed to update order status.');
        }
    }

    public function destroy(Order $order)
    {
        if ($order->payments()->exists()) {
            return back()->with('error', 'Cannot delete order with payments');
        }

        $order->delete();

        $this->clearOrderCaches();

        return redirect()->route('orders.index')->with('success', 'Order deleted successfully');
    }

    public function receipt(Order $order)
    {
        $order->load([
            'customer',
            'user',
            'student',
            'orderItems.product',
            'terminalPayments.user',
            'terminalPayments.card',
            'payments',
        ]);

        return view('orders.receipt', compact('order'));
    }
}
