@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@include('partials.admin-alerts')

@php
    $maxSale = max($salesBars ?: [0]);
    $chartAmounts = [
        $maxSale,
        $maxSale * 0.75,
        $maxSale * 0.50,
        $maxSale * 0.25,
        0,
    ];
    $orderTone = [
        'Processing' => 'bg-sky-50 text-sky-700',
        'Ready' => 'bg-violet-50 text-violet-700',
        'Completed' => 'bg-emerald-50 text-emerald-700',
        'Cancelled' => 'bg-rose-50 text-rose-700',
    ];
@endphp

<section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-extrabold">Admin Dashboard</h1>
        </div>
        <p class="text-sm font-bold text-slate-400">Updated {{ now()->format('d M Y, h:i A') }}</p>
    </div>
</section>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($stats as $stat)
        <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <div class="mb-4 flex items-center justify-between">
                <span class="text-sm font-bold text-slate-400">{{ $stat['label'] }}</span>
                <span class="grid h-9 w-9 place-items-center rounded-lg {{ $stat['accent'] }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $stat['icon'] }}" /></svg>
                </span>
            </div>
            <p class="text-2xl font-extrabold">{{ $stat['value'] }}</p>
            <p class="mt-1 text-xs font-extrabold {{ str_starts_with($stat['change'], '-') ? 'text-rose-500' : 'text-emerald-500' }}">{{ $stat['change'] }}</p>
        </article>
    @endforeach
</section>

<section class="grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-bold text-slate-400">Sales Income</p>
                <p class="mt-1 text-2xl font-extrabold">RM {{ number_format($salesIncome, 2) }}</p>
                <p class="mt-1 text-xs font-bold text-slate-400">
                    {{ $salesStart->format('d M Y') }} - {{ $salesEnd->format('d M Y') }}
                </p>
            </div>
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-2">
                <label class="text-xs font-bold text-slate-500">
                    <span class="mb-1 block">From</span>
                    <input type="date" name="sales_start" value="{{ $salesStart->toDateString() }}" class="h-9 rounded-lg border-slate-200 px-2 text-xs font-bold text-slate-600">
                </label>
                <label class="text-xs font-bold text-slate-500">
                    <span class="mb-1 block">To</span>
                    <input type="date" name="sales_end" value="{{ $salesEnd->toDateString() }}" class="h-9 rounded-lg border-slate-200 px-2 text-xs font-bold text-slate-600">
                </label>
                <button type="submit" class="h-9 rounded-lg bg-indigo-600 px-3 text-xs font-extrabold text-white">Apply</button>
            </form>
        </div>

        <div class="grid h-64 grid-cols-[100px_minmax(0,1fr)] gap-3">
            <div class="flex flex-col justify-between pb-8 text-right text-xs font-bold text-slate-300">
                @foreach ($chartAmounts as $chartAmount)
                    <span>RM {{ number_format($chartAmount, 2) }}</span>
                @endforeach
            </div>
            <div class="relative min-w-0 overflow-x-auto">
                <div class="absolute inset-x-0 top-0 h-px bg-slate-100"></div>
                <div class="absolute inset-x-0 top-1/4 h-px bg-slate-100"></div>
                <div class="absolute inset-x-0 top-1/2 h-px bg-slate-100"></div>
                <div class="absolute inset-x-0 top-3/4 h-px bg-slate-100"></div>
                <div class="relative flex h-full min-w-full items-end gap-3 pb-8">
                    @foreach ($salesBars as $index => $amount)
                        @php($height = $maxSale > 0 ? ($amount / $maxSale) * 100 : 0)
                        <div class="flex h-full min-w-12 flex-1 flex-col items-center gap-2">
                            <div class="flex min-h-0 w-full flex-1 items-end justify-center">
                                @if ($amount > 0)
                                    <div class="w-full max-w-10 rounded-t-lg bg-indigo-600" @style(["height: {$height}%"]) title="RM {{ number_format($amount, 2) }}"></div>
                                @endif
                            </div>
                            <span class="text-[11px] font-bold text-slate-300">{{ $salesLabels[$index] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </article>

    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h2 class="text-lg font-extrabold">Database Snapshot</h2>
        <dl class="mt-4 grid gap-3 text-sm">
            <div class="flex justify-between rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Active Orders</dt><dd class="font-extrabold">{{ $dashboardData['active_orders'] }}</dd></div>
            <div class="flex justify-between rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Completed Orders</dt><dd class="font-extrabold">{{ $dashboardData['completed_orders'] }}</dd></div>
            <div class="flex justify-between rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Low Stock Products</dt><dd class="font-extrabold">{{ $dashboardData['low_stock_products'] }}</dd></div>
            <div class="flex justify-between rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">This Month Payments</dt><dd class="font-extrabold">RM {{ number_format($dashboardData['recent_payments'], 2) }}</dd></div>
            <div class="flex justify-between rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Inventory Value</dt><dd class="font-extrabold">RM {{ number_format($dashboardData['inventory_value'], 2) }}</dd></div>
        </dl>
    </article>
</section>

<section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-extrabold">Latest Orders</h2>
        <a href="{{ route('orders.index') }}" class="text-sm font-extrabold text-indigo-600">Manage all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead><tr class="border-b border-slate-100 text-xs font-extrabold uppercase text-slate-400">
                <th class="py-3 pr-4">Order</th><th class="py-3 pr-4">Customer</th><th class="py-3 pr-4">Items</th><th class="py-3 pr-4">Status</th><th class="py-3 text-right">Amount</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <tr>
                        <td class="py-4 pr-4 font-extrabold text-slate-900">{{ $order['id'] }}</td>
                        <td class="py-4 pr-4 font-semibold text-slate-600">{{ $order['customer'] }}</td>
                        <td class="py-4 pr-4 text-slate-500">{{ $order['item'] }}</td>
                        <td class="py-4 pr-4"><span class="rounded-full px-3 py-1 text-xs font-extrabold {{ $orderTone[$order['status']] ?? 'bg-slate-100 text-slate-600' }}">{{ $order['status'] }}</span></td>
                        <td class="py-4 text-right font-extrabold">{{ $order['amount'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center font-semibold text-slate-400">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
