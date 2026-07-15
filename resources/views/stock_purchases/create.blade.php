@extends('layouts.app')

@section('title', 'New Stock Purchase')
@section('page-title', 'New Stock Purchase')

@section('content')
<section class="max-w-5xl mx-auto">
    <div class="mb-5">
        <a href="{{ route('stock-purchases.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-700">&larr; Back to List</a>
        <h1 class="text-xl font-extrabold text-slate-950 mt-1">New Stock Purchase</h1>
        <p class="text-xs text-slate-500 mt-0.5">Purchase is saved as <span class="font-bold text-amber-600">Pending</span> — stock will only update when you mark it as <span class="font-bold text-emerald-600">Received</span>.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 p-4 text-xs font-semibold text-rose-600 ring-1 ring-rose-100">
            <p class="font-bold mb-1">Please fix the following errors:</p>
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('stock-purchases.store') }}" class="space-y-6">
        @csrf

        {{-- Purchase Details --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 grid gap-4 sm:grid-cols-2">
            <h2 class="sm:col-span-2 text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2">Purchase Information</h2>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600">Supplier <span class="text-rose-400">*</span></label>
                <select name="supplier_id" required class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400">
                    <option value="">Select Supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->company_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600">Purchase Date <span class="text-rose-400">*</span></label>
                <input name="purchase_date" type="date" value="{{ old('purchase_date', date('Y-m-d')) }}" required
                    class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400">
            </div>

            <div class="space-y-1 sm:col-span-2">
                <label class="text-xs font-bold text-slate-600">Notes / Remarks</label>
                <textarea name="notes" rows="2"
                    class="w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 focus:border-indigo-400"
                    placeholder="Add purchase details, invoice references, etc...">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Items --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-sm font-extrabold text-slate-900">Purchase Items</h2>
                <div class="flex gap-2">
                    <button type="button" onclick="openNewProductModal(null)" class="h-8 rounded-lg bg-emerald-50 px-3 text-xs font-extrabold text-emerald-600 hover:bg-emerald-100 transition">
                        + Create New Product
                    </button>
                    <button type="button" onclick="addProductRow()" class="h-8 rounded-lg bg-indigo-50 px-3 text-xs font-extrabold text-indigo-600 hover:bg-indigo-100 transition">
                        + Add Row
                    </button>
                </div>
            </div>

            {{-- Info banner --}}
            <div class="rounded-lg bg-blue-50 px-4 py-2.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                💡 <strong>How it works:</strong> Enter the <em>buy unit</em> (e.g. Carton) and <em>units per buy</em> (e.g. 24 pcs per carton). The system will add <strong>Qty × Units/Buy</strong> to stock when you mark as Received.
                Example: 2 Cartons × 24 pcs = <strong>48 pcs</strong> added to stock.
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="purchase-items-table">
                    <thead>
                        <tr class="text-xs font-bold uppercase text-slate-400 border-b border-slate-100">
                            <th class="py-2 pr-3" style="min-width: 200px;">Product</th>
                            <th class="py-2 pr-3 text-center" style="width: 80px;">Sell Unit</th>
                            <th class="py-2 pr-3" style="width: 110px;">Buy In</th>
                            <th class="py-2 pr-3 text-center" style="width: 80px;">Units/Buy</th>
                            <th class="py-2 pr-3 text-center" style="width: 70px;">Qty</th>
                            <th class="py-2 pr-3 text-center" style="width: 80px;">Total Units</th>
                            <th class="py-2 pr-3 text-right" style="width: 120px;">Purchase Price/Buy (RM)</th>
                            <th class="py-2 pr-3 text-right" style="width: 110px;">Selling Price/Unit (RM)</th>
                            <th class="py-2 pr-3 text-right" style="width: 90px;">Subtotal</th>
                            <th class="py-2" style="width: 32px;"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="items-tbody">
                        {{-- Rows inserted dynamically --}}
                    </tbody>
                </table>
            </div>

            {{-- Grand Total --}}
            <div class="flex justify-end items-center gap-4 mt-4 border-t border-slate-100 pt-4">
                <span class="text-xs font-bold text-slate-500">Grand Total:</span>
                <span class="text-lg font-extrabold text-slate-950" id="grand-total-display">RM 0.00</span>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('stock-purchases.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-100 px-5 text-sm font-extrabold text-slate-600 hover:bg-slate-200">
                Cancel
            </a>
            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-6 text-sm font-extrabold text-white hover:bg-indigo-700">
                Save Purchase (Pending)
            </button>
        </div>
    </form>

    {{-- Create Product Inline Modal --}}
    <dialog id="new-product-modal" class="rounded-xl border border-slate-100 bg-white p-6 shadow-2xl max-w-md w-full backdrop:bg-slate-950/30">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h3 class="text-md font-extrabold text-slate-900">Create New Product</h3>
            <button type="button" onclick="closeNewProductModal()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>
        <form id="inline-product-form" onsubmit="submitInlineProduct(event)" class="space-y-4">
            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600">Product Name <span class="text-rose-400">*</span></label>
                <input name="name" required class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold focus:border-indigo-400" placeholder="e.g. Milo 3-in-1">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600">Category <span class="text-rose-400">*</span></label>
                <select name="category_id" required class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold focus:border-indigo-400">
                    <option value="">Select Category</option>
                    @foreach ($categories as $cat)
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
                    <input name="price" type="number" step="0.01" min="0.01" required class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold focus:border-indigo-400 text-right" placeholder="0.00">
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600">Barcode / SKU (Optional)</label>
                <input name="sku" class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-semibold focus:border-indigo-400" placeholder="Auto-generated if empty">
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeNewProductModal()" class="h-9 rounded-lg bg-slate-100 px-4 text-xs font-extrabold text-slate-600 hover:bg-slate-200">
                    Cancel
                </button>
                <button type="submit" class="h-9 rounded-lg bg-indigo-600 px-5 text-xs font-extrabold text-white hover:bg-indigo-700">
                    Save Product
                </button>
            </div>
        </form>
    </dialog>
</section>

@push('scripts')
<script>
    let rowIdx = 0;
    let products = @json($products);
    const purchaseUnits = @json($purchaseUnits);
    let activeRowSelectIdx = null;

    document.addEventListener('DOMContentLoaded', () => {
        addProductRow();
    });

    function buildPurchaseUnitOptions(selected = 'Unit') {
        return purchaseUnits.map(u =>
            `<option value="${u}" ${u === selected ? 'selected' : ''}>${u}</option>`
        ).join('');
    }

    function buildProductOptions() {
        let opts = '<option value="">Select Product</option>';
        products.forEach(p => {
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

    function addProductRow() {
        const tbody = document.getElementById('items-tbody');
        const idx = rowIdx;

        const row = document.createElement('tr');
        row.id = `row-${idx}`;
        row.className = 'group align-top';
        row.innerHTML = `
            <td class="py-2 pr-3">
                <div class="flex items-center gap-1.5">
                    <select name="items[${idx}][product_id]" required onchange="onProductChange(this, ${idx})"
                        class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-2 text-xs font-semibold text-slate-800 focus:border-indigo-400">
                        ${buildProductOptions()}
                    </select>
                    <button type="button" onclick="openNewProductModal(${idx})"
                        class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 flex items-center justify-center transition flex-shrink-0 font-bold"
                        title="Create New Product">+</button>
                </div>
            </td>
            <td id="sell-unit-${idx}" class="py-2 pr-3 text-center font-extrabold text-slate-500 text-xs align-middle">—</td>
            <td class="py-2 pr-3">
                <select name="items[${idx}][purchase_unit]" onchange="onPurchaseUnitChange(this, ${idx})"
                    class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-2 text-xs font-semibold text-slate-800 focus:border-indigo-400">
                    ${buildPurchaseUnitOptions('Unit')}
                </select>
            </td>
            <td class="py-2 pr-3">
                <input name="items[${idx}][units_per_purchase]" type="number" min="1" value="1" required
                    oninput="updateTotalUnits(${idx})"
                    class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-2 text-xs font-semibold text-slate-800 focus:border-indigo-400 text-center">
            </td>
            <td class="py-2 pr-3">
                <input name="items[${idx}][quantity]" type="number" min="1" value="1" required
                    oninput="updateTotalUnits(${idx}); calculateSubtotal(${idx})"
                    class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-2 text-xs font-semibold text-slate-800 focus:border-indigo-400 text-center">
            </td>
            <td class="py-2 pr-3 text-center align-middle">
                <span id="total-units-${idx}" class="inline-block rounded-md bg-indigo-50 px-2 py-1 text-xs font-extrabold text-indigo-700">1 pcs</span>
            </td>
            <td class="py-2 pr-3">
                <input name="items[${idx}][purchase_price]" type="number" step="0.01" min="0.01" value="0.00" required
                    oninput="calculateSubtotal(${idx})"
                    class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-2 text-xs font-semibold text-slate-800 focus:border-indigo-400 text-right">
            </td>
            <td class="py-2 pr-3">
                <input name="items[${idx}][selling_price]" type="number" step="0.01" min="0.01" value="0.00" required
                    class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-2 text-xs font-semibold text-slate-800 focus:border-indigo-400 text-right">
            </td>
            <td class="py-2 pr-3 text-right font-bold text-slate-700 text-xs align-middle">
                RM <span id="subtotal-${idx}">0.00</span>
            </td>
            <td class="py-2 align-middle">
                <button type="button" onclick="removeRow(${idx})"
                    class="h-7 w-7 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-100 flex items-center justify-center transition">
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
        if (row) { row.remove(); calculateGrandTotal(); }
    }

    function onProductChange(selectEl, idx) {
        const option = selectEl.options[selectEl.selectedIndex];
        if (option && option.value) {
            const purchaseUnitSelect = document.querySelector(`select[name="items[${idx}][purchase_unit]"]`);
            const unitsPerPurchaseInput = document.querySelector(`input[name="items[${idx}][units_per_purchase]"]`);
            const purchasePriceInput = document.querySelector(`input[name="items[${idx}][purchase_price]"]`);
            const sellingPriceInput = document.querySelector(`input[name="items[${idx}][selling_price]"]`);
            const sellUnitCell = document.getElementById(`sell-unit-${idx}`);

            const savedPurchaseUnit = option.dataset.purchaseUnit || 'Unit';
            const savedUnitsPerCarton = parseInt(option.dataset.unitsPerCarton) || 1;

            sellUnitCell.textContent = option.dataset.unit || 'pcs';
            purchaseUnitSelect.value = savedPurchaseUnit;
            unitsPerPurchaseInput.value = savedUnitsPerCarton;
            purchasePriceInput.value = parseFloat(option.dataset.cost || 0).toFixed(2);
            sellingPriceInput.value = parseFloat(option.dataset.price || 0).toFixed(2);
        } else {
            document.getElementById(`sell-unit-${idx}`).textContent = '—';
        }
        updateTotalUnits(idx);
        calculateSubtotal(idx);
    }

    function onPurchaseUnitChange(selectEl, idx) {
        updateTotalUnits(idx);
    }

    function updateTotalUnits(idx) {
        const qty = parseInt(document.querySelector(`input[name="items[${idx}][quantity]"]`).value) || 0;
        const upu = parseInt(document.querySelector(`input[name="items[${idx}][units_per_purchase]"]`).value) || 1;
        const sellUnit = document.getElementById(`sell-unit-${idx}`).textContent.trim();
        const totalEl = document.getElementById(`total-units-${idx}`);
        const display = sellUnit && sellUnit !== '—' ? sellUnit : 'pcs';
        if (totalEl) totalEl.textContent = `${qty * upu} ${display}`;
    }

    function calculateSubtotal(idx) {
        const qty = parseInt(document.querySelector(`input[name="items[${idx}][quantity]"]`).value) || 0;
        const cost = parseFloat(document.querySelector(`input[name="items[${idx}][purchase_price]"]`).value) || 0;
        const subtotalSpan = document.getElementById(`subtotal-${idx}`);
        if (subtotalSpan) subtotalSpan.textContent = (qty * cost).toFixed(2);
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let total = 0;
        document.querySelectorAll('[id^="subtotal-"]').forEach(span => {
            total += parseFloat(span.textContent) || 0;
        });
        document.getElementById('grand-total-display').textContent = `RM ${total.toFixed(2)}`;
    }

    // Modal helpers
    function openNewProductModal(rowSelectIdx) {
        activeRowSelectIdx = rowSelectIdx;
        document.getElementById('new-product-modal').showModal();
    }

    function closeNewProductModal() {
        document.getElementById('inline-product-form').reset();
        document.getElementById('new-product-modal').close();
    }

    function submitInlineProduct(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        fetch("{{ route('stock-purchases.product-inline') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                "Accept": "application/json"
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) return response.json().then(err => { throw err; });
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const p = data.product;
                products.push(p);

                // Add to all product selects
                document.querySelectorAll('[name$="[product_id]"]').forEach(select => {
                    const option = document.createElement('option');
                    option.value = p.id;
                    option.textContent = `${p.name} (Stock: 0)`;
                    option.dataset.price = p.price;
                    option.dataset.cost = p.cost_price || 0;
                    option.dataset.unit = p.unit || 'pcs';
                    option.dataset.purchaseUnit = p.purchase_unit || 'Unit';
                    option.dataset.unitsPerCarton = p.units_per_carton || 1;
                    select.appendChild(option);
                });

                // Auto-select on the row that triggered the modal
                if (activeRowSelectIdx !== null) {
                    const select = document.querySelector(`select[name="items[${activeRowSelectIdx}][product_id]"]`);
                    if (select) {
                        select.value = p.id;
                        onProductChange(select, activeRowSelectIdx);
                    }
                }

                closeNewProductModal();
            }
        })
        .catch(err => {
            alert(err.message || "Failed to create product. Ensure product name is unique.");
        });
    }
</script>
@endpush
@endsection
