<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days = $this->validatedDays($request);
        $end = now()->endOfDay();
        $start = now()->subDays($days - 1)->startOfDay();
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        $currentOrders = Order::whereBetween('created_at', [$start, $end]);
        $previousOrders = Order::whereBetween('created_at', [$previousStart, $previousEnd]);

        $revenue = (float) (clone $currentOrders)->where('payment_status', 'Paid')->sum('total_amount');
        $previousRevenue = (float) (clone $previousOrders)->where('payment_status', 'Paid')->sum('total_amount');
        $orderCount = (clone $currentOrders)->count();
        $previousOrderCount = (clone $previousOrders)->count();
        $averageOrder = $orderCount > 0 ? (float) (clone $currentOrders)->avg('total_amount') : 0;
        $previousAverageOrder = $previousOrderCount > 0 ? (float) (clone $previousOrders)->avg('total_amount') : 0;
        $paidOrders = (clone $currentOrders)->where('payment_status', 'Paid')->count();
        $previousPaidOrders = (clone $previousOrders)->where('payment_status', 'Paid')->count();
        $paidRate = $orderCount > 0 ? ($paidOrders / $orderCount) * 100 : 0;
        $previousPaidRate = $previousOrderCount > 0 ? ($previousPaidOrders / $previousOrderCount) * 100 : 0;

        $metrics = [
            ['label' => 'Paid Revenue', 'value' => 'RM '.number_format($revenue, 2), 'change' => $this->percentageChange($revenue, $previousRevenue), 'tone' => 'indigo'],
            ['label' => 'Orders', 'value' => number_format($orderCount), 'change' => $this->percentageChange($orderCount, $previousOrderCount), 'tone' => 'sky'],
            ['label' => 'Average Order', 'value' => 'RM '.number_format($averageOrder, 2), 'change' => $this->percentageChange($averageOrder, $previousAverageOrder), 'tone' => 'amber'],
            ['label' => 'Paid Order Rate', 'value' => number_format($paidRate, 1).'%', 'change' => $this->percentageChange($paidRate, $previousPaidRate), 'tone' => 'emerald'],
        ];

        $dailyOrders = Order::whereBetween('created_at', [$start, $end])
            ->get(['created_at', 'total_amount', 'payment_status'])
            ->groupBy(fn (Order $order) => $order->created_at->format('Y-m-d'));

        $salesTrend = collect(range(0, $days - 1))->map(function (int $offset) use ($start, $dailyOrders) {
            $date = $start->copy()->addDays($offset);
            $orders = $dailyOrders->get($date->format('Y-m-d'), collect());

            return [
                'label' => $date->format($date->day === 1 || $offset === 0 ? 'd M' : 'd'),
                'revenue' => (float) $orders->where('payment_status', 'Paid')->sum('total_amount'),
                'orders' => $orders->count(),
            ];
        });

        $maxRevenue = max(1, (float) $salesTrend->max('revenue'));

        $statusCounts = Order::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = collect(['Pending', 'Processing', 'Packed', 'Ready', 'Completed', 'Cancelled'])
            ->map(fn (string $status) => [
                'label' => $status,
                'total' => (int) ($statusCounts[$status] ?? 0),
                'percentage' => $orderCount > 0 ? (($statusCounts[$status] ?? 0) / $orderCount) * 100 : 0,
            ]);

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
            ->get();

        $categoryTotal = max(1, (float) $categorySales->sum('revenue'));

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
            ->get();

        $inventory = [
            'value' => (float) Product::sum(DB::raw('price * stock_quantity')),
            'active' => Product::where('is_active', true)->count(),
            'low' => Product::where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'min_stock_level')->count(),
            'out' => Product::where('stock_quantity', 0)->count(),
        ];

        $customerStats = [
            'total' => Customer::count(),
            'new' => Customer::whereBetween('created_at', [$start, $end])->count(),
            'active' => Order::whereBetween('created_at', [$start, $end])->distinct('customer_id')->count('customer_id'),
            'returning' => Order::whereBetween('created_at', [$start, $end])
                ->select('customer_id')
                ->groupBy('customer_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count(),
        ];

        return view('analytics', compact(
            'days',
            'start',
            'end',
            'metrics',
            'salesTrend',
            'maxRevenue',
            'statuses',
            'categorySales',
            'categoryTotal',
            'topProducts',
            'inventory',
            'customerStats',
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $days = $this->validatedDays($request);
        $start = now()->subDays($days - 1)->startOfDay();
        $orders = Order::with('customer')
            ->whereBetween('created_at', [$start, now()->endOfDay()])
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order Number', 'Date', 'Customer', 'Status', 'Payment Status', 'Subtotal', 'Discount', 'Tax', 'Total']);

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

    private function percentageChange(float|int $current, float|int $previous): array
    {
        if ((float) $previous === 0.0) {
            return ['value' => $current > 0 ? 100.0 : 0.0, 'positive' => $current >= 0];
        }

        $change = (($current - $previous) / abs($previous)) * 100;

        return ['value' => $change, 'positive' => $change >= 0];
    }
}
