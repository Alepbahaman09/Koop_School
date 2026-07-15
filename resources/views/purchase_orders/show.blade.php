@extends('layouts.app')

@section('title', 'Purchase Order ' . $purchaseOrder->po_number)
@section('page-title', 'Purchase Order ' . $purchaseOrder->po_number)

@section('content')
@include('partials.admin-alerts')

<section class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('purchase-orders.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-700">&larr; Back to List</a>
            <h1 class="text-xl font-extrabold text-slate-950 mt-1">Purchase Order: {{ $purchaseOrder->po_number }}</h1>
        </div>
        <div>
            @if ($purchaseOrder->status === 'received')
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600 ring-1 ring-emerald-100">
                    Received
                </span>
            @elseif ($purchaseOrder->status === 'partially_received')
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-600 ring-1 ring-amber-100">
                    Partially Received
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-600 ring-1 ring-blue-100">
                    Pending
                </span>
            @endif
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid gap-4 sm:grid-cols-2">
        {{-- Supplier info --}}
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 space-y-3">
            <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2">Supplier Details</h2>
            <div>
                <p class="text-xs font-bold text-slate-400">Company Name</p>
                <p class="text-sm font-extrabold text-slate-800">{{ $purchaseOrder->supplier->company_name }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400">Contact Person</p>
                <p class="text-sm font-semibold text-slate-700">{{ $purchaseOrder->supplier->contact_person ?: '—' }}</p>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <p class="text-xs font-bold text-slate-400">Email</p>
                    <p class="text-xs font-semibold text-slate-700 truncate">{{ $purchaseOrder->supplier->email ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400">Phone</p>
                    <p class="text-xs font-semibold text-slate-700">{{ $purchaseOrder->supplier->phone ?: '—' }}</p>
                </div>
            </div>
        </article>

        {{-- Order meta details --}}
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 space-y-3">
            <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2">PO Metadata</h2>
            <div>
                <p class="text-xs font-bold text-slate-400">Order Date</p>
                <p class="text-sm font-semibold text-slate-800">{{ $purchaseOrder->order_date->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400">Created By</p>
                <p class="text-sm font-semibold text-slate-700">{{ $purchaseOrder->admin->name ?? 'System' }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400">Internal Notes</p>
                <p class="text-xs font-semibold text-slate-600 bg-slate-50 p-2 rounded-lg mt-1 border border-slate-100">
                    {{ $purchaseOrder->notes ?: 'No notes attached to this order.' }}
                </p>
            </div>
        </article>
    </div>

    {{-- Items list / Receipt input form if not fully received --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2 mb-4">Ordered Items</h2>
        
        @if ($purchaseOrder->status !== 'received')
            <form method="POST" action="{{ route('purchase-orders.receive', $purchaseOrder) }}" class="space-y-6">
                @csrf
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-xs font-bold uppercase text-slate-400 border-b border-slate-100">
                        <th class="py-2 pr-4">Product Name</th>
                        <th class="py-2 pr-4 text-center">Ordered</th>
                        <th class="py-2 pr-4 text-center">Received So Far</th>
                        @if ($purchaseOrder->status !== 'received')
                            <th class="py-2 pr-4 text-center" style="width: 20%;">Receive Now</th>
                        @endif
                        <th class="py-2 pr-4 text-right">Unit Cost</th>
                        <th class="py-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($purchaseOrder->items as $idx => $item)
                        @php
                            $remaining = $item->quantity_ordered - $item->quantity_received;
                        @endphp
                        <tr class="text-slate-700">
                            <td class="py-3 pr-4 font-bold text-slate-900">
                                {{ $item->product->name }}
                                @if ($purchaseOrder->status !== 'received')
                                    <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $item->product_id }}">
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-center font-semibold">
                                {{ $item->quantity_ordered }}
                            </td>
                            <td class="py-3 pr-4 text-center font-semibold">
                                @if ($item->quantity_received >= $item->quantity_ordered)
                                    <span class="text-emerald-600 font-extrabold">{{ $item->quantity_received }}</span>
                                @elseif ($item->quantity_received > 0)
                                    <span class="text-amber-600 font-extrabold">{{ $item->quantity_received }}</span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            @if ($purchaseOrder->status !== 'received')
                                <td class="py-3 pr-4">
                                    <input name="items[{{ $idx }}][qty_to_receive]" type="number" min="0" max="{{ $remaining }}" value="{{ old('items.'.$idx.'.qty_to_receive', $remaining) }}" required
                                        class="h-8 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-800 focus:border-indigo-400 text-center">
                                </td>
                            @endif
                            <td class="py-3 pr-4 text-right font-semibold">
                                RM {{ number_format($item->unit_cost, 2) }}
                            </td>
                            <td class="py-3 text-right font-bold text-slate-900">
                                RM {{ number_format($item->subtotal, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end items-center gap-4 mt-6 border-t border-slate-100 pt-4">
            <span class="text-xs font-bold text-slate-500">PO Grand Total:</span>
            <span class="text-lg font-extrabold text-slate-950">RM {{ number_format($purchaseOrder->total_amount, 2) }}</span>
        </div>

        {{-- Meta receiving fields if status is pending or partially_received --}}
        @if ($purchaseOrder->status !== 'received')
                <div class="mt-6 border-t border-slate-100 pt-4 grid gap-4 sm:grid-cols-2">
                    <h3 class="sm:col-span-2 text-xs font-extrabold text-slate-500 uppercase tracking-wider">Goods Receipt Details</h3>
                    
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-600">Receive Date <span class="text-rose-400">*</span></label>
                        <input name="receive_date" type="date" value="{{ old('receive_date', date('Y-m-d')) }}" required
                            class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-800 focus:border-indigo-400">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-600">Received By</label>
                        <input name="received_by" type="text" value="{{ old('received_by', auth()->user()->name ?? '') }}"
                            class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-800 focus:border-indigo-400"
                            placeholder="e.g. Ahmad Razif">
                    </div>

                    <div class="space-y-1 sm:col-span-2">
                        <label class="text-xs font-bold text-slate-600">Remarks / Receiving Notes</label>
                        <textarea name="notes" rows="2"
                            class="w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-800 focus:border-indigo-400"
                            placeholder="e.g. Items received in perfect condition."></textarea>
                    </div>

                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-6 text-sm font-extrabold text-white hover:bg-indigo-700 shadow-md">
                            Save Goods Receipt & Update Stock
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    {{-- Goods receipts history --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2 mb-4">Receiving History</h2>
        @forelse ($purchaseOrder->goodsReceipts as $receipt)
            <div class="p-4 rounded-xl border border-slate-100 bg-slate-50/50 mb-3 last:mb-0">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-bold text-slate-800 flex items-center gap-2">
                        <span>Receipt ID: #{{ $receipt->id }}</span>
                        <a href="{{ route('goods-receipts.show', $receipt) }}" class="text-[10px] font-extrabold text-indigo-600 hover:underline">View Receipt Page &rarr;</a>
                    </p>
                    <p class="text-xs text-slate-400 font-semibold">Date: {{ $receipt->receive_date->format('d M Y') }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4 text-xs mb-3">
                    <div>
                        <span class="font-bold text-slate-500">Received By:</span> {{ $receipt->received_by }}
                    </div>
                    <div>
                        <span class="font-bold text-slate-500">Notes:</span> {{ $receipt->notes ?: '—' }}
                    </div>
                </div>
                <table class="w-full text-left text-xs bg-white rounded-lg border border-slate-100 overflow-hidden">
                    <thead>
                        <tr class="bg-slate-100 text-slate-500 font-bold uppercase">
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2 text-right">Quantity Received</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($receipt->receiptItems as $rItem)
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700">{{ $rItem->product->name }}</td>
                                <td class="px-3 py-2 text-right font-bold text-slate-950">{{ $rItem->quantity_received }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p class="text-xs font-semibold text-slate-400 py-4 text-center">No goods receipts recorded yet.</p>
        @endforelse
    </div>
</section>
@endsection
