<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()
            ->select(['id', 'order_number', 'customer_id', 'user_id', 'student_id', 'status', 'total_amount', 'payment_status', 'notes', 'created_at'])
            ->with([
                'customer:id,parent_name,student_name,class,phone,email,address',
                'user:id,name,username,email,phone_number',
                'student:id,name,class',
                'orderItems:id,order_id,product_id,size,quantity,unit_price,subtotal',
                'orderItems.product:id,name',
                'statusHistory' => function ($q) {
                    $q->select(['id', 'order_id', 'user_id', 'admin_id', 'status', 'created_at'])
                        ->latest()
                        ->with(['user:id,name', 'admin:id,name']);
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
            $search = trim($request->search);
            $numericId = null;
            if (preg_match('/^(?:kiosk[- ]?|cash[- ]?)?(\d+)$/i', $search, $matches)) {
                $numericId = (int) $matches[1];
            }

            $query->where(function ($q) use ($request, $numericId) {
                $q->where('order_number', 'like', '%'.$request->search.'%');
                if ($numericId) {
                    $q->orWhere('id', $numericId)
                        ->orWhereHas('terminalPayments', fn ($tq) => $tq->where('id', $numericId));
                }
                $q->orWhereHas('customer', function ($q2) use ($request) {
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

        $orders = $query->latest()->paginate(20)->withQueryString();
        $stats = $this->orderStats();
        $statuses = Order::STATUSES;

        // Payment method options for the filter dropdown
        $paymentMethods = ['Cash', 'NFC', 'Online'];

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

    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
            'status' => ['required', Rule::in(Order::STATUSES)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $targetStatus = $validated['status'];
            $notes = $validated['notes'] ?? ('Bulk update to '.$targetStatus);
            $adminId = auth()->id();
            $updatedOrderIds = [];

            DB::transaction(function () use ($validated, $targetStatus, $notes, $adminId, &$updatedOrderIds) {
                $orders = Order::with('terminalPayments')->whereIn('id', $validated['order_ids'])->get();

                foreach ($orders as $order) {
                    $isKiosk = $order->terminalPayments->isNotEmpty()
                        || str_starts_with($order->order_number, 'POS-')
                        || str_starts_with($order->order_number, 'KIOSK-')
                        || str_contains(strtolower($order->notes ?? ''), 'pos')
                        || str_contains(strtolower($order->notes ?? ''), 'kiosk');

                    if ($isKiosk) {
                        continue;
                    }

                    if ($order->status !== $targetStatus) {
                        $order->update(['status' => $targetStatus]);
                        $updatedOrderIds[] = $order->id;

                        OrderStatusHistory::create([
                            'order_id' => $order->id,
                            'admin_id' => $adminId,
                            'status' => $targetStatus,
                            'notes' => $notes,
                        ]);
                    }
                }
            });

            $count = count($updatedOrderIds);
            $message = $count > 0
                ? "{$count} order(s) updated to {$targetStatus}."
                : "Selected order(s) are already {$targetStatus}.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'status' => $targetStatus,
                    'updated_ids' => $updatedOrderIds,
                    'count' => $count,
                    'stats' => $this->orderStats(),
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Failed to update selected orders.'], 500);
            }

            return back()->with('error', 'Failed to update selected orders.');
        }
    }

    public function destroy(Order $order)
    {
        if ($order->payments()->exists()) {
            return back()->with('error', 'Cannot delete order with payments');
        }

        $order->delete();

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
