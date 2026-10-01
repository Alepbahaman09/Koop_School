<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockPurchase;
use App\Models\StockPurchaseItem;
use App\Models\Payment;
use App\Models\TerminalPayment;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $days = $this->validatedDays($request);
        $end = now()->endOfDay();
        $start = $days > 0 ? now()->subDays($days - 1)->startOfDay() : now()->subYears(5)->startOfDay();

        $data = $this->reportData($days);
        $data['days'] = $days;
        $data['start'] = $start;
        $data['end'] = $end;
        $data['salesTrend'] = collect($data['salesTrend']);
        $data['categorySales'] = collect($data['categorySales'])->map(fn (array $c) => (object) $c);
        $data['topProducts'] = collect($data['topProducts'])->map(fn (array $p) => (object) $p);
        $data['purchaseProductAnalysis'] = collect($data['purchaseProductAnalysis'])->map(fn (array $p) => (object) $p);
        $data['auditTransactions'] = collect($data['auditTransactions'])->map(fn (array $t) => (object) $t);

        return view('financial-report', $data);
    }

    /**
     * Generate official formatted PDF financial and audit report.
     */
    public function exportPdf(Request $request)
    {
        $days = $this->validatedDays($request);
        $withAudit = $request->boolean('with_audit', true);

        $end = now()->endOfDay();
        $start = $days > 0 ? now()->subDays($days - 1)->startOfDay() : now()->subYears(5)->startOfDay();

        $data = $this->reportData($days, true);
        $data['days'] = $days;
        $data['start'] = $start;
        $data['end'] = $end;
        $data['withAudit'] = $withAudit;
        $data['categorySales'] = collect($data['categorySales'])->map(fn (array $c) => (object) $c);
        $data['topProducts'] = collect($data['topProducts'])->map(fn (array $p) => (object) $p);
        $data['purchaseProductAnalysis'] = collect($data['purchaseProductAnalysis'])->map(fn (array $p) => (object) $p);
        $data['auditTransactions'] = collect($data['auditTransactions'])->map(fn (array $t) => (object) $t);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.financial-pdf', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'financial-audit-report-' . $start->format('Y-m-d') . '-to-' . $end->format('Y-m-d') . '.pdf';

        if ($request->boolean('stream')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }



    private function validatedDays(Request $request): int
    {
        $days = $request->integer('days', 30);

        return in_array($days, [7, 30, 90, 365, 0], true) ? $days : 30;
    }

    private function reportData(int $days, bool $fullAudit = false): array
    {
        $end = now()->endOfDay();
        $start = $days > 0 ? now()->subDays($days - 1)->startOfDay() : now()->subYears(5)->startOfDay();

        $previousEnd = $start->copy()->subSecond();
        $previousStart = $days > 0 ? $previousEnd->copy()->subDays($days - 1)->startOfDay() : $start;

        // 1. Calculate Income (payments + terminal_payments where status = Completed)
        $incomePaymentsQuery = DB::table('payments')
            ->where('status', 'Completed')
            ->whereBetween('paid_at', [$start, $end]);
        $currentIncomePayments = (float) $incomePaymentsQuery->sum('amount');
        $currentIncomePaymentsCount = $incomePaymentsQuery->count();

        $incomeTerminalQuery = DB::table('terminal_payments')
            ->where('status', 'Completed')
            ->whereBetween('paid_at', [$start, $end]);
        $currentIncomeTerminal = (float) $incomeTerminalQuery->sum('amount');
        $currentIncomeTerminalCount = $incomeTerminalQuery->count();

        $totalIncome = $currentIncomePayments + $currentIncomeTerminal;
        $totalSalesTransactions = $currentIncomePaymentsCount + $currentIncomeTerminalCount;

        // Previous period income for percentage comparison
        $previousIncome = 0.0;
        $previousExpenses = 0.0;
        if ($days > 0) {
            $prevPayments = (float) DB::table('payments')
                ->where('status', 'Completed')
                ->whereBetween('paid_at', [$previousStart, $previousEnd])
                ->sum('amount');
            $prevTerminal = (float) DB::table('terminal_payments')
                ->where('status', 'Completed')
                ->whereBetween('paid_at', [$previousStart, $previousEnd])
                ->sum('amount');
            $previousIncome = $prevPayments + $prevTerminal;

            $previousExpenses = (float) StockPurchase::query()
                ->where('status', 'received')
                ->whereBetween('purchase_date', [$previousStart->toDateString(), $previousEnd->toDateString()])
                ->sum('total_amount');
        }

        // 2. Calculate Expenses (stock purchases where status = received)
        $expensesQuery = StockPurchase::query()
            ->where('status', 'received')
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()]);
        $totalExpenses = (float) $expensesQuery->sum('total_amount');
        $totalPurchasesCount = $expensesQuery->count();

        // 3. Calculate Net Profit & Margins
        $netProfit = $totalIncome - $totalExpenses;
        $previousProfit = $previousIncome - $previousExpenses;
        $netProfitMargin = $totalIncome > 0 ? (($netProfit / $totalIncome) * 100) : 0;

        $incomeChange = $this->percentageChange($totalIncome, $previousIncome);
        $expensesChange = $this->percentageChange($totalExpenses, $previousExpenses);
        $profitChange = $this->percentageChange($netProfit, $previousProfit);

        $metrics = [
            [
                'label'    => 'Total Income',
                'value'    => 'RM ' . number_format($totalIncome, 2),
                'amount'   => $totalIncome,
                'subtext'  => 'Online: RM ' . number_format($currentIncomePayments, 2) . ' | POS: RM ' . number_format($currentIncomeTerminal, 2),
                'count'    => $totalSalesTransactions . ' transactions',
                'change'   => $incomeChange,
                'tone'     => 'emerald',
            ],
            [
                'label'    => 'Total Expenses',
                'value'    => 'RM ' . number_format($totalExpenses, 2),
                'amount'   => $totalExpenses,
                'subtext'  => $totalPurchasesCount . ' stock intake orders received',
                'count'    => $totalPurchasesCount . ' purchase orders',
                'change'   => $expensesChange,
                'tone'     => 'rose',
            ],
            [
                'label'    => 'Net Profit',
                'value'    => 'RM ' . number_format($netProfit, 2),
                'amount'   => $netProfit,
                'subtext'  => number_format($netProfitMargin, 1) . '% net profit margin',
                'count'    => $netProfit >= 0 ? 'Surplus revenue' : 'Deficit / Inflow phase',
                'change'   => $profitChange,
                'tone'     => $netProfit >= 0 ? 'indigo' : 'rose',
            ],
        ];

        // 4. Sales Trend (Income vs Expenses vs Net Profit over time)
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

        if ($days === 365 || $days === 0) {
            // Group by month for longer ranges (12 months)
            $monthsCount = 12;
            $salesTrend = collect(range(0, $monthsCount - 1))->map(function (int $i) use ($monthsCount, $dailyPayments, $dailyTerminalPayments, $dailyExpenses) {
                $monthDate = now()->subMonths($monthsCount - 1 - $i)->startOfMonth();
                $monthKey = $monthDate->format('Y-m');

                $income = 0.0;
                $expenses = 0.0;
                foreach ($dailyPayments as $d => $v) {
                    if (str_starts_with($d, $monthKey)) $income += $v;
                }
                foreach ($dailyTerminalPayments as $d => $v) {
                    if (str_starts_with($d, $monthKey)) $income += $v;
                }
                foreach ($dailyExpenses as $d => $v) {
                    if (str_starts_with($d, $monthKey)) $expenses += $v;
                }

                return [
                    'label'      => $monthDate->format('M Y'),
                    'revenue'    => round($income, 2),
                    'expenses'   => round($expenses, 2),
                    'net_profit' => round($income - $expenses, 2),
                ];
            })->values()->all();
        } else {
            // Daily points
            $stepDays = $days <= 30 ? 1 : ($days <= 90 ? 2 : 7);
            $pointsCount = (int) ceil($days / $stepDays);

            $salesTrend = collect(range(0, $pointsCount - 1))->map(function (int $offset) use ($start, $stepDays, $dailyPayments, $dailyTerminalPayments, $dailyExpenses) {
                $date = $start->copy()->addDays($offset * $stepDays);
                $dateStr = $date->toDateString();

                $income = 0.0;
                $expenses = 0.0;

                for ($d = 0; $d < $stepDays; $d++) {
                    $curDateStr = $date->copy()->addDays($d)->toDateString();
                    $income += (float) ($dailyPayments->get($curDateStr, 0) + $dailyTerminalPayments->get($curDateStr, 0));
                    $expenses += (float) $dailyExpenses->get($curDateStr, 0);
                }

                return [
                    'label'      => $date->format($date->day === 1 || $offset === 0 ? 'd M' : 'd M'),
                    'revenue'    => round($income, 2),
                    'expenses'   => round($expenses, 2),
                    'net_profit' => round($income - $expenses, 2),
                ];
            })->values()->all();
        }

        // 5. Category Sales Performance & 6. Top Selling Products (Audited with sold-units expenses & profit)
        $latestPurchaseItems = DB::table('stock_purchase_items')
            ->whereNotNull('product_id')
            ->where('purchase_price', '>', 0)
            ->orderBy('id', 'desc')
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');

        $soldItems = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', '!=', 'Cancelled')
            ->select(
                'categories.id as category_id',
                'categories.name as category_name',
                'products.id as product_id',
                'products.name as product_name',
                'products.sku as product_sku',
                'products.image as product_image',
                'products.price as product_price',
                'products.cost_price as product_cost_price',
                'products.units_per_carton as product_units_per_carton',
                'products.stock_quantity as product_stock_quantity',
                'order_items.quantity',
                'order_items.subtotal'
            )
            ->get();

        $categoriesMap = [];
        $productsMap = [];
        $categoryTotalRevenue = 0.0;

        foreach ($soldItems as $item) {
            $catId = $item->category_id ?: 0;
            $catName = $item->category_name ?: 'Uncategorised';
            $pId = $item->product_id;

            $qty = (int) $item->quantity;
            $rev = (float) $item->subtotal;
            $avgSelling = $qty > 0 ? ($rev / $qty) : (float) $item->product_price;

            $carton = max(1, (int) ($item->product_units_per_carton ?: 1));
            $unitCost = (float) $item->product_cost_price;

            if ($unitCost <= 0 && isset($latestPurchaseItems[$pId])) {
                $pi = $latestPurchaseItems[$pId];
                $unitCost = (float) $pi->purchase_price;
                $carton = max(1, (int) ($pi->units_per_purchase ?: 1));
            }

            if ($carton > 1) {
                $unitCost = $unitCost / $carton;
            } elseif ($unitCost > $avgSelling && $avgSelling > 0 && $unitCost >= 50.00) {
                $unitCost = round($unitCost / 50, 2);
            }

            // Expenses of the total units sold (NOT the entire stock batch expense)
            $exp = round($qty * $unitCost, 2);

            // Category aggregation
            if (!isset($categoriesMap[$catId])) {
                $categoriesMap[$catId] = [
                    'name'     => $catName,
                    'units'    => 0,
                    'revenue'  => 0.0,
                    'expenses' => 0.0,
                ];
            }
            $categoriesMap[$catId]['units'] += $qty;
            $categoriesMap[$catId]['revenue'] += $rev;
            $categoriesMap[$catId]['expenses'] += $exp;
            $categoryTotalRevenue += $rev;

            // Product aggregation
            if (!isset($productsMap[$pId])) {
                $imageUrl = null;
                if ($item->product_image) {
                    $imageUrl = (str_starts_with($item->product_image, 'http://') || str_starts_with($item->product_image, 'https://'))
                        ? $item->product_image
                        : Storage::disk('public')->url(ltrim(str_replace('storage/', '', $item->product_image), '/'));
                }

                $productsMap[$pId] = [
                    'id'             => $pId,
                    'name'           => $item->product_name,
                    'sku'            => $item->product_sku,
                    'image_url'      => $imageUrl,
                    'category'       => $catName,
                    'stock_quantity' => (int) $item->product_stock_quantity,
                    'unit_cost'      => round($unitCost, 2),
                    'price'          => (float) $item->product_price,
                    'units'          => 0,
                    'revenue'        => 0.0,
                    'expenses'       => 0.0,
                ];
            }
            $productsMap[$pId]['units'] += $qty;
            $productsMap[$pId]['revenue'] += $rev;
            $productsMap[$pId]['expenses'] += $exp;
        }

        $categorySales = collect($categoriesMap)->map(function ($cat) use ($categoryTotalRevenue) {
            $rev = round($cat['revenue'], 2);
            $exp = round($cat['expenses'], 2);
            $profit = round($rev - $exp, 2);
            $margin = $rev > 0 ? round(($profit / $rev) * 100, 1) : 0.0;
            $percent = $categoryTotalRevenue > 0 ? round(($rev / $categoryTotalRevenue) * 100, 1) : 0.0;

            return [
                'name'     => $cat['name'],
                'units'    => $cat['units'],
                'revenue'  => $rev,
                'expenses' => $exp,
                'profit'   => $profit,
                'margin'   => $margin,
                'percent'  => $percent,
            ];
        })->sortByDesc('revenue')->values()->all();

        $topProducts = collect($productsMap)->map(function ($p) {
            $units = $p['units'];
            $rev = round($p['revenue'], 2);
            $exp = round($p['expenses'], 2);
            $profit = round($rev - $exp, 2);
            $avgPrice = $units > 0 ? round($rev / $units, 2) : $p['price'];
            $margin = $rev > 0 ? round(($profit / $rev) * 100, 1) : 0.0;

            return [
                'id'             => $p['id'],
                'name'           => $p['name'],
                'sku'            => $p['sku'],
                'image_url'      => $p['image_url'],
                'category'       => $p['category'],
                'stock_quantity' => $p['stock_quantity'],
                'units'          => $units,
                'unit_cost'      => round($p['unit_cost'], 2),
                'avg_price'      => $avgPrice,
                'revenue'        => $rev,
                'expenses'       => $exp,
                'profit'         => $profit,
                'margin'         => $margin,
            ];
        })->sortByDesc('units')->take(10)->values()->all();

        // 7. Item Purchase Product Analysis (Procurement cost, suppliers, margins, and sales comparison)
        $stockPurchases = StockPurchase::with(['supplier', 'items.product.category'])
            ->where('status', 'received')
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $productUnitsSold = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', '!=', 'Cancelled')
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as sold_qty'))
            ->groupBy('order_items.product_id')
            ->pluck('sold_qty', 'order_items.product_id');

        $purchaseAnalysisMap = [];
        foreach ($stockPurchases as $sp) {
            foreach ($sp->items as $item) {
                $key = $item->product_id ?: $item->item_name;
                if (!isset($purchaseAnalysisMap[$key])) {
                    $purchaseAnalysisMap[$key] = [
                        'product_id'      => $item->product_id,
                        'name'            => $item->item_name,
                        'sku'             => $item->item_sku ?: ($item->product?->sku ?? '—'),
                        'category'        => $item->product?->category?->name ?? 'Uncategorised',
                        'suppliers'       => [],
                        'units_purchased' => 0,
                        'total_spent'     => 0,
                        'unit_cost'       => (float) $item->purchase_price,
                        'selling_price'   => (float) ($item->selling_price ?: ($item->product?->price ?? 0)),
                    ];
                }
                if ($sp->supplier && !in_array($sp->supplier->company_name, $purchaseAnalysisMap[$key]['suppliers'], true)) {
                    $purchaseAnalysisMap[$key]['suppliers'][] = $sp->supplier->company_name;
                }
                $purchaseAnalysisMap[$key]['units_purchased'] += $item->quantity;
                $purchaseAnalysisMap[$key]['total_spent']     += (float) $item->subtotal;
                $purchaseAnalysisMap[$key]['unit_cost']        = (float) $item->purchase_price;
                if ($item->selling_price > 0) {
                    $purchaseAnalysisMap[$key]['selling_price'] = (float) $item->selling_price;
                }
            }
        }

        $purchaseProductAnalysis = collect($purchaseAnalysisMap)->map(function ($item) use ($productUnitsSold) {
            $units = max(1, $item['units_purchased']);
            $avgCost = $item['total_spent'] / $units;
            $sellingPrice = $item['selling_price'];
            $margin = $sellingPrice - $avgCost;
            $marginPercent = $sellingPrice > 0 ? (($margin / $sellingPrice) * 100) : 0;
            $sold = $item['product_id'] ? ($productUnitsSold->get($item['product_id'], 0)) : 0;

            return [
                'name'            => $item['name'],
                'sku'             => $item['sku'],
                'category'        => $item['category'],
                'suppliers'       => implode(', ', $item['suppliers']) ?: '—',
                'units_purchased' => $item['units_purchased'],
                'avg_cost'        => round($avgCost, 2),
                'selling_price'   => round($sellingPrice, 2),
                'margin'          => round($margin, 2),
                'margin_percent'  => round($marginPercent, 1),
                'total_spent'     => round($item['total_spent'], 2),
                'units_sold'      => (int) $sold,
            ];
        })->sortByDesc('total_spent')->values()->all();

        // 8. Audit Transactions Ledger (All Income & Expenses combined chronologically)
        $auditList = collect();

        // Payments (Online Checkout)
        $payments = Payment::with('order')
            ->where('status', 'Completed')
            ->whereBetween('paid_at', [$start, $end])
            ->latest('paid_at')
            ->take($fullAudit ? 250 : 60)
            ->get();
        foreach ($payments as $p) {
            if ($p->order) {
                $isKiosk = str_starts_with((string) $p->order->order_number, 'POS-')
                    || str_starts_with((string) $p->order->order_number, 'KIOSK-')
                    || str_contains(strtolower($p->order->notes ?? ''), 'pos')
                    || str_contains(strtolower($p->order->notes ?? ''), 'kiosk');
                $orderId = $isKiosk
                    ? 'Kiosk-' . $p->order->id
                    : (string) ($p->order->order_number ?: $p->order->id);
            } else {
                $orderId = (string) ($p->order_id ?: $p->id);
            }

            $auditList->push([
                'timestamp' => $p->paid_at ?? $p->created_at,
                'date_str'  => ($p->paid_at ?? $p->created_at)->format('d M Y, h:i A'),
                'order_id'  => $orderId,
                'source'    => 'Online Checkout (' . ($p->payment_method ?: 'Online') . ')',
                'type'      => 'Income',
                'amount'    => (float) $p->amount,
                'status'    => 'Completed',
            ]);
        }

        // Terminal Payments (POS Cashier / Kiosk)
        $terminalPayments = TerminalPayment::with('order')
            ->where('status', 'Completed')
            ->whereBetween('paid_at', [$start, $end])
            ->latest('paid_at')
            ->take($fullAudit ? 250 : 60)
            ->get();
        foreach ($terminalPayments as $tp) {
            $orderId = 'Kiosk-' . ($tp->order?->id ?: $tp->order_id ?: $tp->id);

            $auditList->push([
                'timestamp' => $tp->paid_at ?? $tp->created_at,
                'date_str'  => ($tp->paid_at ?? $tp->created_at)->format('d M Y, h:i A'),
                'order_id'  => $orderId,
                'source'    => 'POS Cashier Terminal',
                'type'      => 'Income',
                'amount'    => (float) $tp->amount,
                'status'    => 'Completed',
            ]);
        }

        // Stock Purchases (Procurement)
        $stockPurchasesList = StockPurchase::with('supplier')
            ->where('status', 'received')
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->latest('purchase_date')
            ->take($fullAudit ? 250 : 60)
            ->get();
        foreach ($stockPurchasesList as $sp) {
            $auditList->push([
                'timestamp' => Carbon::parse($sp->purchase_date),
                'date_str'  => Carbon::parse($sp->purchase_date)->format('d M Y'),
                'order_id'  => 'PO-' . $sp->id,
                'source'    => 'Vendor: ' . ($sp->supplier?->company_name ?? 'Supplier'),
                'type'      => 'Expense',
                'amount'    => (float) $sp->total_amount,
                'status'    => 'Received',
            ]);
        }

        $auditTransactions = $auditList->sortByDesc('timestamp')->values()->all();

        return compact(
            'metrics',
            'totalIncome',
            'totalExpenses',
            'netProfit',
            'netProfitMargin',
            'currentIncomePayments',
            'currentIncomeTerminal',
            'totalSalesTransactions',
            'totalPurchasesCount',
            'salesTrend',
            'categorySales',
            'categoryTotalRevenue',
            'topProducts',
            'purchaseProductAnalysis',
            'auditTransactions'
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
