@extends('layouts.app')

@section('title', 'Analytics')
@section('page-title', 'Analytics')

@section('content')
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-extrabold tracking-normal">Analytics</h1>
    <div class="flex gap-2">
        <button class="h-10 rounded-lg bg-white px-4 text-sm font-bold text-slate-600 ring-1 ring-slate-200">Last 30 days</button>
        <button class="h-10 rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm shadow-indigo-100">Export Report</button>
    </div>
</section>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-4 flex items-start justify-between">
            <p class="text-sm font-semibold text-slate-500">Conversion Rate</p>
            <div class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-50">
                <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-slate-900">4.8%</p>
        <p class="mt-2 text-xs font-semibold text-emerald-600">+0.6% vs last month</p>
    </article>

    <article class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-4 flex items-start justify-between">
            <p class="text-sm font-semibold text-slate-500">Avg. Order Value</p>
            <div class="grid h-10 w-10 place-items-center rounded-lg bg-amber-50">
                <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-slate-900">$65.30</p>
        <p class="mt-2 text-xs font-semibold text-emerald-600">+$4.20 vs last month</p>
    </article>

    <article class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-4 flex items-start justify-between">
            <p class="text-sm font-semibold text-slate-500">Sessions</p>
            <div class="grid h-10 w-10 place-items-center rounded-lg bg-blue-50">
                <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-slate-900">48.2K</p>
        <p class="mt-2 text-xs font-semibold text-emerald-600">+22% this month</p>
    </article>

    <article class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-4 flex items-start justify-between">
            <p class="text-sm font-semibold text-slate-500">Bounce Rate</p>
            <div class="grid h-10 w-10 place-items-center rounded-lg bg-rose-50">
                <svg class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-slate-900">28.4%</p>
        <p class="mt-2 text-xs font-semibold text-emerald-600">-3.2% improved</p>
    </article>
</section>

<section class="grid gap-5 xl:grid-cols-2">
    <article class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Monthly Revenue</h2>
                <p class="text-xs font-medium text-slate-400">January - October 2025</p>
            </div>
            <div class="flex gap-3 text-xs font-semibold">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-indigo-600"></span> Revenue</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-rose-400"></span> Expenses</span>
            </div>
        </div>
        <div class="relative h-72">
            <div class="absolute left-0 top-0 flex h-full flex-col justify-between text-xs font-medium text-slate-400">
                <span>$140K</span>
                <span>$120K</span>
                <span>$100K</span>
                <span>$80K</span>
                <span>$60K</span>
                <span>$40K</span>
                <span>$20K</span>
                <span>$0K</span>
            </div>
            <div class="ml-12 h-full rounded-lg bg-gradient-to-b from-indigo-50/40 to-rose-50/40 p-4">
                <svg class="h-full w-full" viewBox="0 0 600 240" preserveAspectRatio="none">
                    <path d="M0 170 L60 155 L120 145 L180 140 L240 130 L300 135 L360 120 L420 125 L480 115 L540 105 L600 95" fill="none" stroke="#8b5cf6" stroke-width="2" />
                    <path d="M0 200 L60 190 L120 185 L180 180 L240 175 L300 178 L360 172 L420 170 L480 168 L540 165 L600 160" fill="none" stroke="#fb7185" stroke-width="2" />
                </svg>
            </div>
            <div class="mt-2 flex justify-between text-xs font-medium text-slate-400">
                <span>Jan</span>
                <span>Feb</span>
                <span>Mar</span>
                <span>Apr</span>
                <span>May</span>
                <span>Jun</span>
                <span>Jul</span>
                <span>Aug</span>
                <span>Sep</span>
                <span>Oct</span>
            </div>
        </div>
    </article>

    <article class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="mb-6 text-base font-bold text-slate-900">Sales by Category</h2>
        <div class="flex items-center justify-between">
            <div class="relative h-56 w-56">
                <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
                    <circle cx="50" cy="50" r="35" fill="none" stroke="#6366f1" stroke-width="14" stroke-dasharray="70 251" stroke-dashoffset="0"></circle>
                    <circle cx="50" cy="50" r="35" fill="none" stroke="#3b82f6" stroke-width="14" stroke-dasharray="51 251" stroke-dashoffset="-70"></circle>
                    <circle cx="50" cy="50" r="35" fill="none" stroke="#f59e0b" stroke-width="14" stroke-dasharray="30 251" stroke-dashoffset="-121"></circle>
                    <circle cx="50" cy="50" r="35" fill="none" stroke="#10b981" stroke-width="14" stroke-dasharray="52 251" stroke-dashoffset="-151"></circle>
                    <circle cx="50" cy="50" r="35" fill="none" stroke="#ef4444" stroke-width="14" stroke-dasharray="18 251" stroke-dashoffset="-203"></circle>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <p class="text-3xl font-bold text-slate-900">7.5K</p>
                    <p class="text-xs font-medium text-slate-400">Total</p>
                </div>
            </div>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-sm bg-indigo-600"></span>
                    <span class="text-sm font-medium text-slate-700">Clothes</span>
                    <span class="ml-auto text-sm font-bold text-slate-900">3,200</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-sm bg-blue-500"></span>
                    <span class="text-sm font-medium text-slate-700">Shoes</span>
                    <span class="ml-auto text-sm font-bold text-slate-900">1,850</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-sm bg-amber-500"></span>
                    <span class="text-sm font-medium text-slate-700">Electronics</span>
                    <span class="ml-auto text-sm font-bold text-slate-900">1,100</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-sm bg-emerald-500"></span>
                    <span class="text-sm font-medium text-slate-700">Accessories</span>
                    <span class="ml-auto text-sm font-bold text-slate-900">900</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-sm bg-red-500"></span>
                    <span class="text-sm font-medium text-slate-700">Sports</span>
                    <span class="ml-auto text-sm font-bold text-slate-900">450</span>
                </div>
            </div>
        </div>
    </article>
</section>

<section class="grid gap-5 xl:grid-cols-2">
    <article class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="mb-6 text-base font-bold text-slate-900">Traffic Sources</h2>
        <div class="space-y-5">
            <div>
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="font-semibold text-slate-700">Organic Search</span>
                    <span class="font-bold text-slate-900">42%</span>
                </div>
                <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-indigo-600" style="width: 42%"></div>
                </div>
            </div>
            <div>
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="font-semibold text-slate-700">Social Media</span>
                    <span class="font-bold text-slate-900">28%</span>
                </div>
                <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-emerald-500" style="width: 28%"></div>
                </div>
            </div>
            <div>
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="font-semibold text-slate-700">Direct</span>
                    <span class="font-bold text-slate-900">18%</span>
                </div>
                <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-slate-400" style="width: 18%"></div>
                </div>
            </div>
        </div>
    </article>

    <article class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="mb-6 text-base font-bold text-slate-900">Top Selling Products</h2>
        <div class="space-y-3">
            <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4">
                <div class="flex items-center gap-3">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-indigo-100 text-2xl">👖</div>
                    <div>
                        <p class="font-bold text-slate-900">Lc Waikiki Jean Cargo</p>
                        <p class="text-xs font-medium text-slate-400">1,240 sold</p>
                    </div>
                </div>
                <p class="text-lg font-bold text-indigo-600">$31K</p>
            </div>
            <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4">
                <div class="flex items-center gap-3">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-emerald-100 text-2xl">👟</div>
                    <div>
                        <p class="font-bold text-slate-900">Nike Air Max 2024</p>
                        <p class="text-xs font-medium text-slate-400">890 sold</p>
                    </div>
                </div>
                <p class="text-lg font-bold text-indigo-600">$80K</p>
            </div>
        </div>
    </article>
</section>
@endsection
