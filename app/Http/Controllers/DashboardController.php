<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    private const DEFAULT_SALES_DAYS = 10;

    public function index(Request $request)
    {
        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $orderTotals = Cache::remember('dashboard.order_totals.current_statuses', 30, fn () => (array) Order::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COALESCE(SUM(CASE WHEN payment_status = 'Paid' THEN total_amount ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN payment_status = 'Paid' AND created_at BETWEEN ? AND ? THEN total_amount ELSE 0 END), 0) as this_month_revenue,
                COALESCE(SUM(CASE WHEN payment_status = 'Paid' AND created_at BETWEEN ? AND ? THEN total_amount ELSE 0 END), 0) as last_month_revenue,
                COUNT(*) as total_orders,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as this_month_orders,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as last_month_orders,
                COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as active_orders,
                COUNT(CASE WHEN status = ? THEN 1 END) as completed_orders
                SQL,
                [
                    $thisMonthStart,
                    $thisMonthEnd,
                    $lastMonthStart,
                    $lastMonthEnd,
                    $thisMonthStart,
                    $thisMonthEnd,
                    $lastMonthStart,
                    $lastMonthEnd,
                    Order::STATUS_PROCESSING,
                    Order::STATUS_READY,
                    Order::STATUS_COMPLETED,
                ]
            )
            ->first());

        $totalRevenue = (float) $orderTotals['total_revenue'];
        $thisMonthRevenue = (float) $orderTotals['this_month_revenue'];
        $lastMonthRevenue = (float) $orderTotals['last_month_revenue'];
        $revenueChange = $lastMonthRevenue > 0 ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        $totalOrders = (int) $orderTotals['total_orders'];
        $thisMonthOrders = (int) $orderTotals['this_month_orders'];
        $lastMonthOrders = (int) $orderTotals['last_month_orders'];
        $ordersChange = $lastMonthOrders > 0 ? (($thisMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100 : 0;

        $customerTotals = Cache::remember('dashboard.customer_totals', 30, fn () => (array) Customer::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COUNT(*) as total_customers,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as this_month_customers,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as last_month_customers
                SQL,
                [$thisMonthStart, $thisMonthEnd, $lastMonthStart, $lastMonthEnd]
            )
            ->first());

        $totalCustomers = (int) $customerTotals['total_customers'];
        $thisMonthCustomers = (int) $customerTotals['this_month_customers'];
        $lastMonthCustomers = (int) $customerTotals['last_month_customers'];
        $customersChange = $lastMonthCustomers > 0 ? (($thisMonthCustomers - $lastMonthCustomers) / $lastMonthCustomers) * 100 : 0;

        $productTotals = Cache::remember('dashboard.product_totals', 30, fn () => (array) Product::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COUNT(*) as total_products,
                COUNT(CASE WHEN stock_quantity <= min_stock_level THEN 1 END) as low_stock_products,
                COALESCE(SUM(price * stock_quantity), 0) as inventory_value
                SQL
            )
            ->first());

        $totalProducts = (int) $productTotals['total_products'];
        $lowStockProducts = (int) $productTotals['low_stock_products'];
        $activeOrders = (int) $orderTotals['active_orders'];
        $completedOrders = (int) $orderTotals['completed_orders'];

        $recentPayments = Cache::remember('dashboard.recent_payments', 30, fn () => Payment::where('status', 'Completed')
            ->whereBetween('paid_at', [$thisMonthStart, $thisMonthEnd])
            ->sum('amount'));
        $inventoryValue = (float) $productTotals['inventory_value'];

        $stats = [
            ['label' => 'Total Revenue', 'value' => 'RM '.number_format($totalRevenue, 2), 'change' => sprintf('%+.1f%% from last month', $revenueChange), 'accent' => 'bg-violet-50 text-violet-600', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v2m0 12v2m8-8h-2M6 12H4m12.95-4.95-1.414 1.414M8.464 15.536 7.05 16.95m0-9.9 1.414 1.414m7.072 7.072 1.414 1.414'],
            ['label' => 'Total Users', 'value' => number_format($totalCustomers), 'change' => sprintf('%+.1f%% from last month', $customersChange), 'accent' => 'bg-emerald-50 text-emerald-600', 'icon' => 'M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m8-4a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
            ['label' => 'Total Orders', 'value' => number_format($totalOrders), 'change' => sprintf('%+.1f%% from last month', $ordersChange), 'accent' => 'bg-sky-50 text-sky-600', 'icon' => 'M6 6h15l-1.5 9h-12L6 6Zm0 0L5 3H2m7 18a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm9 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z'],
            ['label' => 'Total Products', 'value' => number_format($totalProducts), 'change' => $lowStockProducts.' low stock items', 'accent' => 'bg-amber-50 text-amber-600', 'icon' => 'M3 7h18v12H3V7Zm3-4h12v4H6V3Zm12 10h.01'],
        ];

        [$salesStart, $salesEnd] = $this->salesDateRange($request);
        $salesCacheKey = "dashboard.sales_by_date.{$salesStart->toDateString()}.{$salesEnd->toDateString()}";
        $timezone = config('app.timezone');

        $salesByDate = Cache::remember($salesCacheKey, 30, fn () => Order::query()
            ->where('payment_status', 'Paid')
            ->whereBetween('created_at', [$salesStart->copy()->utc(), $salesEnd->copy()->utc()])
            ->selectRaw(
                "DATE(created_at AT TIME ZONE 'UTC' AT TIME ZONE ?) as sales_date, COALESCE(SUM(total_amount), 0) as total",
                [$timezone]
            )
            ->groupBy('sales_date')
            ->pluck('total', 'sales_date')
            ->all());

        $salesBars = [];
        $salesLabels = [];
        for ($date = $salesStart->copy(); $date->lte($salesEnd); $date->addDay()) {
            $salesBars[] = (float) ($salesByDate[$date->toDateString()] ?? 0);
            $salesLabels[] = $date->format('d M');
        }

        $salesIncome = array_sum($salesBars);

        $orders = Order::query()
            ->select(['id', 'order_number', 'customer_id', 'status', 'total_amount', 'created_at'])
            ->with([
                'customer:id,parent_name',
                'orderItems:id,order_id,product_id,quantity',
                'orderItems.product:id,name',
            ])
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
            'active_orders' => $activeOrders,
            'completed_orders' => $completedOrders,
            'low_stock_products' => $lowStockProducts,
            'recent_payments' => $recentPayments,
            'inventory_value' => $inventoryValue,
        ];

        return view('dashboard', compact(
            'stats',
            'salesBars',
            'salesLabels',
            'salesIncome',
            'salesStart',
            'salesEnd',
            'orders',
            'dashboardData'
        ));
    }

    private function salesDateRange(Request $request): array
    {
        $dates = $request->validate([
            'sales_start' => ['nullable', 'date_format:Y-m-d'],
            'sales_end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:sales_start'],
        ]);

        $end = isset($dates['sales_end'])
            ? Carbon::createFromFormat('Y-m-d', $dates['sales_end'])->endOfDay()
            : now()->endOfDay();

        $start = isset($dates['sales_start'])
            ? Carbon::createFromFormat('Y-m-d', $dates['sales_start'])->startOfDay()
            : $end->copy()->subDays(self::DEFAULT_SALES_DAYS - 1)->startOfDay();

        return [$start, $end];
    }
}
