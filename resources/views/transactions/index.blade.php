@extends('layouts.app')

@section('title', 'Transactions')
@section('page-title', 'Transactions')

@section('content')
@php
    $statuses = ['Pending', 'Completed', 'Failed', 'Refunded'];
    $methods = ['Cash', 'Card', 'Online Banking', 'E-Wallet', 'Cheque'];
    $statusClass = [
        'Pending' => 'bg-amber-50 text-amber-700',
        'Completed' => 'bg-emerald-50 text-emerald-700',
        'Failed' => 'bg-rose-50 text-rose-700',
        'Refunded' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold">Transactions</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">Review payments without loading the full order ledger.</p>
    </div>
</section>

<section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
    <form method="GET" class="mb-5 grid gap-3 lg:grid-cols-[1fr_13rem_13rem_auto] lg:items-end">
        <label class="text-xs font-extrabold uppercase text-slate-400">Search
            <input name="search" value="{{ request('search') }}" placeholder="Reference or order number" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
        </label>
        <label class="text-xs font-extrabold uppercase text-slate-400">Status
            <select name="status" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs font-extrabold uppercase text-slate-400">Method
            <select name="payment_method" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
                <option value="">All methods</option>
                @foreach ($methods as $method)
                    <option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ $method }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex gap-2">
            <button class="h-10 rounded-lg bg-slate-900 px-5 text-sm font-extrabold text-white">Filter</button>
            <a href="{{ route('transactions.index') }}" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-slate-500 ring-1 ring-slate-200">Clear</a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs font-extrabold uppercase text-slate-400">
                    <th class="py-3 pr-4">Reference</th>
                    <th class="py-3 pr-4">Order</th>
                    <th class="py-3 pr-4">Customer</th>
                    <th class="py-3 pr-4">Method</th>
                    <th class="py-3 pr-4">Amount</th>
                    <th class="py-3 pr-4">Paid At</th>
                    <th class="py-3 text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($payments as $payment)
                    <tr class="align-top">
                        <td class="py-4 pr-4 font-extrabold text-slate-900">{{ $payment->payment_reference }}</td>
                        <td class="py-4 pr-4">
                            <p class="font-bold text-slate-700">{{ $payment->order?->order_number ?? 'Missing order' }}</p>
                            <p class="text-xs text-slate-400">{{ $payment->order?->payment_status }}</p>
                        </td>
                        <td class="py-4 pr-4">
                            <p class="font-semibold text-slate-600">{{ $payment->order?->customer?->parent_name ?? 'Unknown parent' }}</p>
                            <p class="text-xs text-slate-400">{{ $payment->order?->customer?->student_name }}</p>
                        </td>
                        <td class="py-4 pr-4 font-semibold text-slate-600">{{ $payment->payment_method }}</td>
                        <td class="py-4 pr-4 font-extrabold">RM {{ number_format($payment->amount, 2) }}</td>
                        <td class="py-4 pr-4 text-slate-500">{{ $payment->paid_at?->format('d M Y, h:i A') ?? $payment->created_at->format('d M Y, h:i A') }}</td>
                        <td class="py-4 text-right">
                            <span class="rounded-full px-3 py-1 text-xs font-extrabold {{ $statusClass[$payment->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $payment->status }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-12 text-center font-semibold text-slate-400">No transactions match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $payments->links() }}</div>
</section>
@endsection
