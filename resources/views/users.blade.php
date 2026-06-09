@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')

@section('content')
@include('partials.admin-alerts')

<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold">Parent & Student Users</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">View registered mobile app accounts, contact details, location, and order history.</p>
    </div>
</section>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['Registered Users', $stats['total'], 'text-indigo-600'],
        ['Active Accounts', $stats['active'], 'text-emerald-600'],
        ['Inactive Accounts', $stats['inactive'], 'text-slate-600'],
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
            <input name="search" value="{{ request('search') }}" placeholder="Parent, student, ID, or email" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
        </label>
        <label class="text-xs font-extrabold uppercase text-slate-400">Status
            <select name="status" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </label>
        <div class="flex gap-2">
            <button class="h-10 rounded-lg bg-slate-900 px-5 text-sm font-extrabold text-white">Filter</button>
            <a href="{{ route('users.index') }}" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-slate-500 ring-1 ring-slate-200">Clear</a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[1050px] text-left text-sm">
            <thead><tr class="border-b border-slate-100 text-xs font-extrabold uppercase text-slate-400">
                <th class="py-3 pr-4">Parent / Student</th><th class="py-3 pr-4">Contact</th><th class="py-3 pr-4">Location</th>
                <th class="py-3 pr-4">Created Account</th><th class="py-3 pr-4">Orders</th><th class="py-3 pr-4">Total Paid</th><th class="py-3 text-right">Details</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($customers as $customer)
                <tr class="align-top">
                    <td class="py-4 pr-4">
                        <div class="flex items-center gap-3">
                            <div class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-50 text-sm font-extrabold text-indigo-700">{{ strtoupper(substr($customer->parent_name, 0, 1) . substr($customer->student_name, 0, 1)) }}</div>
                            <div>
                                <p class="font-extrabold text-slate-900">{{ $customer->parent_name }}</p>
                                <p class="text-xs font-semibold text-slate-400">{{ $customer->student_name }} &middot; {{ $customer->student_id }} &middot; {{ $customer->class }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 pr-4">
                        <p class="font-semibold text-slate-600">{{ $customer->email }}</p>
                        <p class="text-xs text-slate-400">{{ $customer->phone }}</p>
                    </td>
                    <td class="py-4 pr-4">
                        <p class="max-w-64 truncate font-semibold text-slate-600">{{ $customer->address }}</p>
                        @if ($customer->latitude && $customer->longitude)
                            <a class="text-xs font-bold text-indigo-600" target="_blank" href="https://www.google.com/maps?q={{ $customer->latitude }},{{ $customer->longitude }}">Open map</a>
                        @else
                            <span class="text-xs font-bold text-slate-400">No map pin</span>
                        @endif
                    </td>
                    <td class="py-4 pr-4 font-semibold text-slate-500">{{ $customer->created_at->format('d M Y') }}</td>
                    <td class="py-4 pr-4 font-extrabold">{{ $customer->orders_count }}</td>
                    <td class="py-4 pr-4 font-extrabold">RM {{ number_format($customer->total_spent ?? 0, 2) }}</td>
                    <td class="py-4 text-right">
                        <details @if(request('customer') == $customer->id) open @endif>
                            <summary class="cursor-pointer list-none font-extrabold text-indigo-600">View</summary>
                            <div class="fixed inset-0 z-40 bg-slate-950/30"></div>
                            <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-4xl overflow-y-auto rounded-lg bg-white p-6 text-left shadow-2xl">
                                <div class="mb-5 flex items-start justify-between">
                                    <div>
                                        <h2 class="text-lg font-extrabold">{{ $customer->parent_name }}</h2>
                                        <p class="text-sm text-slate-500">{{ $customer->student_name }} &middot; {{ $customer->student_id }} &middot; {{ $customer->class }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full px-3 py-1 text-xs font-extrabold {{ $customer->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $customer->is_active ? 'Active' : 'Inactive' }}</span>
                                        <button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500" title="Close">&times;</button>
                                    </div>
                                </div>

                                <div class="grid gap-5 lg:grid-cols-[20rem_1fr]">
                                    <div class="space-y-3 rounded-lg bg-slate-50 p-4 text-sm">
                                        <p><span class="font-extrabold">Email:</span> {{ $customer->email }}</p>
                                        <p><span class="font-extrabold">Phone:</span> {{ $customer->phone }}</p>
                                        <p><span class="font-extrabold">Address:</span> {{ $customer->address }}</p>
                                        <p><span class="font-extrabold">Location:</span> {{ $customer->latitude && $customer->longitude ? $customer->latitude . ', ' . $customer->longitude : 'Not provided' }}</p>
                                        <p><span class="font-extrabold">Created:</span> {{ $customer->created_at->format('d M Y, h:i A') }}</p>
                                    </div>

                                    <div>
                                        <h3 class="mb-3 text-sm font-extrabold text-slate-500">Recent Orders</h3>
                                        <div class="divide-y divide-slate-100 rounded-lg ring-1 ring-slate-100">
                                            @forelse ($customer->orders as $order)
                                                <div class="grid grid-cols-[1fr_auto] gap-3 p-3">
                                                    <div>
                                                        <p class="font-extrabold">{{ $order->order_number }}</p>
                                                        <p class="text-xs text-slate-400">{{ $order->created_at->format('d M Y') }} &middot; {{ $order->status }} &middot; {{ $order->payment_status }}</p>
                                                    </div>
                                                    <p class="font-extrabold">RM {{ number_format($order->total_amount, 2) }}</p>
                                                </div>
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
                <tr><td colspan="7" class="py-12 text-center font-semibold text-slate-400">No users match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $customers->links() }}</div>
</section>
@endsection
