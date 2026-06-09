@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-extrabold tracking-normal">Notifications</h1>
    <button class="h-10 rounded-lg bg-white px-5 text-sm font-extrabold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">Mark all as read</button>
</section>

<section class="rounded-lg bg-white shadow-sm ring-1 ring-slate-100">
    <div class="divide-y divide-slate-100">
        <article class="flex items-start gap-4 p-5 hover:bg-slate-50">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-indigo-50">
                <span class="text-2xl">🛒</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-extrabold text-slate-900">New Order Received #ORD-0047</p>
                <p class="mt-1 text-sm text-slate-500">Esther Howard placed an order worth $320.00 for 12 items</p>
                <p class="mt-2 text-xs font-semibold text-slate-400">5 minutes ago</p>
            </div>
            <span class="h-2 w-2 shrink-0 rounded-full bg-indigo-600"></span>
        </article>

        <article class="flex items-start gap-4 p-5 hover:bg-slate-50">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-amber-50">
                <span class="text-2xl">⚠️</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-extrabold text-slate-900">Low Stock Alert</p>
                <p class="mt-1 text-sm text-slate-500">Smart Watch Pro X has only 8 units left in inventory</p>
                <p class="mt-2 text-xs font-semibold text-slate-400">1 hour ago</p>
            </div>
            <span class="h-2 w-2 shrink-0 rounded-full bg-indigo-600"></span>
        </article>

        <article class="flex items-start gap-4 p-5 hover:bg-slate-50">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-emerald-50">
                <span class="text-2xl">💰</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-extrabold text-slate-900">Payment Received</p>
                <p class="mt-1 text-sm text-slate-500">Monthly subscription payment of $89.00 processed successfully</p>
                <p class="mt-2 text-xs font-semibold text-slate-400">3 hours ago</p>
            </div>
            <span class="h-2 w-2 shrink-0 rounded-full bg-indigo-600"></span>
        </article>

        <article class="flex items-start gap-4 p-5 hover:bg-slate-50">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-violet-50">
                <span class="text-2xl">⭐</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-extrabold text-slate-900">New VIP Customer</p>
                <p class="mt-1 text-sm text-slate-500">Esther Howard has been upgraded to VIP status after $3,000+ in purchases</p>
                <p class="mt-2 text-xs font-semibold text-slate-400">Yesterday</p>
            </div>
        </article>

        <article class="flex items-start gap-4 p-5 hover:bg-slate-50">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-sky-50">
                <span class="text-2xl">📊</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-extrabold text-slate-900">Monthly Report Ready</p>
                <p class="mt-1 text-sm text-slate-500">Your October 2025 sales report is ready to download</p>
                <p class="mt-2 text-xs font-semibold text-slate-400">2 days ago</p>
            </div>
        </article>

        <article class="flex items-start gap-4 p-5 hover:bg-slate-50">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-blue-50">
                <span class="text-2xl">📦</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-extrabold text-slate-900">Order Status Updated</p>
                <p class="mt-1 text-sm text-slate-500">Order #ORD-0039 has been marked as delivered successfully</p>
                <p class="mt-2 text-xs font-semibold text-slate-400">3 days ago</p>
            </div>
        </article>

        <article class="flex items-start gap-4 p-5 hover:bg-slate-50">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-rose-50">
                <span class="text-2xl">❌</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-extrabold text-slate-900">Order Cancelled</p>
                <p class="mt-1 text-sm text-slate-500">Order #ORD-0038 was cancelled by Cody Fisher</p>
                <p class="mt-2 text-xs font-semibold text-slate-400">4 days ago</p>
            </div>
        </article>

        <article class="flex items-start gap-4 p-5 hover:bg-slate-50">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-indigo-50">
                <span class="text-2xl">🎉</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-extrabold text-slate-900">Sales Milestone Reached</p>
                <p class="mt-1 text-sm text-slate-500">Congratulations! You've reached 1,000 orders this month</p>
                <p class="mt-2 text-xs font-semibold text-slate-400">5 days ago</p>
            </div>
        </article>

        <article class="flex items-start gap-4 p-5 hover:bg-slate-50">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-emerald-50">
                <span class="text-2xl">✅</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-extrabold text-slate-900">Stock Replenished</p>
                <p class="mt-1 text-sm text-slate-500">Nike Air Max 2024 inventory has been restocked with 100 units</p>
                <p class="mt-2 text-xs font-semibold text-slate-400">1 week ago</p>
            </div>
        </article>
    </div>
</section>
@endsection
