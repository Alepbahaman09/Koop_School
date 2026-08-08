@extends('layouts.app')

@section('title', 'Suppliers')
@section('page-title', 'Suppliers')

@section('content')
@include('partials.admin-alerts')

{{-- Page Header --}}
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold text-slate-950">Suppliers</h1>
        <p class="mt-1 text-xs font-semibold text-slate-400">Manage your cooperative suppliers and vendor contacts.</p>
    </div>
    
    @if ($tab === 'suppliers')
        {{-- Add Supplier Modal --}}
        <details class="group" {{ old('_supplier_create') ? 'open' : '' }}>
            <summary class="inline-flex h-10 cursor-pointer list-none items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700">
                <span class="text-lg">+</span> Add Supplier
            </summary>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/30 p-4" onclick="this.closest('details').open=false">
                <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-xl bg-white p-6 shadow-2xl text-left" onclick="event.stopPropagation()">
                    <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-lg font-extrabold">Add Supplier</h2>
                    <button type="button" onclick="this.closest('details').open=false"
                        class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500 hover:bg-slate-200">&times;</button>
                </div>
                <form method="POST" action="{{ route('suppliers.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @include('partials.supplier-form', ['supplier' => null])
                    <div class="flex justify-end sm:col-span-2">
                        <button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white hover:bg-indigo-700">
                            Create Supplier
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </details>
    @else
        {{-- New Stock Purchase — trigger button --}}
        <button type="button" onclick="spDrawerOpen()"
            class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700">
            <span class="text-lg">+</span> New Stock Purchase
        </button>
    @endif
</section>

{{-- Tabs --}}
<div class="border-b border-slate-200">
    <nav class="-mb-px flex space-x-6" aria-label="Tabs">
        <a href="{{ route('suppliers.index', ['tab' => 'suppliers']) }}" class="border-b-2 px-1 pb-4 text-sm font-extrabold {{ $tab === 'suppliers' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
            Suppliers List
        </a>
        <a href="{{ route('suppliers.index', ['tab' => 'purchases']) }}" class="border-b-2 px-1 pb-4 text-sm font-extrabold {{ $tab === 'purchases' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
            Stock Purchases
        </a>
    </nav>
</div>

@if ($tab === 'suppliers')
    {{-- Search & Filter Suppliers --}}
    <section class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
        <h2 class="mb-3 text-sm font-extrabold text-slate-900">Filter Suppliers</h2>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="tab" value="suppliers">
            <input name="search" value="{{ request('search') }}"
                placeholder="Search by company name"
                class="h-9 w-64 flex-none rounded-lg border-slate-200 bg-white text-xs font-semibold focus:border-indigo-400 focus:ring-indigo-200">
            <button class="h-9 flex-none rounded-lg bg-indigo-600 px-4 text-xs font-extrabold text-white hover:bg-indigo-700">Filter</button>
            @if (request()->filled('search'))
                <a href="{{ route('suppliers.index', ['tab' => 'suppliers']) }}"
                    class="h-9 flex-none rounded-lg bg-slate-100 px-4 text-xs font-extrabold text-slate-600 hover:bg-slate-200 leading-9">
                    Clear
                </a>
            @endif
        </form>
    </section>

    {{-- Suppliers Table --}}
    <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-xs font-bold uppercase text-slate-400">
                        <th class="px-5 py-4">Supplier ID</th>
                        <th class="px-5 py-4">Company Name</th>
                        <th class="px-5 py-4">Contact Details</th>
                        <th class="px-5 py-4">Date Added</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($suppliers as $supplier)
                        <tr class="group hover:bg-slate-50/60 transition">
                            {{-- Supplier ID --}}
                            <td class="px-5 py-4 font-bold text-indigo-600">
                                {{ $supplier->supplier_id }}
                            </td>
                            {{-- Company name --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-indigo-100 text-sm font-extrabold text-indigo-600">
                                        {{ strtoupper(substr($supplier->company_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-slate-900">{{ $supplier->company_name }}</p>
                                    </div>
                                </div>
                            </td>
                            {{-- Contact Details --}}
                            <td class="px-5 py-4 text-slate-700">
                                <p class="font-extrabold text-slate-800">{{ $supplier->contact_person ?: '—' }}</p>
                                <p class="text-xs font-semibold text-slate-500">{{ $supplier->email ?: '—' }}</p>
                                <p class="text-xs font-semibold text-slate-500">{{ $supplier->phone ?: '—' }}</p>
                            </td>
                            {{-- Date Added --}}
                            <td class="px-5 py-4 text-slate-500 font-semibold text-xs">
                                {{ $supplier->created_at->format('d M Y') }}
                            </td>
                            {{-- Actions --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- View --}}
                                    <details>
                                        <summary class="grid h-8 w-8 cursor-pointer list-none place-items-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition" title="View">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </summary>
                                        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/30 p-4" onclick="this.closest('details').open=false">
                                            <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-xl bg-white p-6 shadow-2xl text-left" onclick="event.stopPropagation()">
                                                <div class="mb-5 flex items-center justify-between">
                                                <h2 class="text-lg font-extrabold">Supplier Details</h2>
                                                <button type="button" onclick="this.closest('details').open=false"
                                                    class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500 hover:bg-slate-200">&times;</button>
                                            </div>
                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div class="space-y-1">
                                                    <span class="text-xs font-bold text-slate-400 uppercase">Supplier ID</span>
                                                    <p class="text-sm font-extrabold text-slate-900">{{ $supplier->supplier_id }}</p>
                                                </div>
                                                <div class="space-y-1">
                                                    <span class="text-xs font-bold text-slate-400 uppercase">Company Name</span>
                                                    <p class="text-sm font-extrabold text-slate-900">{{ $supplier->company_name }}</p>
                                                </div>
                                                <div class="space-y-1">
                                                    <span class="text-xs font-bold text-slate-400 uppercase">Contact Person</span>
                                                    <p class="text-sm font-semibold text-slate-700">{{ $supplier->contact_person ?: '—' }}</p>
                                                </div>
                                                <div class="space-y-1">
                                                    <span class="text-xs font-bold text-slate-400 uppercase">Email</span>
                                                    <p class="text-sm font-semibold text-slate-700">{{ $supplier->email ?: '—' }}</p>
                                                </div>
                                                <div class="space-y-1">
                                                    <span class="text-xs font-bold text-slate-400 uppercase">Phone</span>
                                                    <p class="text-sm font-semibold text-slate-700">{{ $supplier->phone ?: '—' }}</p>
                                                </div>
                                                <div class="space-y-1">
                                                    <span class="text-xs font-bold text-slate-400 uppercase">Date Added</span>
                                                    <p class="text-sm font-semibold text-slate-700">{{ $supplier->created_at->format('d M Y, g:i A') }}</p>
                                                </div>
                                                <div class="space-y-1 sm:col-span-2">
                                                    <span class="text-xs font-bold text-slate-400 uppercase">Address</span>
                                                    <p class="text-sm font-semibold text-slate-700 whitespace-pre-line">{{ $supplier->address ?: '—' }}</p>
                                                </div>
                                            </div>
                                            <div class="mt-6 flex justify-end">
                                                <button type="button" onclick="this.closest('details').open=false"
                                                    class="h-10 rounded-lg bg-slate-100 px-5 text-sm font-extrabold text-slate-600 hover:bg-slate-200">
                                                    Close
                                                </button>
                                            </div>
                                        </div>
                                        </div>
                                    </details>

                                    {{-- Edit --}}
                                    <details {{ request('edit') == $supplier->id ? 'open' : '' }}>
                                        <summary class="grid h-8 w-8 cursor-pointer list-none place-items-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition" title="Edit">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="m4 20 4-1 11-11-3-3L5 16l-1 4ZM14 7l3 3" />
                                            </svg>
                                        </summary>
                                        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/30 p-4" onclick="this.closest('details').open=false">
                                            <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-xl bg-white p-6 shadow-2xl text-left" onclick="event.stopPropagation()">
                                                <div class="mb-5 flex items-center justify-between">
                                                <h2 class="text-lg font-extrabold">Edit {{ $supplier->company_name }}</h2>
                                                <button type="button" onclick="this.closest('details').open=false"
                                                    class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500 hover:bg-slate-200">&times;</button>
                                            </div>
                                            <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="grid gap-4 sm:grid-cols-2">
                                                @csrf
                                                @method('PATCH')
                                                @include('partials.supplier-form', ['supplier' => $supplier])
                                                <div class="flex justify-between items-center sm:col-span-2 mt-4 pt-4 border-t border-slate-100">
                                                    <button type="button" 
                                                            onclick="if(confirm('Are you sure you want to permanently delete this supplier? This action cannot be undone.')) { document.getElementById('delete-form-{{ $supplier->id }}').submit(); }"
                                                            class="h-10 rounded-lg bg-rose-50 px-5 text-sm font-extrabold text-rose-600 hover:bg-rose-100">
                                                        Delete Supplier
                                                    </button>
                                                    <button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white hover:bg-indigo-700">
                                                        Save Changes
                                                    </button>
                                                </div>
                                            </form>
                                            <form id="delete-form-{{ $supplier->id }}" method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                        </div>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-20 text-center text-sm font-semibold text-slate-400">
                                No suppliers match your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($suppliers->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $suppliers->links() }}
            </div>
        @endif
    </section>

@else
    {{-- Search & Filter Purchases --}}
    <section class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
        <h2 class="mb-3 text-sm font-extrabold text-slate-900">Search Purchases</h2>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="tab" value="purchases">
            <input name="search" value="{{ request('search') }}"
                placeholder="Search by supplier name"
                class="h-9 w-64 flex-none rounded-lg border-slate-200 bg-white text-xs font-semibold focus:border-indigo-400 focus:ring-indigo-200">
            <button class="h-9 flex-none rounded-lg bg-indigo-600 px-4 text-xs font-extrabold text-white hover:bg-indigo-700">Search</button>
            @if (request()->filled('search'))
                <a href="{{ route('suppliers.index', ['tab' => 'purchases']) }}"
                    class="h-9 flex-none rounded-lg bg-slate-100 px-4 text-xs font-extrabold text-slate-600 hover:bg-slate-200 leading-9">
                    Clear
                </a>
            @endif
        </form>
    </section>

    {{-- Purchases Table --}}
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
@endif

@endsection

{{-- ============================================================
     NEW STOCK PURCHASE — Full-height slide-over drawer
     Covers the main content area (right of the fixed sidebar)
     ============================================================ --}}

{{-- Backdrop --}}
<div id="sp-backdrop"
    onclick="spDrawerClose()"
    class="hidden fixed inset-0 z-40 bg-slate-950/40 transition-opacity"></div>

{{-- Drawer panel: fixed to right of sidebar (lg:left-64) filling full height --}}
<div id="sp-drawer"
    class="hidden fixed top-0 bottom-0 right-0 z-50 flex flex-col bg-white shadow-2xl overflow-hidden"
    style="left: 0; transition: transform .25s ease;"
    aria-modal="true" role="dialog" aria-label="New Stock Purchase">

    {{-- Drawer Header --}}
    <div class="flex shrink-0 items-center justify-between border-b border-slate-100 bg-white px-6 py-4">
        <div>
            <h2 class="text-lg font-extrabold text-slate-950">New Stock Purchase</h2>
            <p class="text-xs font-semibold text-slate-400 mt-0.5">
                Saved as <span class="font-bold text-amber-600">Pending</span> — stock updates when marked as
                <span class="font-bold text-emerald-600">Received</span>.
            </p>
        </div>
        <button type="button" onclick="spDrawerClose()"
            class="grid h-9 w-9 place-items-center rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Scrollable body --}}
    <div class="flex-1 overflow-y-auto px-6 py-5">

        @if ($errors->any())
            <div class="mb-5 rounded-xl bg-rose-50 p-4 text-xs font-semibold text-rose-600 ring-1 ring-rose-100">
                <p class="font-bold mb-1">Please fix the following errors:</p>
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="sp-form" method="POST" action="{{ route('stock-purchases.store') }}" class="space-y-5">
            @csrf

            {{-- Purchase Details --}}
            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100 grid gap-4 sm:grid-cols-3">
                <h3 class="sm:col-span-3 text-xs font-extrabold uppercase tracking-wide text-slate-400 border-b border-slate-200 pb-2">Purchase Information</h3>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-600">Supplier <span class="text-rose-400">*</span></label>
                    <select name="supplier_id" required class="h-10 w-full rounded-lg border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400">
                        <option value="">Select Supplier</option>
                        @foreach ($spSuppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->company_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-600">Purchase Date <span class="text-rose-400">*</span></label>
                    <input name="purchase_date" type="date" value="{{ date('Y-m-d') }}" required
                        class="h-10 w-full rounded-lg border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-600">Notes / Remarks</label>
                    <input name="notes" type="text"
                        class="h-10 w-full rounded-lg border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400"
                        placeholder="Invoice ref, remarks...">
                </div>
            </div>

            {{-- Items --}}
            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 class="text-xs font-extrabold uppercase tracking-wide text-slate-400">Purchase Items</h3>
                    <div class="flex gap-2">
                        <button type="button" onclick="spOpenNewProductModal(null)"
                            class="h-8 rounded-lg bg-emerald-50 px-3 text-xs font-extrabold text-emerald-600 hover:bg-emerald-100 transition">+ New Product</button>
                        <button type="button" onclick="spAddRow()"
                            class="h-8 rounded-lg bg-indigo-50 px-3 text-xs font-extrabold text-indigo-600 hover:bg-indigo-100 transition">+ Add Row</button>
                    </div>
                </div>

                <div class="rounded-lg bg-blue-50 px-4 py-2.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                    💡 <strong>How it works:</strong> Enter the <em>buy unit</em> (e.g. Carton) and <em>units per buy</em> (e.g. 24 pcs).
                    Stock updates by <strong>Qty × Units/Buy</strong> when marked as Received.
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm" id="sp-items-table">
                        <thead>
                            <tr class="text-xs font-bold uppercase text-slate-400 border-b border-slate-200">
                                <th class="py-2 pr-3" style="min-width:200px">Product</th>
                                <th class="py-2 pr-3 text-center" style="width:70px">Sell Unit</th>
                                <th class="py-2 pr-3" style="width:110px">Buy In</th>
                                <th class="py-2 pr-3 text-center" style="width:80px">Units/Buy</th>
                                <th class="py-2 pr-3 text-center" style="width:65px">Qty</th>
                                <th class="py-2 pr-3 text-center" style="width:80px">Total Units</th>
                                <th class="py-2 pr-3 text-right" style="width:130px">Purchase Price/Buy (RM)</th>
                                <th class="py-2 pr-3 text-right" style="width:120px">Selling Price/Unit (RM)</th>
                                <th class="py-2 pr-3 text-right" style="width:90px">Subtotal</th>
                                <th class="py-2" style="width:32px"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="sp-items-tbody"></tbody>
                    </table>
                </div>

                <div class="flex justify-end items-center gap-4 border-t border-slate-200 pt-3">
                    <span class="text-xs font-bold text-slate-500">Grand Total:</span>
                    <span class="text-lg font-extrabold text-slate-950" id="sp-grand-total">RM 0.00</span>
                </div>
            </div>

            {{-- Footer actions inside scrollable area --}}
            <div class="flex justify-end gap-3 pb-2">
                <button type="button" onclick="spDrawerClose()"
                    class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-100 px-5 text-sm font-extrabold text-slate-600 hover:bg-slate-200">
                    Cancel
                </button>
                <button type="submit"
                    class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-6 text-sm font-extrabold text-white hover:bg-indigo-700">
                    Save Purchase (Pending)
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Inline Create Product Dialog --}}
<dialog id="sp-new-product-modal"
    class="rounded-xl border border-slate-100 bg-white p-6 shadow-2xl max-w-md w-full backdrop:bg-slate-950/30" style="z-index:60;">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
        <h3 class="text-md font-extrabold text-slate-900">Create New Product</h3>
        <button type="button" onclick="spCloseNewProductModal()"
            class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
    </div>
    <form id="sp-inline-product-form" onsubmit="spSubmitInlineProduct(event)" class="space-y-4">
        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-600">Product Name <span class="text-rose-400">*</span></label>
            <input name="name" required class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold focus:border-indigo-400" placeholder="e.g. Milo 3-in-1">
        </div>
        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-600">Category <span class="text-rose-400">*</span></label>
            <select name="category_id" required class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold focus:border-indigo-400">
                <option value="">Select Category</option>
                @foreach ($spCategories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600">Selling Unit <span class="text-rose-400">*</span></label>
                <input name="unit" required class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold focus:border-indigo-400" placeholder="pcs, bottle, pack">
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600">Selling Price (RM) <span class="text-rose-400">*</span></label>
                <input name="price" type="number" step="0.01" min="0.01" required
                    class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold focus:border-indigo-400 text-right" placeholder="0.00">
            </div>
        </div>
        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-600">Barcode / SKU (Optional)</label>
            <input name="sku" class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold focus:border-indigo-400" placeholder="Auto-generated if empty">
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" onclick="spCloseNewProductModal()"
                class="h-9 rounded-lg bg-slate-100 px-4 text-xs font-extrabold text-slate-600 hover:bg-slate-200">Cancel</button>
            <button type="submit"
                class="h-9 rounded-lg bg-indigo-600 px-5 text-xs font-extrabold text-white hover:bg-indigo-700">Save Product</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
(function () {
    const SIDEBAR_W = 256; // px — matches w-64 sidebar
    let spRowIdx = 0;
    let spProducts = @json($spProducts ?? []);
    const spPurchaseUnits = @json($spPurchaseUnits ?? []);
    let spActiveRowIdx = null;
    let spInitialised = false;

    const drawer   = document.getElementById('sp-drawer');
    const backdrop = document.getElementById('sp-backdrop');

    function applyDrawerLeft() {
        // On lg screens (>= 1024px) push the drawer past the sidebar
        drawer.style.left = window.innerWidth >= 1024 ? SIDEBAR_W + 'px' : '0';
    }
    window.addEventListener('resize', applyDrawerLeft);

    window.spDrawerOpen = function () {
        applyDrawerLeft();
        drawer.classList.remove('hidden');
        backdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (!spInitialised) {
            spAddRow();
            spInitialised = true;
        }
    };

    window.spDrawerClose = function () {
        drawer.classList.add('hidden');
        backdrop.classList.add('hidden');
        document.body.style.overflow = '';
    };

    // Close on Escape
    document.addEventListener('keydown', e => { if (e.key === 'Escape') spDrawerClose(); });

    window.spAddRow = function () {
        const tbody = document.getElementById('sp-items-tbody');
        const idx = spRowIdx;
        const row = document.createElement('tr');
        row.id = `sp-row-${idx}`;
        row.className = 'group align-top';
        row.innerHTML = `
            <td class="py-2 pr-3">
                <div class="flex items-center gap-1.5">
                    <select name="items[${idx}][product_id]" required onchange="spOnProductChange(this,${idx})"
                        class="h-9 w-full rounded-lg border-slate-200 bg-white px-2 text-xs font-semibold text-slate-800 focus:border-indigo-400">
                        ${spBuildProductOptions()}
                    </select>
                    <button type="button" onclick="spOpenNewProductModal(${idx})"
                        class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 flex items-center justify-center transition flex-shrink-0 font-bold"
                        title="Create New Product">+</button>
                </div>
            </td>
            <td id="sp-sell-unit-${idx}" class="py-2 pr-3 text-center font-extrabold text-slate-500 text-xs align-middle">—</td>
            <td class="py-2 pr-3">
                <select name="items[${idx}][purchase_unit]" onchange="spOnPurchaseUnitChange(this,${idx})"
                    class="h-9 w-full rounded-lg border-slate-200 bg-white px-2 text-xs font-semibold text-slate-800 focus:border-indigo-400">
                    ${spBuildUnitOptions('Unit')}
                </select>
            </td>
            <td class="py-2 pr-3">
                <input name="items[${idx}][units_per_purchase]" type="number" min="1" value="1" required
                    oninput="spUpdateTotalUnits(${idx})"
                    class="h-9 w-full rounded-lg border-slate-200 bg-white px-2 text-xs font-semibold text-slate-800 focus:border-indigo-400 text-center">
            </td>
            <td class="py-2 pr-3">
                <input name="items[${idx}][quantity]" type="number" min="1" value="1" required
                    oninput="spUpdateTotalUnits(${idx}); spCalcSubtotal(${idx})"
                    class="h-9 w-full rounded-lg border-slate-200 bg-white px-2 text-xs font-semibold text-slate-800 focus:border-indigo-400 text-center">
            </td>
            <td class="py-2 pr-3 text-center align-middle">
                <span id="sp-total-units-${idx}" class="inline-block rounded-md bg-indigo-50 px-2 py-1 text-xs font-extrabold text-indigo-700">1 pcs</span>
            </td>
            <td class="py-2 pr-3">
                <input name="items[${idx}][purchase_price]" type="number" step="0.01" min="0.01" value="0.00" required
                    oninput="spCalcSubtotal(${idx})"
                    class="h-9 w-full rounded-lg border-slate-200 bg-white px-2 text-xs font-semibold text-slate-800 focus:border-indigo-400 text-right">
            </td>
            <td class="py-2 pr-3">
                <input name="items[${idx}][selling_price]" type="number" step="0.01" min="0.01" value="0.00" required
                    class="h-9 w-full rounded-lg border-slate-200 bg-white px-2 text-xs font-semibold text-slate-800 focus:border-indigo-400 text-right">
            </td>
            <td class="py-2 pr-3 text-right font-bold text-slate-700 text-xs align-middle">
                RM <span id="sp-subtotal-${idx}">0.00</span>
            </td>
            <td class="py-2 align-middle">
                <button type="button" onclick="spRemoveRow(${idx})"
                    class="h-7 w-7 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-100 flex items-center justify-center transition">&times;</button>
            </td>`;
        tbody.appendChild(row);
        spRowIdx++;
        spCalcGrandTotal();
    };

    window.spRemoveRow = function (idx) {
        document.getElementById(`sp-row-${idx}`)?.remove();
        spCalcGrandTotal();
    };

    window.spOnProductChange = function (sel, idx) {
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.value) {
            document.getElementById(`sp-sell-unit-${idx}`).textContent = opt.dataset.unit || 'pcs';
            document.querySelector(`select[name="items[${idx}][purchase_unit]"]`).value = opt.dataset.purchaseUnit || 'Unit';
            document.querySelector(`input[name="items[${idx}][units_per_purchase]"]`).value = parseInt(opt.dataset.unitsPerCarton) || 1;
            document.querySelector(`input[name="items[${idx}][purchase_price]"]`).value = parseFloat(opt.dataset.cost || 0).toFixed(2);
            document.querySelector(`input[name="items[${idx}][selling_price]"]`).value = parseFloat(opt.dataset.price || 0).toFixed(2);
        } else {
            document.getElementById(`sp-sell-unit-${idx}`).textContent = '—';
        }
        spUpdateTotalUnits(idx);
        spCalcSubtotal(idx);
    };

    window.spOnPurchaseUnitChange = function (sel, idx) { spUpdateTotalUnits(idx); };

    window.spUpdateTotalUnits = function (idx) {
        const qty  = parseInt(document.querySelector(`input[name="items[${idx}][quantity]"]`).value) || 0;
        const upu  = parseInt(document.querySelector(`input[name="items[${idx}][units_per_purchase]"]`).value) || 1;
        const unit = document.getElementById(`sp-sell-unit-${idx}`).textContent.trim();
        const el   = document.getElementById(`sp-total-units-${idx}`);
        if (el) el.textContent = `${qty * upu} ${unit !== '—' ? unit : 'pcs'}`;
    };

    window.spCalcSubtotal = function (idx) {
        const qty  = parseInt(document.querySelector(`input[name="items[${idx}][quantity]"]`).value) || 0;
        const cost = parseFloat(document.querySelector(`input[name="items[${idx}][purchase_price]"]`).value) || 0;
        const el   = document.getElementById(`sp-subtotal-${idx}`);
        if (el) el.textContent = (qty * cost).toFixed(2);
        spCalcGrandTotal();
    };

    window.spCalcGrandTotal = function () {
        let total = 0;
        document.querySelectorAll('[id^="sp-subtotal-"]').forEach(s => total += parseFloat(s.textContent) || 0);
        document.getElementById('sp-grand-total').textContent = `RM ${total.toFixed(2)}`;
    };

    function spBuildProductOptions() {
        let opts = '<option value="">Select Product</option>';
        spProducts.forEach(p => {
            opts += `<option value="${p.id}"
                data-price="${p.price}"
                data-cost="${p.cost_price || 0}"
                data-unit="${p.unit || 'pcs'}"
                data-purchase-unit="${p.purchase_unit || 'Unit'}"
                data-units-per-carton="${p.units_per_carton || 1}"
            >${p.name} (Stock: ${p.stock_quantity})</option>`;
        });
        return opts;
    }

    function spBuildUnitOptions(selected) {
        return spPurchaseUnits.map(u =>
            `<option value="${u}" ${u === selected ? 'selected' : ''}>${u}</option>`
        ).join('');
    }

    window.spOpenNewProductModal = function (rowIdx) {
        spActiveRowIdx = rowIdx;
        document.getElementById('sp-new-product-modal').showModal();
    };

    window.spCloseNewProductModal = function () {
        document.getElementById('sp-inline-product-form').reset();
        document.getElementById('sp-new-product-modal').close();
    };

    window.spSubmitInlineProduct = function (e) {
        e.preventDefault();
        fetch("{{ route('stock-purchases.product-inline') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: new FormData(e.target)
        })
        .then(r => r.ok ? r.json() : r.json().then(err => { throw err; }))
        .then(data => {
            if (data.success) {
                const p = data.product;
                spProducts.push(p);
                const newOpt = `<option value="${p.id}" data-price="${p.price}" data-cost="${p.cost_price||0}" data-unit="${p.unit||'pcs'}" data-purchase-unit="${p.purchase_unit||'Unit'}" data-units-per-carton="${p.units_per_carton||1}">${p.name} (Stock: 0)</option>`;
                document.querySelectorAll('[name$="[product_id]"]').forEach(sel => sel.insertAdjacentHTML('beforeend', newOpt));
                if (spActiveRowIdx !== null) {
                    const sel = document.querySelector(`select[name="items[${spActiveRowIdx}][product_id]"]`);
                    if (sel) { sel.value = p.id; spOnProductChange(sel, spActiveRowIdx); }
                }
                spCloseNewProductModal();
            }
        })
        .catch(err => alert(err.message || 'Failed to create product.'));
    };
}());
</script>
@endpush
