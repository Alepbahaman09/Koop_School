@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
@include('partials.admin-alerts')

<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold tracking-normal">Notifications</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">{{ number_format($stats['unread']) }} unread of {{ number_format($stats['total']) }} total notifications.</p>
    </div>
    <form method="POST" action="{{ route('notifications.readAll') }}">
        @csrf
        <button class="h-10 rounded-lg bg-white px-5 text-sm font-extrabold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">Mark all as read</button>
    </form>
</section>

<section class="grid gap-4 sm:grid-cols-3">
    @foreach ([['All', $stats['total'], ''], ['Unread', $stats['unread'], 'unread'], ['Orders', $stats['orders'], 'orders']] as [$label, $value, $key])
        <a href="{{ route('notifications', array_filter(['filter' => $key])) }}" class="rounded-lg bg-white p-5 shadow-sm ring-1 {{ $filter === $key || ($key === '' && $filter === '') ? 'ring-indigo-200' : 'ring-slate-100' }}">
            <p class="text-sm font-bold text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-950">{{ number_format($value) }}</p>
        </a>
    @endforeach
</section>

<section class="rounded-lg bg-white shadow-sm ring-1 ring-slate-100">
    <div class="divide-y divide-slate-100">
        @forelse ($notifications as $notification)
            <article class="flex items-start gap-4 p-5 hover:bg-slate-50">
                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg {{ $notification->read_at ? 'bg-slate-100 text-slate-400' : 'bg-indigo-50 text-indigo-600' }}">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="{{ $notification->type === 'order_created' ? 'M6 6h15l-1.5 9h-12L6 6Zm0 0L5 3H2m7 18h.01m9 0h.01' : 'M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Zm-8 12h4' }}" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-extrabold text-slate-900">{{ $notification->title }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $notification->message }}</p>
                    <p class="mt-2 text-xs font-semibold text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    @unless ($notification->read_at)
                        <span class="h-2 w-2 rounded-full bg-indigo-600"></span>
                    @endunless
                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-lg px-3 py-1.5 text-xs font-extrabold text-indigo-600 ring-1 ring-indigo-100 hover:bg-indigo-50">{{ $notification->link ? 'Open' : 'Read' }}</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="p-12 text-center">
                <p class="font-extrabold text-slate-700">No notifications yet</p>
                <p class="mt-1 text-sm font-medium text-slate-400">New mobile app orders will appear here automatically.</p>
            </div>
        @endforelse
    </div>
    <div class="border-t border-slate-100 p-4">{{ $notifications->links() }}</div>
</section>
@endsection
