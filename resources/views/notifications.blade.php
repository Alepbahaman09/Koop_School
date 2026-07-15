@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold tracking-normal">Notifications</h1>
        <p class="mt-1 text-sm font-medium text-slate-500"><span data-notification-summary-unread>{{ number_format($stats['unread']) }}</span> unread of <span data-notification-summary-total>{{ number_format($stats['total']) }}</span> total notifications.</p>
    </div>
    <form method="POST" action="{{ route('notifications.readAll') }}">
        @csrf
        <button class="h-10 rounded-lg bg-white px-5 text-sm font-extrabold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">Mark all as read</button>
    </form>
</section>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([['All', $stats['total'], '', 'total'], ['Unread', $stats['unread'], 'unread', 'unread'], ['Orders', $stats['orders'], 'orders', 'orders'], ['Stock Alerts', $stats['stock'], 'stock', 'stock']] as [$label, $value, $key, $stat])
        <a href="{{ route('notifications', array_filter(['filter' => $key])) }}" class="rounded-lg bg-white p-5 shadow-sm ring-1 {{ $filter === $key || ($key === '' && $filter === '') ? 'ring-indigo-200' : 'ring-slate-100' }}">
            <p class="text-sm font-bold text-slate-400">{{ $label }}</p>
            <p data-notification-stat="{{ $stat }}" class="mt-2 text-2xl font-extrabold text-slate-950">{{ number_format($value) }}</p>
        </a>
    @endforeach
</section>

<section data-notification-page data-filter="{{ $filter }}" class="rounded-lg bg-white shadow-sm ring-1 ring-slate-100">
    <div data-notification-list class="divide-y divide-slate-100">
        @forelse ($notifications as $notification)
            @include('partials.notification-item', ['notification' => $notification])
        @empty
            <div data-notification-empty class="p-12 text-center">
                <p class="font-extrabold text-slate-700">No notifications yet</p>
                <p class="mt-1 text-sm font-medium text-slate-400">New orders and stock alerts will appear here automatically.</p>
            </div>
        @endforelse
    </div>
    <div class="border-t border-slate-100 p-4">{{ $notifications->links() }}</div>
</section>
@endsection
