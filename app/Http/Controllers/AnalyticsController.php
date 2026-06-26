<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days = $this->validatedDays($request);
        $end = now()->endOfDay();
        $start = now()->subDays($days - 1)->startOfDay();
        $data = Cache::remember("analytics.index.{$days}", 60, fn () => $this->analyticsData($days));
        $data['days'] = $days;
        $data['start'] = $start;
        $data['end'] = $end;
        $data['salesTrend'] = collect($data['salesTrend']);
        $data['statuses'] = collect($data['statuses']);
        $data['categorySales'] = collect($data['categorySales'])->map(fn (array $category) => (object) $category);
        $data['topProducts'] = collect($data['topProducts'])->map(fn (array $product) => (object) $product);

        return view('analytics', $data);
    }

    public function export(Request $request): StreamedResponse
    {
        $days = $this->validatedDays($request);
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        return response()->streamDownload(function () use ($start, $end) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order Number', 'Date', 'Customer', 'Status', 'Payment Status', 'Subtotal', 'Discount', 'Tax', 'Total']);

            $orders = Order::query()
                ->select(['id', 'order_number', 'customer_id', 'status', 'payment_status', 'subtotal', 'discount', 'tax', 'total_amount', 'created_at'])
                ->with('customer:id,parent_name,student_name')
                ->whereBetween('created_at', [$start, $end])
                ->latest()
                ->cursor();

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->customer?->parent_name ?? $order->customer?->student_name ?? 'N/A',
                    $order->status,
                    $order->payment_status,
                    $order->subtotal,
                    $order->discount,
                    $order->tax,
                    $order->total_amount,
                ]);
            }

            fclose($file);
        }, 'analytics-'.$start->format('Y-m-d').'-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function validatedDays(Request $request): int
    {
        $days = $request->integer('days', 30);

        return in_array($days, [7, 30, 90, 365], true) ? $days : 30;
    }

    private function analyticsData(int $days): array
    {
        $end = now()->endOfDay();
        $start = now()->subDays($days - 1)->startOfDay();
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        $orderTotals = (array) Order::query()
            ->toBase()
            ->whereBetween('created_at', [$previousStart, $end])
            ->selectRaw(
                <<<'SQL'
                COALESCE(SUM(CASE WHEN payment_status = 'Paid' AND created_at BETWEEN ? AND ? THEN total_amount ELSE 0 END), 0) as revenue,
                COALESCE(SUM(CASE WHEN payment_status = 'Paid' AND created_at BETWEEN ? AND ? THEN total_amount ELSE 0 END), 0) as previous_revenue,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as order_count,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as previous_order_count,
                COALESCE(AVG(CASE WHEN created_at BETWEEN ? AND ? THEN total_amount END), 0) as average_order,
                COALESCE(AVG(CASE WHEN created_at BETWEEN ? AND ? THEN total_amount END), 0) as previous_average_order,
                COUNT(CASE WHEN payment_status = 'Paid' AND created_at BETWEEN ? AND ? THEN 1 END) as paid_orders,
                COUNT(CASE WHEN payment_status = 'Paid' AND created_at BETWEEN ? AND ? THEN 1 END) as previous_paid_orders
                SQL,
                [
                    $start, $end,
                    $previousStart, $previousEnd,
                    $start, $end,
                    $previousStart, $previousEnd,
                    $start, $end,
                    $previousStart, $previousEnd,
                    $start, $end,
                    $previousStart, $previousEnd,
                ]
            )
            ->first();

        $revenue = (float) $orderTotals['revenue'];
        $previousRevenue = (float) $orderTotals['previous_revenue'];
        $orderCount = (int) $orderTotals['order_count'];
        $previousOrderCount = (int) $orderTotals['previous_order_count'];
        $averageOrder = (float) $orderTotals['average_order'];
        $previousAverageOrder = (float) $orderTotals['previous_average_order'];
        $paidOrders = (int) $orderTotals['paid_orders'];
        $previousPaidOrders = (int) $orderTotals['previous_paid_orders'];
        $paidRate = $orderCount > 0 ? ($paidOrders / $orderCount) * 100 : 0;
        $previousPaidRate = $previousOrderCount > 0 ? ($previousPaidOrders / $previousOrderCount) * 100 : 0;

        $metrics = [
            ['label' => 'Paid Revenue', 'value' => 'RM '.number_format($revenue, 2), 'change' => $this->percentageChange($revenue, $previousRevenue), 'tone' => 'indigo'],
            ['label' => 'Orders', 'value' => number_format($orderCount), 'change' => $this->percentageChange($orderCount, $previousOrderCount), 'tone' => 'sky'],
            ['label' => 'Average Order', 'value' => 'RM '.number_format($averageOrder, 2), 'change' => $this->percentageChange($averageOrder, $previousAverageOrder), 'tone' => 'amber'],
            ['label' => 'Paid Order Rate', 'value' => number_format($paidRate, 1).'%', 'change' => $this->percentageChange($paidRate, $previousPaidRate), 'tone' => 'emerald'],
        ];

        $dailyOrders = Order::whereBetween('created_at', [$start, $end])
            ->selectRaw(
                'DATE(created_at) as order_date, COALESCE(SUM(CASE WHEN payment_status = ? THEN total_amount ELSE 0 END), 0) as revenue, COUNT(*) as orders',
                ['Paid']
            )
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy(fn ($row) => (string) $row->order_date);

        $salesTrend = collect(range(0, $days - 1))->map(function (int $offset) use ($start, $dailyOrders) {
            $date = $start->copy()->addDays($offset);
            $day = $dailyOrders->get($date->toDateString());

            return [
                'label' => $date->format($date->day === 1 || $offset === 0 ? 'd M' : 'd'),
                'revenue' => (float) ($day->revenue ?? 0),
                'orders' => (int) ($day->orders ?? 0),
            ];
        })->values()->all();

        $maxRevenue = max(1, (float) collect($salesTrend)->max('revenue'));

        $statusCounts = Order::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = collect(['Pending', 'Processing', 'Packed', 'Ready', 'Completed', 'Cancelled'])
            ->map(fn (string $status) => [
                'label' => $status,
                'total' => (int) ($statusCounts[$status] ?? 0),
                'percentage' => $orderCount > 0 ? (($statusCounts[$status] ?? 0) / $orderCount) * 100 : 0,
            ])
            ->values()
            ->all();

        $categorySales = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', '!=', 'Cancelled')
            ->select('categories.name', DB::raw('SUM(order_items.quantity) as units'), DB::raw('SUM(order_items.subtotal) as revenue'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->limit(6)
            ->get()
            ->map(fn ($category) => [
                'name' => $category->name,
                'units' => (int) $category->units,
                'revenue' => (float) $category->revenue,
            ])
            ->all();

        $categoryTotal = max(1, (float) collect($categorySales)->sum('revenue'));

        $topProducts = Product::query()
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function ($join) use ($start, $end) {
                $join->on('orders.id', '=', 'order_items.order_id')
                    ->whereBetween('orders.created_at', [$start, $end])
                    ->where('orders.status', '!=', 'Cancelled');
            })
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.image',
                'products.stock_quantity',
                DB::raw('COALESCE(SUM(CASE WHEN orders.id IS NOT NULL THEN order_items.quantity ELSE 0 END), 0) as units'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.id IS NOT NULL THEN order_items.subtotal ELSE 0 END), 0) as revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.image', 'products.stock_quantity')
            ->orderByDesc('units')
            ->limit(5)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'image_url' => $product->image_url,
                'stock_quantity' => (int) $product->stock_quantity,
                'units' => (int) $product->units,
                'revenue' => (float) $product->revenue,
            ])
            ->all();

        $productTotals = (array) Product::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COALESCE(SUM(price * stock_quantity), 0) as value,
                COUNT(CASE WHEN is_active = true THEN 1 END) as active,
                COUNT(CASE WHEN stock_quantity > 0 AND stock_quantity <= min_stock_level THEN 1 END) as low,
                COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out
                SQL
            )
            ->first();

        $inventory = [
            'value' => (float) $productTotals['value'],
            'active' => (int) $productTotals['active'],
            'low' => (int) $productTotals['low'],
            'out' => (int) $productTotals['out'],
        ];

        $customerTotals = (array) DB::query()
            ->selectRaw(
                <<<'SQL'
                (SELECT COUNT(*) FROM customers) as total,
                (SELECT COUNT(*) FROM customers WHERE created_at BETWEEN ? AND ?) as new,
                (SELECT COUNT(DISTINCT customer_id) FROM orders WHERE created_at BETWEEN ? AND ?) as active,
                (SELECT COUNT(*) FROM (
                    SELECT customer_id FROM orders
                    WHERE created_at BETWEEN ? AND ?
                    GROUP BY customer_id
                    HAVING COUNT(*) > 1
                ) as returning_customers) as returning
                SQL,
                [$start, $end, $start, $end, $start, $end]
            )
            ->first();

        $customerStats = [
            'total' => (int) $customerTotals['total'],
            'new' => (int) $customerTotals['new'],
            'active' => (int) $customerTotals['active'],
            'returning' => (int) $customerTotals['returning'],
        ];

        return compact(
            'metrics',
            'salesTrend',
            'maxRevenue',
            'statuses',
            'categorySales',
            'categoryTotal',
            'topProducts',
            'inventory',
            'customerStats',
        );
    }

    private function percentageChange(float|int $current, float|int $previous): array
    {
        if ((float) $previous === 0.0) {
            return ['value' => $current > 0 ? 100.0 : 0.0, 'positive' => $current >= 0];
        }

        $change = (($current - $previous) / abs($previous)) * 100;

        return ['value' => $change, 'positive' => $change >= 0];
    }
}
