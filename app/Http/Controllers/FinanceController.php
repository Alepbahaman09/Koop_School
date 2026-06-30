<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        [$month, $start, $end] = $this->period($request);
        $data = Cache::remember("finance.index.{$month}", 60, fn () => $this->financeData($start, $end));
        $data['month'] = $month;
        $data['start'] = $start;
        $data['end'] = $end;
        $data['trend'] = collect($data['trend']);
        $data['expenseBreakdown'] = collect($data['expenseBreakdown'])->map(fn (array $expense) => (object) $expense);
        $data['recentTransactions'] = collect($data['recentTransactions']);

        return view('finance', $data);
    }

    public function export(Request $request): StreamedResponse
    {
        [$month, $start, $end] = $this->period($request);

        return response()->streamDownload(function () use ($start, $end) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Reference', 'Description', 'Type', 'Amount', 'Status']);

            Payment::query()
                ->where('status', 'Completed')
                ->whereBetween('paid_at', [$start, $end])
                ->orderBy('paid_at')
                ->cursor()
                ->each(fn (Payment $payment) => fputcsv($file, [
                    $payment->paid_at?->format('Y-m-d') ?? $payment->created_at->format('Y-m-d'),
                    $payment->payment_reference,
                    'Order payment',
                    'Income',
                    $payment->amount,
                    $payment->status,
                ]));

            PurchaseOrder::query()
                ->whereIn('status', ['Received', 'Completed'])
                ->whereBetween('order_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('order_date')
                ->cursor()
                ->each(fn (PurchaseOrder $purchaseOrder) => fputcsv($file, [
                    $purchaseOrder->order_date->format('Y-m-d'),
                    $purchaseOrder->po_number,
                    'Purchase order',
                    'Expense',
                    -1 * (float) $purchaseOrder->total_amount,
                    $purchaseOrder->status,
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

        $incomeByMonth = Payment::query()
            ->where('status', 'Completed')
            ->whereBetween('paid_at', [$trendStart, $end])
            ->selectRaw("TO_CHAR(DATE_TRUNC('month', paid_at), 'YYYY-MM') as period, COALESCE(SUM(amount), 0) as total")
            ->groupByRaw("DATE_TRUNC('month', paid_at)")
            ->pluck('total', 'period');

        $expensesByMonth = PurchaseOrder::query()
            ->whereIn('status', ['Received', 'Completed'])
            ->whereBetween('order_date', [$trendStart->toDateString(), $end->toDateString()])
            ->selectRaw("TO_CHAR(DATE_TRUNC('month', order_date::timestamp), 'YYYY-MM') as period, COALESCE(SUM(total_amount), 0) as total")
            ->groupByRaw("DATE_TRUNC('month', order_date::timestamp)")
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

        $expenseBreakdown = PurchaseOrder::query()
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->whereIn('purchase_orders.status', ['Received', 'Completed'])
            ->whereBetween('purchase_orders.order_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(suppliers.company_name, suppliers.name) as label, COALESCE(SUM(purchase_orders.total_amount), 0) as amount')
            ->groupByRaw('COALESCE(suppliers.company_name, suppliers.name)')
            ->orderByDesc('amount')
            ->limit(5)
            ->get()
            ->map(fn ($expense) => [
                'label' => $expense->label,
                'amount' => (float) $expense->amount,
            ])
            ->all();

        $recentPayments = Payment::query()
            ->leftJoin('orders', 'orders.id', '=', 'payments.order_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('payments.status', 'Completed')
            ->selectRaw(
                <<<'SQL'
                payments.payment_reference as id,
                CONCAT('Payment for ', COALESCE(orders.order_number, 'missing order')) as description,
                COALESCE(customers.parent_name, customers.student_name, 'Unknown customer') as party,
                payments.paid_at as happened_at,
                payments.amount as amount,
                'Income' as type,
                payments.status as status
                SQL
            )
            ->latest('payments.paid_at')
            ->limit(8)
            ->get();

        $recentExpenses = PurchaseOrder::query()
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->whereIn('purchase_orders.status', ['Received', 'Completed'])
            ->selectRaw(
                <<<'SQL'
                purchase_orders.po_number as id,
                CONCAT('Purchase order ', purchase_orders.po_number) as description,
                COALESCE(suppliers.company_name, suppliers.name, 'Unknown supplier') as party,
                purchase_orders.order_date as happened_at,
                purchase_orders.total_amount as amount,
                'Expense' as type,
                purchase_orders.status as status
                SQL
            )
            ->latest('purchase_orders.order_date')
            ->limit(8)
            ->get();

        $recentTransactions = $recentPayments
            ->merge($recentExpenses)
            ->sortByDesc('happened_at')
            ->take(10)
            ->values()
            ->map(fn ($transaction) => [
                'id' => $transaction->id,
                'description' => $transaction->description,
                'party' => $transaction->party,
                'date_label' => $transaction->happened_at ? Carbon::parse($transaction->happened_at)->format('d M Y') : 'N/A',
                'sort_date' => (string) $transaction->happened_at,
                'amount' => (float) $transaction->amount,
                'type' => $transaction->type,
                'status' => $transaction->status,
            ])
            ->all();

        return compact('metrics', 'trend', 'expenseBreakdown', 'recentTransactions', 'income', 'expenses', 'profit');
    }
}
