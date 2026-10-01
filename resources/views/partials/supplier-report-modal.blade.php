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

            {{-- Close Button --}}
            <form method="dialog" class="absolute top-4 right-5 m-0">
                <button class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition font-extrabold text-lg" title="Close report dialog">&times;</button>
            </form>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">

            {{-- VIEW 1: ALL SUPPLIERS SUMMARY --}}
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
                                    <span class="block">{{ $log->created_at?->format('d M Y, h:i A') ?? '—' }}</span>
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
                                        <td class="p-3 text-slate-500">{{ $supReport->created_at?->format('d M Y') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- VIEW 2: SELECTED SINGLE SUPPLIER AUDIT --}}
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
                        <div class="rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50/60 to-purple-50/40 p-5">
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
                                    <p class="mt-1 text-[11px] text-slate-400">Registered: {{ $sup->created_at?->format('d M Y') ?? '—' }}</p>
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
                                                        <span class="font-bold text-slate-800">{{ $purchase->purchase_date?->format('d M Y') ?? ($purchase->created_at?->format('d M Y') ?? '—') }}</span>
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
                                            <span class="block">{{ $log->created_at?->format('d M Y, h:i A') ?? '—' }}</span>
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
