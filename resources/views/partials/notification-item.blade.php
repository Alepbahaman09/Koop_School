<article data-notification-id="{{ $notification->id }}" class="flex items-start gap-4 p-5 hover:bg-slate-50">
    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg {{ $notification->read_at ? 'bg-slate-100 text-slate-400' : 'bg-indigo-50 text-indigo-600' }}">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="{{ $notification->isOrderNotification() ? 'M6 6h15l-1.5 9h-12L6 6Zm0 0L5 3H2m7 18h.01m9 0h.01' : 'M12 3 2 21h20L12 3Zm0 6v5m0 3h.01' }}" />
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
