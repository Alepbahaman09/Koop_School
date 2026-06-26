@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
@include('partials.admin-alerts')

<form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
    @csrf
    @method('PATCH')

    <section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-extrabold tracking-normal">Settings</h1>
            <p class="mt-1 text-sm font-medium text-slate-500">Manage admin profile, cooperative details, and alert preferences.</p>
        </div>
        <button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white shadow-sm shadow-indigo-100">Save Changes</button>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <div class="mb-5 flex items-center gap-2">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-indigo-50 text-indigo-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21a8 8 0 1 0-16 0m12-13a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" /></svg>
            </span>
            <h2 class="text-lg font-extrabold">Admin Profile</h2>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-600">Full Name</span>
                <input name="name" value="{{ old('name', $admin->name) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700">
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-600">Email Address</span>
                <input type="email" name="email" value="{{ old('email', $admin->email) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </label>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <div class="mb-5 flex items-center gap-2">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-emerald-50 text-emerald-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v14H4zM8 9h8M8 13h5" /></svg>
            </span>
            <h2 class="text-lg font-extrabold">Cooperative Information</h2>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-600">Store Name</span>
                <input name="store_name" value="{{ old('store_name', $settings->store_name) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700">
                <x-input-error :messages="$errors->get('store_name')" class="mt-2" />
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-600">Store Email</span>
                <input type="email" name="store_email" value="{{ old('store_email', $settings->store_email) }}" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700">
                <x-input-error :messages="$errors->get('store_email')" class="mt-2" />
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-600">Phone</span>
                <input name="store_phone" value="{{ old('store_phone', $settings->store_phone) }}" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700">
                <x-input-error :messages="$errors->get('store_phone')" class="mt-2" />
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-600">Currency</span>
                <select name="currency" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700">
                    <option value="MYR" @selected(old('currency', $settings->currency) === 'MYR')>MYR - Malaysian Ringgit</option>
                </select>
                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
            </label>
            <label class="block sm:col-span-2">
                <span class="mb-2 block text-sm font-bold text-slate-600">Address</span>
                <textarea name="store_address" rows="3" class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">{{ old('store_address', $settings->store_address) }}</textarea>
                <x-input-error :messages="$errors->get('store_address')" class="mt-2" />
            </label>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <div class="mb-5 flex items-center gap-2">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-amber-50 text-amber-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" /></svg>
            </span>
            <h2 class="text-lg font-extrabold">Notification Preferences</h2>
        </div>

        <div class="space-y-5">
            @foreach ([
                'new_order_alerts' => ['New Order Alerts', 'Create an admin notification whenever the mobile app creates an order.'],
                'payment_alerts' => ['Payment Alerts', 'Keep payment alert preference available for finance workflows.'],
                'low_stock_alerts' => ['Low Stock Alerts', 'Keep low-stock alert preference available for inventory workflows.'],
                'email_notifications' => ['Email Notifications', 'Store whether operational emails should be sent when mail is configured.'],
            ] as $key => [$label, $help])
                <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-4 last:border-b-0 last:pb-0">
                    <div>
                        <p class="font-extrabold text-slate-900">{{ $label }}</p>
                        <p class="text-sm text-slate-500">{{ $help }}</p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" name="notification_preferences[{{ $key }}]" value="1" @checked(old("notification_preferences.$key", $preferences[$key])) class="peer sr-only">
                        <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-indigo-600 peer-checked:after:translate-x-full"></div>
                    </label>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <div class="mb-5 flex items-center gap-2">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-rose-50 text-rose-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6l-7-3Zm-2 9 1.5 1.5L15 10" /></svg>
            </span>
            <h2 class="text-lg font-extrabold">Security</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-600">Current Password</span>
                <input type="password" name="current_password" autocomplete="current-password" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700">
                <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-600">New Password</span>
                <input type="password" name="password" autocomplete="new-password" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-600">Confirm Password</span>
                <input type="password" name="password_confirmation" autocomplete="new-password" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700">
            </label>
        </div>
    </section>
</form>
@endsection
