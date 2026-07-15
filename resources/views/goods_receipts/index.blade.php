@extends('layouts.app')

@section('title', 'Goods Receipts')
@section('page-title', 'Goods Receipts')

@section('content')
@include('partials.admin-alerts')

{{-- Page Header --}}
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold text-slate-950">Receive Goods</h1>
        <p class="mt-1 text-xs font-semibold text-slate-400">Receive inventory and record goods receipts from purchase orders.</p>
    </div>
    <div>
        <a href="{{ route('goods-receipts.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700">
            <span class="text-lg">+</span> Receive Goods
        </a>
    </div>
</section>

{{-- Search & Filter --}}
<section class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
    <h2 class="mb-3 text-sm font-extrabold text-slate-900">Filter Receipts</h2>
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input name="search" value="{{ request('search') }}"
            placeholder="Search by PO number or supplier"
            class="h-9 w-64 flex-none rounded-lg border-slate-200 bg-white text-xs font-semibold focus:border-indigo-400 focus:ring-indigo-200">
        <button class="h-9 flex-none rounded-lg bg-indigo-600 px-4 text-xs font-extrabold text-white hover:bg-indigo-700">Filter</button>
        @if (request()->anyFilled(['search']))
            <a href="{{ route('goods-receipts.index') }}"
                class="h-9 flex-none rounded-lg bg-slate-100 px-4 text-xs font-extrabold text-slate-600 hover:bg-slate-200 leading-9">
                Clear
            </a>
        @endif
    </form>
</section>

{{-- Goods Receipts Table --}}
<section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs font-bold uppercase text-slate-400">
                    <th class="px-5 py-4">Receipt ID</th>
                    <th class="px-5 py-4">PO Number</th>
                    <th class="px-5 py-4">Supplier</th>
                    <th class="px-5 py-4">Receive Date</th>
                    <th class="px-5 py-4">Received By</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($goodsReceipts as $receipt)
                    <tr class="group hover:bg-slate-50/60 transition">
                        <td class="px-5 py-4 font-bold text-slate-900">
                            #{{ $receipt->id }}
                        </td>
                        <td class="px-5 py-4 font-bold text-indigo-600">
                            {{ $receipt->purchaseOrder->po_number }}
                        </td>
                        <td class="px-5 py-4 font-extrabold text-slate-900">
                            {{ $receipt->purchaseOrder->supplier->company_name }}
                        </td>
                        <td class="px-5 py-4 text-slate-600 font-semibold">
                            {{ $receipt->receive_date->format('d M Y') }}
                        </td>
                        <td class="px-5 py-4 font-semibold text-slate-700">
                            {{ $receipt->received_by }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('goods-receipts.show', $receipt) }}"
                                class="inline-flex h-8 items-center gap-1 rounded-lg bg-indigo-50 px-3 text-xs font-extrabold text-indigo-600 hover:bg-indigo-100 transition">
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-20 text-center text-sm font-semibold text-slate-400">
                            No goods receipts recorded.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($goodsReceipts->hasPages())
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $goodsReceipts->links() }}
        </div>
    @endif
</section>
@endsection
