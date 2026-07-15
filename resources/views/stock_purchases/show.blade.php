@extends('layouts.app')

@section('title', 'Stock Purchase #' . str_pad($stockPurchase->id, 5, '0', STR_PAD_LEFT))
@section('page-title', 'Stock Purchase #' . str_pad($stockPurchase->id, 5, '0', STR_PAD_LEFT))

@section('content')
@include('partials.admin-alerts')
<section class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('stock-purchases.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-700">&larr; Back to List</a>
            <h1 class="text-xl font-extrabold text-slate-950 mt-1">Stock Purchase: #{{ str_pad($stockPurchase->id, 5, '0', STR_PAD_LEFT) }}</h1>
        </div>
        <div class="flex items-center gap-2">
            @if ($stockPurchase->status === 'received')
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600 ring-1 ring-emerald-100">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Received
                </span>
            @else
                <form method="POST" action="{{ route('stock-purchases.receive', $stockPurchase) }}" class="inline-block">
                    @csrf
                    <button type="submit" class="inline-flex h-8 items-center justify-center rounded-lg bg-indigo-600 px-3 text-xs font-extrabold text-white hover:bg-indigo-700 transition">
                        Mark as Received
                    </button>
                </form>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-600 ring-1 ring-amber-100">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span> Pending
                </span>
            @endif
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid gap-4 sm:grid-cols-2">
        {{-- Supplier details --}}
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 space-y-3">
            <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2">Supplier Details</h2>
            <div>
                <p class="text-xs font-bold text-slate-400">Company Name</p>
                <p class="text-sm font-extrabold text-slate-800">{{ $stockPurchase->supplier->company_name }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400">Contact Person</p>
                <p class="text-sm font-semibold text-slate-700">{{ $stockPurchase->supplier->contact_person ?: '—' }}</p>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <p class="text-xs font-bold text-slate-400">Email</p>
                    <p class="text-xs font-semibold text-slate-700 truncate">{{ $stockPurchase->supplier->email ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400">Phone</p>
                    <p class="text-xs font-semibold text-slate-700">{{ $stockPurchase->supplier->phone ?: '—' }}</p>
                </div>
            </div>
        </article>

        {{-- Purchase Meta --}}
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 space-y-3">
            <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2">Purchase Metadata</h2>
            <div>
                <p class="text-xs font-bold text-slate-400">Purchase Date</p>
                <p class="text-sm font-semibold text-slate-800">{{ $stockPurchase->purchase_date->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400">Recorded By</p>
                <p class="text-sm font-semibold text-slate-700">{{ $stockPurchase->creator->name ?? 'System' }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400">Purchase Notes</p>
                <p class="text-xs font-semibold text-slate-600 bg-slate-50 p-2 rounded-lg mt-1 border border-slate-100">
                    {{ $stockPurchase->notes ?: 'No purchase notes recorded.' }}
                </p>
            </div>
        </article>
    </div>

    {{-- Items List --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2 mb-4">Purchased Items</h2>
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="text-xs font-bold uppercase text-slate-400 border-b border-slate-100">
                    <th class="py-2 pr-4">Product Name</th>
                    <th class="py-2 pr-4">SKU / Barcode</th>
                    <th class="py-2 pr-4 text-center">Quantity</th>
                    <th class="py-2 pr-4 text-right">Purchase Price</th>
                    <th class="py-2 pr-4 text-right">Selling Price/Unit</th>
                    <th class="py-2 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($stockPurchase->items as $item)
                    <tr class="text-slate-700">
                        <td class="py-3 pr-4 font-bold text-slate-900">
                            {{ $item->product->name }}
                        </td>
                        <td class="py-3 pr-4 font-semibold text-slate-500">
                            {{ $item->product->sku }}
                        </td>
                        <td class="py-3 pr-4 text-center font-bold text-slate-800">
                            +{{ $item->quantity }}
                        </td>
                        <td class="py-3 pr-4 text-right font-semibold">
                            RM {{ number_format($item->purchase_price, 2) }}
                        </td>
                        <td class="py-3 pr-4 text-right font-semibold text-emerald-600">
                            RM {{ number_format($item->selling_price, 2) }}
                        </td>
                        <td class="py-3 text-right font-bold text-slate-950">
                            RM {{ number_format($item->subtotal, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="flex justify-end items-center gap-4 mt-6 border-t border-slate-100 pt-4">
            <span class="text-xs font-bold text-slate-500">Total Purchase Value:</span>
            <span class="text-lg font-extrabold text-slate-950">RM {{ number_format($stockPurchase->total_amount, 2) }}</span>
        </div>
    </div>
</section>
@endsection
