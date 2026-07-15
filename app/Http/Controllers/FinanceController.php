<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\StockPurchase;
use App\Models\TerminalPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        [$month, $start, $end] = $this->period($request);
        $data = $this->financeData($start, $end);
        $data['month'] = $month;
        $data['start'] = $start;
        $data['end'] = $end;
        $data['trend'] = collect($data['trend']);
        $data['expenseBreakdown'] = collect($data['expenseBreakdown'])->map(fn (array $expense) => (object) $expense);

        return view('finance', $data);
    }

    public function export(Request $request): StreamedResponse
    {
        [$month, $start, $end] = $this->period($request);

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
        }, 'finance-'.$month.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function period(Request $request): array
    {
        $month = $request->input('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();

        return [$month, $start, $start->copy()->endOfMonth()];
    }

    private function financeData(Carbon $start, Carbon $end): array
    {
        $trendStart = $start->copy()->subMonthsNoOverflow(5)->startOfMonth();
        $previousStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $previousStart->copy()->endOfMonth();

        // Income = payments (checkout flow) + terminal_payments (POS cashier), both Completed
        $incomeRaw = DB::select("
            SELECT TO_CHAR(DATE_TRUNC('month', paid_at), 'YYYY-MM') as period,
                   COALESCE(SUM(amount), 0) as total
            FROM (
                SELECT paid_at, amount FROM payments WHERE status = 'Completed'
                    AND paid_at BETWEEN ? AND ?
                UNION ALL
                SELECT paid_at, amount FROM terminal_payments WHERE status = 'Completed'
                    AND paid_at BETWEEN ? AND ?
            ) AS combined
            GROUP BY DATE_TRUNC('month', paid_at)
        ", [$trendStart, $end, $trendStart, $end]);

        $incomeByMonth = collect($incomeRaw)->pluck('total', 'period')->map(fn ($v) => (float) $v);

        $expensesByMonth = \App\Models\StockPurchase::query()
            ->where('status', 'received')
            ->whereBetween('purchase_date', [$trendStart->toDateString(), $end->toDateString()])
            ->selectRaw("TO_CHAR(DATE_TRUNC('month', purchase_date::timestamp), 'YYYY-MM') as period, COALESCE(SUM(total_amount), 0) as total")
            ->groupByRaw("DATE_TRUNC('month', purchase_date::timestamp)")
            ->pluck('total', 'period');

        $periods = collect(range(5, 0))->map(fn (int $offset) => $start->copy()->subMonthsNoOverflow($offset)->startOfMonth());
        $trend = $periods->map(function (Carbon $period) use ($incomeByMonth, $expensesByMonth) {
            $key = $period->format('Y-m');
            $income = (float) ($incomeByMonth[$key] ?? 0);
            $expenses = (float) ($expensesByMonth[$key] ?? 0);

            return [
                'period' => $key,
                'label' => $period->format('M'),
                'income' => $income,
                'expenses' => $expenses,
                'profit' => $income - $expenses,
            ];
        })->values()->all();

        $currentKey = $start->format('Y-m');
        $previousKey = $previousStart->format('Y-m');
        $income = (float) ($incomeByMonth[$currentKey] ?? 0);
        $previousIncome = (float) ($incomeByMonth[$previousKey] ?? 0);
        $expenses = (float) ($expensesByMonth[$currentKey] ?? 0);
        $previousExpenses = (float) ($expensesByMonth[$previousKey] ?? 0);
        $profit = $income - $expenses;
        $previousProfit = $previousIncome - $previousExpenses;

        $metrics = [
            ['label' => 'Total Income', 'value' => $income, 'previous' => $previousIncome, 'tone' => 'indigo'],
            ['label' => 'Total Expenses', 'value' => $expenses, 'previous' => $previousExpenses, 'tone' => 'rose'],
            ['label' => 'Net Profit', 'value' => $profit, 'previous' => $previousProfit, 'tone' => $profit >= 0 ? 'emerald' : 'rose'],
        ];

        $expenseBreakdown = \App\Models\StockPurchase::query()
            ->join('suppliers', 'suppliers.id', '=', 'stock_purchases.supplier_id')
            ->where('stock_purchases.status', 'received')
            ->whereBetween('stock_purchases.purchase_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('suppliers.company_name as label, COALESCE(SUM(stock_purchases.total_amount), 0) as amount')
            ->groupBy('suppliers.company_name')
            ->orderByDesc('amount')
            ->limit(5)
            ->get()
            ->map(fn ($expense) => [
                'label' => $expense->label,
                'amount' => (float) $expense->amount,
            ])
            ->all();

        return compact('metrics', 'trend', 'expenseBreakdown');
    }
}
