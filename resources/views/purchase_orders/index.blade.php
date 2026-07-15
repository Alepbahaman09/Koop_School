@extends('layouts.app')

@section('title', 'Purchase Orders')
@section('page-title', 'Purchase Orders')

@section('content')
@include('partials.admin-alerts')

{{-- Page Header --}}
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold text-slate-950">Purchase Orders</h1>
        <p class="mt-1 text-xs font-semibold text-slate-400">Create and manage your cooperative supplier purchase orders.</p>
    </div>
    <div>
        <a href="{{ route('purchase-orders.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700">
            <span class="text-lg">+</span> Create Purchase Order
        </a>
    </div>
</section>

{{-- Search & Filter --}}
<section class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
    <h2 class="mb-3 text-sm font-extrabold text-slate-900">Filter Orders</h2>
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input name="search" value="{{ request('search') }}"
            placeholder="Search by PO number or supplier"
            class="h-9 w-64 flex-none rounded-lg border-slate-200 bg-white text-xs font-semibold focus:border-indigo-400 focus:ring-indigo-200">
        <select name="status" class="h-9 w-40 flex-none rounded-lg border-slate-200 bg-white text-xs font-bold text-slate-600 focus:border-indigo-400">
            <option value="">All statuses</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="partially_received" @selected(request('status') === 'partially_received')>Partially Received</option>
            <option value="received" @selected(request('status') === 'received')>Received</option>
        </select>
        <button class="h-9 flex-none rounded-lg bg-indigo-600 px-4 text-xs font-extrabold text-white hover:bg-indigo-700">Filter</button>
        @if (request()->anyFilled(['search', 'status']))
            <a href="{{ route('purchase-orders.index') }}"
                class="h-9 flex-none rounded-lg bg-slate-100 px-4 text-xs font-extrabold text-slate-600 hover:bg-slate-200 leading-9">
                Clear
            </a>
        @endif
    </form>
</section>

{{-- Purchase Orders Table --}}
<section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs font-bold uppercase text-slate-400">
                    <th class="px-5 py-4">PO Number</th>
                    <th class="px-5 py-4">Supplier</th>
                    <th class="px-5 py-4">Order Date</th>
                    <th class="px-5 py-4">Total Amount</th>
                    <th class="px-5 py-4">Status</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($purchaseOrders as $po)
                    <tr class="group hover:bg-slate-50/60 transition">
                        <td class="px-5 py-4 font-bold text-indigo-600">
                            {{ $po->po_number }}
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-extrabold text-slate-900">{{ $po->supplier->company_name }}</p>
                            <p class="text-[11px] font-semibold text-slate-400">Contact: {{ $po->supplier->contact_person ?: '—' }}</p>
                        </td>
                        <td class="px-5 py-4 text-slate-600 font-semibold">
                            {{ $po->order_date->format('d M Y') }}
                        </td>
                        <td class="px-5 py-4 font-bold text-slate-800">
                            RM {{ number_format($po->total_amount, 2) }}
                        </td>
                        <td class="px-5 py-4">
                            @if ($po->status === 'received')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600 ring-1 ring-emerald-100">
                                    Received
                                </span>
                            @elseif ($po->status === 'partially_received')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-600 ring-1 ring-amber-100">
                                    Partially Received
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-600 ring-1 ring-blue-100">
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('purchase-orders.show', $po) }}"
                                class="inline-flex h-8 items-center gap-1 rounded-lg bg-indigo-50 px-3 text-xs font-extrabold text-indigo-600 hover:bg-indigo-100 transition">
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-20 text-center text-sm font-semibold text-slate-400">
                            No purchase orders found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($purchaseOrders->hasPages())
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $purchaseOrders->links() }}
        </div>
    @endif
</section>
@endsection
