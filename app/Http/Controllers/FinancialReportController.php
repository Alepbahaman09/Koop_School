<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockPurchase;
use App\Models\Payment;
use App\Models\TerminalPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $days = $this->validatedDays($request);
        $end = now()->endOfDay();
        $start = now()->subDays($days - 1)->startOfDay();

        // Use consistent cache naming to avoid cache conflicts
        $data = Cache::remember("analytics.index.top_items.{$days}", 60, fn () => $this->reportData($days));
        $data['days'] = $days;
        $data['start'] = $start;
        $data['end'] = $end;
        $data['salesTrend'] = collect($data['salesTrend']);
        $data['categorySales'] = collect($data['categorySales'])->map(fn (array $category) => (object) $category);
        $data['topProducts'] = collect($data['topProducts'])->map(fn (array $product) => (object) $product);

        return view('financial-report', $data);
    }

    public function export(Request $request): StreamedResponse
    {
        $days = $this->validatedDays($request);
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        return response()->streamDownload(function () use ($start, $end) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Reference', 'Supplier / Source', 'Type', 'Amount (RM)', 'Status']);

            // Income: checkout payments
            Payment::query()
                ->where('status', 'Completed')
                ->whereBetween('paid_at', [$start, $end])
                ->orderBy('paid_at')
                ->cursor()
                ->each(fn (Payment $payment) => fputcsv($file, [
                    $payment->paid_at?->format('Y-m-d') ?? $payment->created_at->format('Y-m-d'),
                    $payment->payment_reference,
                    'App Checkout',
                    'Income',
                    number_format((float) $payment->amount, 2),
                    $payment->status,
                ]));

            // Income: POS cashier terminal payments
            TerminalPayment::query()
                ->where('status', 'Completed')
                ->whereBetween('paid_at', [$start, $end])
                ->orderBy('paid_at')
                ->cursor()
                ->each(fn (TerminalPayment $tp) => fputcsv($file, [
                    $tp->paid_at?->format('Y-m-d') ?? $tp->created_at->format('Y-m-d'),
                    $tp->payment_reference,
                    'POS Cashier',
                    'Income',
                    number_format((float) $tp->amount, 2),
                    $tp->status,
                ]));

            // Expenses: received stock purchases only
            StockPurchase::with('supplier')
                ->where('status', 'received')
                ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('purchase_date')
                ->cursor()
                ->each(fn (StockPurchase $sp) => fputcsv($file, [
                    $sp->purchase_date->format('Y-m-d'),
                    'SP-' . str_pad($sp->id, 5, '0', STR_PAD_LEFT),
                    $sp->supplier?->company_name ?? '—',
                    'Expense',
                    number_format((float) $sp->total_amount, 2),
                    'Received',
                ]));

            fclose($file);
        }, 'financial-report-'.$start->format('Y-m-d').'-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function validatedDays(Request $request): int
    {
        $days = $request->integer('days', 30);

        return in_array($days, [7, 30, 90, 365], true) ? $days : 30;
    }

    private function reportData(int $days): array
    {
        $end = now()->endOfDay();
        $start = now()->subDays($days - 1)->startOfDay();
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        // 1. Calculate Income (payments + terminal_payments where status = Completed)
        $currentIncomePayments = (float) DB::table('payments')
            ->where('status', 'Completed')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');
        $currentIncomeTerminal = (float) DB::table('terminal_payments')
            ->where('status', 'Completed')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');
        $totalIncome = $currentIncomePayments + $currentIncomeTerminal;

        $previousIncomePayments = (float) DB::table('payments')
            ->where('status', 'Completed')
            ->whereBetween('paid_at', [$previousStart, $previousEnd])
            ->sum('amount');
        $previousIncomeTerminal = (float) DB::table('terminal_payments')
            ->where('status', 'Completed')
            ->whereBetween('paid_at', [$previousStart, $previousEnd])
            ->sum('amount');
        $previousIncome = $previousIncomePayments + $previousIncomeTerminal;

        // 2. Calculate Expenses (stock purchases where status = received)
        $totalExpenses = (float) StockPurchase::query()
            ->where('status', 'received')
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total_amount');
        $previousExpenses = (float) StockPurchase::query()
            ->where('status', 'received')
            ->whereBetween('purchase_date', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->sum('total_amount');

        // 3. Calculate Net Profit
        $netProfit = $totalIncome - $totalExpenses;
        $previousProfit = $previousIncome - $previousExpenses;

        $incomeChange = $this->percentageChange($totalIncome, $previousIncome);
        $expensesChange = $this->percentageChange($totalExpenses, $previousExpenses);
        $profitChange = $this->percentageChange($netProfit, $previousProfit);

        $metrics = [
            ['label' => 'Total Income', 'value' => 'RM ' . number_format($totalIncome, 2), 'change' => $incomeChange, 'tone' => 'indigo'],
            ['label' => 'Total Expenses', 'value' => 'RM ' . number_format($totalExpenses, 2), 'change' => $expensesChange, 'tone' => 'rose'],
            ['label' => 'Net Profit', 'value' => 'RM ' . number_format($netProfit, 2), 'change' => $profitChange, 'tone' => $netProfit >= 0 ? 'emerald' : 'rose'],
        ];

        // 4. Daily sales trend (Income vs Expenses)
        $dailyPayments = DB::table('payments')
            ->selectRaw('DATE(paid_at) as paid_date, SUM(amount) as total')
            ->where('status', 'Completed')
            ->whereBetween('paid_at', [$start, $end])
            ->groupByRaw('DATE(paid_at)')
            ->pluck('total', 'paid_date')
            ->map(fn($v) => (float)$v);

        $dailyTerminalPayments = DB::table('terminal_payments')
            ->selectRaw('DATE(paid_at) as paid_date, SUM(amount) as total')
            ->where('status', 'Completed')
            ->whereBetween('paid_at', [$start, $end])
            ->groupByRaw('DATE(paid_at)')
            ->pluck('total', 'paid_date')
            ->map(fn($v) => (float)$v);

        $dailyExpenses = DB::table('stock_purchases')
            ->selectRaw('purchase_date, SUM(total_amount) as total')
            ->where('status', 'received')
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('purchase_date')
            ->pluck('total', 'purchase_date')
            ->map(fn($v) => (float)$v);

        $salesTrend = collect(range(0, $days - 1))->map(function (int $offset) use ($start, $dailyPayments, $dailyTerminalPayments, $dailyExpenses) {
            $date = $start->copy()->addDays($offset);
            $dateStr = $date->toDateString();
            
            $income = (float) ($dailyPayments->get($dateStr, 0) + $dailyTerminalPayments->get($dateStr, 0));
            $expenses = (float) $dailyExpenses->get($dateStr, 0);

            return [
                'label' => $date->format($date->day === 1 || $offset === 0 ? 'd M' : 'd'),
                'revenue' => $income,
                'expenses' => $expenses,
            ];
        })->values()->all();

        // 5. Category sales
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

        // 6. Top Products
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
            ->havingRaw('SUM(CASE WHEN orders.id IS NOT NULL THEN order_items.quantity ELSE 0 END) > 0')
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

        return compact(
            'metrics',
            'salesTrend',
            'categorySales',
            'categoryTotal',
            'topProducts'
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
