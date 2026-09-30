<?php

namespace App\Http\Controllers;

use App\Models\Order;
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

        // 1. Order and Revenue Statistics
        $orderTotals = Cache::remember('dashboard.order_totals.v3', 30, fn () => (array) Order::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COALESCE(SUM(CASE WHEN payment_status = 'Paid' THEN total_amount ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN payment_status = 'Paid' AND created_at BETWEEN ? AND ? THEN total_amount ELSE 0 END), 0) as this_month_revenue,
                COALESCE(SUM(CASE WHEN payment_status = 'Paid' AND created_at BETWEEN ? AND ? THEN total_amount ELSE 0 END), 0) as last_month_revenue,
                COUNT(*) as total_orders,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as this_month_orders,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as last_month_orders,
                COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as pending_orders,
                COUNT(CASE WHEN status = ? THEN 1 END) as cancelled_orders,
                COUNT(CASE WHEN payment_status = 'Refunded' THEN 1 END) as refunded_purchases
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
                    'Pending',
                    Order::STATUS_CANCELLED,
                ]
            )
            ->first());

        $totalRevenue = (float) ($orderTotals['total_revenue'] ?? 0);
        $thisMonthRevenue = (float) ($orderTotals['this_month_revenue'] ?? 0);
        $lastMonthRevenue = (float) ($orderTotals['last_month_revenue'] ?? 0);
        $revenueChange = $lastMonthRevenue > 0 ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        $totalOrders = (int) ($orderTotals['total_orders'] ?? 0);
        $thisMonthOrders = (int) ($orderTotals['this_month_orders'] ?? 0);
        $lastMonthOrders = (int) ($orderTotals['last_month_orders'] ?? 0);
        $ordersChange = $lastMonthOrders > 0 ? (($thisMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100 : 0;

        $pendingOrders = (int) ($orderTotals['pending_orders'] ?? 0);
        $cancelledOrders = (int) ($orderTotals['cancelled_orders'] ?? 0);
        $refundedPurchases = (int) ($orderTotals['refunded_purchases'] ?? 0);

        // 2. Product Statistics
        $productTotals = Cache::remember('dashboard.product_totals.v3', 30, fn () => (array) Product::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COUNT(*) as total_products,
                COUNT(CASE WHEN stock_quantity > 0 AND stock_quantity <= min_stock_level THEN 1 END) as low_stock_products,
                COUNT(CASE WHEN stock_quantity <= 0 THEN 1 END) as out_of_stock_products
                SQL
            )
            ->first());

        $totalProducts = (int) ($productTotals['total_products'] ?? 0);
        $lowStockProducts = (int) ($productTotals['low_stock_products'] ?? 0);
        $outOfStockProducts = (int) ($productTotals['out_of_stock_products'] ?? 0);

        // 3. Top Stat Cards: Total Sale, Total Order, Total Product
        $outOfStockText = $outOfStockProducts . ' ' . ($outOfStockProducts === 1 ? 'item out of stock' : 'items out of stock');

        $stats = [
            [
                'label' => 'Total Sale',
                'value' => 'RM ' . number_format($totalRevenue, 2),
                'change' => sprintf('%+.1f%% from last month', $revenueChange),
                'change_color' => $revenueChange < 0 ? 'text-rose-500' : 'text-emerald-500',
                'accent' => 'bg-violet-50 text-violet-600',
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v2m0 12v2m8-8h-2M6 12H4m12.95-4.95-1.414 1.414M8.464 15.536 7.05 16.95m0-9.9 1.414 1.414m7.072 7.072 1.414 1.414',
            ],
            [
                'label' => 'Total Order',
                'value' => number_format($totalOrders),
                'change' => sprintf('%+.1f%% from last month', $ordersChange),
                'change_color' => $ordersChange < 0 ? 'text-rose-500' : 'text-emerald-500',
                'accent' => 'bg-sky-50 text-sky-600',
                'icon' => 'M6 6h15l-1.5 9h-12L6 6Zm0 0L5 3H2m7 18a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm9 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z',
            ],
            [
                'label' => 'Total Product',
                'value' => number_format($totalProducts),
                'change' => $outOfStockText,
                'change_color' => 'text-amber-500',
                'accent' => 'bg-amber-50 text-amber-600',
                'icon' => 'M3 7h18v12H3V7Zm3-4h12v4H6V3Zm12 10h.01',
            ],
        ];

        // 4. Attention Required Items (Replaces Database Snapshot)
        $attentionItems = [
            [
                'label' => 'Low Stock Products',
                'count' => $lowStockProducts,
                'color' => 'yellow',
                'badge_class' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-300',
                'dot_class' => 'bg-amber-400',
                'url' => route('products.index', ['stock' => 'low']),
            ],
            [
                'label' => 'Out of Stock Products',
                'count' => $outOfStockProducts,
                'color' => 'red',
                'badge_class' => 'bg-rose-100 text-rose-800 ring-1 ring-rose-300',
                'dot_class' => 'bg-rose-500',
                'url' => route('products.index', ['stock' => 'out']),
            ],
            [
                'label' => 'Orders Pending',
                'count' => $pendingOrders,
                'color' => 'blue',
                'badge_class' => 'bg-sky-100 text-sky-800 ring-1 ring-sky-300',
                'dot_class' => 'bg-sky-500',
                'url' => route('orders.index', ['status' => 'Processing']),
            ],
            [
                'label' => 'Cancelled Orders',
                'count' => $cancelledOrders,
                'color' => 'slate',
                'badge_class' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-300',
                'dot_class' => 'bg-slate-400',
                'url' => route('orders.index', ['status' => 'Cancelled']),
            ],
            [
                'label' => 'Refund Purchases',
                'count' => $refundedPurchases,
                'color' => 'purple',
                'badge_class' => 'bg-purple-100 text-purple-800 ring-1 ring-purple-300',
                'dot_class' => 'bg-purple-400',
                'url' => route('orders.index', ['payment_status' => 'Refunded']),
            ],
        ];

        // 5. Sales Date Range and Analytics (Display by Month by default)
        $viewMode = $request->input('view', 'month');
        $timezone = config('app.timezone', 'Asia/Kuala_Lumpur');
        $salesBars = [];
        $salesLabels = [];
        $selectedYear = (int) $request->input('year', now()->year);
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        if ($request->filled('sales_start') && $request->filled('sales_end')) {
            $viewMode = 'custom';
            $salesStart = Carbon::parse($request->input('sales_start'))->startOfDay();
            $salesEnd = Carbon::parse($request->input('sales_end'))->endOfDay();
            if ($salesStart->gt($salesEnd)) {
                [$salesStart, $salesEnd] = [$salesEnd->copy()->startOfDay(), $salesStart->copy()->endOfDay()];
            }

            $salesByDate = Order::query()
                ->where('payment_status', 'Paid')
                ->whereBetween('created_at', [$salesStart->copy()->utc(), $salesEnd->copy()->utc()])
                ->selectRaw("DATE(created_at AT TIME ZONE 'UTC' AT TIME ZONE ?) as sales_date, COALESCE(SUM(total_amount), 0) as total", [$timezone])
                ->groupBy('sales_date')
                ->pluck('total', 'sales_date')
                ->all();

            for ($date = $salesStart->copy(); $date->lte($salesEnd); $date->addDay()) {
                $salesBars[] = (float) ($salesByDate[$date->toDateString()] ?? 0);
                $salesLabels[] = $date->format('d M');
            }
            $salesPeriodLabel = $salesStart->format('d M Y') . ' - ' . $salesEnd->format('d M Y');
        } elseif ($viewMode === 'daily') {
            try {
                $monthDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
            } catch (\Exception $e) {
                $monthDate = now()->startOfMonth();
                $selectedMonth = $monthDate->format('Y-m');
            }

            $salesStart = $monthDate->copy()->startOfMonth();
            $salesEnd = $monthDate->copy()->endOfMonth();
            $selectedYear = (int) $monthDate->year;

            $salesCacheKey = "dashboard.sales_daily.{$selectedMonth}";
            $salesByDate = Cache::remember($salesCacheKey, 30, function () use ($salesStart, $salesEnd, $timezone) {
                return Order::query()
                    ->where('payment_status', 'Paid')
                    ->whereBetween('created_at', [$salesStart->copy()->utc(), $salesEnd->copy()->utc()])
                    ->selectRaw("DATE(created_at AT TIME ZONE 'UTC' AT TIME ZONE ?) as sales_date, COALESCE(SUM(total_amount), 0) as total", [$timezone])
                    ->groupBy('sales_date')
                    ->pluck('total', 'sales_date')
                    ->all();
            });

            for ($date = $salesStart->copy(); $date->lte($salesEnd); $date->addDay()) {
                $salesBars[] = (float) ($salesByDate[$date->toDateString()] ?? 0);
                $salesLabels[] = $date->format('d M');
            }
            $salesPeriodLabel = $salesStart->format('F Y');
        } else {
            // Default: Display by Month (12 months of the year)
            $viewMode = 'month';
            $yearStart = Carbon::create($selectedYear, 1, 1)->startOfDay();
            $yearEnd = Carbon::create($selectedYear, 12, 31)->endOfDay();
            $salesStart = $yearStart;
            $salesEnd = $yearEnd;

            $salesCacheKey = "dashboard.sales_monthly.{$selectedYear}";
            $monthlySalesRaw = Cache::remember($salesCacheKey, 30, function () use ($yearStart, $yearEnd, $timezone) {
                return Order::query()
                    ->where('payment_status', 'Paid')
                    ->whereBetween('created_at', [$yearStart->copy()->utc(), $yearEnd->copy()->utc()])
                    ->selectRaw("TO_CHAR(created_at AT TIME ZONE 'UTC' AT TIME ZONE ?, 'YYYY-MM') as month_key, COALESCE(SUM(total_amount), 0) as total", [$timezone])
                    ->groupBy('month_key')
                    ->pluck('total', 'month_key')
                    ->all();
            });

            $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            for ($m = 1; $m <= 12; $m++) {
                $mKey = sprintf('%04d-%02d', $selectedYear, $m);
                $salesBars[] = (float) ($monthlySalesRaw[$mKey] ?? 0);
                $salesLabels[] = $monthNames[$m - 1];
            }
            $salesPeriodLabel = "Jan {$selectedYear} - Dec {$selectedYear}";
        }

        $salesIncome = array_sum($salesBars);

        // Available years for dropdown
        $availableYears = Order::query()
            ->where('payment_status', 'Paid')
            ->selectRaw("EXTRACT(YEAR FROM created_at) as yr")
            ->distinct()
            ->pluck('yr')
            ->map(fn ($y) => (int) $y)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        // 6. Latest Orders
        $orders = Order::query()
            ->select(['id', 'order_number', 'customer_id', 'user_id', 'status', 'total_amount', 'created_at'])
            ->with([
                'customer:id,parent_name,student_name',
                'user:id,name,username',
                'orderItems:id,order_id,product_id,size,quantity',
                'orderItems.product:id,name',
            ])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                $itemNames = $order->orderItems->map(function ($item) {
                    return $item->product?->name ?? 'Item';
                })->take(2)->join(', ');

                $itemCount = $order->orderItems->count();
                if ($itemCount > 2) {
                    $itemNames .= ' +'.($itemCount - 2).' more';
                }

                $customerName = $order->customer?->parent_name
                    ?? $order->user?->name
                    ?? $order->user?->username
                    ?? (str_starts_with($order->order_number, 'POS-') ? ('Kiosk-' . $order->id) : 'Walk-in');

                return [
                    'id' => str_starts_with($order->order_number, 'POS-') ? ('Kiosk-' . $order->id) : $order->order_number,
                    'customer' => $customerName,
                    'item' => $itemNames ?: 'No items',
                    'status' => $order->status,
                    'amount' => 'RM '.number_format($order->total_amount, 2),
                ];
            });

        $dashboardData = [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'pending_orders' => $pendingOrders,
            'cancelled_orders' => $cancelledOrders,
            'refunded_purchases' => $refundedPurchases,
        ];

        return view('dashboard', compact(
            'stats',
            'attentionItems',
            'salesBars',
            'salesLabels',
            'salesIncome',
            'salesPeriodLabel',
            'salesStart',
            'salesEnd',
            'viewMode',
            'selectedYear',
            'selectedMonth',
            'availableYears',
            'orders',
            'dashboardData'
        ));
    }
}
