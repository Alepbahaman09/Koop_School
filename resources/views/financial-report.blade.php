@extends('layouts.app')

@section('title', 'Financial Report')
@section('page-title', 'Financial Report')

@section('content')
<div class="space-y-6">

    {{-- 1. Page Header & Actions --}}
    <section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-extrabold text-slate-950">Financial Report & Analytics</h1>
            <p class="mt-1 text-xs font-semibold text-slate-400">
                Reporting Range: <span class="text-slate-700 font-bold">{{ $start->format('d M Y') }} &mdash; {{ $end->format('d M Y') }}</span>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            {{-- Time Period Selector --}}
            <form method="GET" class="m-0">
                <select name="days" onchange="this.form.submit()" class="h-10 rounded-xl border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-700 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="7" @selected($days === 7)>Last 7 days</option>
                    <option value="30" @selected($days === 30)>Last 30 days</option>
                    <option value="90" @selected($days === 90)>Last 90 days</option>
                    <option value="365" @selected($days === 365)>Last 1 year</option>
                    <option value="0" @selected($days === 0)>All Time</option>
                </select>
            </form>

            {{-- PROMINENT BUTTON: Financial Report Modal (Print & PDF) --}}
            <button type="button" onclick="document.getElementById('financial-report-modal').showModal()"
                    class="inline-flex h-10 items-center gap-2 rounded-xl bg-indigo-600 px-4 text-xs font-extrabold text-white shadow-sm hover:bg-indigo-700 transition">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Financial Report
            </button>
        </div>
    </section>

    {{-- 2. Top Metric Cards: Total Income, Expenses, and Net Profit (3 Cards) --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {{-- Total Income Card --}}
        <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-100 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wide text-slate-400">Total Income</span>
                    <h3 class="mt-1 text-2xl font-black text-slate-950">RM {{ number_format($totalIncome, 2) }}</h3>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 border-t border-slate-50 pt-2.5 flex items-center justify-between text-xs">
                <span class="text-slate-500 font-semibold">{{ $metrics[0]['subtext'] }}</span>
                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 font-bold {{ $metrics[0]['change']['positive'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                    {{ $metrics[0]['change']['positive'] ? '+' : '' }}{{ number_format($metrics[0]['change']['value'], 1) }}%
                </span>
            </div>
        </div>

        {{-- Total Expenses Card --}}
        <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-100 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wide text-slate-400">Total Expenses</span>
                    <h3 class="mt-1 text-2xl font-black text-rose-600">RM {{ number_format($totalExpenses, 2) }}</h3>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-rose-50 text-rose-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 border-t border-slate-50 pt-2.5 flex items-center justify-between text-xs">
                <span class="text-slate-500 font-semibold">{{ $metrics[1]['subtext'] }}</span>
                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 font-bold {{ $metrics[1]['change']['positive'] ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
                    {{ $metrics[1]['change']['positive'] ? '+' : '' }}{{ number_format($metrics[1]['change']['value'], 1) }}%
                </span>
            </div>
        </div>

        {{-- Net Profit Card --}}
        <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-100 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wide text-slate-400">Net Profit</span>
                    <h3 class="mt-1 text-2xl font-black {{ $netProfit >= 0 ? 'text-indigo-600' : 'text-rose-600' }}">
                        RM {{ number_format($netProfit, 2) }}
                    </h3>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-xl {{ $netProfit >= 0 ? 'bg-indigo-50 text-indigo-600' : 'bg-rose-50 text-rose-600' }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 border-t border-slate-50 pt-2.5 flex items-center justify-between text-xs">
                <span class="font-bold {{ $netProfit >= 0 ? 'text-indigo-700' : 'text-rose-600' }}">
                    {{ number_format($netProfitMargin, 1) }}% Net Margin
                </span>
                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 font-bold {{ $metrics[2]['change']['positive'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                    {{ $metrics[2]['change']['positive'] ? '+' : '' }}{{ number_format($metrics[2]['change']['value'], 1) }}%
                </span>
            </div>
        </div>
    </section>

    {{-- 3. Body Section: Income vs Expenses Comparison Chart & Sales by Category --}}
    <section class="grid grid-cols-1 gap-5 xl:grid-cols-[1.3fr_1fr]">
        {{-- Income vs Expenses Comparison Chart --}}
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-slate-950">Income vs Expenses Comparison</h2>
                    <p class="text-xs font-semibold text-slate-400">Cash inflow vs inventory procurement over time</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-bold">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-slate-600">Income</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                        <span class="text-slate-600">Expenses</span>
                    </span>
                </div>
            </div>
            <div class="h-72 w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </article>

        {{-- Total Sales by Category --}}
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="mb-3">
                    <h2 class="text-sm font-black text-slate-950">Total Sales by Category</h2>
                    <p class="text-xs font-semibold text-slate-400">Completed order revenue distribution across categories</p>
                </div>

                <div class="grid items-center gap-4 sm:grid-cols-[1fr_1.1fr]">
                    <div class="relative mx-auto h-48 w-48">
                        <canvas id="categoryChart"></canvas>
                        <div class="pointer-events-none absolute inset-0 grid place-items-center text-center">
                            <div>
                                <p class="text-xl font-black text-slate-950">RM {{ number_format($totalIncome, 0) }}</p>
                                <p class="text-[10px] font-bold uppercase text-slate-400">Total Inflow</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                        @forelse ($categorySales as $cat)
                            <div class="flex items-center justify-between gap-2 text-xs p-1.5 rounded-lg hover:bg-slate-50 transition">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="category-key h-2.5 w-2.5 shrink-0 rounded-full"></span>
                                    <span class="truncate font-extrabold text-slate-800">{{ $cat->name }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-black text-slate-900">RM {{ number_format($cat->revenue, 2) }}</span>
                                    <span class="block text-[10px] font-semibold {{ $cat->profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        Profit: RM {{ number_format($cat->profit, 2) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="py-6 text-center text-xs font-semibold text-slate-400">No category sales recorded.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="mt-3 border-t border-slate-100 pt-2 flex items-center justify-between text-xs font-bold text-slate-500">
                <span>Categories Count: {{ count($categorySales) }}</span>
                <span class="text-indigo-600">{{ number_format($totalSalesTransactions) }} Total Transactions</span>
            </div>
        </article>
    </section>

    {{-- 4. Top Selling Items Section --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-black text-slate-950">Top Selling Items</h2>
                <p class="text-xs font-semibold text-slate-400">Best-performing school cooperative items ranked by sales volume</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                {{ count($topProducts) }} top items
            </span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] font-extrabold uppercase text-slate-400 border-b border-slate-200">
                    <tr>
                        <th class="p-3 text-center" style="width: 5%;">Rank</th>
                        <th class="p-3" style="width: 25%;">Product Item</th>
                        <th class="p-3" style="width: 12%;">Category</th>
                        <th class="p-3 text-center" style="width: 8%;">Sold</th>
                        <th class="p-3 text-center" style="width: 7%;">Stock</th>
                        <th class="p-3 text-right" style="width: 10%;">Price (RM)</th>
                        <th class="p-3 text-right" style="width: 12%;">Expenses (Sold Units)</th>
                        <th class="p-3 text-right" style="width: 11%;">Revenue (RM)</th>
                        <th class="p-3 text-right" style="width: 11%;">Net Profit (RM)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse ($topProducts as $idx => $prod)
                        <tr class="hover:bg-slate-50/50">
                            <td class="p-3 text-center font-black {{ $idx === 0 ? 'text-amber-500 text-sm' : ($idx === 1 ? 'text-slate-400 text-sm' : ($idx === 2 ? 'text-amber-700 text-sm' : 'text-slate-500')) }}">
                                #{{ $idx + 1 }}
                            </td>
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 shrink-0 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200/60">
                                        @if ($prod->image_url)
                                            <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="grid h-full place-items-center text-slate-400 font-bold text-xs">
                                                {{ substr($prod->name, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-extrabold text-slate-900 truncate">{{ $prod->name }}</p>
                                        @if ($prod->sku)
                                            <p class="font-mono text-[10px] text-slate-400">{{ $prod->sku }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 text-slate-600 font-semibold">{{ $prod->category }}</td>
                            <td class="p-3 text-center font-black text-slate-900">{{ number_format($prod->units) }} pcs</td>
                            <td class="p-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $prod->stock_quantity <= 5 ? 'bg-amber-50 text-amber-700 font-black' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $prod->stock_quantity }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-medium">RM {{ number_format($prod->avg_price, 2) }}</td>
                            <td class="p-3 text-right">
                                <span class="font-extrabold text-rose-600">RM {{ number_format($prod->expenses, 2) }}</span>
                                <span class="block text-[10px] text-slate-400">@ RM {{ number_format($prod->unit_cost, 2) }}</span>
                            </td>
                            <td class="p-3 text-right font-black text-slate-900">RM {{ number_format($prod->revenue, 2) }}</td>
                            <td class="p-3 text-right">
                                <span class="font-black {{ $prod->profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    RM {{ number_format($prod->profit, 2) }}
                                </span>
                                <span class="block text-[10px] font-bold {{ $prod->margin >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                                    {{ $prod->margin > 0 ? '+' : '' }}{{ number_format($prod->margin, 1) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-xs font-semibold text-slate-400">
                                No product sales recorded for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- 5. Item Purchase Product Analysis (New Section) --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-black text-slate-950">Item Purchase Product Analysis</h2>
                <p class="text-xs font-semibold text-slate-400">
                    Stock procurement cost analysis, supplier sourcing, and profit margin spread for received items
                </p>
            </div>
            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">
                {{ count($purchaseProductAnalysis) }} procured items
            </span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] font-extrabold uppercase text-slate-400 border-b border-slate-200">
                    <tr>
                        <th class="p-3" style="width: 26%;">Product / Item & SKU</th>
                        <th class="p-3" style="width: 13%;">Category</th>
                        <th class="p-3" style="width: 15%;">Supplier Sourced</th>
                        <th class="p-3 text-center" style="width: 9%;">Intake Qty</th>
                        <th class="p-3 text-right" style="width: 9%;">Avg Cost</th>
                        <th class="p-3 text-right" style="width: 9%;">Selling Price</th>
                        <th class="p-3 text-right" style="width: 9%;">Margin Spread</th>
                        <th class="p-3 text-right" style="width: 10%;">Total Spent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse ($purchaseProductAnalysis as $item)
                        <tr class="hover:bg-slate-50/50">
                            <td class="p-3">
                                <span class="font-extrabold text-slate-900">{{ $item->name }}</span>
                                @if ($item->sku && $item->sku !== '—')
                                    <span class="block font-mono text-[10px] text-slate-400">{{ $item->sku }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-slate-600 font-semibold">{{ $item->category }}</td>
                            <td class="p-3 font-semibold text-slate-800">{{ $item->suppliers }}</td>
                            <td class="p-3 text-center font-black text-indigo-600">{{ number_format($item->units_purchased) }} pcs</td>
                            <td class="p-3 text-right font-medium">RM {{ number_format($item->avg_cost, 2) }}</td>
                            <td class="p-3 text-right font-medium text-slate-600">RM {{ number_format($item->selling_price, 2) }}</td>
                            <td class="p-3 text-right font-black {{ $item->margin >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                RM {{ number_format($item->margin, 2) }}
                                <span class="block text-[10px] font-bold text-slate-400">({{ number_format($item->margin_percent, 1) }}%)</span>
                            </td>
                            <td class="p-3 text-right font-black text-rose-600">RM {{ number_format($item->total_spent, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-xs font-semibold text-slate-400">
                                No stock purchases or item intakes recorded for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if (count($purchaseProductAnalysis) > 0)
                    <tfoot class="bg-slate-50 border-t border-slate-200 text-xs font-black text-slate-900">
                        <tr>
                            <td colspan="3" class="p-3 text-right uppercase text-[11px] text-slate-400">Total Procured:</td>
                            <td class="p-3 text-center text-indigo-700">{{ number_format(collect($purchaseProductAnalysis)->sum('units_purchased')) }} pcs</td>
                            <td colspan="3"></td>
                            <td class="p-3 text-right text-rose-600">RM {{ number_format(collect($purchaseProductAnalysis)->sum('total_spent'), 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </section>

</div>

{{-- ============================================================
     FINANCIAL AUDIT REPORT MODAL (PREVIEW, PRINT & DOWNLOAD)
     ============================================================ --}}
<dialog id="financial-report-modal" class="m-auto max-h-[92vh] w-[calc(100%-2rem)] max-w-5xl overflow-hidden rounded-2xl bg-white p-0 shadow-2xl backdrop:bg-slate-950/40 text-left text-slate-900">
    <div class="relative flex max-h-[92vh] flex-col">
        {{-- Modal Header --}}
        <div class="relative border-b border-slate-100 px-6 py-4 pr-16 space-y-3.5">
            {{-- Title Section --}}
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-lg font-black text-slate-950">Financial & Cash Flow Audit Report</h2>
                    <p class="text-xs font-semibold text-slate-400">
                        Official revenue report, stock intake expenses, and transaction audit statement for {{ $start->format('d M Y') }} &mdash; {{ $end->format('d M Y') }}.
                    </p>
                </div>
            </div>

            {{-- Action Buttons (Bottom of Title Section) --}}
            <div class="flex flex-wrap items-center gap-2 pt-0.5">
                {{-- Toggle Include Audit Ledger --}}
                <label class="inline-flex items-center gap-2 cursor-pointer rounded-xl bg-slate-50 px-3 py-2 text-xs font-extrabold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100 transition">
                    <input type="checkbox" id="toggle-fin-audit" checked onchange="toggleFinAuditSection(this.checked)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Include Detailed Transaction Audit Ledger</span>
                </label>

                {{-- Download PDF Report Button --}}
                <a id="btn-download-fin-pdf" href="{{ route('financial-report.pdf', ['days' => $days, 'with_audit' => 1]) }}"
                   class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 text-xs font-extrabold text-white hover:bg-rose-700 transition shadow-xs">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Download PDF
                </a>

                {{-- Print Button (Streams exact PDF layout in browser window) --}}
                <button type="button" onclick="printFinancialPdfReport()" class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-slate-100 px-3.5 text-xs font-extrabold text-slate-700 hover:bg-slate-200 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </button>
            </div>

            {{-- Close Button (Top right corner like usual) --}}
            <form method="dialog" class="absolute top-4 right-5 m-0">
                <button class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition font-extrabold text-lg" title="Close report dialog">&times;</button>
            </form>
        </div>

        {{-- Modal Body --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            {{-- Metrics Overview (4 Cards) --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Total Income</p>
                    <p class="mt-1 text-xl font-black text-emerald-600">RM {{ number_format($totalIncome, 2) }}</p>
                    <p class="text-[10px] text-slate-400 font-semibold">{{ $totalSalesTransactions }} sales</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Total Expenses</p>
                    <p class="mt-1 text-xl font-black text-rose-600">RM {{ number_format($totalExpenses, 2) }}</p>
                    <p class="text-[10px] text-slate-400 font-semibold">{{ $totalPurchasesCount }} purchase orders</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Net Profit</p>
                    <p class="mt-1 text-xl font-black {{ $netProfit >= 0 ? 'text-indigo-600' : 'text-rose-600' }}">
                        RM {{ number_format($netProfit, 2) }}
                    </p>
                    <p class="text-[10px] text-slate-400 font-semibold">{{ $netProfit >= 0 ? 'Surplus' : 'Deficit' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Profit Margin</p>
                    <p class="mt-1 text-xl font-black text-slate-900">{{ number_format($netProfitMargin, 1) }}%</p>
                    <p class="text-[10px] text-slate-400 font-semibold">Net operational yield</p>
                </div>
            </div>

            {{-- Category Breakdown Summary Table --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Category Sales & Profit Audit</h3>
                        <p class="text-xs text-slate-400">Audited revenue, product cost expenses, and net profit by item category.</p>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-56 overflow-y-auto rounded-xl border border-slate-100">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-slate-50 text-[11px] font-extrabold uppercase text-slate-400 border-b border-slate-200">
                            <tr>
                                <th class="p-3">Category Name</th>
                                <th class="p-3 text-center">Units Sold</th>
                                <th class="p-3 text-right">Revenue (RM)</th>
                                <th class="p-3 text-right">Expenses (Sold Units)</th>
                                <th class="p-3 text-right">Net Profit (RM)</th>
                                <th class="p-3 text-center">Profit Margin</th>
                                <th class="p-3 text-right">Share (%)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            @forelse ($categorySales as $cat)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3 font-extrabold text-slate-900">{{ $cat->name }}</td>
                                    <td class="p-3 text-center font-bold text-slate-800">{{ number_format($cat->units) }} pcs</td>
                                    <td class="p-3 text-right font-black text-slate-900">RM {{ number_format($cat->revenue, 2) }}</td>
                                    <td class="p-3 text-right font-extrabold text-rose-600">RM {{ number_format($cat->expenses, 2) }}</td>
                                    <td class="p-3 text-right font-black {{ $cat->profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        RM {{ number_format($cat->profit, 2) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-black {{ $cat->margin >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                            {{ $cat->margin > 0 ? '+' : '' }}{{ number_format($cat->margin, 1) }}%
                                        </span>
                                    </td>
                                    <td class="p-3 text-right font-semibold text-slate-500">{{ number_format($cat->percent, 1) }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-6 text-center text-xs font-semibold text-slate-400">No category sales recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Top Selling Products Profit Audit Table --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Top Selling Products Profit Audit</h3>
                        <p class="text-xs text-slate-400">Item-level sales revenue, unit cost expenses, and net profit audit for top products.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                        {{ count($topProducts) }} top items
                    </span>
                </div>

                <div class="overflow-x-auto max-h-56 overflow-y-auto rounded-xl border border-slate-100">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-slate-50 text-[11px] font-extrabold uppercase text-slate-400 border-b border-slate-200">
                            <tr>
                                <th class="p-3 text-center" style="width: 5%;">Rank</th>
                                <th class="p-3">Product Name & SKU</th>
                                <th class="p-3">Category</th>
                                <th class="p-3 text-center">Units Sold</th>
                                <th class="p-3 text-right">Avg Price (RM)</th>
                                <th class="p-3 text-right">Expenses (Sold Units)</th>
                                <th class="p-3 text-right">Revenue (RM)</th>
                                <th class="p-3 text-right">Net Profit (RM)</th>
                                <th class="p-3 text-center">Margin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            @forelse ($topProducts as $idx => $prod)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3 text-center font-black text-slate-500">#{{ $idx + 1 }}</td>
                                    <td class="p-3">
                                        <p class="font-extrabold text-slate-900">{{ $prod->name }}</p>
                                        @if ($prod->sku)
                                            <p class="font-mono text-[10px] text-slate-400">{{ $prod->sku }}</p>
                                        @endif
                                    </td>
                                    <td class="p-3 text-slate-600 font-semibold">{{ $prod->category }}</td>
                                    <td class="p-3 text-center font-black text-slate-900">{{ number_format($prod->units) }} pcs</td>
                                    <td class="p-3 text-right font-medium">RM {{ number_format($prod->avg_price, 2) }}</td>
                                    <td class="p-3 text-right font-extrabold text-rose-600">RM {{ number_format($prod->expenses, 2) }}</td>
                                    <td class="p-3 text-right font-black text-slate-900">RM {{ number_format($prod->revenue, 2) }}</td>
                                    <td class="p-3 text-right font-black {{ $prod->profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        RM {{ number_format($prod->profit, 2) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-black {{ $prod->margin >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                            {{ $prod->margin > 0 ? '+' : '' }}{{ number_format($prod->margin, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-6 text-center text-xs font-semibold text-slate-400">No product sales recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Detailed Transaction Audit Ledger --}}
            <div id="fin-audit-section" class="fin-audit-block rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Financial Transaction Audit Ledger</h3>
                        <p class="text-xs text-slate-400">Traceable register of cash inflows, online payments, and supplier purchase expenses.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                        {{ count($auditTransactions) }} recent records
                    </span>
                </div>

                <div class="max-h-64 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-100">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-slate-50 text-[11px] font-extrabold uppercase text-slate-400 border-b border-slate-200">
                            <tr>
                                <th class="p-3">Date & Time</th>
                                <th class="p-3">Order ID</th>
                                <th class="p-3">Source / Supplier</th>
                                <th class="p-3 text-center">Type</th>
                                <th class="p-3 text-right">Amount (RM)</th>
                                <th class="p-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            @forelse ($auditTransactions as $t)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3 text-slate-500 whitespace-nowrap">{{ $t->date_str }}</td>
                                    <td class="p-3">
                                        <span class="font-mono font-bold text-indigo-700 bg-indigo-50/70 px-2 py-0.5 rounded text-xs">{{ $t->order_id }}</span>
                                    </td>
                                    <td class="p-3 font-semibold text-slate-800">{{ $t->source }}</td>
                                    <td class="p-3 text-center">
                                        @if ($t->type === 'Income')
                                            <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase text-emerald-700">+ Income</span>
                                        @else
                                            <span class="inline-flex rounded-md bg-rose-50 px-2 py-0.5 text-[10px] font-black uppercase text-rose-700">- Expense</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right font-black {{ $t->type === 'Income' ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $t->type === 'Income' ? '+' : '-' }}RM {{ number_format($t->amount, 2) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-600">{{ $t->status }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-xs font-semibold text-slate-400">No transactions recorded for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js"></script>
<script>
(function() {
    const palette = ['#6366f1', '#10b981', '#f59e0b', '#3b82f6', '#ef4444', '#a855f7', '#ec4899', '#06b6d4'];
    document.querySelectorAll('.category-key').forEach((key, index) => {
        key.style.backgroundColor = palette[index % palette.length];
    });

    // 1. Line / Area Chart: Income vs Expenses
    const trendLabels   = @json($salesTrend->pluck('label'));
    const trendRevenue  = @json($salesTrend->pluck('revenue'));
    const trendExpenses = @json($salesTrend->pluck('expenses'));

    const revCanvas = document.getElementById('revenueChart');
    if (revCanvas) {
        const ctx = revCanvas.getContext('2d');

        // Create linear gradient fills for smooth modern depth
        const incomeGradient = ctx.createLinearGradient(0, 0, 0, 260);
        incomeGradient.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
        incomeGradient.addColorStop(1, 'rgba(16, 185, 129, 0.01)');

        const expenseGradient = ctx.createLinearGradient(0, 0, 0, 260);
        expenseGradient.addColorStop(0, 'rgba(244, 63, 94, 0.20)');
        expenseGradient.addColorStop(1, 'rgba(244, 63, 94, 0.01)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [
                    {
                        label: 'Total Income',
                        data: trendRevenue,
                        borderColor: '#10b981',
                        borderWidth: 2.5,
                        backgroundColor: incomeGradient,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 2,
                        pointRadius: trendLabels.length > 31 ? 0 : 3.5,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#10b981',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 2
                    },
                    {
                        label: 'Total Expenses',
                        data: trendExpenses,
                        borderColor: '#f43f5e',
                        borderWidth: 2.5,
                        backgroundColor: expenseGradient,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#f43f5e',
                        pointBorderWidth: 2,
                        pointRadius: trendLabels.length > 31 ? 0 : 3.5,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#f43f5e',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.94)',
                        titleColor: '#ffffff',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyColor: '#e2e8f0',
                        bodyFont: { size: 11, weight: '600' },
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: true,
                        boxPadding: 4,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) {
                                    label += 'RM ' + context.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxTicksLimit: 12,
                            font: { size: 10, weight: 'bold' },
                            color: '#94a3b8'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f8fafc' },
                        ticks: {
                            callback: function(val) { return 'RM ' + val.toLocaleString(); },
                            font: { size: 10, weight: '600' },
                            color: '#94a3b8',
                            padding: 8
                        }
                    }
                }
            }
        });
    }

    // 2. Category Doughnut Chart
    const catLabels  = @json($categorySales->pluck('name'));
    const catRevenue = @json($categorySales->pluck('revenue'));

    const catCtx = document.getElementById('categoryChart');
    if (catCtx) {
        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catRevenue,
                    backgroundColor: palette,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const val = context.parsed;
                                return ' ' + context.label + ': RM ' + val.toLocaleString('en-US', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Helper Functions for Report Modal
    window.toggleFinAuditSection = function(show) {
        document.querySelectorAll('.fin-audit-block').forEach(el => {
            el.style.display = show ? '' : 'none';
        });
        updateFinReportUrls();
    };

    function updateFinReportUrls() {
        const withAudit = document.getElementById('toggle-fin-audit')?.checked ? 1 : 0;
        const days = {{ $days }};
        const url = "{{ route('financial-report.pdf') }}?days=" + days + "&with_audit=" + withAudit;
        const btn = document.getElementById('btn-download-fin-pdf');
        if (btn) btn.href = url;
    }

    window.printFinancialPdfReport = function() {
        const withAudit = document.getElementById('toggle-fin-audit')?.checked ? 1 : 0;
        const days = {{ $days }};
        const url = "{{ route('financial-report.pdf') }}?days=" + days + "&with_audit=" + withAudit + "&stream=1";
        window.open(url, '_blank');
    };
})();
</script>
@endpush
