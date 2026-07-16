@php
    $notificationState = \App\Models\AdminNotification::query()
        ->toBase()
        ->selectRaw('COUNT(CASE WHEN read_at IS NULL THEN 1 END) as unread, COALESCE(MAX(id), 0) as latest_id')
        ->first();
    $unreadNotifications = (int) $notificationState->unread;
    $latestNotificationId = (int) $notificationState->latest_id;
    $navItems = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'M3 13h8V3H3v10Zm10 8h8V3h-8v18ZM3 21h8v-6H3v6Z'],
        ['label' => 'Cashier Terminal', 'route' => 'payment.index', 'icon' => 'M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3H3V5Zm0 4h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Zm4 4h6M7 16h3'],
        ['label' => 'Orders', 'route' => 'orders.index', 'icon' => 'M6 6h15l-1.5 9h-12L6 6Zm0 0L5 3H2m7 18a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm9 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z'],
        ['label' => 'Products', 'route' => 'products.index', 'icon' => 'M20 7 12 3 4 7l8 4 8-4Zm0 0v10l-8 4m8-14-8 4m0 10-8-4V7m8 14V11'],
        ['label' => 'Home Banners', 'route' => 'home-banners.index', 'icon' => 'M4 6h16v12H4zM7 9h5M7 13h10'],
        ['label' => 'Users', 'route' => 'users.index', 'icon' => 'M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m8-4a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
    ];

    $inventoryItems = [
        ['label' => 'Suppliers', 'route' => 'suppliers.index', 'icon' => 'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3Zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3Zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5Zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5Z'],
        ['label' => 'Stock Purchases', 'route' => 'stock-purchases.index', 'icon' => 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2'],
        //['label' => 'Manage Stock', 'route' => 'manage-stock.index', 'icon' => 'M3 10h11M3 14h7m-7-8h16M3 18h16M17 6v12m-2-2 2 2 2-2'],
    ];

    $toolItems = [
        ['label' => 'Analytics', 'route' => 'analytics', 'icon' => 'M4 19V5m5 14V9m5 10V3m5 16v-7'],
        ['label' => 'Finance', 'route' => 'finance', 'icon' => 'M12 6v12m4-8c0-2.2-1.8-4-4-4s-4 1.2-4 3 1.8 3 4 3 4 1.2 4 3-1.8 3-4 3-4-1.8-4-4'],
        ['label' => 'Settings', 'route' => 'settings', 'icon' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8-3a8 8 0 0 0-.11-1.32l2.03-1.58-2-3.46-2.39.96a8.15 8.15 0 0 0-2.28-1.32L15 2h-4l-.36 3.28A8.15 8.15 0 0 0 8.36 6.6l-2.39-.96-2 3.46 2.03 1.58A8 8 0 0 0 6 12c0 .45.04.89.11 1.32l-2.03 1.58 2 3.46 2.39-.96a8.15 8.15 0 0 0 2.28 1.32L11 22h4l.36-3.28a8.15 8.15 0 0 0 2.28-1.32l2.39.96 2-3.46-2.03-1.58c.07-.43.11-.87.11-1.32Z'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Koop School') }} - @yield('title', 'Dashboard')</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900">
        @include('partials.admin-alerts')
        <div class="min-h-screen bg-[#f4f7fb] lg:flex">
            <aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col">
                <div class="flex h-20 items-center gap-3 px-7">
                    <div class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-600 text-white shadow-lg shadow-indigo-200">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 5h16v14H4zM8 9h8M8 13h5" />
                        </svg>
                    </div>
                    <span class="text-lg font-extrabold">KoopAll</span>
                </div>

                <nav class="flex-1 space-y-8 px-4 pb-6">
                    <div>
                        <p class="mb-3 px-3 text-xs font-bold uppercase text-slate-400">Menu</p>
                        <div class="space-y-1">
                            @foreach ($navItems as $item)
                                <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold {{ isset($item['route']) && request()->routeIs($item['route']) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <span class="flex items-center gap-3">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="{{ $item['icon'] }}" />
                                        </svg>
                                        {{ $item['label'] }}
                                    </span>
                                    @isset($item['badge'])
                                        <span class="grid h-5 min-w-5 place-items-center rounded-full bg-indigo-600 px-1.5 text-xs text-white">{{ $item['badge'] }}</span>
                                    @endisset
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 px-3 text-xs font-bold uppercase text-slate-400">Inventory</p>
                        <div class="space-y-1">
                            @foreach ($inventoryItems as $item)
                                <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold {{ isset($item['route']) && request()->routeIs($item['route']) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <span class="flex items-center gap-3">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="{{ $item['icon'] }}" />
                                        </svg>
                                        {{ $item['label'] }}
                                    </span>
                                    @isset($item['badge'])
                                        <span class="grid h-5 min-w-5 place-items-center rounded-full bg-indigo-600 px-1.5 text-xs text-white">{{ $item['badge'] }}</span>
                                    @endisset
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 px-3 text-xs font-bold uppercase text-slate-400">Tools</p>
                        <div class="space-y-1">
                            @foreach ($toolItems as $item)
                                <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold {{ isset($item['route']) && request()->routeIs($item['route']) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <span class="flex items-center gap-3">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="{{ $item['icon'] }}" />
                                        </svg>
                                        {{ $item['label'] }}
                                    </span>
                                    @isset($item['badge'])
                                        <span class="grid h-5 min-w-5 place-items-center rounded-full bg-indigo-600 px-1.5 text-xs text-white">{{ $item['badge'] }}</span>
                                    @endisset
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-rose-500 hover:bg-rose-50">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3" />
                            </svg>
                            Log out
                        </button>
                    </form>
                </nav>

                <div class="border-t border-slate-100 p-4">
                    <a href="{{ route('settings') }}" class="flex items-center gap-3 rounded-lg p-3 hover:bg-slate-50">
                        <div class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-600 text-sm font-bold text-white">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
                        </div>
                    </a>
                </div>
            </aside>

            <main class="min-w-0 flex-1">
                <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
                    <div class="flex h-20 items-center gap-4 px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center gap-3 lg:hidden">
                            <div class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-600 text-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 5h16v14H4zM8 9h8M8 13h5" />
                                </svg>
                            </div>
                            <span class="font-extrabold">KoopAll</span>
                        </div>

                        <div class="hidden items-center gap-2 text-sm font-semibold text-slate-400 sm:flex">
                            <span>Pages</span>
                            <span>/</span>
                            <span class="text-slate-900">@yield('page-title', 'Dashboard')</span>
                        </div>

                        <form method="GET" action="{{ route('products.index') }}" class="mx-auto hidden w-full max-w-sm md:block">
                            <label class="relative block">
                                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 21-4.3-4.3M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                                <input name="search" value="{{ request()->routeIs('products.*') ? request('search') : '' }}" placeholder="Search items, categories, or more..." class="h-10 w-full rounded-lg border-0 bg-slate-50 pl-10 pr-4 text-xs font-semibold text-slate-600 ring-1 ring-slate-100 placeholder:text-slate-400">
                            </label>
                        </form>

                        <div class="ml-auto flex flex-1 items-center justify-end gap-3">
                            <a
                                data-notification-realtime
                                data-changes-url="{{ route('notifications.changes') }}"
                                data-supabase-url="{{ config('services.supabase.url') }}"
                                data-supabase-key="{{ config('services.supabase.anon_key') }}"
                                data-latest-id="{{ $latestNotificationId }}"
                                href="{{ route('notifications') }}"
                                class="relative grid h-10 w-10 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"
                                title="Notifications"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" />
                                </svg>
                                <span data-notification-badge class="absolute right-1 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-extrabold text-white {{ $unreadNotifications > 0 ? '' : 'hidden' }}">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                            </a>
                            <a href="{{ route('settings') }}" class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-600 text-sm font-bold text-white" title="Account settings">
                                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                            </a>
                        </div>
                    </div>
                </header>

                <div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                    @yield('content')
                </div>
            </main>
        </div>
        @stack('scripts')
    </body>
</html>
