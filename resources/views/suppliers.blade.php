@extends('layouts.app')

@section('title', 'Suppliers & Purchases')
@section('page-title', 'Suppliers & Purchases')

@section('content')

{{-- 1. Top Section: Header & Add Supplier & Purchase Together Button --}}
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold text-slate-950">Suppliers & Stock Intake</h1>
        <p class="mt-1 text-xs font-semibold text-slate-400">Manage cooperative vendors, record bulk stock purchases, and view intake history.</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        {{-- Single Combined Supplier Report Button --}}
        <button type="button" onclick="document.getElementById('supplier-report-modal').showModal()"
            class="inline-flex h-10 items-center gap-2 rounded-xl bg-white px-3.5 text-xs font-extrabold text-indigo-700 shadow-xs ring-1 ring-slate-200 hover:bg-slate-50 transition">
            <svg class="h-4 w-4 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Supplier Report
        </button>

        {{-- PROMINENT BUTTON: Add Supplier & Purchase Together --}}
        <button type="button" onclick="openCombinedDrawer()"
            class="inline-flex h-10 items-center gap-2 rounded-xl bg-indigo-600 px-5 text-xs font-extrabold text-white shadow-sm hover:bg-indigo-700 transition">
            <span class="text-base font-black leading-none">+</span> Add Supplier & Purchase
        </button>
    </div>
</section>

{{-- 2. Overview Metric Cards: Total Supplier, Purchase Stock, Total Spent (3 Cards) --}}
<section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    {{-- Total Suppliers Card --}}
    <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Suppliers</span>
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-indigo-50 text-indigo-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </span>
        </div>
        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($supplierStats['total_suppliers'] ?? $suppliers->total()) }}</p>
        <p class="mt-1 text-xs font-semibold text-slate-400">Active registered vendors</p>
    </div>

    {{-- Total Stock Purchases Card --}}
    <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Stock Purchases</span>
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-50 text-emerald-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </span>
        </div>
        <p class="mt-2 text-2xl font-black text-emerald-600">{{ number_format($supplierStats['total_purchases'] ?? 0) }}</p>
        <p class="mt-1 text-xs font-semibold text-emerald-600">Recorded intake orders</p>
    </div>

    {{-- Total Purchase Value Spent --}}
    <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Expenditure</span>
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-violet-50 text-violet-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
        </div>
        <p class="mt-2 text-2xl font-black text-slate-900">RM {{ number_format($supplierStats['total_spent'] ?? 0, 2) }}</p>
        <p class="mt-1 text-xs font-semibold text-slate-400">Cumulative stock purchases</p>
    </div>
</section>

{{-- 3. Search & Filter Suppliers --}}
<section class="rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-100">
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <div class="relative min-w-[260px] flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input name="search" value="{{ request('search') }}" placeholder="Search supplier ID, company name, or contact person..."
                   class="h-10 w-full rounded-xl border-slate-200 bg-slate-50/50 pl-9 pr-3 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <button class="h-10 rounded-xl bg-indigo-600 px-5 text-xs font-extrabold text-white hover:bg-indigo-700 transition">
            Search
        </button>
        @if (request()->filled('search'))
            <a href="{{ route('suppliers.index') }}" class="inline-flex h-10 items-center rounded-xl bg-slate-100 px-4 text-xs font-extrabold text-slate-600 hover:bg-slate-200">
                Reset
            </a>
        @endif
    </form>
</section>

    {{-- Body Section Display: Suppliers Table --}}
    <section class="overflow-hidden rounded-2xl bg-white shadow-xs ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] font-extrabold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3.5">Supplier ID</th>
                        <th class="px-5 py-3.5">Company Name</th>
                        <th class="px-5 py-3.5">Contact Detail</th>
                        <th class="px-5 py-3.5">Date Added</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($suppliers as $supplier)
                        <tr class="hover:bg-slate-50/60 transition">
                            {{-- ID Supplier --}}
                            <td class="px-5 py-4 font-mono font-black text-indigo-600">
                                {{ $supplier->supplier_id }}
                            </td>

                            {{-- Name Company --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-indigo-50 text-xs font-black text-indigo-700 ring-1 ring-indigo-100">
                                        {{ strtoupper(substr($supplier->company_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-slate-900 text-sm">{{ $supplier->company_name }}</p>
                                        <p class="text-[11px] font-semibold text-slate-400">
                                            {{ $supplier->stockPurchases->count() }} purchase(s) on record
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- Contact Details --}}
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-800">{{ $supplier->contact_person ?: '—' }}</p>
                                <div class="flex items-center gap-2 mt-0.5 text-slate-500 font-medium">
                                    @if ($supplier->phone)
                                        <span>📞 {{ $supplier->phone }}</span>
                                    @endif
                                    @if ($supplier->email)
                                        <span>✉️ {{ $supplier->email }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Dated --}}
                            <td class="px-5 py-4 text-slate-500 font-semibold">
                                {{ $supplier->created_at->format('d M Y') }}
                            </td>

                            {{-- Action Column --}}
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- 1. View Supplier & Purchase Detail Pop-up Modal --}}
                                    <button type="button" onclick="document.getElementById('supplier-detail-{{ $supplier->id }}').showModal()"
                                        class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-indigo-50 px-3 text-xs font-extrabold text-indigo-700 hover:bg-indigo-100 transition"
                                        title="View supplier & purchase details">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Details
                                    </button>

                                     {{-- 2. Edit Supplier Details --}}
                                    <button type="button"
                                        onclick='openEditSupplierDrawer(@json($supplier))'
                                        class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-amber-50 px-3 text-xs font-extrabold text-amber-700 hover:bg-amber-100 transition"
                                        title="Edit supplier details">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m4 20 4-1 11-11-3-3L5 16l-1 4ZM14 7l3 3" />
                                        </svg>
                                        Edit
                                    </button>
                                </div>

                                {{-- Supplier Details & Purchase Items Pop-up Modal --}}
                                <dialog id="supplier-detail-{{ $supplier->id }}" class="m-auto max-h-[92vh] w-[calc(100%-2rem)] max-w-4xl overflow-hidden rounded-2xl bg-white p-0 shadow-2xl backdrop:bg-slate-950/40 text-left">
                                    <div class="flex max-h-[92vh] flex-col">
                                        {{-- Header --}}
                                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-50 text-indigo-700 font-black">
                                                    {{ strtoupper(substr($supplier->company_name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <h3 class="text-base font-black text-slate-900">{{ $supplier->company_name }}</h3>
                                                    <p class="text-xs text-slate-400">Supplier ID: <span class="font-mono font-bold text-indigo-600">{{ $supplier->supplier_id }}</span> &bull; Added {{ $supplier->created_at->format('d M Y') }}</p>
                                                </div>
                                            </div>
                                            <form method="dialog">
                                                <button class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition font-extrabold">&times;</button>
                                            </form>
                                        </div>

                                        {{-- Body --}}
                                        <div class="flex-1 overflow-y-auto p-6 space-y-6">
                                            {{-- Supplier Profile Card --}}
                                            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                                                <h4 class="text-xs font-extrabold uppercase tracking-wide text-slate-400 mb-3">Vendor Contact Information</h4>
                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                                                    <div>
                                                        <span class="text-slate-400 font-bold block">Contact Person</span>
                                                        <span class="font-extrabold text-slate-800">{{ $supplier->contact_person ?: '—' }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-400 font-bold block">Phone</span>
                                                        <span class="font-extrabold text-slate-800">{{ $supplier->phone ?: '—' }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-400 font-bold block">Email</span>
                                                        <span class="font-extrabold text-slate-800">{{ $supplier->email ?: '—' }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-400 font-bold block">Total Purchases</span>
                                                        <span class="font-black text-indigo-600">{{ $supplier->stockPurchases->count() }} Orders</span>
                                                    </div>
                                                    @if ($supplier->address)
                                                        <div class="col-span-2 sm:col-span-4 border-t border-slate-200/60 pt-2">
                                                            <span class="text-slate-400 font-bold block">Address</span>
                                                            <span class="font-medium text-slate-700">{{ $supplier->address }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Supplier Purchase History & Item Details --}}
                                            <div>
                                                <div class="flex flex-wrap items-center justify-between gap-3 mb-4 rounded-xl bg-indigo-50/70 p-4 ring-1 ring-indigo-100">
                                                    <div>
                                                        <h4 class="text-sm font-black text-slate-900">Purchase Orders & Delivered Items</h4>
                                                        <p class="text-xs text-slate-500">History of stock intakes and purchase items from this supplier</p>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-indigo-600">Total Spent</span>
                                                        <span class="text-xl sm:text-2xl font-black text-indigo-700">RM {{ number_format($supplier->stockPurchases->sum('total_amount'), 2) }}</span>
                                                    </div>
                                                </div>

                                                <div class="space-y-4">
                                                    @forelse ($supplier->stockPurchases as $purchase)
                                                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                                                            {{-- Purchase Header --}}
                                                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3 mb-3">
                                                                <div class="flex items-center gap-2">
                                                                    <span class="font-mono font-black text-indigo-600 text-xs">#{{ str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}</span>
                                                                    <span class="text-xs text-slate-400">&bull;</span>
                                                                    <span class="text-xs font-bold text-slate-700">Date: {{ $purchase->purchase_date->format('d M Y') }}</span>
                                                                    @if ($purchase->notes)
                                                                        <span class="text-xs text-slate-400">&bull; {{ $purchase->notes }}</span>
                                                                    @endif
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    @if ($purchase->status === 'received')
                                                                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-700 ring-1 ring-emerald-200">
                                                                            Received
                                                                        </span>
                                                                    @else
                                                                        <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-700 ring-1 ring-amber-200">
                                                                            Pending
                                                                        </span>
                                                                    @endif
                                                                    <span class="text-sm font-black text-slate-900">RM {{ number_format($purchase->total_amount, 2) }}</span>
                                                                </div>
                                                            </div>

                                                            {{-- Purchase Items Table --}}
                                                            <div class="overflow-x-auto">
                                                                <table class="w-full text-left text-xs">
                                                                    <thead>
                                                                        <tr class="text-[10px] font-extrabold uppercase text-slate-400 border-b border-slate-100">
                                                                            <th class="py-2.5 pr-3">Product Item</th>
                                                                            <th class="py-2.5 pr-3 text-center">Purchase Qty</th>
                                                                            <th class="py-2.5 pr-3 text-right">Purchase Price</th>
                                                                            <th class="py-2.5 pr-3 text-right">Selling Price</th>
                                                                            <th class="py-2.5 text-right">Subtotal</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody class="divide-y divide-slate-50 text-slate-700">
                                                                        @foreach ($purchase->items as $item)
                                                                            @php
                                                                                $displayItemName = $item->item_name ?: ($item->product?->name ?? 'Product Item');
                                                                                $displayItemSku = $item->item_sku ?: ($item->product?->sku ?? '—');
                                                                                $displayImage = $item->image_url ?: $item->product?->image_url;
                                                                            @endphp
                                                                            <tr>
                                                                                <td class="py-2.5 pr-3">
                                                                                    <div class="flex items-center gap-2.5">
                                                                                        @if ($displayImage)
                                                                                            <img src="{{ $displayImage }}" alt="{{ $displayItemName }}" class="h-8 w-8 rounded-lg object-cover ring-1 ring-slate-200 shrink-0">
                                                                                        @else
                                                                                            <div class="h-8 w-8 rounded-lg bg-slate-100 text-slate-400 grid place-items-center text-xs font-bold shrink-0">
                                                                                                📦
                                                                                            </div>
                                                                                        @endif
                                                                                        <div>
                                                                                            <p class="font-extrabold text-slate-900">{{ $displayItemName }}</p>
                                                                                            <p class="font-mono text-[10px] text-slate-400">{{ $displayItemSku }}</p>
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                                <td class="py-2.5 pr-3 text-center font-black text-slate-800">{{ number_format($item->quantity) }}</td>
                                                                                <td class="py-2.5 pr-3 text-right font-semibold text-slate-700">RM {{ number_format($item->purchase_price, 2) }}</td>
                                                                                <td class="py-2.5 pr-3 text-right font-semibold text-slate-500">RM {{ number_format($item->selling_price, 2) }}</td>
                                                                                <td class="py-2.5 text-right font-black text-slate-900">RM {{ number_format($item->subtotal, 2) }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="rounded-xl bg-slate-50 p-6 text-center text-xs font-semibold text-slate-400">
                                                            No stock purchases recorded for this supplier yet.
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Footer with Delete Supplier Button --}}
                                        <div class="flex items-center justify-between border-t border-slate-100 px-6 py-3 bg-slate-50/50">
                                            {{-- Delete Supplier Form --}}
                                            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    onclick="if(confirm('Permanently delete supplier &quot;{{ addslashes($supplier->company_name) }}&quot;? Linked products will remain in store and associated orders will be cleared.')) { this.closest('form').submit(); }"
                                                    class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-rose-50 px-4 text-xs font-extrabold text-rose-600 hover:bg-rose-100 transition"
                                                    title="Delete supplier">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Delete Supplier
                                                </button>
                                            </form>

                                            <form method="dialog">
                                                <button class="h-9 rounded-xl bg-slate-200 px-5 text-xs font-extrabold text-slate-700 hover:bg-slate-300">Close</button>
                                            </form>
                                        </div>
                                    </div>
                                </dialog>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center text-xs font-semibold text-slate-400">
                                No suppliers found matching your query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($suppliers->hasPages())
            <div class="border-t border-slate-100 p-4">
                {{ $suppliers->links() }}
            </div>
        @endif
    </section>

{{-- ============================================================
     SUPPLIER & PURCHASES REPORT MODAL
     ============================================================ --}}
<dialog id="supplier-report-modal" class="m-auto max-h-[92vh] w-[calc(100%-2rem)] max-w-5xl overflow-hidden rounded-2xl bg-white p-0 shadow-2xl backdrop:bg-slate-950/40 text-left text-slate-900">
    <div class="relative flex max-h-[92vh] flex-col">
        {{-- Header --}}
        <div class="relative border-b border-slate-100 px-6 py-4 pr-16 space-y-3.5">
            {{-- Title Section --}}
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-lg font-black text-slate-950">Supplier & Stock Purchases Report</h2>
                    <p class="text-xs font-semibold text-slate-400">Complete summary of cooperative suppliers, purchase history, and expenditure trace.</p>
                </div>
            </div>

            {{-- Action Buttons: Bottom of Title Section (supplier filter, checkbox, download, print) --}}
            <div class="flex flex-wrap items-center gap-2 pt-0.5">
                {{-- Supplier Selection Filter --}}
                <div class="inline-flex items-center gap-1.5 rounded-xl bg-slate-50 px-2.5 py-1.5 ring-1 ring-slate-200">
                    <label for="report-supplier-select" class="text-xs font-black text-slate-500 whitespace-nowrap">Supplier:</label>
                    <select id="report-supplier-select" onchange="onReportSupplierChange(this.value)"
                            class="rounded-lg border-slate-300 bg-white py-1 pl-2.5 pr-7 text-xs font-bold text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                        <option value="">All Suppliers (Summary)</option>
                        @foreach ($allSuppliersReport as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->company_name }} ({{ $sup->supplier_id }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Toggle Include Action & Audit Report --}}
                <label class="inline-flex items-center gap-2 cursor-pointer rounded-xl bg-slate-50 px-3 py-2 text-xs font-extrabold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100 transition">
                    <input type="checkbox" id="toggle-sup-actions" checked onchange="toggleSupActionSection(this.checked)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Include Action & Audit Trail</span>
                </label>

                {{-- Download PDF Report Button --}}
                <a id="btn-download-sup-pdf" href="{{ route('suppliers.report.pdf', ['with_actions' => 1]) }}"
                   class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 text-xs font-extrabold text-white hover:bg-rose-700 transition shadow-xs">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Download PDF
                </a>

                {{-- Print Button (Streams exact PDF layout in browser window) --}}
                <button type="button" onclick="printSupplierPdfReport()" class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-slate-100 px-3.5 text-xs font-extrabold text-slate-700 hover:bg-slate-200 transition">
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

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">

            {{-- ==========================================
                 VIEW 1: ALL SUPPLIERS SUMMARY
                 ========================================== --}}
            <div id="report-all-suppliers-container" class="space-y-6">
                {{-- Metrics Overview (3 Cards) --}}
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-[11px] font-bold uppercase text-slate-400">Total Suppliers</p>
                        <p class="mt-1 text-xl font-black text-slate-900">{{ number_format($supplierStats['total_suppliers'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-[11px] font-bold uppercase text-slate-400">Total Purchases</p>
                        <p class="mt-1 text-xl font-black text-emerald-600">{{ number_format($supplierStats['total_purchases'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-[11px] font-bold uppercase text-slate-400">Total Expenditure</p>
                        <p class="mt-1 text-xl font-black text-indigo-700">RM {{ number_format($supplierStats['total_spent'] ?? 0, 2) }}</p>
                    </div>
                </div>

                {{-- Activity Audit Trail for Suppliers & Stock Intakes --}}
                <div id="sup-audit-section" class="sup-audit-block rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Supplier & Purchase Audit Trail</h3>
                            <p class="text-xs text-slate-400">Chronological history of registered suppliers, bulk stock intakes, and vendor updates.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                            {{ $supplierActivityLogs->count() }} recent events
                        </span>
                    </div>

                    <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-100">
                        @forelse ($supplierActivityLogs as $log)
                            <div class="flex items-start justify-between gap-3 p-3 hover:bg-slate-50/60 transition">
                                <div class="flex items-start gap-2.5">
                                    @if ($log->action === 'created')
                                        <span class="mt-0.5 inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-700">Added</span>
                                    @elseif ($log->action === 'updated')
                                        <span class="mt-0.5 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-indigo-700">Edited</span>
                                    @elseif ($log->action === 'deleted')
                                        <span class="mt-0.5 inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-rose-700">Deleted</span>
                                    @else
                                        <span class="mt-0.5 inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-slate-700">{{ $log->action }}</span>
                                    @endif
                                    <div>
                                        <p class="text-xs font-extrabold text-slate-900">
                                            <span class="capitalize text-slate-500 font-bold">[{{ str_replace('_', ' ', $log->entity_type) }}]</span> {{ $log->entity_name }}
                                        </p>
                                        @if ($log->details)
                                            <p class="text-xs text-slate-500 mt-0.5">{{ $log->details }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right shrink-0 text-[11px] text-slate-400">
                                    <span class="font-semibold text-slate-600">{{ $log->admin_name ?: 'Admin' }}</span>
                                    <span class="block">{{ $log->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-xs font-semibold text-slate-400">
                                No supplier or stock intake activities recorded yet.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Full Suppliers & Purchases Breakdown Table --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Registered Suppliers Directory & Spending Summary</h3>
                            <p class="text-xs text-slate-400">All cooperative suppliers with contact info, total orders placed, and cumulative payment.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto max-h-80 overflow-y-auto rounded-xl border border-slate-100">
                        <table class="w-full text-left text-xs">
                            <thead class="sticky top-0 bg-slate-50 text-[11px] font-extrabold uppercase text-slate-400 border-b border-slate-200">
                                <tr>
                                    <th class="p-3">Supplier ID</th>
                                    <th class="p-3">Company Name</th>
                                    <th class="p-3">Contact Person</th>
                                    <th class="p-3">Contact Number</th>
                                    <th class="p-3">Email</th>
                                    <th class="p-3 text-center">Orders</th>
                                    <th class="p-3 text-right">Total Spent</th>
                                    <th class="p-3">Date Added</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                @foreach ($allSuppliersReport as $supReport)
                                    @php
                                        $spent = $supReport->stockPurchases->sum('total_amount');
                                    @endphp
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-3 font-mono font-black text-indigo-600">{{ $supReport->supplier_id }}</td>
                                        <td class="p-3 font-extrabold text-slate-900">{{ $supReport->company_name }}</td>
                                        <td class="p-3">{{ $supReport->contact_person ?: '—' }}</td>
                                        <td class="p-3">{{ $supReport->phone ?: '—' }}</td>
                                        <td class="p-3">{{ $supReport->email ?: '—' }}</td>
                                        <td class="p-3 text-center font-bold text-slate-900">{{ $supReport->stockPurchases->count() }}</td>
                                        <td class="p-3 text-right font-black text-emerald-600">RM {{ number_format($spent, 2) }}</td>
                                        <td class="p-3 text-slate-500">{{ $supReport->created_at->format('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ==========================================
                 VIEW 2: SELECTED SINGLE SUPPLIER AUDIT
                 ========================================== --}}
            <div id="report-single-supplier-container" class="hidden space-y-6">
                @foreach ($allSuppliersReport as $sup)
                    @php
                        $supPurchases = $sup->stockPurchases;
                        $supSpent = $supPurchases->sum('total_amount');
                        $supTotalUnits = 0;
                        foreach ($supPurchases as $p) {
                            $supTotalUnits += $p->items->sum('quantity');
                        }
                        $supLogs = $supplierActivityLogs->filter(function($l) use ($sup) {
                            return ($l->entity_type === 'supplier' && ($l->entity_id == $sup->id || str_contains($l->entity_name, $sup->company_name)))
                                || ($l->entity_type === 'stock_purchase' && str_contains($l->details, $sup->company_name));
                        });
                    @endphp
                    <div id="supplier-report-card-{{ $sup->id }}" class="supplier-report-detail hidden space-y-6">
                        {{-- Supplier Profile Verification Banner --}}
                        <div class="rounded-2xl border border-indigo-100 bg-linear-to-r from-indigo-50/60 to-purple-50/40 p-5">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-lg bg-indigo-600 px-2 py-0.5 font-mono text-xs font-black text-white">{{ $sup->supplier_id }}</span>
                                        <h3 class="text-base font-black text-slate-900">{{ $sup->company_name }}</h3>
                                    </div>
                                    <div class="mt-2 grid grid-cols-1 gap-x-6 gap-y-1 text-xs text-slate-600 sm:grid-cols-2">
                                        <p><span class="font-bold text-slate-400">Contact Person:</span> {{ $sup->contact_person ?: '—' }}</p>
                                        <p><span class="font-bold text-slate-400">Phone:</span> {{ $sup->phone ?: '—' }}</p>
                                        <p><span class="font-bold text-slate-400">Email:</span> {{ $sup->email ?: '—' }}</p>
                                        <p><span class="font-bold text-slate-400">Address:</span> {{ $sup->address ?: '—' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-black text-emerald-800">
                                        Active Vendor
                                    </span>
                                    <p class="mt-1 text-[11px] text-slate-400">Registered: {{ $sup->created_at->format('d M Y') }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Supplier Metrics Overview (3 Cards) --}}
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <p class="text-[11px] font-bold uppercase text-slate-400">Total Purchase Orders</p>
                                <p class="mt-1 text-xl font-black text-slate-900">{{ $supPurchases->count() }} Orders</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <p class="text-[11px] font-bold uppercase text-slate-400">Total Units Received</p>
                                <p class="mt-1 text-xl font-black text-emerald-600">{{ number_format($supTotalUnits) }} pcs</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <p class="text-[11px] font-bold uppercase text-slate-400">Cumulative Expenditure</p>
                                <p class="mt-1 text-xl font-black text-indigo-700">RM {{ number_format($supSpent, 2) }}</p>
                            </div>
                        </div>

                        {{-- Stock Purchases & Purchased Items Table --}}
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-extrabold text-slate-900">Stock Purchases & Intake History</h4>
                                    <p class="text-xs text-slate-400">Detailed item lines procured from {{ $sup->company_name }}.</p>
                                </div>
                                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">
                                    {{ $supPurchases->count() }} Orders
                                </span>
                            </div>

                            <div class="overflow-x-auto max-h-80 overflow-y-auto rounded-xl border border-slate-100">
                                <table class="w-full text-left text-xs">
                                    <thead class="sticky top-0 bg-slate-50 text-[11px] font-extrabold uppercase text-slate-400 border-b border-slate-200">
                                        <tr>
                                            <th class="p-3">Purchase Date</th>
                                            <th class="p-3">Item Name & SKU</th>
                                            <th class="p-3">Category</th>
                                            <th class="p-3 text-center">Intake Qty</th>
                                            <th class="p-3 text-right">Cost Price (RM)</th>
                                            <th class="p-3 text-right">Selling Price (RM)</th>
                                            <th class="p-3 text-right">Subtotal (RM)</th>
                                            <th class="p-3 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                        @php $hasItems = false; @endphp
                                        @foreach ($supPurchases as $purchase)
                                            @foreach ($purchase->items as $item)
                                                @php $hasItems = true; @endphp
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="p-3 text-slate-500 whitespace-nowrap">
                                                        <span class="font-bold text-slate-800">{{ $purchase->purchase_date->format('d M Y') }}</span>
                                                        <span class="block text-[10px] text-slate-400">#PO-{{ $purchase->id }}</span>
                                                    </td>
                                                    <td class="p-3">
                                                        <span class="font-extrabold text-slate-900">{{ $item->item_name }}</span>
                                                        @if ($item->item_sku)
                                                            <span class="block font-mono text-[10px] text-slate-400">{{ $item->item_sku }}</span>
                                                        @endif
                                                        @if ($item->product && $item->product->sizes && $item->product->sizes->isNotEmpty())
                                                            <span class="mt-0.5 inline-block text-[10px] font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">
                                                                Sizes: {{ $item->product->sizes->pluck('size')->join(', ') }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="p-3 text-slate-600">
                                                        {{ $item->product?->category?->name ?: '—' }}
                                                    </td>
                                                    <td class="p-3 text-center font-black text-indigo-600">
                                                        {{ number_format($item->quantity) }}
                                                    </td>
                                                    <td class="p-3 text-right font-medium">
                                                        RM {{ number_format($item->purchase_price, 2) }}
                                                    </td>
                                                    <td class="p-3 text-right font-medium text-slate-500">
                                                        RM {{ number_format($item->selling_price, 2) }}
                                                    </td>
                                                    <td class="p-3 text-right font-black text-emerald-600">
                                                        RM {{ number_format($item->subtotal, 2) }}
                                                    </td>
                                                    <td class="p-3 text-center">
                                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase text-emerald-700">
                                                            Received
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                        @if (!$hasItems)
                                            <tr>
                                                <td colspan="8" class="p-8 text-center text-xs font-semibold text-slate-400">
                                                    No stock purchase items recorded for this supplier yet.
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    @if ($hasItems)
                                        <tfoot class="bg-slate-50 border-t border-slate-200 text-xs font-black text-slate-900">
                                            <tr>
                                                <td colspan="3" class="p-3 text-right uppercase text-[11px] text-slate-400">Total:</td>
                                                <td class="p-3 text-center text-indigo-700">{{ number_format($supTotalUnits) }} pcs</td>
                                                <td colspan="2"></td>
                                                <td class="p-3 text-right text-emerald-600">RM {{ number_format($supSpent, 2) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>

                        {{-- Supplier-specific Activity & Audit Trail --}}
                        <div class="sup-audit-block rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-extrabold text-slate-900">Vendor Activity & Audit History</h4>
                                    <p class="text-xs text-slate-400">Audit logs related to {{ $sup->company_name }}.</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                    {{ $supLogs->count() }} events
                                </span>
                            </div>

                            <div class="max-h-52 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-100">
                                @forelse ($supLogs as $log)
                                    <div class="flex items-start justify-between gap-3 p-3 hover:bg-slate-50/60 transition">
                                        <div class="flex items-start gap-2.5">
                                            <span class="mt-0.5 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-indigo-700">
                                                {{ $log->action }}
                                            </span>
                                            <div>
                                                <p class="text-xs font-extrabold text-slate-900">
                                                    {{ $log->entity_name }}
                                                </p>
                                                @if ($log->details)
                                                    <p class="text-xs text-slate-500 mt-0.5">{{ $log->details }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0 text-[11px] text-slate-400">
                                            <span class="font-semibold text-slate-600">{{ $log->admin_name ?: 'Admin' }}</span>
                                            <span class="block">{{ $log->created_at->format('d M Y, h:i A') }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-6 text-center text-xs font-semibold text-slate-400">
                                        No specific audit events logged for this vendor.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</dialog>

@endsection

{{-- ============================================================
     COMBINED ADD SUPPLIER & PURCHASE HUGE DRAWER POP-UP
     Covers entire body page background except the left navbar
     ============================================================ --}}
<div id="combined-backdrop" onclick="closeCombinedDrawer()" class="hidden fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-xs transition-opacity"></div>

<div id="combined-drawer"
     class="hidden fixed top-0 bottom-0 right-0 z-50 flex flex-col bg-white shadow-2xl overflow-hidden text-slate-900"
     style="left: 0; transition: transform .25s ease;"
     aria-modal="true" role="dialog" aria-label="Add Supplier & Purchase Together">

    {{-- Drawer Header --}}
    <div class="flex shrink-0 items-center justify-between border-b border-slate-100 bg-white px-6 py-4">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-50 text-indigo-700 font-black">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </span>
            <div>
                <h2 id="drawer-title" class="text-lg font-black text-slate-950">Add Supplier & Stock Purchase Together</h2>
                <p id="drawer-subtitle" class="text-xs font-semibold text-slate-400 mt-0.5">
                    Enter supplier contact details and purchase items. Stock is automatically updated in store and database upon saving.
                </p>
            </div>
        </div>
        <button type="button" onclick="closeCombinedDrawer()" class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition font-extrabold">
            &times;
        </button>
    </div>

    {{-- Drawer Scrollable Content --}}
    <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
        @if ($errors->any())
            <div class="rounded-xl bg-rose-50 p-4 text-xs font-semibold text-rose-600 ring-1 ring-rose-200">
                <p class="font-bold mb-1">Please correct the following errors:</p>
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="combined-form" method="POST" action="{{ route('suppliers.store-with-purchase') }}" enctype="multipart/form-data" class="space-y-6" onsubmit="handleCombinedFormSubmit(event)">
            @csrf
            <div id="method-container"></div>

            {{-- SECTION 1: SUPPLIER DETAILS FIRST --}}
            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/80 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="grid h-6 w-6 place-items-center rounded-md bg-indigo-600 text-white text-xs font-black">1</span>
                        <h3 class="text-sm font-extrabold text-slate-900" id="section-1-title">Supplier Information</h3>
                    </div>

                    {{-- Supplier mode: New vs Existing --}}
                    <div id="supplier-mode-switcher" class="flex items-center gap-2 bg-white rounded-xl p-1 border border-slate-200 text-xs font-bold">
                        <label class="cursor-pointer px-3 py-1 rounded-lg transition" id="mode-new-label">
                            <input type="radio" name="supplier_mode" value="new" checked onchange="toggleSupplierMode('new')" class="hidden">
                            <span>+ New Supplier</span>
                        </label>
                        <label class="cursor-pointer px-3 py-1 rounded-lg transition text-slate-500" id="mode-existing-label">
                            <input type="radio" name="supplier_mode" value="existing" onchange="toggleSupplierMode('existing')" class="hidden">
                            <span>Existing Supplier</span>
                        </label>
                    </div>
                </div>

                {{-- Existing Supplier Dropdown --}}
                <div id="existing-supplier-box" class="hidden space-y-1">
                    <label class="text-xs font-extrabold uppercase text-slate-500">Choose Existing Supplier <span class="text-rose-500">*</span></label>
                    <select name="existing_supplier_id" id="existing_supplier_select" onchange="onExistingSupplierChange(this)" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select a registered supplier...</option>
                        @foreach ($spSuppliers as $sup)
                            <option value="{{ $sup->id }}"
                                    data-name="{{ $sup->company_name }}"
                                    data-contact="{{ $sup->contact_person }}"
                                    data-phone="{{ $sup->phone }}"
                                    data-email="{{ $sup->email }}"
                                    data-address="{{ $sup->address }}">
                                {{ $sup->company_name }} ({{ $sup->supplier_id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Supplier Details Grid --}}
                <div id="new-supplier-fields" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="space-y-1 sm:col-span-2 lg:col-span-1">
                        <label class="text-xs font-extrabold uppercase text-slate-500">Company Name <span class="text-rose-500">*</span></label>
                        <input name="company_name" id="inp_company_name" placeholder="e.g. Syarikat Pembekal Maju Sdn Bhd" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-extrabold uppercase text-slate-500">Contact Person</label>
                        <input name="contact_person" id="inp_contact_person" placeholder="e.g. En. Rahman" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-extrabold uppercase text-slate-500">Phone Number</label>
                        <input name="phone" id="inp_phone" placeholder="e.g. 012-3456789" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-extrabold uppercase text-slate-500">Email Address</label>
                        <input name="email" id="inp_email" type="email" placeholder="vendor@supplier.com" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="space-y-1 sm:col-span-2">
                        <label class="text-xs font-extrabold uppercase text-slate-500">Physical Address</label>
                        <input name="address" id="inp_address" placeholder="Vendor warehouse or office address..." class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                {{-- Purchase Date & Notes (intake only) --}}
                <div id="intake-date-notes" class="grid gap-3 sm:grid-cols-2 pt-2 border-t border-slate-200/60">
                    <div class="space-y-1">
                        <label class="text-xs font-extrabold uppercase text-slate-500">Purchase Date <span class="text-rose-500">*</span></label>
                        <input name="purchase_date" id="inp_purchase_date" type="date" value="{{ date('Y-m-d') }}" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-extrabold uppercase text-slate-500">Invoice / Receipt Notes</label>
                        <input name="notes" id="inp_notes" placeholder="Invoice #INV-2026, delivery note..." class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            {{-- SECTION 2: PURCHASE ITEMS FOLLOWED BY SUPPLIER DETAIL --}}
            <div id="purchase-section-container" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200/80 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="grid h-6 w-6 place-items-center rounded-md bg-indigo-600 text-white text-xs font-black">2</span>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Purchase Items Intake</h3>
                            <p class="text-xs text-slate-400">Add products purchased from this supplier. Calculates units and inventory automatically.</p>
                        </div>
                    </div>

                    {{-- Add new section for other item purchase from same supplier --}}
                    <button type="button" onclick="addPurchaseItemCard()" class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-indigo-600 px-4 text-xs font-extrabold text-white hover:bg-indigo-700 transition shadow-xs">
                        <span class="text-sm font-black leading-none">+</span> Add Another Item
                    </button>
                </div>

                {{-- Container for dynamically added purchase item cards --}}
                <div id="purchase-items-container" class="space-y-4"></div>

                {{-- Grand Total Display --}}
                <div class="flex items-center justify-between rounded-xl bg-white p-4 border border-slate-200/80">
                    <span class="text-xs font-extrabold uppercase text-slate-500">Total Purchase Payment to Supplier:</span>
                    <span class="text-xl font-black text-indigo-700" id="purchase-grand-total">RM 0.00</span>
                </div>
            </div>

            {{-- Notice shown only in Edit Supplier Mode (purchases locked) --}}
            <div id="edit-locked-purchase-notice" class="hidden rounded-xl border border-amber-200 bg-amber-50/70 p-4 text-xs font-semibold text-amber-800">
                <div class="flex items-start gap-2.5">
                    <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="font-extrabold text-amber-900">Purchase Records Locked in Edit Mode</p>
                        <p class="mt-0.5 text-amber-700">Past stock purchases cannot be modified retroactively to protect inventory audit integrity. To record a new stock intake for this vendor, click <strong>+ Add Supplier & Purchase</strong> at the top of the page.</p>
                    </div>
                </div>
            </div>

            {{-- Footer Submit Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="closeCombinedDrawer()" class="h-11 rounded-xl bg-slate-100 px-6 text-xs font-extrabold text-slate-600 hover:bg-slate-200 transition">
                    Cancel
                </button>
                <button type="submit" id="btn-drawer-submit" class="h-11 rounded-xl bg-indigo-600 px-7 text-xs font-extrabold text-white shadow-sm hover:bg-indigo-700 transition">
                    Save Supplier & Stock Intake
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================
     PRE-SAVE CONFIRMATION POP-UP MODAL
     User must review and confirm data before saving to DB
     ============================================================ --}}
<dialog id="confirm-save-modal" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-2xl overflow-hidden rounded-2xl bg-white p-0 shadow-2xl backdrop:bg-slate-950/40 text-left text-slate-900">
    <div class="flex max-h-[90vh] flex-col">
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-50 text-indigo-700 font-black">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-base font-black text-slate-900">Verify & Confirm Details</h3>
                    <p class="text-xs font-semibold text-slate-400">Please recheck your entries before saving to the system.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('confirm-save-modal').close()" class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition font-extrabold">&times;</button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-5">
            {{-- Vendor Summary --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-extrabold uppercase tracking-wide text-slate-400">Vendor Information</span>
                    <span id="confirm-badge-mode" class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-[10px] font-black uppercase text-indigo-700">New Supplier</span>
                </div>
                <h4 id="confirm-company-name" class="text-base font-black text-slate-900">—</h4>
                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-slate-600">
                    <div>
                        <span class="text-slate-400 font-bold block text-[10px] uppercase">Contact Person</span>
                        <span id="confirm-contact-person" class="font-extrabold text-slate-800">—</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold block text-[10px] uppercase">Phone</span>
                        <span id="confirm-phone" class="font-extrabold text-slate-800">—</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold block text-[10px] uppercase">Email</span>
                        <span id="confirm-email" class="font-extrabold text-slate-800">—</span>
                    </div>
                    <div id="confirm-date-box">
                        <span class="text-slate-400 font-bold block text-[10px] uppercase">Purchase Date</span>
                        <span id="confirm-purchase-date" class="font-extrabold text-slate-800">—</span>
                    </div>
                </div>
            </div>

            {{-- Purchase Items Summary (only shown if in intake mode) --}}
            <div id="confirm-items-section" class="space-y-2">
                <span class="text-[11px] font-extrabold uppercase tracking-wide text-slate-400">Purchase Items to Add & Restock</span>
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] font-extrabold uppercase text-slate-400 border-b border-slate-200">
                            <tr>
                                <th class="p-2.5">Item Name</th>
                                <th class="p-2.5 text-center">Purchase Qty</th>
                                <th class="p-2.5 text-right">Cost (RM)</th>
                                <th class="p-2.5 text-right">Sell (RM)</th>
                                <th class="p-2.5 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="confirm-items-tbody" class="divide-y divide-slate-100 font-medium text-slate-700">
                            {{-- Dynamically inserted --}}
                        </tbody>
                    </table>
                </div>

                {{-- Grand total badge --}}
                <div class="flex items-center justify-between rounded-xl bg-indigo-50/70 p-3.5 border border-indigo-100">
                    <span class="text-xs font-bold text-slate-700">Total Purchase Payment:</span>
                    <span id="confirm-grand-total" class="text-lg font-black text-indigo-700">RM 0.00</span>
                </div>

                <p class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-100 flex items-center gap-1.5">
                    <svg class="h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Stock quantities will be incremented in the database and catalog immediately.
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4 bg-slate-50/50">
            <button type="button" onclick="document.getElementById('confirm-save-modal').close()" class="h-10 rounded-xl bg-slate-100 px-5 text-xs font-extrabold text-slate-600 hover:bg-slate-200 transition">
                Review / Edit Again
            </button>
            <button type="button" id="btn-final-confirm-save" onclick="executeFinalFormSubmit()" class="inline-flex h-10 items-center gap-2 rounded-xl bg-indigo-600 px-6 text-xs font-extrabold text-white shadow-sm hover:bg-indigo-700 transition">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Confirm & Save
            </button>
        </div>
    </div>
</dialog>

@push('scripts')
<script>
(function() {
    const SIDEBAR_W = 256; // 16rem = 256px
    const drawer    = document.getElementById('combined-drawer');
    const backdrop  = document.getElementById('combined-backdrop');

    const categories     = @json($spCategories ?? []);
    const availableSizes = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'];

    let itemCardIndex = 0;
    let currentFormMode = 'intake'; // 'intake' or 'edit'

    function applyDrawerLeft() {
        if (window.innerWidth >= 1024) {
            drawer.style.left = SIDEBAR_W + 'px';
        } else {
            drawer.style.left = '0px';
        }
    }
    window.addEventListener('resize', applyDrawerLeft);

    // Open Drawer in "Add Supplier & Stock Purchase Together" mode
    window.openCombinedDrawer = function() {
        currentFormMode = 'intake';
        applyDrawerLeft();
        drawer.classList.remove('hidden');
        backdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        // Configure Drawer Header & Labels
        document.getElementById('drawer-title').textContent = 'Add Supplier & Stock Purchase Together';
        document.getElementById('drawer-subtitle').textContent = 'Enter supplier contact details and purchase items. Stock is automatically updated in store and database upon saving.';
        document.getElementById('btn-drawer-submit').textContent = 'Save Supplier & Stock Intake';

        // Reset form action & remove PATCH method
        const form = document.getElementById('combined-form');
        form.action = "{{ route('suppliers.store-with-purchase') }}";
        document.getElementById('method-container').innerHTML = '';

        // Show switcher, show purchase items, hide locked notice, show date & notes
        document.getElementById('supplier-mode-switcher').classList.remove('hidden');
        document.getElementById('purchase-section-container').classList.remove('hidden');
        document.getElementById('edit-locked-purchase-notice').classList.add('hidden');
        document.getElementById('intake-date-notes').classList.remove('hidden');
        document.getElementById('inp_purchase_date').required = true;

        // Reset all supplier inputs completely empty (no leftover previous input)
        document.getElementById('inp_company_name').value = '';
        document.getElementById('inp_contact_person').value = '';
        document.getElementById('inp_phone').value = '';
        document.getElementById('inp_email').value = '';
        document.getElementById('inp_address').value = '';
        document.getElementById('inp_notes').value = '';
        document.getElementById('inp_purchase_date').value = "{{ date('Y-m-d') }}";
        document.getElementById('existing_supplier_select').value = '';

        // Default to 'new' supplier mode
        toggleSupplierMode('new');
        const radioNew = document.querySelector('input[name="supplier_mode"][value="new"]');
        if (radioNew) radioNew.checked = true;

        // Clean purchase item cards: empty container & add 1 fresh, empty card
        document.getElementById('purchase-items-container').innerHTML = '';
        itemCardIndex = 0;
        addPurchaseItemCard();
        calcGrandTotal();
    };

    // Open Drawer in "Edit Supplier Details" mode (Purchases non-editable)
    window.openEditSupplierDrawer = function(supplier) {
        currentFormMode = 'edit';
        applyDrawerLeft();
        drawer.classList.remove('hidden');
        backdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        // Configure Drawer Header & Labels
        document.getElementById('drawer-title').textContent = 'Edit Supplier Details';
        document.getElementById('drawer-subtitle').textContent = 'Update contact details and address information for this supplier.';
        document.getElementById('btn-drawer-submit').textContent = 'Save Supplier Changes';

        // Form action pointing to PATCH /suppliers/{id}
        const form = document.getElementById('combined-form');
        form.action = "/suppliers/" + supplier.id;
        document.getElementById('method-container').innerHTML = '<input type="hidden" name="_method" value="PATCH">';

        // Hide mode switcher, hide purchase items section, show locked notice, hide intake date/notes
        document.getElementById('supplier-mode-switcher').classList.add('hidden');
        document.getElementById('purchase-section-container').classList.add('hidden');
        document.getElementById('edit-locked-purchase-notice').classList.remove('hidden');
        document.getElementById('intake-date-notes').classList.add('hidden');
        document.getElementById('inp_purchase_date').required = false;

        // Display supplier fields and populate with current supplier details
        document.getElementById('new-supplier-fields').classList.remove('hidden');
        document.getElementById('existing-supplier-box').classList.add('hidden');

        document.getElementById('inp_company_name').value = supplier.company_name || '';
        document.getElementById('inp_company_name').required = true;
        document.getElementById('inp_contact_person').value = supplier.contact_person || '';
        document.getElementById('inp_phone').value = supplier.phone || '';
        document.getElementById('inp_email').value = supplier.email || '';
        document.getElementById('inp_address').value = supplier.address || '';
    };

    // Alias for compatibility
    window.openEditCombinedDrawer = window.openEditSupplierDrawer;

    window.closeCombinedDrawer = function() {
        drawer.classList.add('hidden');
        backdrop.classList.add('hidden');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            const confirmModal = document.getElementById('confirm-save-modal');
            if (confirmModal && confirmModal.open) {
                confirmModal.close();
            } else if (!drawer.classList.contains('hidden')) {
                closeCombinedDrawer();
            }
        }
    });

    window.toggleSupplierMode = function(mode) {
        const newFields   = document.getElementById('new-supplier-fields');
        const existingBox = document.getElementById('existing-supplier-box');
        const newLbl      = document.getElementById('mode-new-label');
        const existLbl    = document.getElementById('mode-existing-label');
        const companyInp  = document.getElementById('inp_company_name');
        const existSelect = document.getElementById('existing_supplier_select');

        // Always clean and empty all supplier inputs whenever switching mode/tab
        companyInp.value = '';
        document.getElementById('inp_contact_person').value = '';
        document.getElementById('inp_phone').value = '';
        document.getElementById('inp_email').value = '';
        document.getElementById('inp_address').value = '';
        existSelect.value = '';

        // ALSO clean and empty all purchase items section whenever switching mode/tab
        const container = document.getElementById('purchase-items-container');
        if (container) {
            container.innerHTML = '';
            itemCardIndex = 0;
            addPurchaseItemCard();
            calcGrandTotal();
        }

        newFields.classList.remove('hidden');

        if (mode === 'new') {
            existingBox.classList.add('hidden');
            companyInp.required = true;
            existSelect.required = false;

            newLbl.classList.add('bg-indigo-600', 'text-white');
            newLbl.classList.remove('text-slate-500');
            existLbl.classList.remove('bg-indigo-600', 'text-white');
            existLbl.classList.add('text-slate-500');
        } else {
            existingBox.classList.remove('hidden');
            companyInp.required = false;
            existSelect.required = true;

            existLbl.classList.add('bg-indigo-600', 'text-white');
            existLbl.classList.remove('text-slate-500');
            newLbl.classList.remove('bg-indigo-600', 'text-white');
            newLbl.classList.add('text-slate-500');
        }
    };

    window.onExistingSupplierChange = function(sel) {
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.value) {
            document.getElementById('inp_company_name').value = opt.dataset.name || opt.text.split(' (')[0];
            document.getElementById('inp_contact_person').value = opt.dataset.contact || '';
            document.getElementById('inp_phone').value = opt.dataset.phone || '';
            document.getElementById('inp_email').value = opt.dataset.email || '';
            document.getElementById('inp_address').value = opt.dataset.address || '';
        } else {
            document.getElementById('inp_company_name').value = '';
            document.getElementById('inp_contact_person').value = '';
            document.getElementById('inp_phone').value = '';
            document.getElementById('inp_email').value = '';
            document.getElementById('inp_address').value = '';
        }
    };

    function updateReportUrls() {
        const withActions = document.getElementById('toggle-sup-actions')?.checked ? 1 : 0;
        const supplierId = document.getElementById('report-supplier-select')?.value || '';
        let url = "{{ route('suppliers.report.pdf') }}?with_actions=" + withActions;
        if (supplierId) {
            url += '&supplier_id=' + encodeURIComponent(supplierId);
        }
        const btn = document.getElementById('btn-download-sup-pdf');
        if (btn) btn.href = url;
    }

    window.toggleSupActionSection = function(show) {
        document.querySelectorAll('.sup-audit-block').forEach(el => {
            el.style.display = show ? '' : 'none';
        });
        updateReportUrls();
    };

    window.onReportSupplierChange = function(supId) {
        const allView = document.getElementById('report-all-suppliers-container');
        const singleContainer = document.getElementById('report-single-supplier-container');

        // Hide all single supplier report cards
        document.querySelectorAll('.supplier-report-detail').forEach(el => el.classList.add('hidden'));

        if (!supId) {
            if (allView) allView.classList.remove('hidden');
            if (singleContainer) singleContainer.classList.add('hidden');
        } else {
            if (allView) allView.classList.add('hidden');
            if (singleContainer) singleContainer.classList.remove('hidden');
            const target = document.getElementById('supplier-report-card-' + supId);
            if (target) {
                target.classList.remove('hidden');
            }
        }
        updateReportUrls();
    };

    // Print supplier report matching the downloaded PDF
    window.printSupplierPdfReport = function() {
        const withActions = document.getElementById('toggle-sup-actions')?.checked ? 1 : 0;
        const supplierId = document.getElementById('report-supplier-select')?.value || '';
        let url = "{{ route('suppliers.report.pdf') }}?stream=1&with_actions=" + withActions;
        if (supplierId) {
            url += '&supplier_id=' + encodeURIComponent(supplierId);
        }
        window.open(url, '_blank');
    };

    // Add a clean, empty purchase item card
    window.addPurchaseItemCard = function() {
        const idx = itemCardIndex++;
        const container = document.getElementById('purchase-items-container');

        let categoryOptions = '<option value="">Uncategorised</option>';
        categories.forEach(c => {
            categoryOptions += `<option value="${c.id}">${c.name}</option>`;
        });

        let sizeCardsHtml = '';
        availableSizes.forEach(s => {
            sizeCardsHtml += `
                <div class="rounded-xl border border-slate-200 bg-white p-2.5 shadow-2xs space-y-1.5" id="size-box-${idx}-${s}">
                    <label class="flex items-center gap-1.5 text-xs font-black text-slate-800 cursor-pointer">
                        <input type="checkbox" name="items[${idx}][sizes][]" value="${s}" onchange="toggleSizeInput(this, ${idx}, '${s}')" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Size ${s}</span>
                    </label>
                    <div class="space-y-0.5">
                        <label class="text-[9px] font-extrabold uppercase text-slate-400 block">Stock Qty</label>
                        <input type="number" min="0" value="" placeholder="0" name="items[${idx}][size_stock][${s}]" id="size-stock-${idx}-${s}" disabled oninput="onSizeStockOrPriceChange(${idx})"
                            class="h-7 w-full rounded-md border-slate-200 text-xs text-center font-bold disabled:bg-slate-100 disabled:text-slate-300 focus:border-indigo-500">
                    </div>
                    <div class="space-y-0.5">
                        <label class="text-[9px] font-extrabold uppercase text-slate-400 block">Price (RM)</label>
                        <input type="number" step="0.01" min="0.01" value="" placeholder="0.00" name="items[${idx}][size_price][${s}]" id="size-price-${idx}-${s}" disabled oninput="onSizeStockOrPriceChange(${idx})"
                            class="h-7 w-full rounded-md border-slate-200 text-xs text-center font-bold disabled:bg-slate-100 disabled:text-slate-300 focus:border-indigo-500">
                    </div>
                </div>
            `;
        });

        const card = document.createElement('div');
        card.id = `item-card-${idx}`;
        card.className = 'rounded-2xl border border-slate-200 bg-white p-4 shadow-xs space-y-4';
        card.innerHTML = `
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <span class="inline-flex items-center gap-2 text-xs font-black text-slate-800">
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-indigo-100 text-indigo-700 text-[10px]">#${idx + 1}</span>
                    Purchase Item Details
                </span>
                <button type="button" onclick="removeItemCard(${idx})" class="text-rose-500 hover:text-rose-700 text-xs font-extrabold inline-flex items-center gap-1">
                    &times; Remove Item
                </button>
            </div>

            {{-- Product Name, Category, SKU --}}
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="space-y-1 sm:col-span-2 lg:col-span-1">
                    <label class="text-[11px] font-extrabold uppercase text-slate-500">Product Name <span class="text-rose-500">*</span></label>
                    <input name="items[${idx}][name]" required placeholder="e.g. School Uniform Shirt" value="" class="h-9 w-full rounded-xl border-slate-200 text-xs font-bold focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-extrabold uppercase text-slate-500">Category</label>
                    <select name="items[${idx}][category_id]" class="h-9 w-full rounded-xl border-slate-200 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                        ${categoryOptions}
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-extrabold uppercase text-slate-500">SKU / Barcode</label>
                    <input name="items[${idx}][sku]" placeholder="PRD-XXXX (auto if empty)" value="" class="h-9 w-full rounded-xl border-slate-200 font-mono text-xs focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            {{-- Quantity, Purchase Price, Selling Price --}}
            <div class="grid gap-3 grid-cols-1 sm:grid-cols-3 items-end bg-slate-50/70 p-3.5 rounded-xl border border-slate-100">
                <div class="space-y-1">
                    <label class="text-[10px] font-extrabold uppercase text-slate-500">Purchase Quantity <span class="text-rose-500">*</span></label>
                    <input name="items[${idx}][quantity]" type="number" min="1" value="" placeholder="e.g. 50" required oninput="calcItemSubtotal(${idx})"
                        class="h-9 w-full rounded-lg border-slate-200 bg-white text-xs font-bold text-center focus:border-indigo-500">
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-extrabold uppercase text-slate-500">Purchase Price (RM) <span class="text-rose-500">*</span></label>
                    <input name="items[${idx}][purchase_price]" type="number" step="0.01" min="0.01" value="" placeholder="0.00" required oninput="calcItemSubtotal(${idx})"
                        class="h-9 w-full rounded-lg border-slate-200 bg-white text-xs font-bold text-right focus:border-indigo-500">
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-extrabold uppercase text-slate-500">Selling Price (RM) <span class="text-rose-500">*</span></label>
                    <input name="items[${idx}][selling_price]" type="number" step="0.01" min="0.01" value="" placeholder="0.00" required oninput="onBaseSellPriceChange(${idx}, this.value)"
                        class="h-9 w-full rounded-lg border-slate-200 bg-white text-xs font-bold text-right focus:border-indigo-500">
                </div>
            </div>

            {{-- Live Subtotal Display --}}
            <div class="flex items-center justify-between bg-indigo-50/50 p-3 rounded-xl border border-indigo-100 text-xs">
                <span class="font-bold text-slate-600">Subtotal Price to Pay Supplier:</span>
                <span id="item-subtotal-display-${idx}" class="font-black text-slate-900 text-sm">RM 0.00</span>
                <input type="hidden" id="item-subtotal-val-${idx}" value="0">
            </div>

            {{-- Size Section: Allow Product Sizes with distinct price & stock per size --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-xs font-extrabold text-slate-800 cursor-pointer">
                        <input type="checkbox" name="items[${idx}][has_sizes]" value="1" onchange="toggleSizeSection(${idx}, this.checked)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Allow Product Sizes (XS, S, M, L, XL, etc.) with Size-Specific Prices</span>
                    </label>
                </div>
                <div id="size-section-${idx}" class="hidden pt-2 border-t border-slate-200/80 space-y-2.5">
                    <p class="text-[11px] font-semibold text-slate-500">
                        Select sizes and enter the stock quantity & distinct mobile app selling price for each size:
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2.5">
                        ${sizeCardsHtml}
                    </div>
                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-indigo-700 bg-indigo-50/70 px-3 py-1.5 rounded-lg border border-indigo-100">
                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Each size price will sync directly to the mobile app catalog. Total quantity is automatically calculated from size quantities.</span>
                    </div>
                </div>
            </div>

            {{-- Image, Low Stock, App Visibility --}}
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 items-end">
                <div class="space-y-1">
                    <label class="text-[11px] font-extrabold uppercase text-slate-500">Low Stock Alert Level</label>
                    <input name="items[${idx}][min_stock_level]" type="number" min="0" value="5" class="h-9 w-full rounded-xl border-slate-200 text-xs font-bold text-center">
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-extrabold uppercase text-slate-500">Upload Product Image</label>
                    <input name="items[${idx}][image]" type="file" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-2 file:h-9 file:border-0 file:bg-indigo-50 file:px-3 file:text-xs file:font-bold file:text-indigo-700 file:rounded-lg">
                </div>

                <div class="space-y-1">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer h-9 px-2 rounded-xl bg-slate-50 border border-slate-200">
                        <input type="hidden" name="items[${idx}][is_active]" value="0">
                        <input type="checkbox" name="items[${idx}][is_active]" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Publish to Mobile App</span>
                    </label>
                </div>
            </div>

            {{-- Description --}}
            <div class="space-y-1">
                <label class="text-[11px] font-extrabold uppercase text-slate-500">Product Description</label>
                <input name="items[${idx}][description]" placeholder="Optional description or item details..." value="" class="h-9 w-full rounded-xl border-slate-200 text-xs">
            </div>
        `;

        container.appendChild(card);
        calcItemSubtotal(idx);
    };

    window.removeItemCard = function(idx) {
        const card = document.getElementById(`item-card-${idx}`);
        if (card) {
            card.remove();
            calcGrandTotal();
        }
    };

    window.toggleSizeSection = function(idx, show) {
        const sec = document.getElementById(`size-section-${idx}`);
        const qtyInp = document.querySelector(`input[name="items[${idx}][quantity]"]`);
        if (sec) sec.classList.toggle('hidden', !show);
        if (qtyInp) {
            qtyInp.readOnly = show;
            if (show) {
                qtyInp.classList.add('bg-slate-100', 'cursor-not-allowed');
                onSizeStockOrPriceChange(idx);
            } else {
                qtyInp.classList.remove('bg-slate-100', 'cursor-not-allowed');
                calcItemSubtotal(idx);
            }
        }
    };

    window.toggleSizeInput = function(cb, idx, size) {
        const stockInp = document.getElementById(`size-stock-${idx}-${size}`);
        const priceInp = document.getElementById(`size-price-${idx}-${size}`);
        const baseSell = document.querySelector(`input[name="items[${idx}][selling_price]"]`)?.value || '';

        if (stockInp) {
            stockInp.disabled = !cb.checked;
            stockInp.required = cb.checked;
            if (!cb.checked) stockInp.value = '';
        }
        if (priceInp) {
            priceInp.disabled = !cb.checked;
            priceInp.required = cb.checked;
            if (!cb.checked) {
                priceInp.value = '';
            } else if (!priceInp.value && baseSell) {
                priceInp.value = baseSell;
            }
        }
        onSizeStockOrPriceChange(idx);
    };

    window.onBaseSellPriceChange = function(idx, val) {
        const hasSizes = document.querySelector(`input[name="items[${idx}][has_sizes]"]`)?.checked;
        if (hasSizes && val) {
            availableSizes.forEach(s => {
                const pInp = document.getElementById(`size-price-${idx}-${s}`);
                if (pInp && !pInp.disabled && !pInp.value) {
                    pInp.value = val;
                }
            });
        }
    };

    window.onSizeStockOrPriceChange = function(idx) {
        const hasSizes = document.querySelector(`input[name="items[${idx}][has_sizes]"]`)?.checked;
        if (!hasSizes) return;

        let totalSizeStock = 0;
        const checkedBoxes = document.querySelectorAll(`input[name="items[${idx}][sizes][]"]:checked`);
        checkedBoxes.forEach(cb => {
            const size = cb.value;
            const sInp = document.getElementById(`size-stock-${idx}-${size}`);
            totalSizeStock += parseInt(sInp?.value) || 0;
        });

        const qtyInp = document.querySelector(`input[name="items[${idx}][quantity]"]`);
        if (qtyInp) {
            qtyInp.value = totalSizeStock > 0 ? totalSizeStock : '';
        }

        calcItemSubtotal(idx);
    };

    window.calcItemSubtotal = function(idx) {
        const qty = parseInt(document.querySelector(`input[name="items[${idx}][quantity]"]`)?.value) || 0;
        const cost = parseFloat(document.querySelector(`input[name="items[${idx}][purchase_price]"]`)?.value) || 0;
        const subtotal = qty * cost;

        const subtotalDisp = document.getElementById(`item-subtotal-display-${idx}`);
        if (subtotalDisp) subtotalDisp.textContent = `RM ${subtotal.toFixed(2)}`;

        const subtotalVal = document.getElementById(`item-subtotal-val-${idx}`);
        if (subtotalVal) subtotalVal.value = subtotal;

        calcGrandTotal();
    };

    window.calcGrandTotal = function() {
        let total = 0;
        document.querySelectorAll('[id^="item-subtotal-val-"]').forEach(el => {
            total += parseFloat(el.value) || 0;
        });
        const grandEl = document.getElementById('purchase-grand-total');
        if (grandEl) grandEl.textContent = `RM ${total.toFixed(2)}`;
    };

    // Pre-save Confirmation Modal trigger
    window.handleCombinedFormSubmit = function(event) {
        event.preventDefault();
        const form = document.getElementById('combined-form');

        // Check native HTML5 validity
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const companyName = document.getElementById('inp_company_name').value.trim();
        const contact = document.getElementById('inp_contact_person').value.trim() || '—';
        const phone = document.getElementById('inp_phone').value.trim() || '—';
        const email = document.getElementById('inp_email').value.trim() || '—';
        const purchaseDate = document.getElementById('inp_purchase_date').value || '—';

        document.getElementById('confirm-company-name').textContent = companyName || 'Unnamed Vendor';
        document.getElementById('confirm-contact-person').textContent = contact;
        document.getElementById('confirm-phone').textContent = phone;
        document.getElementById('confirm-email').textContent = email;
        document.getElementById('confirm-purchase-date').textContent = purchaseDate;

        const badgeMode = document.getElementById('confirm-badge-mode');
        const itemsSection = document.getElementById('confirm-items-section');
        const dateBox = document.getElementById('confirm-date-box');

        if (currentFormMode === 'edit') {
            badgeMode.textContent = 'Edit Supplier';
            badgeMode.className = 'rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-black uppercase text-amber-700';
            itemsSection.classList.add('hidden');
            dateBox.classList.add('hidden');
        } else {
            const isNew = document.querySelector('input[name="supplier_mode"][value="new"]')?.checked;
            badgeMode.textContent = isNew ? 'New Supplier Intake' : 'Existing Supplier Intake';
            badgeMode.className = 'rounded-full bg-indigo-50 px-2.5 py-0.5 text-[10px] font-black uppercase text-indigo-700';
            itemsSection.classList.remove('hidden');
            dateBox.classList.remove('hidden');

            // Populate items verification table
            const tbody = document.getElementById('confirm-items-tbody');
            tbody.innerHTML = '';

            const itemCards = document.querySelectorAll('[id^="item-card-"]');
            if (itemCards.length === 0) {
                alert('Please add at least one purchase item before saving.');
                return;
            }

            let totalAmount = 0;
            let validItemFound = false;

            for (const card of itemCards) {
                const name = card.querySelector('input[name*="[name]"]')?.value.trim();
                const sku = card.querySelector('input[name*="[sku]"]')?.value.trim() || 'Auto-SKU';
                const qty = parseInt(card.querySelector('input[name*="[quantity]"]')?.value) || 0;
                const cost = parseFloat(card.querySelector('input[name*="[purchase_price]"]')?.value) || 0;
                const sell = parseFloat(card.querySelector('input[name*="[selling_price]"]')?.value) || 0;
                const subtotal = qty * cost;

                const hasSizes = card.querySelector('input[name*="[has_sizes]"]')?.checked;
                let sizeDetails = [];
                if (hasSizes) {
                    const checkedSizes = card.querySelectorAll('input[name*="[sizes][]"]:checked');
                    if (checkedSizes.length === 0) {
                        alert(`Please select at least one size for "${name || 'the product'}" or uncheck the sizes option.`);
                        return;
                    }
                    checkedSizes.forEach(cb => {
                        const s = cb.value;
                        const sStock = card.querySelector(`input[name*="[size_stock][${s}]"]`)?.value || '0';
                        const sPrice = card.querySelector(`input[name*="[size_price][${s}]"]`)?.value || sell.toFixed(2);
                        sizeDetails.push(`${s}: ${sStock} pcs (RM ${parseFloat(sPrice || 0).toFixed(2)})`);
                    });
                }

                if (name) {
                    validItemFound = true;
                    totalAmount += subtotal;
                    const sizeInfoHtml = sizeDetails.length > 0 
                        ? `<div class="mt-1 text-[10px] text-indigo-700 bg-indigo-50/70 px-2 py-0.5 rounded font-bold">${sizeDetails.join(', ')}</div>` 
                        : '';
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="p-2.5 font-bold text-slate-900">
                            ${name}
                            <span class="block font-mono text-[10px] text-slate-400 font-normal">${sku}</span>
                            ${sizeInfoHtml}
                        </td>
                        <td class="p-2.5 text-center font-black text-indigo-600">${qty}</td>
                        <td class="p-2.5 text-right font-medium">RM ${cost.toFixed(2)}</td>
                        <td class="p-2.5 text-right font-medium text-slate-500">${sizeDetails.length > 0 ? 'Sizes' : 'RM ' + sell.toFixed(2)}</td>
                        <td class="p-2.5 text-right font-black text-slate-900">RM ${subtotal.toFixed(2)}</td>
                    `;
                    tbody.appendChild(tr);
                }
            }

            if (!validItemFound) {
                alert('Please provide product details for the purchase items.');
                return;
            }

            document.getElementById('confirm-grand-total').textContent = `RM ${totalAmount.toFixed(2)}`;
        }

        // Show confirmation modal
        document.getElementById('confirm-save-modal').showModal();
    };

    // User confirmed on modal: submit the form to database
    window.executeFinalFormSubmit = function() {
        const btn = document.getElementById('btn-final-confirm-save');
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            Saving to Database...
        `;
        document.getElementById('combined-form').submit();
    };
})();
</script>
@endpush
