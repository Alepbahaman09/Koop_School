@extends('layouts.app')

@section('title', 'Create Purchase Order')
@section('page-title', 'Create Purchase Order')

@section('content')
<section class="max-w-4xl mx-auto">
    <div class="mb-5">
        <a href="{{ route('purchase-orders.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-700">&larr; Back to List</a>
        <h1 class="text-xl font-extrabold text-slate-950 mt-1">Create Purchase Order</h1>
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

    <form method="POST" action="{{ route('purchase-orders.store') }}" class="space-y-6">
        @csrf

        {{-- General PO Information --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 grid gap-4 sm:grid-cols-2">
            <h2 class="sm:col-span-2 text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2">Order Information</h2>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600">Supplier <span class="text-rose-400">*</span></label>
                <select name="supplier_id" required class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200">
                    <option value="">Select Supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->company_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600">PO Number <span class="text-rose-400">*</span></label>
                <input name="po_number" value="{{ old('po_number', $poNumber) }}" required
                    class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
                    placeholder="PO-YYYYMMDD-XXXX">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600">Order Date <span class="text-rose-400">*</span></label>
                <input name="order_date" type="date" value="{{ old('order_date', date('Y-m-d')) }}" required
                    class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200">
            </div>

            <div class="space-y-1 sm:col-span-2">
                <label class="text-xs font-bold text-slate-600">Notes / Instructions</label>
                <textarea name="notes" rows="2"
                    class="w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
                    placeholder="Add any specific instructions or terms for the vendor...">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Dynamic PO Items --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-4">
                <h2 class="text-sm font-extrabold text-slate-900">Order Items</h2>
                <button type="button" onclick="addProductRow()" class="h-8 rounded-lg bg-indigo-50 px-3 text-xs font-extrabold text-indigo-600 hover:bg-indigo-100 transition">
                    + Add Row
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="po-items-table">
                    <thead>
                        <tr class="text-xs font-bold uppercase text-slate-400 border-b border-slate-100">
                            <th class="py-2 pr-4" style="width: 45%;">Product</th>
                            <th class="py-2 pr-4" style="width: 20%;">Qty</th>
                            <th class="py-2 pr-4" style="width: 20%;">Unit Cost (RM)</th>
                            <th class="py-2 pr-4 text-right" style="width: 15%;">Subtotal</th>
                            <th class="py-2" style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {{-- Rows are dynamically inserted here by JS --}}
                    </tbody>
                </table>
            </div>

            {{-- Grand Total Section --}}
            <div class="flex justify-end items-center gap-4 mt-6 border-t border-slate-100 pt-4">
                <span class="text-xs font-bold text-slate-500">Grand Total:</span>
                <span class="text-lg font-extrabold text-slate-950" id="grand-total-display">RM 0.00</span>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('purchase-orders.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-100 px-5 text-sm font-extrabold text-slate-600 hover:bg-slate-200">
                Cancel
            </a>
            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-6 text-sm font-extrabold text-white hover:bg-indigo-700">
                Save Purchase Order
            </button>
        </div>
    </form>
</section>

@push('scripts')
<script>
    let rowIdx = 0;
    const products = @json($products);

    // Initial Row
    document.addEventListener('DOMContentLoaded', () => {
        addProductRow();
    });

    function addProductRow() {
        const tbody = document.querySelector('#po-items-table tbody');
        
        let selectOptions = '<option value="">Select Product</option>';
        products.forEach(p => {
            selectOptions += `<option value="${p.id}" data-price="${p.price}">${p.name} (Stock: ${p.stock_quantity})</option>`;
        });

        const row = document.createElement('tr');
        row.id = `row-${rowIdx}`;
        row.className = 'group';
        row.innerHTML = `
            <td class="py-3 pr-4">
                <select name="items[${rowIdx}][product_id]" required onchange="onProductChange(this, ${rowIdx})" class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-800 focus:border-indigo-400">
                    ${selectOptions}
                </select>
            </td>
            <td class="py-3 pr-4">
                <input name="items[${rowIdx}][quantity_ordered]" type="number" min="1" value="1" required oninput="calculateSubtotal(${rowIdx})" class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-800 focus:border-indigo-400 text-center">
            </td>
            <td class="py-3 pr-4">
                <input name="items[${rowIdx}][unit_cost]" type="number" step="0.01" min="0.01" value="0.00" required oninput="calculateSubtotal(${rowIdx})" class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-800 focus:border-indigo-400 text-right">
            </td>
            <td class="py-3 pr-4 text-right font-bold text-slate-700 text-xs">
                RM <span id="subtotal-${rowIdx}">0.00</span>
            </td>
            <td class="py-3 text-right">
                <button type="button" onclick="removeRow(${rowIdx})" class="h-7 w-7 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-100 flex items-center justify-center transition">
                    &times;
                </button>
            </td>
        `;
        tbody.appendChild(row);
        rowIdx++;
        calculateGrandTotal();
    }

    function removeRow(idx) {
        const row = document.getElementById(`row-${idx}`);
        if (row) {
            row.remove();
            calculateGrandTotal();
        }
    }

    function onProductChange(selectEl, idx) {
        const option = selectEl.options[selectEl.selectedIndex];
        const defaultCostInput = document.querySelector(`input[name="items[${idx}][unit_cost]"]`);
        if (option && option.dataset.price) {
            defaultCostInput.value = parseFloat(option.dataset.price).toFixed(2);
        }
        calculateSubtotal(idx);
    }

    function calculateSubtotal(idx) {
        const qtyInput = document.querySelector(`input[name="items[${idx}][quantity_ordered]"]`);
        const costInput = document.querySelector(`input[name="items[${idx}][unit_cost]"]`);
        const subtotalSpan = document.getElementById(`subtotal-${idx}`);

        const qty = parseInt(qtyInput.value) || 0;
        const cost = parseFloat(costInput.value) || 0;
        const subtotal = qty * cost;

        if (subtotalSpan) {
            subtotalSpan.textContent = subtotal.toFixed(2);
        }
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let total = 0;
        document.querySelectorAll('[id^="subtotal-"]').forEach(span => {
            total += parseFloat(span.textContent) || 0;
        });
        document.getElementById('grand-total-display').textContent = `RM ${total.toFixed(2)}`;
    }
</script>
@endpush
@endsection
