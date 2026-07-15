@extends('layouts.app')

@section('title', 'Goods Receipt #' . $goodsReceipt->id)
@section('page-title', 'Goods Receipt #' . $goodsReceipt->id)

@section('content')
<section class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('goods-receipts.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-700">&larr; Back to List</a>
            <h1 class="text-xl font-extrabold text-slate-950 mt-1">Goods Receipt: #{{ $goodsReceipt->id }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('goods-receipts.edit', $goodsReceipt) }}" class="inline-flex h-8 items-center justify-center rounded-lg bg-indigo-600 px-3 text-xs font-extrabold text-white hover:bg-indigo-700 transition">
                Edit Receipt
            </a>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600 ring-1 ring-emerald-100">
                Processed
            </span>
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid gap-4 sm:grid-cols-2">
        {{-- Receipt Metadata --}}
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 space-y-3">
            <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2">Receipt Information</h2>
            <div>
                <p class="text-xs font-bold text-slate-400">Receive Date</p>
                <p class="text-sm font-semibold text-slate-800">{{ $goodsReceipt->receive_date->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400">Received By</p>
                <p class="text-sm font-semibold text-slate-700">{{ $goodsReceipt->received_by }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400">Remarks / Notes</p>
                <p class="text-xs font-semibold text-slate-600 bg-slate-50 p-2 rounded-lg mt-1 border border-slate-100">
                    {{ $goodsReceipt->notes ?: 'No remarks attached to this receipt.' }}
                </p>
            </div>
        </article>

        {{-- Purchase Order Meta --}}
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 space-y-3">
            <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2">Linked Purchase Order</h2>
            <div>
                <p class="text-xs font-bold text-slate-400">PO Number</p>
                <p class="text-sm font-extrabold text-indigo-600">
                    <a href="{{ route('purchase-orders.show', $goodsReceipt->purchaseOrder) }}" class="hover:underline">
                        {{ $goodsReceipt->purchaseOrder->po_number }}
                    </a>
                </p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400">Supplier</p>
                <p class="text-sm font-extrabold text-slate-800">{{ $goodsReceipt->purchaseOrder->supplier->company_name }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400">PO Status</p>
                <p class="text-sm mt-1">
                    @if ($goodsReceipt->purchaseOrder->status === 'received')
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-600 ring-1 ring-emerald-100">
                            Received
                        </span>
                    @elseif ($goodsReceipt->purchaseOrder->status === 'partially_received')
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-600 ring-1 ring-amber-100">
                            Partially Received
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-bold text-blue-600 ring-1 ring-blue-100">
                            Pending
                        </span>
                    @endif
                </p>
            </div>
        </article>
    </div>

    {{-- Received Items --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2 mb-4">Received Items List</h2>
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="text-xs font-bold uppercase text-slate-400 border-b border-slate-100">
                    <th class="py-2 pr-4">Product Name</th>
                    <th class="py-2 text-right">Quantity Received</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($goodsReceipt->receiptItems as $item)
                    <tr class="text-slate-700">
                        <td class="py-3 pr-4 font-bold text-slate-900">
                            {{ $item->product->name }}
                        </td>
                        <td class="py-3 text-right font-extrabold text-slate-800">
                            {{ $item->quantity_received }} units
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection
