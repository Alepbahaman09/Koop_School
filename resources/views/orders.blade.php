@extends('layouts.app')

@section('title', 'Orders')
@section('page-title', 'Orders')

@section('content')
@php
    $statusClass = [
        'Processing' => 'bg-sky-50 text-sky-700',
        'Ready' => 'bg-violet-50 text-violet-700',
        'Completed' => 'bg-emerald-50 text-emerald-700',
        'Cancelled' => 'bg-rose-50 text-rose-700',
    ];

    // Colors for payment method badges
    $paymentMethodClass = [
        'Cash' => 'bg-amber-50 text-amber-700',
        'NFC' => 'bg-blue-50 text-blue-700',
        'Online' => 'bg-purple-50 text-purple-700',
    ];
@endphp

{{-- Page header --}}
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold">Orders</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">View customer orders and update their status from this page.</p>
    </div>
</section>

{{-- Stats cards: Total Orders, In Progress, Completed, Cancelled --}}
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['Total Orders', 'total', $stats['total'], 'text-indigo-600'],
        ['In Progress', 'in_progress', $stats['in_progress'], 'text-sky-600'],
        ['Completed', 'completed', $stats['completed'], 'text-emerald-600'],
        ['Cancelled', 'cancelled', $stats['cancelled'], 'text-rose-600'],
    ] as [$label, $key, $value, $tone])
        <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <p class="text-sm font-bold text-slate-400">{{ $label }}</p>
            <p data-order-stat="{{ $key }}" class="mt-2 text-2xl font-extrabold {{ $tone }}">{{ number_format($value) }}</p>
        </article>
    @endforeach
</section>

{{-- Orders table section --}}
<section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">

    {{-- Filter form --}}
    <form method="GET" class="mb-5 grid gap-3 lg:grid-cols-[1fr_13rem_13rem_auto] lg:items-end">

        {{-- Search box --}}
        <label class="text-xs font-extrabold uppercase text-slate-400">Search
            <input name="search" value="{{ request('search') }}" placeholder="Order number, parent, or student" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
        </label>

        {{-- Order status filter --}}
        <label class="text-xs font-extrabold uppercase text-slate-400">Order Status
            <select name="status" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </label>

        {{-- Payment method filter: Cash, NFC, Online --}}
        <label class="text-xs font-extrabold uppercase text-slate-400">Payment
            <select name="payment_method" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
                <option value="">All payments</option>
                @foreach ($paymentMethods as $method)
                    <option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ $method }}</option>
                @endforeach
            </select>
        </label>

        {{-- Filter and clear buttons --}}
        <div class="flex gap-2">
            <button class="h-10 rounded-lg bg-slate-900 px-5 text-sm font-extrabold text-white">Filter</button>
            <a href="{{ route('orders.index') }}" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-slate-500 ring-1 ring-slate-200">Clear</a>
        </div>
    </form>

    {{-- Bulk Action Banner (appears when orders are selected) --}}
    <div id="bulkBanner" class="hidden mb-4 rounded-xl border border-indigo-100 bg-indigo-50/70 p-3.5 text-indigo-950 shadow-xs transition-all">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-7 items-center justify-center rounded-md bg-indigo-600 px-2.5 text-xs font-black text-white">
                    <span id="bulkBannerCount">0</span>&nbsp;selected
                </span>
                <p class="text-sm font-bold text-indigo-950">Assign status to selected orders:</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="hidden md:flex items-center gap-1.5 mr-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Quick:</span>
                    <button type="button" data-bulk-quick="Ready" class="bulk-quick-btn h-8 rounded-md bg-violet-100 px-2.5 text-xs font-extrabold text-violet-700 hover:bg-violet-200 transition">Ready</button>
                    <button type="button" data-bulk-quick="Completed" class="bulk-quick-btn h-8 rounded-md bg-emerald-100 px-2.5 text-xs font-extrabold text-emerald-700 hover:bg-emerald-200 transition">Complete</button>
                    <button type="button" data-bulk-quick="Processing" class="bulk-quick-btn h-8 rounded-md bg-sky-100 px-2.5 text-xs font-extrabold text-sky-700 hover:bg-sky-200 transition">Processing</button>
                    <button type="button" data-bulk-quick="Cancelled" class="bulk-quick-btn h-8 rounded-md bg-rose-100 px-2.5 text-xs font-extrabold text-rose-700 hover:bg-rose-200 transition">Cancel</button>
                </div>
                <select id="bulkStatusSelectTop" class="h-9 rounded-lg border-indigo-200 bg-white px-3 text-xs font-bold text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="" disabled selected>Choose status...</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
                <button type="button" class="bulk-apply-btn inline-flex h-9 items-center justify-center rounded-lg bg-indigo-600 px-4 text-xs font-black text-white shadow-xs hover:bg-indigo-700 active:scale-95 transition">
                    Assign
                </button>
                <button type="button" class="bulk-clear-btn inline-flex h-9 items-center justify-center rounded-lg border border-indigo-200 bg-white px-3 text-xs font-bold text-indigo-700 hover:bg-indigo-100/60 active:scale-95 transition">
                    Clear
                </button>
            </div>
        </div>
    </div>

    {{-- Orders table --}}
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1050px] text-left text-sm">
            <thead><tr class="border-b border-slate-100 text-xs font-extrabold uppercase text-slate-400">
                <th class="w-10 py-3 pl-4 pr-2 text-center">
                    <input type="checkbox" id="selectAllOrders" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" title="Select all online orders">
                </th>
                <th class="py-3 pr-4">Order</th><th class="py-3 pr-4">Customer</th><th class="py-3 pr-4">Items</th>
                <th class="py-3 pr-4">Total</th><th class="py-3 pr-4">Payment</th><th class="py-3 pr-4">Status</th><th class="py-3 pr-4">Action</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($orders as $order)
                @php
                    // ─── Figure out the payment method and customer label ───
                    $terminalPayment = $order->terminalPayments->first();
                    $regularPayment = $order->payments->first();

                    if ($terminalPayment && $terminalPayment->payment_method === 'Cash') {
                        // Kiosk POS cash sale
                        $cashUserName = $terminalPayment->user?->name
                            ?? $terminalPayment->user?->username
                            ?? $order->user?->name
                            ?? $order->user?->username
                            ?? ($order->customer && $order->customer->parent_name !== 'Walk-in Parent' ? $order->customer->parent_name : null);
                        $customerLabel = $cashUserName ? ($cashUserName . ' - Cash') : ('Cash-' . $terminalPayment->id);
                        $paymentMethod = 'Cash';

                    } elseif ($terminalPayment && $terminalPayment->payment_method === 'NFC Card') {
                        // Kiosk POS NFC card sale — show "{user name} - {card owner name}"
                        $nfcUserName = $terminalPayment->user?->name
                            ?? $terminalPayment->user?->username
                            ?? 'Unknown';
                        $cardOwnerName = $terminalPayment->card?->owner ?? 'Unknown card';
                        $customerLabel = $nfcUserName . ' - ' . $cardOwnerName;
                        $paymentMethod = 'NFC';

                    } elseif ($regularPayment) {
                        // Online / mobile app sale — show "{parent name} - {student name}"
                        $parentName = $order->user?->name
                            ?? $order->user?->username
                            ?? $order->customer?->parent_name
                            ?? 'Unknown';
                        $childName = $order->student?->name
                            ?? $order->customer?->student_name
                            ?? 'Unknown student';
                        $customerLabel = $parentName . ' - ' . $childName;
                        $paymentMethod = 'Online';

                    } else {
                        // Kiosk order that has no payment record yet
                        $customerLabel = 'Kiosk-' . $order->id;
                        $paymentMethod = '-';
                    }

                    $isKioskPayment = (bool) ($terminalPayment || str_starts_with($order->order_number, 'POS-') || str_starts_with($order->order_number, 'KIOSK-') || in_array($paymentMethod, ['Cash', 'NFC'], true) || str_contains(strtolower($order->notes ?? ''), 'pos') || str_contains(strtolower($order->notes ?? ''), 'kiosk'));

                    // For the detail panel sidebar
                    $studentLabel = $order->student?->name
                        ? $order->student->name . ($order->student->class ? ' (' . $order->student->class . ')' : '')
                        : ($order->customer?->student_name && $order->customer->student_name !== '-'
                            ? $order->customer->student_name . ($order->customer->class && $order->customer->class !== '-' ? ' (' . $order->customer->class . ')' : '')
                            : 'Not recorded');

                    // Parent account name for the detail panel
                    $parentAccountName = $order->user?->username
                        ?? $order->user?->name
                        ?? $order->customer?->parent_name
                        ?? 'Unknown user';
                @endphp
                <tr data-order-row="{{ $order->id }}" class="align-top transition-colors">

                    {{-- Row selection checkbox (online payments only; kiosk payments disabled) --}}
                    @if (! $isKioskPayment)
                        <td data-select-cell class="w-10 py-4 pl-4 pr-2 text-center cursor-pointer">
                            <input type="checkbox" class="order-select-checkbox h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" value="{{ $order->id }}">
                        </td>
                    @else
                        <td class="w-10 py-4 pl-4 pr-2 text-center">
                            <input type="checkbox" disabled class="h-4 w-4 rounded border-slate-200 bg-slate-100 text-slate-300 opacity-40 cursor-not-allowed" title="Disabled for kiosk orders">
                        </td>
                    @endif

                    {{-- Order number and date --}}
                    <td class="py-4 pr-4">
                        @if ($isKioskPayment || str_starts_with($order->order_number, 'POS-') || str_starts_with($order->order_number, 'KIOSK-'))
                            <p class="font-extrabold text-slate-900">Kiosk-{{ $order->id }}</p>
                        @else
                            <p class="font-extrabold text-slate-900">{{ $order->order_number }}</p>
                        @endif
                        <p class="text-xs font-bold text-slate-400">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                    </td>

                    {{-- Customer label (changes based on payment method) --}}
                    <td class="py-4 pr-4">
                        <p class="font-bold text-slate-700">{{ $customerLabel }}</p>
                    </td>

                    {{-- Item count --}}
                    <td class="py-4 pr-4 font-semibold text-slate-600">{{ $order->orderItems->sum('quantity') }} item(s)</td>

                    {{-- Total amount --}}
                    <td class="py-4 pr-4 font-extrabold">RM {{ number_format($order->total_amount, 2) }}</td>

                    {{-- Payment method badge --}}
                    <td class="py-4 pr-4">
                        <span data-order-payment class="rounded-full px-3 py-1 text-xs font-extrabold {{ $paymentMethodClass[$paymentMethod] ?? 'bg-slate-100 text-slate-600' }}">{{ $paymentMethod }}</span>
                    </td>

                    {{-- Order status badge --}}
                    <td class="py-4 pr-4"><span data-order-status class="rounded-full px-3 py-1 text-xs font-extrabold {{ $statusClass[$order->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $order->status }}</span></td>

                    {{-- Manage button and detail modal (hidden for kiosk payments) --}}
                    <td class="py-4 pr-4">
                        @if (! $isKioskPayment)
                        <details>
                            <summary class="cursor-pointer list-none font-extrabold text-indigo-600">Manage</summary>
                            <div class="fixed inset-0 z-40 bg-slate-950/30"></div>
                            <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-4xl overflow-y-auto rounded-lg bg-white p-6 text-left shadow-2xl">

                                {{-- Modal header --}}
                                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h2 class="text-lg font-extrabold">
                                            @if ($isKioskPayment || str_starts_with($order->order_number, 'POS-') || str_starts_with($order->order_number, 'KIOSK-'))
                                                Kiosk-{{ $order->id }}
                                            @else
                                                {{ $order->order_number }}
                                            @endif
                                        </h2>
                                        <p class="text-sm font-medium text-slate-500">Customer: {{ $customerLabel }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('orders.receipt', $order) }}?print=1" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-extrabold text-slate-700 shadow-xs hover:bg-slate-50 hover:border-slate-300 transition">
                                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                            Print Receipt
                                        </a>
                                        <span data-order-status class="rounded-full px-3 py-1 text-xs font-extrabold {{ $statusClass[$order->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $order->status }}</span>
                                        <button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500 hover:bg-slate-200" title="Close">&times;</button>
                                    </div>
                                </div>

                                {{-- Order items and sidebar --}}
                                <div class="grid gap-5 lg:grid-cols-[1fr_20rem]">

                                    {{-- Left side: order items --}}
                                    <div>
                                        <div class="mb-3 flex items-center justify-between">
                                            <h3 class="text-sm font-extrabold text-slate-700">Order Items</h3>
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-extrabold text-slate-600">{{ $order->orderItems->sum('quantity') }} item(s)</span>
                                        </div>
                                        <div class="space-y-3">
                                            @foreach ($order->orderItems as $item)
                                                <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                                        <div>
                                                            <p class="text-base font-extrabold text-slate-900">{{ $item->product?->name ?? 'Deleted product' }}</p>
                                                            <p class="mt-1 text-sm font-semibold text-slate-500">Quantity: {{ $item->quantity }} &middot; Item price: RM {{ number_format($item->unit_price, 2) }}</p>
                                                        </div>
                                                        @if ($item->size)
                                                            <span class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-extrabold text-white shadow-sm">
                                                                <span class="text-indigo-200">SIZE</span>
                                                                <span class="text-base leading-none">{{ $item->size }}</span>
                                                            </span>
                                                        @else
                                                            <span class="rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-slate-500 ring-1 ring-slate-200">No size</span>
                                                        @endif
                                                    </div>

                                                    <p class="mt-4 border-t border-slate-200 pt-3 text-right text-sm font-bold text-slate-500">
                                                        Total: <span class="text-base font-extrabold text-indigo-700">RM {{ number_format($item->subtotal, 2) }}</span>
                                                    </p>
                                                </article>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Right side: customer info and status update --}}
                                    <div class="space-y-4">

                                        {{-- Customer and order info --}}
                                        <div class="rounded-lg bg-slate-50 p-4">
                                            <p class="text-xs font-extrabold uppercase text-slate-400">Purchase For</p>
                                            <p class="mt-2 font-bold text-indigo-700">{{ $studentLabel }}</p>
                                            <p class="mt-3 text-xs font-extrabold uppercase text-slate-400">Customer</p>
                                            <p class="mt-1 font-bold">{{ $customerLabel }}</p>
                                            <p class="mt-3 text-xs font-extrabold uppercase text-slate-400">Payment Method</p>
                                            <p class="mt-1 font-bold">{{ $paymentMethod }}</p>
                                            <p class="text-sm text-slate-500">{{ $order->user?->phone_number ?: $order->customer?->phone }} &middot; {{ $order->user?->email ?: $order->customer?->email }}</p>
                                            <p class="mt-2 text-sm text-slate-500">{{ $order->customer?->address }}</p>
                                        </div>

                                        {{-- Status update form --}}
                                        <form data-status-form method="POST" action="{{ route('orders.updateStatus', $order) }}" class="rounded-lg bg-slate-50 p-4">
                                            @csrf @method('PATCH')
                                            <label class="text-xs font-extrabold uppercase text-slate-400">Update Status
                                                <select name="status" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
                                                    @foreach ($statuses as $status)
                                                        <option value="{{ $status }}" @selected($order->status === $status)>{{ $status }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label class="mt-3 block text-xs font-extrabold uppercase text-slate-400">Admin Notes
                                                <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border-slate-200 text-sm" placeholder="Optional update note"></textarea>
                                            </label>
                                            <button class="mt-3 h-10 w-full rounded-lg bg-indigo-600 text-sm font-extrabold text-white">Save Status</button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Payment summary and status history --}}
                                <div class="mt-5 grid gap-5 lg:grid-cols-2">

                                    {{-- Payment summary --}}
                                    <div class="rounded-lg ring-1 ring-slate-100">
                                        <div class="border-b border-slate-100 p-3 text-sm font-extrabold text-slate-500">Payment Summary</div>
                                        <div class="p-3 text-sm">
                                            <p>Total: <span class="font-extrabold">RM {{ number_format($order->total_amount, 2) }}</span></p>
                                            <p>Paid: <span class="font-extrabold">RM {{ number_format($order->completed_payments_total ?? 0, 2) }}</span></p>
                                            <p>Method: <span class="font-extrabold">{{ $paymentMethod }}</span></p>
                                            <p>Status: <span class="font-extrabold">{{ $order->payment_status }}</span></p>
                                        </div>
                                    </div>

                                    {{-- Status history --}}
                                    <div class="rounded-lg ring-1 ring-slate-100">
                                        <div class="border-b border-slate-100 p-3 text-sm font-extrabold text-slate-500">Status History</div>
                                        <div data-status-history class="max-h-44 overflow-y-auto p-3 text-sm">
                                            @forelse ($order->statusHistory as $history)
                                                <p class="mb-2"><span class="font-extrabold">{{ $history->status }}</span> by {{ $history->admin?->name ?? $history->user?->name ?? 'System' }} <span class="text-slate-400">{{ $history->created_at->diffForHumans() }}</span></p>
                                            @empty
                                                <p data-empty-history class="font-semibold text-slate-400">No status updates yet.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </details>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-12 text-center font-semibold text-slate-400">No orders match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
</section>

{{-- Floating Bulk Actions Dock (visible when orders are selected and scrolled) --}}
<div id="bulkFloatingBar" class="fixed bottom-6 inset-x-0 mx-auto w-fit max-w-[95vw] z-50 hidden transition-all duration-300">
    <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-slate-900/95 backdrop-blur-md px-5 py-3 text-white shadow-2xl ring-1 ring-white/10">
        <div class="flex items-center gap-2 border-r border-slate-700/80 pr-3">
            <span class="grid h-6 w-6 place-items-center rounded-full bg-indigo-500 text-xs font-black text-white" id="bulkFloatingCount">0</span>
            <span class="text-sm font-bold text-slate-200">Selected</span>
        </div>
        <div class="hidden sm:flex items-center gap-1.5 border-r border-slate-700/80 pr-3">
            <button type="button" data-bulk-quick="Ready" class="bulk-quick-btn h-8 rounded-lg bg-violet-500/20 text-violet-300 hover:bg-violet-500/30 px-2.5 text-xs font-extrabold transition">Ready</button>
            <button type="button" data-bulk-quick="Completed" class="bulk-quick-btn h-8 rounded-lg bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 px-2.5 text-xs font-extrabold transition">Complete</button>
            <button type="button" data-bulk-quick="Processing" class="bulk-quick-btn h-8 rounded-lg bg-sky-500/20 text-sky-300 hover:bg-sky-500/30 px-2.5 text-xs font-extrabold transition">Processing</button>
            <button type="button" data-bulk-quick="Cancelled" class="bulk-quick-btn h-8 rounded-lg bg-rose-500/20 text-rose-300 hover:bg-rose-500/30 px-2.5 text-xs font-extrabold transition">Cancel</button>
        </div>
        <div class="flex items-center gap-2">
            <select id="bulkStatusSelectBottom" class="h-9 rounded-lg bg-slate-800 border-slate-700 text-xs font-extrabold text-white focus:border-indigo-500 focus:ring-indigo-500">
                <option value="" disabled selected>Assign status...</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
            <button type="button" class="bulk-apply-btn inline-flex h-9 items-center gap-1.5 rounded-lg bg-indigo-600 px-4 text-xs font-black text-white shadow hover:bg-indigo-500 active:scale-95 transition">
                Apply
            </button>
        </div>
        <div class="border-l border-slate-700/80 pl-3">
            <button type="button" class="bulk-clear-btn text-xs font-bold text-slate-400 hover:text-white transition">
                Clear
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const snapshotUrl = @json(route('orders.snapshot'));
    const statusClasses = @json($statusClass);
    const statusToneClasses = Object.values(statusClasses).flatMap((classes) => classes.split(' '));
    const defaultStatusClasses = ['bg-slate-100', 'text-slate-600'];
    let snapshotLoading = false;

    function updateStats(stats) {
        Object.entries(stats).forEach(([key, value]) => {
            const element = document.querySelector(`[data-order-stat="${key}"]`);
            if (element) {
                element.textContent = Number(value).toLocaleString();
            }
        });
    }

    function updateStatus(row, status, forceFormUpdate = false) {
        const classes = (statusClasses[status] ?? defaultStatusClasses.join(' ')).split(' ');

        row.querySelectorAll('[data-order-status]').forEach((badge) => {
            badge.classList.remove(...statusToneClasses, ...defaultStatusClasses);
            badge.classList.add(...classes);
            badge.textContent = status;
        });

        const form = row.querySelector('[data-status-form]');
        const select = form?.querySelector('select[name="status"]');
        if (select && (forceFormUpdate || form.dataset.edited !== 'true')) {
            select.value = status;
        }
    }

    function addHistory(row, history) {
        const container = row.querySelector('[data-status-history]');
        if (! container) {
            return;
        }

        container.querySelector('[data-empty-history]')?.remove();

        const entry = document.createElement('p');
        entry.className = 'mb-2';

        const status = document.createElement('span');
        status.className = 'font-extrabold';
        status.textContent = history.status;

        const time = document.createElement('span');
        time.className = 'text-slate-400';
        time.textContent = history.updated_at;

        entry.append(status, ` by ${history.updated_by} `, time);
        container.prepend(entry);
    }

    document.querySelectorAll('[data-status-form]').forEach((form) => {
        form.querySelector('select[name="status"]')?.addEventListener('change', () => {
            form.dataset.edited = 'true';
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const row = form.closest('[data-order-row]');
            const button = form.querySelector('button[type="submit"], button:not([type])');
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Saving...';
            form.dataset.edited = 'true';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });
                const data = await response.json();

                if (! response.ok) {
                    const validationMessage = data.errors
                        ? Object.values(data.errors).flat()[0]
                        : data.message;
                    throw new Error(validationMessage || 'Failed to update order status.');
                }

                delete form.dataset.edited;
                updateStatus(row, data.order.status, true);
                updateStats(data.stats);
                if (data.history) {
                    addHistory(row, data.history);
                }
                form.querySelector('textarea[name="notes"]').value = '';
                window.showToast(data.message);
            } catch (error) {
                window.showToast(error.message, 'error');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    });

    // ─── Bulk Order Selection & Status Assignment ───
    const bulkUrl = @json(route('orders.bulkUpdateStatus'));
    const csrfToken = @json(csrf_token());

    const selectAll = document.getElementById('selectAllOrders');
    const banner = document.getElementById('bulkBanner');
    const bannerCount = document.getElementById('bulkBannerCount');
    const floatingBar = document.getElementById('bulkFloatingBar');
    const floatingCount = document.getElementById('bulkFloatingCount');
    const selectTop = document.getElementById('bulkStatusSelectTop');
    const selectBottom = document.getElementById('bulkStatusSelectBottom');
    const applyButtons = document.querySelectorAll('.bulk-apply-btn');
    const clearButtons = document.querySelectorAll('.bulk-clear-btn');
    const quickButtons = document.querySelectorAll('.bulk-quick-btn');

    function getOrderCheckboxes() {
        return [...document.querySelectorAll('.order-select-checkbox:not(:disabled)')];
    }

    function syncSelectionUI() {
        const boxes = getOrderCheckboxes();
        const checked = boxes.filter(cb => cb.checked);
        const count = checked.length;
        const total = boxes.length;

        if (bannerCount) bannerCount.textContent = count;
        if (floatingCount) floatingCount.textContent = count;

        if (count > 0) {
            banner?.classList.remove('hidden');
            floatingBar?.classList.remove('hidden');
        } else {
            banner?.classList.add('hidden');
            floatingBar?.classList.add('hidden');
        }

        if (selectAll) {
            if (total === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
                selectAll.disabled = true;
                selectAll.classList.add('opacity-40', 'cursor-not-allowed');
                selectAll.classList.remove('cursor-pointer');
            } else {
                selectAll.disabled = false;
                selectAll.classList.remove('opacity-40', 'cursor-not-allowed');
                selectAll.classList.add('cursor-pointer');
                if (count === total) {
                    selectAll.checked = true;
                    selectAll.indeterminate = false;
                } else if (count > 0) {
                    selectAll.checked = false;
                    selectAll.indeterminate = true;
                } else {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                }
            }
        }

        boxes.forEach(cb => {
            const row = cb.closest('[data-order-row]');
            if (row) {
                if (cb.checked) {
                    row.classList.add('bg-indigo-50/50');
                } else {
                    row.classList.remove('bg-indigo-50/50');
                }
            }
        });
    }

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            if (selectAll.disabled) return;
            getOrderCheckboxes().forEach(cb => {
                cb.checked = selectAll.checked;
            });
            syncSelectionUI();
        });
    }

    let lastCheckedIndex = -1;
    document.addEventListener('click', (e) => {
        // Toggle if clicked directly on enabled checkbox
        if (e.target.matches('.order-select-checkbox:not(:disabled)')) {
            const boxes = getOrderCheckboxes();
            const currentIndex = boxes.indexOf(e.target);
            if (e.shiftKey && lastCheckedIndex !== -1 && lastCheckedIndex !== currentIndex) {
                const start = Math.min(lastCheckedIndex, currentIndex);
                const end = Math.max(lastCheckedIndex, currentIndex);
                const checkState = e.target.checked;
                for (let i = start; i <= end; i++) {
                    boxes[i].checked = checkState;
                }
            }
            lastCheckedIndex = currentIndex;
            syncSelectionUI();
            return;
        }

        // Toggle if clicked on cell area
        const td = e.target.closest('td[data-select-cell]');
        if (td && e.target.tagName !== 'INPUT') {
            const cb = td.querySelector('.order-select-checkbox:not(:disabled)');
            if (cb) {
                cb.checked = !cb.checked;
                syncSelectionUI();
            }
        }
    });

    // Run initial selection UI sync on page load
    syncSelectionUI();

    clearButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            getOrderCheckboxes().forEach(cb => cb.checked = false);
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            if (selectTop) selectTop.value = '';
            if (selectBottom) selectBottom.value = '';
            syncSelectionUI();
        });
    });

    if (selectTop && selectBottom) {
        selectTop.addEventListener('change', () => {
            selectBottom.value = selectTop.value;
        });
        selectBottom.addEventListener('change', () => {
            selectTop.value = selectBottom.value;
        });
    }

    async function performBulkUpdate(targetStatus) {
        const checked = getOrderCheckboxes().filter(cb => cb.checked);
        const orderIds = checked.map(cb => parseInt(cb.value, 10)).filter(id => !isNaN(id));

        if (orderIds.length === 0) {
            window.showToast('Please select at least one order.', 'error');
            return;
        }

        if (! targetStatus) {
            window.showToast('Please choose a status to assign.', 'error');
            return;
        }

        if (targetStatus === 'Cancelled') {
            const confirmed = window.confirm(`Are you sure you want to mark ${orderIds.length} order(s) as Cancelled?`);
            if (! confirmed) {
                return;
            }
        }

        applyButtons.forEach(btn => {
            btn.disabled = true;
            btn.dataset.originalText = btn.textContent;
            btn.textContent = 'Updating...';
        });

        try {
            const response = await fetch(bulkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    order_ids: orderIds,
                    status: targetStatus,
                }),
            });

            const data = await response.json();

            if (! response.ok) {
                const validationMessage = data.errors
                    ? Object.values(data.errors).flat()[0]
                    : data.message;
                throw new Error(validationMessage || 'Failed to update orders.');
            }

            // Update row status badges and selectors on the page
            orderIds.forEach(id => {
                const row = document.querySelector(`[data-order-row="${id}"]`);
                if (row) {
                    updateStatus(row, data.status, true);
                }
            });

            // Update stats cards
            if (data.stats) {
                updateStats(data.stats);
            }

            // Uncheck and clear
            getOrderCheckboxes().forEach(cb => cb.checked = false);
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            if (selectTop) selectTop.value = '';
            if (selectBottom) selectBottom.value = '';
            syncSelectionUI();

            window.showToast(data.message || `Orders updated to ${data.status}.`);
        } catch (error) {
            window.showToast(error.message, 'error');
        } finally {
            applyButtons.forEach(btn => {
                btn.disabled = false;
                if (btn.dataset.originalText) {
                    btn.textContent = btn.dataset.originalText;
                }
            });
        }
    }

    applyButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const status = selectTop?.value || selectBottom?.value;
            performBulkUpdate(status);
        });
    });

    quickButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const status = btn.dataset.bulkQuick;
            if (selectTop) selectTop.value = status;
            if (selectBottom) selectBottom.value = status;
            performBulkUpdate(status);
        });
    });

    async function refreshOrderSection() {
        if (snapshotLoading || document.hidden) {
            return;
        }

        const rows = [...document.querySelectorAll('[data-order-row]')];
        const orderIds = rows.map((row) => row.dataset.orderRow).join(',');
        snapshotLoading = true;

        try {
            const response = await fetch(`${snapshotUrl}?order_ids=${encodeURIComponent(orderIds)}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (! response.ok) {
                return;
            }

            const data = await response.json();
            updateStats(data.stats);

            data.orders.forEach((order) => {
                const row = document.querySelector(`[data-order-row="${order.id}"]`);
                if (row) {
                    updateStatus(row, order.status);
                }
            });
        } catch {
            // The next poll will retry if the connection is temporarily unavailable.
        } finally {
            snapshotLoading = false;
        }
    }

    window.setInterval(refreshOrderSection, 5000);
    document.addEventListener('visibilitychange', refreshOrderSection);
});
</script>
@endpush
