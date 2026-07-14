@extends('layouts.app')

@section('title', 'Orders')
@section('page-title', 'Orders')

@section('content')
@php
    $paymentStatuses = ['Unpaid', 'Partial', 'Paid', 'Refunded'];
    $statusClass = [
        'Processing' => 'bg-sky-50 text-sky-700',
        'Ready' => 'bg-violet-50 text-violet-700',
        'Completed' => 'bg-emerald-50 text-emerald-700',
        'Cancelled' => 'bg-rose-50 text-rose-700',
    ];
@endphp

<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold">Orders</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">View customer orders and update their status from this page.</p>
    </div>
</section>

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

<section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
    <form method="GET" class="mb-5 grid gap-3 lg:grid-cols-[1fr_13rem_13rem_auto] lg:items-end">
        <label class="text-xs font-extrabold uppercase text-slate-400">Search
            <input name="search" value="{{ request('search') }}" placeholder="Order number, parent, or student" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
        </label>
        <label class="text-xs font-extrabold uppercase text-slate-400">Order Status
            <select name="status" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs font-extrabold uppercase text-slate-400">Payment
            <select name="payment_status" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
                <option value="">All payments</option>
                @foreach ($paymentStatuses as $paymentStatus)
                    <option value="{{ $paymentStatus }}" @selected(request('payment_status') === $paymentStatus)>{{ $paymentStatus }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex gap-2">
            <button class="h-10 rounded-lg bg-slate-900 px-5 text-sm font-extrabold text-white">Filter</button>
            <a href="{{ route('orders.index') }}" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-slate-500 ring-1 ring-slate-200">Clear</a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[1050px] text-left text-sm">
            <thead><tr class="border-b border-slate-100 text-xs font-extrabold uppercase text-slate-400">
                <th class="py-3 pr-4">Order</th><th class="py-3 pr-4">Customer</th><th class="py-3 pr-4">Items</th>
                <th class="py-3 pr-4">Total</th><th class="py-3 pr-4">Payment</th><th class="py-3 pr-4">Status</th><th class="py-3 text-right">Update</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($orders as $order)
                <tr data-order-row="{{ $order->id }}" class="align-top">
                    <td class="py-4 pr-4">
                        <p class="font-extrabold text-slate-900">{{ $order->order_number }}</p>
                        <p class="text-xs font-bold text-slate-400">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                    </td>
                    <td class="py-4 pr-4">
                        <p class="font-bold text-slate-700">{{ $order->customer?->parent_name ?? 'Unknown parent' }}</p>
                        <p class="text-xs font-semibold text-slate-400">{{ $order->customer?->student_name }} {{ $order->customer?->class ? '(' . $order->customer->class . ')' : '' }}</p>
                    </td>
                    <td class="py-4 pr-4 font-semibold text-slate-600">{{ $order->orderItems->sum('quantity') }} item(s)</td>
                    <td class="py-4 pr-4 font-extrabold">RM {{ number_format($order->total_amount, 2) }}</td>
                    <td class="py-4 pr-4">
                        <span data-order-payment class="rounded-full px-3 py-1 text-xs font-extrabold {{ $order->payment_status === 'Paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $order->payment_status }}</span>
                    </td>
                    <td class="py-4 pr-4"><span data-order-status class="rounded-full px-3 py-1 text-xs font-extrabold {{ $statusClass[$order->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $order->status }}</span></td>
                    <td class="py-4 text-right">
                        <details>
                            <summary class="cursor-pointer list-none font-extrabold text-indigo-600">Manage</summary>
                            <div class="fixed inset-0 z-40 bg-slate-950/30"></div>
                            <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-4xl overflow-y-auto rounded-lg bg-white p-6 text-left shadow-2xl">
                                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h2 class="text-lg font-extrabold">{{ $order->order_number }}</h2>
                                        <p class="text-sm font-medium text-slate-500">{{ $order->customer?->parent_name }} for {{ $order->customer?->student_name }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span data-order-status class="rounded-full px-3 py-1 text-xs font-extrabold {{ $statusClass[$order->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $order->status }}</span>
                                        <button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500" title="Close">&times;</button>
                                    </div>
                                </div>

                                <div class="grid gap-5 lg:grid-cols-[1fr_20rem]">
                                    <div>
                                        <h3 class="mb-3 text-sm font-extrabold text-slate-500">Order Items</h3>
                                        <div class="divide-y divide-slate-100 rounded-lg ring-1 ring-slate-100">
                                            @foreach ($order->orderItems as $item)
                                                <div class="grid grid-cols-[1fr_auto] gap-3 p-3">
                                                    <div>
                                                        <p class="font-bold">{{ $item->product?->name ?? 'Deleted product' }}</p>
                                                        <p class="text-xs text-slate-400">RM {{ number_format($item->unit_price, 2) }} x {{ $item->quantity }}</p>
                                                    </div>
                                                    <p class="font-extrabold">RM {{ number_format($item->subtotal, 2) }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="rounded-lg bg-slate-50 p-4">
                                            <p class="text-xs font-extrabold uppercase text-slate-400">Customer</p>
                                            <p class="mt-2 font-bold">{{ $order->customer?->parent_name }}</p>
                                            <p class="text-sm text-slate-500">{{ $order->customer?->phone }} &middot; {{ $order->customer?->email }}</p>
                                            <p class="mt-2 text-sm text-slate-500">{{ $order->customer?->address }}</p>
                                        </div>

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

                                <div class="mt-5 grid gap-5 lg:grid-cols-2">
                                    <div class="rounded-lg ring-1 ring-slate-100">
                                        <div class="border-b border-slate-100 p-3 text-sm font-extrabold text-slate-500">Payment Summary</div>
                                        <div class="p-3 text-sm">
                                            <p>Total: <span class="font-extrabold">RM {{ number_format($order->total_amount, 2) }}</span></p>
                                            <p>Paid: <span class="font-extrabold">RM {{ number_format($order->completed_payments_total ?? 0, 2) }}</span></p>
                                            <p>Status: <span class="font-extrabold">{{ $order->payment_status }}</span></p>
                                        </div>
                                    </div>
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
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-12 text-center font-semibold text-slate-400">No orders match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
</section>
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

    function updatePayment(row, status) {
        row.querySelectorAll('[data-order-payment]').forEach((badge) => {
            badge.classList.remove('bg-emerald-50', 'text-emerald-700', 'bg-slate-100', 'text-slate-600');
            badge.classList.add(...(status === 'Paid'
                ? ['bg-emerald-50', 'text-emerald-700']
                : ['bg-slate-100', 'text-slate-600']));
            badge.textContent = status;
        });
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
                    updatePayment(row, order.payment_status);
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
