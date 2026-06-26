<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

        if ($request->status) {
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
        $orderStats = Cache::remember('orders.stats', 30, fn () => (array) Order::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status IN ('Processing', 'Packed', 'Ready') THEN 1 END) as in_progress,
                COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled
                SQL
            )
            ->first());

        $stats = [
            'total' => (int) $orderStats['total'],
            'pending' => (int) $orderStats['pending'],
            'in_progress' => (int) $orderStats['in_progress'],
            'completed' => (int) $orderStats['completed'],
            'cancelled' => (int) $orderStats['cancelled'],
        ];

        return view('orders', compact('orders', 'stats'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:Pending,Processing,Packed,Ready,Completed,Cancelled',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            $order->update(['status' => $validated['status']]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'admin_id' => auth()->id(),
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return back()->with('success', 'Order status updated to '.$validated['status'].'.');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

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
