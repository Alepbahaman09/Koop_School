@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')

@section('content')
@php
    // Full card background color matching status — exactly like the top summary cards
    $statusCardClass = [
        'Processing' => 'bg-sky-50',
        'Ready' => 'bg-violet-50',
        'Completed' => 'bg-emerald-50',
        'Cancelled' => 'bg-rose-50',
    ];

    // Status badge style on top of the colored cards
    $statusBadgeClass = [
        'Processing' => 'bg-white text-sky-700 shadow-sm',
        'Ready' => 'bg-white text-violet-700 shadow-sm',
        'Completed' => 'bg-white text-emerald-700 shadow-sm',
        'Cancelled' => 'bg-white text-rose-700 shadow-sm',
    ];

    $orderStatuses = ['Processing', 'Ready', 'Completed', 'Cancelled'];
@endphp

<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold">Users</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">View registered mobile app accounts, contact details, and order history.</p>
    </div>
</section>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['Registered Users', $stats['total'], 'text-indigo-600'],
        ['Verified Accounts', $stats['verified'], 'text-emerald-600'],
        ['Awaiting Verification', $stats['unverified'], 'text-slate-600'],
        ['Users With Orders', $stats['with_orders'], 'text-sky-600'],
    ] as [$label, $value, $tone])
        <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <p class="text-sm font-bold text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-2xl font-extrabold {{ $tone }}">{{ number_format($value) }}</p>
        </article>
    @endforeach
</section>

<section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
    <form method="GET" class="mb-5 grid gap-3 lg:grid-cols-[1fr_12rem_auto] lg:items-end">
        <label class="text-xs font-extrabold uppercase text-slate-400">Search
            <input name="search" value="{{ request('search') }}" placeholder="User, email, student, or class" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
        </label>
        <label class="text-xs font-extrabold uppercase text-slate-400">Status
            <select name="status" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
                <option value="">All</option>
                <option value="verified" @selected(request('status') === 'verified')>Verified</option>
                <option value="unverified" @selected(request('status') === 'unverified')>Unverified</option>
            </select>
        </label>
        <div class="flex gap-2">
            <button class="h-10 rounded-lg bg-slate-900 px-5 text-sm font-extrabold text-white">Filter</button>
            <a href="{{ route('users.index') }}" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-slate-500 ring-1 ring-slate-200">Clear</a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead><tr class="border-b border-slate-100 text-xs font-extrabold uppercase text-slate-400">
                <th class="py-3 pr-4">User</th><th class="py-3 pr-4">Contact</th>
                <th class="py-3 pr-4">Created Account</th><th class="py-3 pr-4">Orders</th><th class="py-3 pr-4">Verified</th><th class="py-3 pr-4">Details</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($users as $user)
                <tr class="align-top">
                    <td class="py-4 pr-4">
                        <div class="flex items-center gap-3">
                            <div class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-50 text-sm font-extrabold text-indigo-700">{{ strtoupper(substr($user->username ?: $user->name, 0, 1)) }}</div>
                            <div>
                                <p class="font-extrabold text-slate-900">{{ $user->username ?: $user->name }}</p>
                                <p class="text-xs font-semibold text-slate-400">{{ $user->students->count() }} {{ $user->students->count() === 1 ? 'student' : 'students' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 pr-4">
                        <p class="font-semibold text-slate-600">{{ $user->email }}</p>
                        <p class="text-xs text-slate-400">{{ $user->phone_number ?: 'No phone number' }}</p>
                    </td>
                    <td class="py-4 pr-4 font-semibold text-slate-500">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="py-4 pr-4 font-extrabold">{{ $user->orders_count }}</td>

                    {{-- Verified status badge --}}
                    <td class="py-4 pr-4">
                        @if ($user->email_verified_at)
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-700">Verified</span>
                        @else
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-extrabold text-slate-500">Unverified</span>
                        @endif
                    </td>

                    {{-- Details column --}}
                    <td class="py-4 pr-4">
                        <details @if(request('customer') == $user->id) open @endif>
                            <summary class="cursor-pointer list-none font-extrabold text-indigo-600">View</summary>
                            <div class="fixed inset-0 z-40 bg-slate-950/30"></div>
                            <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-4xl overflow-y-auto rounded-lg bg-white p-6 text-left shadow-2xl">
                                <div class="mb-5 flex items-start justify-between">
                                    <div>
                                        <h2 class="text-lg font-extrabold">{{ $user->username ?: $user->name }}</h2>
                                        <p class="text-sm font-medium text-slate-500">Registered parent account</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full px-3 py-1 text-xs font-extrabold {{ $user->email_verified_at ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $user->email_verified_at ? 'Verified' : 'Unverified' }}</span>
                                        <button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500" title="Close">&times;</button>
                                    </div>
                                </div>

                                <div class="grid gap-5 lg:grid-cols-[20rem_1fr]">
                                    <div class="space-y-3 rounded-lg bg-slate-50 p-4 text-sm">
                                        <p><span class="font-extrabold">Email:</span> {{ $user->email }}</p>
                                        <p><span class="font-extrabold">Phone:</span> {{ $user->phone_number ?: 'Not provided' }}</p>
                                        <p><span class="font-extrabold">Created:</span> {{ $user->created_at->format('d M Y, h:i A') }}</p>

                                        <div class="border-t border-slate-200 pt-3">
                                            <p class="mb-2 text-xs font-extrabold uppercase text-slate-400">Students</p>
                                            <div class="space-y-2">
                                                @forelse ($user->students as $student)
                                                    <div class="rounded-lg bg-white p-3 ring-1 ring-slate-100">
                                                        <p class="font-extrabold text-slate-800">{{ $student->name }}</p>
                                                        <p class="text-xs font-semibold text-slate-400">Class {{ $student->class }}</p>
                                                    </div>
                                                @empty
                                                    <p class="font-semibold text-slate-400">No students registered.</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        {{-- Total orders and total amount paid summary --}}
                                        <div class="mb-4 grid grid-cols-2 gap-3">
                                            <div class="rounded-lg bg-indigo-50 p-4">
                                                <p class="text-xs font-extrabold uppercase text-indigo-400">Total Orders</p>
                                                <p class="mt-1 text-xl font-extrabold text-indigo-700">{{ $user->orders_count }}</p>
                                            </div>
                                            <div class="rounded-lg bg-emerald-50 p-4">
                                                <p class="text-xs font-extrabold uppercase text-emerald-400">Total Paid</p>
                                                <p class="mt-1 text-xl font-extrabold text-emerald-700">RM {{ number_format($user->total_spent ?? 0, 2) }}</p>
                                            </div>
                                        </div>

                                        <h3 class="mb-3 text-sm font-extrabold text-slate-500">Recent Orders</h3>
                                        <div class="space-y-3">
                                            @forelse ($user->orders as $order)
                                                @php
                                                    $isKioskOrder = str_starts_with($order->order_number, 'POS-')
                                                        || ($order->relationLoaded('terminalPayments') && $order->terminalPayments->isNotEmpty());
                                                @endphp

                                                @if ($isKioskOrder)
                                                    {{-- Kiosk order: cannot be clicked, no manage access (same as orders page) --}}
                                                    <div class="rounded-lg p-4 {{ $statusCardClass[$order->status] ?? 'bg-slate-50' }}">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex items-center gap-2">
                                                                    <p class="font-extrabold text-slate-900">Kiosk-{{ $order->id }}</p>
                                                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-extrabold {{ $statusBadgeClass[$order->status] ?? 'bg-white text-slate-600 shadow-sm' }}">{{ $order->status }}</span>
                                                                </div>

                                                                @if ($order->student)
                                                                    <p class="mt-1 text-xs font-semibold text-slate-600">{{ $order->student->name }} ({{ $order->student->class }})</p>
                                                                @endif

                                                                <p class="mt-1 text-xs text-slate-400">
                                                                    {{ $order->created_at->format('d M Y, h:i A') }}
                                                                </p>
                                                            </div>

                                                            <p class="font-extrabold text-slate-900">RM {{ number_format($order->total_amount, 2) }}</p>
                                                        </div>
                                                    </div>
                                                @else
                                                    {{-- Online order: clickable card to open manage section --}}
                                                    <details data-order-card class="group rounded-lg p-4 transition-colors {{ $statusCardClass[$order->status] ?? 'bg-slate-50' }}">
                                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex items-center gap-2">
                                                                    <p class="font-extrabold text-slate-900">{{ $order->order_number }}</p>
                                                                    <span data-order-status-badge class="rounded-full px-2.5 py-0.5 text-xs font-extrabold {{ $statusBadgeClass[$order->status] ?? 'bg-white text-slate-600 shadow-sm' }}">{{ $order->status }}</span>
                                                                </div>

                                                                @if ($order->student)
                                                                    <p class="mt-1 text-xs font-semibold text-slate-600">{{ $order->student->name }} ({{ $order->student->class }})</p>
                                                                @endif

                                                                <p class="mt-1 text-xs text-slate-400">
                                                                    {{ $order->created_at->format('d M Y, h:i A') }}
                                                                </p>
                                                            </div>

                                                            <p class="font-extrabold text-slate-900">RM {{ number_format($order->total_amount, 2) }}</p>
                                                        </summary>

                                                        {{-- Status update form like the order manage in order page --}}
                                                        <form data-status-form method="POST" action="{{ route('orders.updateStatus', $order) }}" class="mt-4 border-t border-slate-200/80 pt-4" onclick="event.stopPropagation()">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="grid gap-3 sm:grid-cols-[1fr_auto] items-end">
                                                                <label class="block text-xs font-extrabold uppercase text-slate-600">
                                                                    Update Status
                                                                    <select name="status" class="mt-1 h-10 w-full rounded-lg border-slate-200 bg-white text-xs font-bold text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                                        @foreach ($orderStatuses as $statusOption)
                                                                            <option value="{{ $statusOption }}" @selected($order->status === $statusOption)>{{ $statusOption }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </label>
                                                                <button type="submit" class="h-10 rounded-lg bg-indigo-600 px-5 text-xs font-extrabold text-white shadow-sm hover:bg-indigo-700">
                                                                    Save Status
                                                                </button>
                                                            </div>
                                                            <label class="mt-3 block text-xs font-extrabold uppercase text-slate-600">
                                                                Admin Notes
                                                                <input type="text" name="notes" placeholder="Optional note for status update" class="mt-1 h-9 w-full rounded-lg border-slate-200 bg-white text-xs text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                                                            </label>
                                                        </form>
                                                    </details>
                                                @endif
                                            @empty
                                                <p class="p-5 text-center font-semibold text-slate-400">No orders yet.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-12 text-center font-semibold text-slate-400">No users match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $users->links() }}</div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const statusBadgeClasses = @json($statusBadgeClass);
    const statusCardClasses = {
        'Processing': 'bg-sky-50',
        'Ready': 'bg-violet-50',
        'Completed': 'bg-emerald-50',
        'Cancelled': 'bg-rose-50',
    };

    const allCardColors = ['bg-sky-50', 'bg-violet-50', 'bg-emerald-50', 'bg-rose-50', 'bg-slate-50'];
    const allBadgeClasses = Object.values(statusBadgeClasses).flatMap(c => c.split(' '));

    document.querySelectorAll('[data-status-form]').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Saving...';

            const card = form.closest('[data-order-card]');
            const badge = card?.querySelector('[data-order-status-badge]');

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

                if (!response.ok) {
                    const validationMessage = data.errors
                        ? Object.values(data.errors).flat()[0]
                        : data.message;
                    throw new Error(validationMessage || 'Failed to update order status.');
                }

                const newStatus = data.order.status;

                // Update badge text and color styling
                if (badge) {
                    badge.classList.remove(...allBadgeClasses);
                    const newBadgeClass = (statusBadgeClasses[newStatus] || 'bg-white text-slate-600 shadow-sm').split(' ');
                    badge.classList.add(...newBadgeClass);
                    badge.textContent = newStatus;
                }

                // Update card full background color
                if (card) {
                    card.classList.remove(...allCardColors);
                    card.classList.add(statusCardClasses[newStatus] || 'bg-slate-50');
                }

                const notesInput = form.querySelector('input[name="notes"]');
                if (notesInput) {
                    notesInput.value = '';
                }

                if (window.showToast) {
                    window.showToast(data.message);
                }
            } catch (err) {
                if (window.showToast) {
                    window.showToast(err.message, 'error');
                } else {
                    alert(err.message);
                }
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    });
});
</script>
@endpush

