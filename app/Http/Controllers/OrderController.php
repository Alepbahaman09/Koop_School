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
            ->select(['id', 'order_number', 'customer_id', 'status', 'total_amount', 'payment_status', 'created_at'])
            ->with([
                'customer:id,parent_name,student_name,class,phone,email,address',
                'orderItems:id,order_id,product_id,quantity,unit_price,subtotal',
                'orderItems.product:id,name',
                'statusHistory' => function ($q) {
                    $q->select(['id', 'order_id', 'user_id', 'admin_id', 'status', 'created_at'])
                        ->latest()
                        ->with(['user:id,name', 'admin:id,name']);
                },
            ])
            ->withSum(['payments as completed_payments_total' => fn ($q) => $q->where('status', 'Completed')], 'amount');

        if (in_array($request->status, Order::STATUSES, true)) {
            $query->where('status', $request->status);
        }

        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', '%'.$request->search.'%')
                    ->orWhereHas('customer', function ($q2) use ($request) {
                        $q2->where('parent_name', 'like', '%'.$request->search.'%')
                            ->orWhere('student_name', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $orders = $query->latest()->paginate(20)->withQueryString();
        $stats = $this->orderStats();
        $statuses = Order::STATUSES;

        return view('orders', compact('orders', 'stats', 'statuses'));
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
                $order->update(['status' => $validated['status']]);

                return OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'admin_id' => auth()->id(),
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                ]);
            });

            $message = 'Order status updated to '.$validated['status'].'.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'order' => [
                        'id' => $order->id,
                        'status' => $order->status,
                    ],
                    'history' => [
                        'status' => $history->status,
                        'updated_by' => auth()->user()->name,
                        'updated_at' => $history->created_at->diffForHumans(),
                    ],
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

        return redirect()->route('orders.index')->with('success', 'Order deleted successfully');
    }
}
