<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $totalRevenue = Order::where('payment_status', 'Paid')->sum('total_amount');
        $thisMonthRevenue = Order::where('payment_status', 'Paid')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total_amount');
        $lastMonthRevenue = Order::where('payment_status', 'Paid')
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('total_amount');
        $revenueChange = $lastMonthRevenue > 0 ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        $totalOrders = Order::count();
        $thisMonthOrders = Order::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $lastMonthOrders = Order::whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->count();
        $ordersChange = $lastMonthOrders > 0 ? (($thisMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100 : 0;

        $totalCustomers = Customer::count();
        $thisMonthCustomers = Customer::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $lastMonthCustomers = Customer::whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->count();
        $customersChange = $lastMonthCustomers > 0 ? (($thisMonthCustomers - $lastMonthCustomers) / $lastMonthCustomers) * 100 : 0;

        $totalProducts = Product::where('is_active', true)->count();
        $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'min_stock_level')->count();

        $pendingOrders = Order::where('status', 'Pending')->count();
        $processingOrders = Order::whereIn('status', ['Processing', 'Packed', 'Ready'])->count();
        $completedOrders = Order::where('status', 'Completed')->count();

        $recentPayments = Payment::where('status', 'Completed')
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
        $inventoryValue = Product::selectRaw('COALESCE(SUM(price * stock_quantity), 0) as value')->value('value');

        $stats = [
            ['label' => 'Total Revenue', 'value' => 'RM '.number_format($totalRevenue, 2), 'change' => sprintf('%+.1f%% from last month', $revenueChange), 'accent' => 'bg-violet-50 text-violet-600', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v2m0 12v2m8-8h-2M6 12H4m12.95-4.95-1.414 1.414M8.464 15.536 7.05 16.95m0-9.9 1.414 1.414m7.072 7.072 1.414 1.414'],
            ['label' => 'Total Users', 'value' => number_format($totalCustomers), 'change' => sprintf('%+.1f%% from last month', $customersChange), 'accent' => 'bg-emerald-50 text-emerald-600', 'icon' => 'M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m8-4a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
            ['label' => 'Total Orders', 'value' => number_format($totalOrders), 'change' => sprintf('%+.1f%% from last month', $ordersChange), 'accent' => 'bg-sky-50 text-sky-600', 'icon' => 'M6 6h15l-1.5 9h-12L6 6Zm0 0L5 3H2m7 18a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm9 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z'],
            ['label' => 'Active Products', 'value' => number_format($totalProducts), 'change' => $lowStockProducts.' low stock items', 'accent' => 'bg-amber-50 text-amber-600', 'icon' => 'M3 7h18v12H3V7Zm3-4h12v4H6V3Zm12 10h.01'],
        ];

        $salesBars = [];
        $salesLabels = [];
        for ($i = 9; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $salesBars[] = (float) Order::where('payment_status', 'Paid')->whereDate('created_at', $date)->sum('total_amount');
            $salesLabels[] = $date->format('d M');
        }

        $orders = Order::with(['customer', 'orderItems.product'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                $itemNames = $order->orderItems->pluck('product.name')->take(2)->join(', ');
                $itemCount = $order->orderItems->count();
                if ($itemCount > 2) {
                    $itemNames .= ' +'.($itemCount - 2).' more';
                }

                return [
                    'id' => $order->order_number,
                    'customer' => $order->customer->parent_name ?? 'N/A',
                    'item' => $itemNames ?: 'No items',
                    'status' => $order->status,
                    'amount' => 'RM '.number_format($order->total_amount, 2),
                ];
            });

        $dashboardData = [
            'total_revenue' => $totalRevenue,
            'total_customers' => $totalCustomers,
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'pending_orders' => $pendingOrders,
            'processing_orders' => $processingOrders,
            'completed_orders' => $completedOrders,
            'low_stock_products' => $lowStockProducts,
            'recent_payments' => $recentPayments,
            'inventory_value' => $inventoryValue,
        ];

        return view('dashboard', compact('stats', 'salesBars', 'salesLabels', 'orders', 'dashboardData'));
    }
}
