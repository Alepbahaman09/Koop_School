@extends('layouts.app')

@section('title', 'Stock Purchases')
@section('page-title', 'Stock Purchases')

@section('content')
@include('partials.admin-alerts')

{{-- Page Header --}}
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold text-slate-950">Stock Purchases</h1>
        <p class="mt-1 text-xs font-semibold text-slate-400">Record cooperative inventory purchases and update stocks in one step.</p>
    </div>
    <div>
        <a href="{{ route('stock-purchases.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700">
            <span class="text-lg">+</span> New Stock Purchase
        </a>
    </div>
</section>

{{-- Search & Filter --}}
<section class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
    <h2 class="mb-3 text-sm font-extrabold text-slate-900">Search Purchases</h2>
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input name="search" value="{{ request('search') }}"
            placeholder="Search by supplier name"
            class="h-9 w-64 flex-none rounded-lg border-slate-200 bg-white text-xs font-semibold focus:border-indigo-400 focus:ring-indigo-200">
        <button class="h-9 flex-none rounded-lg bg-indigo-600 px-4 text-xs font-extrabold text-white hover:bg-indigo-700">Search</button>
        @if (request()->anyFilled(['search']))
            <a href="{{ route('stock-purchases.index') }}"
                class="h-9 flex-none rounded-lg bg-slate-100 px-4 text-xs font-extrabold text-slate-600 hover:bg-slate-200 leading-9">
                Clear
            </a>
        @endif
    </form>
</section>

{{-- Stock Purchases Table --}}
<section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs font-bold uppercase text-slate-400">
                    <th class="px-5 py-4">Purchase No.</th>
                    <th class="px-5 py-4">Supplier</th>
                    <th class="px-5 py-4">Purchase Date</th>
                    <th class="px-5 py-4">Total Amount</th>
                    <th class="px-5 py-4">Status</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($stockPurchases as $purchase)
                    <tr class="group hover:bg-slate-50/60 transition">
                        <td class="px-5 py-4 font-bold text-indigo-600">
                            #{{ str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-5 py-4 font-extrabold text-slate-900">
                            {{ $purchase->supplier->company_name }}
                        </td>
                        <td class="px-5 py-4 text-slate-600 font-semibold">
                            {{ $purchase->purchase_date->format('d M Y') }}
                        </td>
                        <td class="px-5 py-4 font-bold text-slate-800">
                            RM {{ number_format($purchase->total_amount, 2) }}
                        </td>
                        <td class="px-5 py-4">
                            @if ($purchase->status === 'received')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600 ring-1 ring-emerald-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Received
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-600 ring-1 ring-amber-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span> Pending
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex justify-end items-center gap-2">
                                @if ($purchase->status === 'pending')
                                    <form method="POST" action="{{ route('stock-purchases.receive', $purchase) }}">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex h-8 items-center gap-1 rounded-lg bg-emerald-600 px-3 text-xs font-extrabold text-white hover:bg-emerald-700 transition"
                                            onclick="return confirm('Mark this purchase as received and update stock?')">
                                            Mark as Received
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('stock-purchases.show', $purchase) }}"
                                    class="inline-flex h-8 items-center gap-1 rounded-lg bg-indigo-50 px-3 text-xs font-extrabold text-indigo-600 hover:bg-indigo-100 transition">
                                    View
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-20 text-center text-sm font-semibold text-slate-400">
                            No stock purchases recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($stockPurchases->hasPages())
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $stockPurchases->links() }}
        </div>
    @endif
</section>
@endsection
