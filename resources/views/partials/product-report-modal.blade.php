{{-- ============================================================
     PRODUCT REPORT & AUDIT TRACE MODAL (EXTRACTED PARTIAL)
     ============================================================ --}}
<dialog id="product-report-modal" class="m-auto max-h-[92vh] w-[calc(100%-2rem)] max-w-5xl overflow-hidden rounded-2xl bg-white p-0 shadow-2xl backdrop:bg-slate-950/40">
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
                    <h2 class="text-lg font-black text-slate-950">Product Report & Activity Audit Trace</h2>
                    <p class="text-xs font-semibold text-slate-400">Comprehensive overview of all products, stock valuation, and logged changes.</p>
                </div>
            </div>

            {{-- Action Buttons: Bottom of Title Section (checkbox, download, print) --}}
            <div class="flex flex-wrap items-center gap-2 pt-0.5">
                {{-- Toggle Include Action & Audit Report --}}
                <label class="inline-flex items-center gap-2 cursor-pointer rounded-xl bg-slate-50 px-3 py-1.5 text-xs font-extrabold text-slate-700 ring-1 ring-slate-200">
                    <input type="checkbox" id="toggle-prod-actions" checked onchange="toggleProdActionSection(this.checked)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Include Action & Audit Report</span>
                </label>

                {{-- Download PDF Report Button --}}
                <a id="btn-download-prod-pdf" href="{{ route('products.report.pdf', ['with_actions' => 1]) }}"
                   class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 text-xs font-extrabold text-white hover:bg-rose-700 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Download PDF
                </a>

                {{-- Print Button (Streams exact PDF layout in browser window) --}}
                <button type="button" onclick="printProductPdfReport()" class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-slate-100 px-3.5 text-xs font-extrabold text-slate-700 hover:bg-slate-200 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </button>
            </div>

            {{-- Close Button (Top right corner like usual) --}}
            <form method="dialog" class="absolute top-4 right-5 m-0">
                <button class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition font-extrabold text-lg" title="Close report dialog">&times;</button>
            </form>
        </div>

        {{-- Body with Tabs --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            {{-- Summary Metric Row --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Total Products</p>
                    <p class="mt-1 text-xl font-black text-slate-900">{{ number_format($stats['total']) }} SKUs</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Total Units in Stock</p>
                    <p class="mt-1 text-xl font-black text-indigo-600">{{ number_format($stats['total_units']) }} units</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Inventory Valuation</p>
                    <p class="mt-1 text-xl font-black text-emerald-600">RM {{ number_format($stats['inventory_value'], 2) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Categories / Uncategorised</p>
                    <p class="mt-1 text-xl font-black text-slate-900">{{ $categoryStats['total'] }} / {{ $uncategorizedCount }}</p>
                </div>
            </div>

            {{-- Activity & Audit Trail Section --}}
            <div id="prod-audit-section" class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Change & Activity Audit Trail</h3>
                        <p class="text-xs text-slate-400">Automatic trace of all additions, updates, and deletions across products and categories.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                        {{ count($activityLogs) }} recent events
                    </span>
                </div>

                <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-100">
                    @forelse ($activityLogs as $log)
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
                                        <span class="capitalize text-slate-500 font-bold">[{{ $log->entity_type }}]</span> {{ $log->entity_name }}
                                    </p>
                                    @if ($log->details)
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $log->details }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right shrink-0 text-[11px] text-slate-400">
                                <span class="font-semibold text-slate-600">{{ $log->admin_name ?: 'Admin' }}</span>
                                <span class="block">{{ $log->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs font-semibold text-slate-400">
                            No logged activity yet. Changes made from this point onwards will be traced here automatically.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Full Inventory Catalogue Table --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Catalogue Inventory Breakdown</h3>
                        <p class="text-xs text-slate-400">Complete item listing with stock quantity, pricing, and app visibility status.</p>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-80 overflow-y-auto rounded-xl border border-slate-100">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-slate-50 text-[11px] font-extrabold uppercase text-slate-400 border-b border-slate-200">
                            <tr>
                                <th class="p-3">SKU</th>
                                <th class="p-3">Product Name</th>
                                <th class="p-3">Category</th>
                                <th class="p-3">Stock</th>
                                <th class="p-3 text-right">Selling Price</th>
                                <th class="p-3 text-right">Total Value</th>
                                <th class="p-3 text-center">App Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            @foreach ($reportProducts as $rp)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3 font-mono text-slate-500 font-bold">{{ $rp->sku }}</td>
                                    <td class="p-3 font-extrabold text-slate-900">{{ $rp->name }}</td>
                                    <td class="p-3">
                                        @if ($rp->category)
                                            <span class="rounded bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700">{{ $rp->category->name }}</span>
                                        @else
                                            <span class="rounded bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">Uncategorised</span>
                                        @endif
                                    </td>
                                    <td class="p-3 font-bold {{ $rp->stock_quantity === 0 ? 'text-rose-600' : ($rp->stock_quantity <= $rp->min_stock_level ? 'text-amber-600' : 'text-slate-800') }}">
                                        {{ $rp->stock_quantity }} {{ $rp->unit ?: 'pcs' }}
                                    </td>
                                    <td class="p-3 text-right font-bold text-slate-900">RM {{ number_format($rp->price, 2) }}</td>
                                    <td class="p-3 text-right font-extrabold text-emerald-600">RM {{ number_format($rp->price * $rp->stock_quantity, 2) }}</td>
                                    <td class="p-3 text-center">
                                        @if ($rp->is_active)
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Published</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">Hidden</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</dialog>

