@extends('layouts.app')

@section('title', 'Finance')
@section('page-title', 'Finance')

@section('content')
@include('partials.admin-alerts')

@php
    $metricStyles = [
        'indigo' => 'bg-indigo-600 text-white shadow-indigo-100',
        'rose' => 'bg-white text-rose-600 ring-slate-100',
        'emerald' => 'bg-white text-emerald-600 ring-slate-100',
    ];
    $maxTrend = max(1, $trend->max(fn ($item) => max($item['income'], $item['expenses'])));
    $maxExpense = max(1, (float) $expenseBreakdown->max('amount'));
@endphp

<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold tracking-normal">Finance</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">{{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}</p>
    </div>
    <form method="GET" class="flex flex-wrap gap-2">
        <input type="month" name="month" value="{{ $month }}" class="h-10 rounded-lg border-slate-200 bg-white px-3 text-sm font-bold text-slate-600">
        <button class="h-10 rounded-lg bg-slate-900 px-4 text-sm font-extrabold text-white">Apply</button>
        <a href="{{ route('finance.export', ['month' => $month]) }}" class="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm shadow-indigo-100">Export CSV</a>
    </form>
</section>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ($metrics as $metric)
        @php
            $change = $metric['previous'] > 0 ? (($metric['value'] - $metric['previous']) / abs($metric['previous'])) * 100 : ($metric['value'] > 0 ? 100 : 0);
        @endphp
        <article class="rounded-lg p-6 shadow-sm {{ $metricStyles[$metric['tone']] }}">
            <p class="text-sm font-bold {{ $metric['tone'] === 'indigo' ? 'text-white/90' : 'text-slate-400' }}">{{ $metric['label'] }}</p>
            <p class="mt-2 text-3xl font-extrabold tracking-tight">RM {{ number_format($metric['value'], 2) }}</p>
            <p class="mt-1 text-xs font-extrabold {{ $metric['tone'] === 'indigo' ? 'text-white/80' : ($change >= 0 ? 'text-emerald-500' : 'text-rose-500') }}">{{ $change >= 0 ? '+' : '' }}{{ number_format($change, 1) }}% from previous month</p>
        </article>
    @endforeach
</section>

<section class="grid gap-5 xl:grid-cols-[1.3fr_1fr]">
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h2 class="mb-5 text-lg font-extrabold">Income vs Expenses</h2>
        <div class="mb-3 flex justify-center gap-6 text-xs font-extrabold">
            <span class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-indigo-600"></span> Income</span>
            <span class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-rose-400"></span> Expenses</span>
        </div>
        <div class="flex h-72 items-end gap-3 border-b border-slate-100 px-1 pb-7">
            @foreach ($trend as $item)
                <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                    <div class="flex h-56 w-full items-end gap-1">
                        <div class="flex-1 rounded-t bg-indigo-600" style="height: {{ max(4, ($item['income'] / $maxTrend) * 224) }}px"></div>
                        <div class="flex-1 rounded-t bg-rose-400" style="height: {{ max(4, ($item['expenses'] / $maxTrend) * 224) }}px"></div>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400">{{ $item['label'] }}</span>
                </div>
            @endforeach
        </div>
    </article>

    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h2 class="mb-5 text-lg font-extrabold">Expense Breakdown</h2>
        <div class="space-y-4">
            @forelse ($expenseBreakdown as $expense)
                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <span class="truncate text-sm font-bold text-slate-700">{{ $expense->label }}</span>
                        <span class="text-sm font-extrabold text-slate-900">RM {{ number_format($expense->amount, 2) }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-indigo-600" style="width: {{ max(3, ((float) $expense->amount / $maxExpense) * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="rounded-lg bg-slate-50 p-5 text-sm font-semibold text-slate-400">No received purchase orders for this month.</p>
            @endforelse
        </div>
    </article>
</section>

@endsection
