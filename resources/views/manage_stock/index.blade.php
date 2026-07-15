@extends('layouts.app')

@section('title', 'Manage Stock')
@section('page-title', 'Manage Stock')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Page Header --}}
    <div>
        <h1 class="text-xl font-extrabold text-slate-950">Manage Stock</h1>
        <p class="text-xs text-slate-500 mt-0.5">Manually adjust stock quantities — for stocktake corrections, damaged goods, opening stock, etc.</p>
    </div>

    @if (session('success'))
        <div class="rounded-xl bg-emerald-50 p-4 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl bg-rose-50 p-4 text-xs font-semibold text-rose-700 ring-1 ring-rose-100">
            ❌ {{ session('error') }}
        </div>
    @endif

    {{-- Adjustment Form --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-3 mb-5">New Stock Adjustment</h2>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-rose-50 p-3 text-xs font-semibold text-rose-600 ring-1 ring-rose-100">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('manage-stock.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf

            {{-- Product --}}
            <div class="space-y-1 sm:col-span-2">
                <label class="text-xs font-bold text-slate-600">Product <span class="text-rose-400">*</span></label>
                <select name="product_id" required onchange="updateStockPreview(this)"
                    class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400">
                    <option value="">Select a product...</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}"
                            data-stock="{{ $product->stock_quantity }}"
                            data-unit="{{ $product->unit }}"
                            @selected(old('product_id') == $product->id)>
                            {{ $product->name }} — Current Stock: {{ $product->stock_quantity }} {{ $product->unit }}
                        </option>
                    @endforeach
                </select>
                <p id="current-stock-label" class="text-xs text-slate-400 mt-1 hidden">
                    Current stock: <span id="current-stock-val" class="font-bold text-slate-700"></span>
                </p>
            </div>

            {{-- Type --}}
            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600">Adjustment Type <span class="text-rose-400">*</span></label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="type" value="add" @checked(old('type', 'add') === 'add')
                            class="accent-emerald-600">
                        <span class="text-xs font-extrabold text-emerald-700">➕ Add Stock</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="type" value="deduct" @checked(old('type') === 'deduct')
                            class="accent-rose-500">
                        <span class="text-xs font-extrabold text-rose-600">➖ Deduct Stock</span>
                    </label>
                </div>
            </div>

            {{-- Quantity --}}
            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600">Quantity <span class="text-rose-400">*</span></label>
                <input name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" required
                    class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 text-center">
            </div>

            {{-- Reason --}}
            <div class="space-y-1 sm:col-span-2">
                <label class="text-xs font-bold text-slate-600">Reason <span class="text-rose-400">*</span></label>
                <input name="reason" required value="{{ old('reason') }}"
                    class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400"
                    placeholder="e.g. Stocktake correction, Damaged goods, Opening stock...">
            </div>

            <div class="sm:col-span-2 flex justify-end">
                <button type="submit"
                    class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-6 text-sm font-extrabold text-white hover:bg-indigo-700 transition">
                    Apply Adjustment
                </button>
            </div>
        </form>
    </div>

    {{-- Adjustment History --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-sm font-extrabold text-slate-900">Adjustment History</h2>
        </div>

        @if ($history->isEmpty())
            <div class="py-16 text-center text-sm font-semibold text-slate-400">
                No manual adjustments recorded yet.
            </div>
        @else
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-xs font-bold uppercase text-slate-400 border-b border-slate-100">
                        <th class="px-5 py-3">Date & Time</th>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3 text-center">Type</th>
                        <th class="px-5 py-3 text-center">Qty</th>
                        <th class="px-5 py-3 text-center">Before</th>
                        <th class="px-5 py-3 text-center">After</th>
                        <th class="px-5 py-3">Reason</th>
                        <th class="px-5 py-3">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($history as $log)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3 text-xs text-slate-500 font-semibold whitespace-nowrap">
                                {{ $log->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="px-5 py-3 font-bold text-slate-900">
                                {{ $log->product?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if ($log->type === 'In')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-600 ring-1 ring-emerald-100">
                                        ➕ Add
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-bold text-rose-600 ring-1 ring-rose-100">
                                        ➖ Deduct
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center font-bold text-slate-700">{{ $log->quantity }}</td>
                            <td class="px-5 py-3 text-center text-slate-500 font-semibold">{{ $log->stock_before }}</td>
                            <td class="px-5 py-3 text-center font-bold text-slate-900">{{ $log->stock_after }}</td>
                            <td class="px-5 py-3 text-xs text-slate-600 font-semibold">{{ $log->notes }}</td>
                            <td class="px-5 py-3 text-xs text-slate-500 font-semibold">{{ $log->admin?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($history->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $history->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

@push('scripts')
<script>
    function updateStockPreview(select) {
        const option = select.options[select.selectedIndex];
        const label = document.getElementById('current-stock-label');
        const val = document.getElementById('current-stock-val');
        if (option && option.value) {
            val.textContent = `${option.dataset.stock} ${option.dataset.unit}`;
            label.classList.remove('hidden');
        } else {
            label.classList.add('hidden');
        }
    }
</script>
@endpush
@endsection
