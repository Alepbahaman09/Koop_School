@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-extrabold tracking-normal">Settings</h1>
    <button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white shadow-sm shadow-indigo-100">Save Changes</button>
</section>

<section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-100">
    <div class="mb-5 flex items-center gap-2">
        <span class="text-xl">👤</span>
        <h2 class="text-lg font-extrabold">Profile Information</h2>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-bold text-slate-600">Full Name</label>
            <input type="text" value="Asep Gustiawan" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="mb-2 block text-sm font-bold text-slate-600">Email Address</label>
            <input type="email" value="asep@gustiawan.dev" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="mb-2 block text-sm font-bold text-slate-600">Phone</label>
            <input type="text" value="+62 812 3456 7890" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="mb-2 block text-sm font-bold text-slate-600">Store Name</label>
            <input type="text" value="Capstore" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="sm:col-span-2">
            <label class="mb-2 block text-sm font-bold text-slate-600">Address</label>
            <input type="text" value="Jl. Raya Banjar, West Java, Indonesia" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
        </div>
    </div>
</section>

<section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-100">
    <div class="mb-5 flex items-center gap-2">
        <span class="text-xl">🔔</span>
        <h2 class="text-lg font-extrabold">Notification Preferences</h2>
    </div>

    <div class="space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <p class="font-extrabold text-slate-900">Email Notifications</p>
                <p class="text-sm text-slate-500">Receive order updates via email</p>
            </div>
            <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" checked class="peer sr-only">
                <div class="peer h-6 w-11 rounded-full bg-indigo-600 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-full"></div>
            </label>
        </div>

        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <p class="font-extrabold text-slate-900">SMS Alerts</p>
                <p class="text-sm text-slate-500">Get important alerts via SMS</p>
            </div>
            <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" class="peer sr-only">
                <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-indigo-600 peer-checked:after:translate-x-full"></div>
            </label>
        </div>

        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <p class="font-extrabold text-slate-900">Low Stock Alerts</p>
                <p class="text-sm text-slate-500">Notified when product stock is low</p>
            </div>
            <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" checked class="peer sr-only">
                <div class="peer h-6 w-11 rounded-full bg-indigo-600 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-full"></div>
            </label>
        </div>

        <div class="flex items-center justify-between">
            <div>
                <p class="font-extrabold text-slate-900">New Order Alerts</p>
                <p class="text-sm text-slate-500">Real-time notification for new orders</p>
            </div>
            <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" checked class="peer sr-only">
                <div class="peer h-6 w-11 rounded-full bg-indigo-600 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-full"></div>
            </label>
        </div>
    </div>
</section>

<section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-100">
    <div class="mb-5 flex items-center gap-2">
        <span class="text-xl">🔒</span>
        <h2 class="text-lg font-extrabold">Security</h2>
    </div>

    <div class="space-y-4">
        <div>
            <label class="mb-2 block text-sm font-bold text-slate-600">Current Password</label>
            <input type="password" placeholder="Enter current password" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-bold text-slate-600">New Password</label>
                <input type="password" placeholder="Enter new password" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-slate-600">Confirm Password</label>
                <input type="password" placeholder="Confirm new password" class="h-11 w-full rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <button class="h-10 rounded-lg bg-slate-100 px-5 text-sm font-extrabold text-slate-600 hover:bg-slate-200">Update Password</button>
    </div>
</section>
@endsection
