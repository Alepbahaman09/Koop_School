@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $maxSale = max($salesBars ?: [0]);
    $effectiveMax = $maxSale > 0 ? $maxSale : 100;
    $chartAmounts = [
        $effectiveMax,
        $effectiveMax * 0.75,
        $effectiveMax * 0.50,
        $effectiveMax * 0.25,
        0,
    ];
    $orderTone = [
        'Processing' => 'bg-sky-50 text-sky-700',
        'Ready' => 'bg-violet-50 text-violet-700',
        'Completed' => 'bg-emerald-50 text-emerald-700',
        'Cancelled' => 'bg-rose-50 text-rose-700',
    ];
@endphp

{{-- Header Section --}}
<section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900">Admin Dashboard</h1>
        </div>
        <p class="text-sm font-bold text-slate-400">Updated {{ now()->format('d M Y, h:i A') }}</p>
    </div>
</section>

{{-- Key Metrics Cards: Total Sale, Total Order, Total Product --}}
<section class="grid gap-4 sm:grid-cols-3">
    @foreach ($stats as $stat)
        <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <div class="mb-4 flex items-center justify-between">
                <span class="text-sm font-bold text-slate-400">{{ $stat['label'] }}</span>
                <span class="grid h-9 w-9 place-items-center rounded-lg {{ $stat['accent'] }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="{{ $stat['icon'] }}" />
                    </svg>
                </span>
            </div>
            <p class="text-2xl font-extrabold text-slate-900">{{ $stat['value'] }}</p>
            <p class="mt-1 text-xs font-extrabold {{ $stat['change_color'] }}">
                {{ $stat['change'] }}
            </p>
        </article>
    @endforeach
</section>

{{-- Main Analytics: Sales Income Chart & Attention Required --}}
<section class="grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
    {{-- Sales Income Chart --}}
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <p class="text-sm font-bold text-slate-400">Sales Income</p>
                    @if ($viewMode === 'month')
                        <span class="rounded bg-indigo-50 px-2 py-0.5 text-[11px] font-extrabold text-indigo-700">By Month</span>
                    @elseif ($viewMode === 'daily')
                        <span class="rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-extrabold text-emerald-700">Daily View</span>
                    @else
                        <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-extrabold text-slate-700">Custom Range</span>
                    @endif
                </div>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">RM {{ number_format($salesIncome, 2) }}</p>
                <p class="mt-1 text-xs font-bold text-slate-400">{{ $salesPeriodLabel }}</p>
            </div>

            <div class="flex flex-col items-start sm:items-end gap-2.5">
                {{-- View Mode Toggle --}}
                <div class="inline-flex rounded-lg bg-slate-100 p-1">
                    <a href="{{ route('dashboard', ['view' => 'month', 'year' => $selectedYear]) }}" 
                       class="rounded-md px-3 py-1 text-xs font-extrabold transition-all {{ $viewMode === 'month' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        By Month
                    </a>
                    <a href="{{ route('dashboard', ['view' => 'daily', 'month' => $selectedMonth]) }}" 
                       class="rounded-md px-3 py-1 text-xs font-extrabold transition-all {{ $viewMode === 'daily' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        This Month (Daily)
                    </a>
                </div>

                {{-- Filter by Year or Month --}}
                @if ($viewMode === 'month')
                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-1.5">
                        <input type="hidden" name="view" value="month">
                        <span class="text-xs font-bold text-slate-400">Year:</span>
                        <select name="year" onchange="this.form.submit()" class="h-8 rounded-lg border-slate-200 bg-white px-2.5 py-1 text-xs font-extrabold text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($availableYears as $yr)
                                <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </form>
                @elseif ($viewMode === 'daily')
                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-1.5">
                        <input type="hidden" name="view" value="daily">
                        <span class="text-xs font-bold text-slate-400">Month:</span>
                        <input type="month" name="month" value="{{ $selectedMonth }}" onchange="this.form.submit()" class="h-8 rounded-lg border-slate-200 bg-white px-2 py-1 text-xs font-extrabold text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </form>
                @else
                    <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-1.5">
                        <input type="hidden" name="view" value="custom">
                        <input type="date" name="sales_start" value="{{ $salesStart->toDateString() }}" class="h-8 rounded-lg border-slate-200 px-2 text-xs font-bold text-slate-600">
                        <span class="text-xs text-slate-400">to</span>
                        <input type="date" name="sales_end" value="{{ $salesEnd->toDateString() }}" class="h-8 rounded-lg border-slate-200 px-2 text-xs font-bold text-slate-600">
                        <button type="submit" class="h-8 rounded-lg bg-indigo-600 px-2.5 text-xs font-extrabold text-white hover:bg-indigo-700">Apply</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid h-64 grid-cols-[100px_minmax(0,1fr)] gap-3">
            <div class="flex flex-col justify-between pb-8 text-right text-xs font-bold text-slate-400">
                @foreach ($chartAmounts as $chartAmount)
                    <span>RM {{ number_format($chartAmount, 2) }}</span>
                @endforeach
            </div>
            <div class="relative min-w-0 overflow-x-auto">
                <div class="absolute inset-x-0 top-0 h-px bg-slate-100"></div>
                <div class="absolute inset-x-0 top-1/4 h-px bg-slate-100"></div>
                <div class="absolute inset-x-0 top-1/2 h-px bg-slate-100"></div>
                <div class="absolute inset-x-0 top-3/4 h-px bg-slate-100"></div>
                <div class="relative flex h-full min-w-full items-end gap-2 sm:gap-3 pb-8">
                    @foreach ($salesBars as $index => $amount)
                        @php($height = $maxSale > 0 ? ($amount / $maxSale) * 100 : 0)
                        <div class="flex h-full min-w-8 flex-1 flex-col items-center gap-2">
                            <div class="flex min-h-0 w-full flex-1 items-end justify-center">
                                @if ($amount > 0)
                                    <div class="w-full max-w-10 rounded-t-md bg-indigo-600 transition-all hover:bg-indigo-700 cursor-pointer" 
                                         style="height: {{ max($height, 4) }}%" 
                                         title="{{ $salesLabels[$index] }}: RM {{ number_format($amount, 2) }}"></div>
                                @else
                                    <div class="w-full max-w-6 h-1 rounded-full bg-slate-200" title="{{ $salesLabels[$index] }}: RM 0.00"></div>
                                @endif
                            </div>
                            <span class="text-[11px] font-bold text-slate-400">{{ $salesLabels[$index] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </article>

    {{-- Attention Required Section (Replaced Database Snapshot) --}}
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-50 text-amber-600">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            <line x1="12" y1="9" x2="12" y2="13" />
                            <line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>
                    </span>
                    <h2 class="text-lg font-extrabold text-slate-900">Attention Required</h2>
                </div>
                <span class="text-xs font-bold text-slate-400">Live Status</span>
            </div>
            <p class="mt-1 text-xs font-semibold text-slate-400">Items requiring immediate action or monitoring</p>

            <div class="mt-4 space-y-2.5">
                @foreach ($attentionItems as $item)
                    <a href="{{ $item['url'] }}" class="flex items-center justify-between rounded-lg bg-slate-50 p-3 hover:bg-slate-100 transition-colors">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full {{ $item['dot_class'] }}"></span>
                            <span class="text-sm font-bold text-slate-700">{{ $item['label'] }}</span>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-extrabold {{ $item['badge_class'] }}">
                            {{ $item['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <p class="mt-4 text-center text-xs font-semibold text-slate-400">
            Click any item to view details
        </p>
    </article>
</section>

{{-- Latest Orders Section --}}
<section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-extrabold text-slate-900">Latest Orders</h2>
        <a href="{{ route('orders.index') }}" class="text-sm font-extrabold text-indigo-600 hover:text-indigo-700">Manage all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs font-extrabold uppercase text-slate-400">
                    <th class="py-3 pr-4">Order</th>
                    <th class="py-3 pr-4">Customer</th>
                    <th class="py-3 pr-4">Items</th>
                    <th class="py-3 pr-4">Status</th>
                    <th class="py-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <tr>
                        <td class="py-4 pr-4 font-extrabold text-slate-900">{{ $order['id'] }}</td>
                        <td class="py-4 pr-4 font-semibold text-slate-600">{{ $order['customer'] }}</td>
                        <td class="py-4 pr-4 text-slate-500">{{ $order['item'] }}</td>
                        <td class="py-4 pr-4">
                            <span class="rounded-full px-3 py-1 text-xs font-extrabold {{ $orderTone[$order['status']] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $order['status'] }}
                            </span>
                        </td>
                        <td class="py-4 text-right font-extrabold text-slate-900">{{ $order['amount'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center font-semibold text-slate-400">No orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
