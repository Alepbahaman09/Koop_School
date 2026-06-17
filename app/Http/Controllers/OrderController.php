<?php

namespace App\Http\Controllers;

use App\Models\MobileDocument;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'orderItems.product', 'payments', 'statusHistory' => function ($q) {
            $q->latest()->with(['user', 'admin']);
        }]);

        if ($request->status) $query->where('status', $request->status);
        if ($request->payment_status) $query->where('payment_status', $request->payment_status);
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
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'Pending')->count(),
            'in_progress' => Order::whereIn('status', ['Processing', 'Packed', 'Ready'])->count(),
            'completed' => Order::where('status', 'Completed')->count(),
            'cancelled' => Order::where('status', 'Cancelled')->count(),
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
            $this->syncMobileOrderStatus($order);

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

    private function syncMobileOrderStatus(Order $order): void
    {
        $document = $order->source_document_path
            ? MobileDocument::where('path', $order->source_document_path)->first()
            : MobileDocument::where('data->orderNumber', $order->order_number)->first();
        if (! $document) {
            return;
        }

        $data = $document->data;
        $data['orderStatus'] = $order->status;
        $document->update(['data' => $data]);
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
