@extends('layouts.app')

@section('title', 'Receive Goods')
@section('page-title', 'Receive Goods')

@section('content')
<section class="max-w-4xl mx-auto">
    <div class="mb-5">
        <a href="{{ route('goods-receipts.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-700">&larr; Back to List</a>
        <h1 class="text-xl font-extrabold text-slate-950 mt-1">Receive Goods</h1>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 p-4 text-xs font-semibold text-rose-600 ring-1 ring-rose-100">
            <p class="font-bold mb-1">Please fix the following validation errors:</p>
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Step 1: Select Purchase Order --}}
    <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 mb-6">
        <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2 mb-4">Select Purchase Order</h2>
        <div class="space-y-1">
            <label class="text-xs font-bold text-slate-600">Purchase Order <span class="text-rose-400">*</span></label>
            <select id="po-select" onchange="onPoSelectChange(this)" class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200">
                <option value="">Select a Purchase Order to Receive</option>
                @foreach ($purchaseOrders as $po)
                    <option value="{{ $po->id }}" @selected(request('purchase_order_id') == $po->id || ($selectedPo && $selectedPo->id == $po->id))>
                        {{ $po->po_number }} - {{ $po->supplier->company_name }} ({{ ucfirst($po->status) }})
                    </option>
                @endforeach
            </select>
        </div>
    </article>

    @if ($selectedPo)
        <form method="POST" action="{{ route('goods-receipts.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ $selectedPo->id }}">

            {{-- Receipt Metadata --}}
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 grid gap-4 sm:grid-cols-2">
                <h2 class="sm:col-span-2 text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2">Receipt Information</h2>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-600">Receive Date <span class="text-rose-400">*</span></label>
                    <input name="receive_date" type="date" value="{{ old('receive_date', date('Y-m-d')) }}" required
                        class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-600">Received By</label>
                    <input name="received_by" type="text" value="{{ old('received_by', auth()->user()->name ?? '') }}"
                        class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
                        placeholder="e.g. Ahmad Razif">
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label class="text-xs font-bold text-slate-600">Receiving Notes / Remarks</label>
                    <textarea name="notes" rows="2"
                        class="w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
                        placeholder="e.g. Received in good condition, minor packaging scratches on Nestlé products..."></textarea>
                </div>
            </div>

            {{-- Ordered Items & Qty Received Inputs --}}
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2 mb-4">Select Quantity to Receive</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs font-bold uppercase text-slate-400 border-b border-slate-100">
                                <th class="py-2 pr-4">Product</th>
                                <th class="py-2 pr-4 text-center">Qty Ordered</th>
                                <th class="py-2 pr-4 text-center">Already Received</th>
                                <th class="py-2 pr-4 text-center" style="width: 25%;">Qty to Receive</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($selectedPo->items as $idx => $item)
                                @php
                                    $remaining = $item->quantity_ordered - $item->quantity_received;
                                @endphp
                                <tr class="text-slate-700">
                                    <td class="py-3 pr-4 font-bold text-slate-900">
                                        {{ $item->product->name }}
                                        <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $item->product_id }}">
                                    </td>
                                    <td class="py-3 pr-4 text-center font-semibold">
                                        {{ $item->quantity_ordered }}
                                    </td>
                                    <td class="py-3 pr-4 text-center font-semibold text-slate-400">
                                        {{ $item->quantity_received }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <input name="items[{{ $idx }}][quantity_received]" type="number" min="0" max="{{ $remaining }}" value="{{ old('items.'.$idx.'.quantity_received', $remaining) }}" required
                                            class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-800 focus:border-indigo-400 text-center">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('goods-receipts.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-100 px-5 text-sm font-extrabold text-slate-600 hover:bg-slate-200">
                    Cancel
                </a>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-6 text-sm font-extrabold text-white hover:bg-indigo-700">
                    Save Goods Receipt & Update Stock
                </button>
            </div>
        </form>
    @else
        <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-slate-400 font-semibold text-sm">
            Please select a Purchase Order from the dropdown above to continue.
        </div>
    @endif
</section>

@push('scripts')
<script>
    function onPoSelectChange(select) {
        const id = select.value;
        if (id) {
            window.location.href = `{{ route('goods-receipts.create') }}?purchase_order_id=${id}`;
        } else {
            window.location.href = `{{ route('goods-receipts.create') }}`;
        }
    }
</script>
@endpush
@endsection
