<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $ordersCollection = Order::with(['customer', 'orderItems.product'])->latest()->get();
        $products = Product::all();
        $customers = Customer::all();
        $paidOrders = $ordersCollection->where('payment_status', 'Paid');
        $totalRevenue = (float) $paidOrders->sum('total_amount');

        $stats = [
            ['label' => 'Total Revenue', 'value' => 'RM '.number_format($totalRevenue, 2), 'change' => 'Live from database', 'accent' => 'bg-violet-50 text-violet-600', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2'],
            ['label' => 'Total Users', 'value' => number_format($customers->count()), 'change' => 'App accounts', 'accent' => 'bg-emerald-50 text-emerald-600', 'icon' => 'M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1'],
            ['label' => 'Total Orders', 'value' => number_format($ordersCollection->count()), 'change' => 'Live orders', 'accent' => 'bg-sky-50 text-sky-600', 'icon' => 'M6 6h15l-1.5 9h-12L6 6'],
            ['label' => 'Active Products', 'value' => number_format($products->where('is_active', true)->count()), 'change' => $products->filter(fn ($product) => $product->stock_quantity > 0 && $product->stock_quantity <= $product->min_stock_level)->count().' low stock items', 'accent' => 'bg-amber-50 text-amber-600', 'icon' => 'M3 7h18v12H3V7'],
        ];

        $salesLabels = collect(range(9, 0))->map(fn ($day) => now()->subDays($day)->format('d M'))->all();
        $salesBars = collect(range(9, 0))->map(function ($day) use ($ordersCollection) {
            $date = now()->subDays($day)->format('Y-m-d');

            return (float) $ordersCollection->filter(fn ($order) => $order->payment_status === 'Paid' && $order->created_at?->format('Y-m-d') === $date)->sum('total_amount');
        })->all();

        $orders = $ordersCollection->take(5)->map(fn ($order) => [
            'id' => $order->order_number,
            'customer' => $order->customer?->parent_name ?? 'N/A',
            'item' => $order->orderItems->pluck('product.name')->filter()->take(2)->join(', ') ?: 'No items',
            'status' => $order->status,
            'amount' => 'RM '.number_format($order->total_amount, 2),
        ]);

        $dashboardData = [
            'pending_orders' => $ordersCollection->where('status', 'Pending')->count(),
            'processing_orders' => $ordersCollection->whereIn('status', ['Processing', 'Packed', 'Ready'])->count(),
            'completed_orders' => $ordersCollection->where('status', 'Completed')->count(),
            'low_stock_products' => $products->filter(fn ($product) => $product->stock_quantity > 0 && $product->stock_quantity <= $product->min_stock_level)->count(),
            'recent_payments' => (float) $paidOrders->where('created_at', '>=', now()->subDays(7))->sum('total_amount'),
            'inventory_value' => (float) $products->sum(fn ($product) => $product->price * $product->stock_quantity),
        ];

        return view('dashboard', compact('stats', 'salesBars', 'salesLabels', 'orders', 'dashboardData'));
    }
}
