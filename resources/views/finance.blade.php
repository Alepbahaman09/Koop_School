@extends('layouts.app')

@section('title', 'Finance')
@section('page-title', 'Finance')

@section('content')
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-extrabold tracking-normal">Finance</h1>
    <div class="flex gap-2">
        <button class="h-10 rounded-lg bg-white px-4 text-sm font-bold text-slate-600 ring-1 ring-slate-200">October 2025</button>
        <button class="h-10 rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm shadow-indigo-100">Generate Report</button>
    </div>
</section>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <article class="overflow-hidden rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 p-6 text-white shadow-lg shadow-indigo-200">
        <p class="text-sm font-bold opacity-90">Total Income</p>
        <p class="mt-2 text-4xl font-extrabold tracking-tight">$124,542</p>
        <p class="mt-1 text-xs font-extrabold">▲ +41% from last month</p>
    </article>

    <article class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <p class="text-sm font-bold text-slate-400">Total Expenses</p>
        <p class="mt-2 text-4xl font-extrabold tracking-tight text-rose-600">$63,890</p>
        <p class="mt-1 text-xs font-extrabold text-rose-500">▼ -8% from last month</p>
    </article>

    <article class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <p class="text-sm font-bold text-slate-400">Net Profit</p>
        <p class="mt-2 text-4xl font-extrabold tracking-tight text-emerald-600">$60,652</p>
        <p class="mt-1 text-xs font-extrabold text-emerald-500">▲ +41% from last month</p>
    </article>
</section>

<section class="grid gap-5 xl:grid-cols-[1.3fr_1fr]">
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h2 class="mb-5 text-lg font-extrabold">Income vs Expense</h2>
        <div class="mb-3 flex justify-center gap-6 text-xs font-extrabold">
            <span class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-indigo-600"></span> Income</span>
            <span class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-rose-400"></span> Expense</span>
        </div>
        <div class="grid h-64 grid-cols-[50px_1fr] gap-3">
            <div class="flex flex-col justify-between pb-6 text-right text-xs font-bold text-slate-300">
                <span>$140K</span>
                <span>$120K</span>
                <span>$100K</span>
                <span>$80K</span>
                <span>$60K</span>
                <span>$40K</span>
                <span>$20K</span>
                <span>$0K</span>
            </div>
            <div class="relative">
                <div class="absolute inset-x-0 top-0 h-px bg-slate-100"></div>
                <div class="absolute inset-x-0 top-[14.28%] h-px bg-slate-100"></div>
                <div class="absolute inset-x-0 top-[28.56%] h-px bg-slate-100"></div>
                <div class="absolute inset-x-0 top-[42.84%] h-px bg-slate-100"></div>
                <div class="absolute inset-x-0 top-[57.12%] h-px bg-slate-100"></div>
                <div class="absolute inset-x-0 top-[71.4%] h-px bg-slate-100"></div>
                <div class="absolute inset-x-0 top-[85.68%] h-px bg-slate-100"></div>
                <div class="relative flex h-full items-end gap-2 pb-6">
                    @php
                        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
                        $income = [65, 72, 68, 75, 80, 78, 88, 85, 95, 98];
                        $expense = [45, 52, 48, 50, 55, 52, 58, 60, 62, 65];
                    @endphp
                    @foreach ($months as $index => $month)
                        <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                            <div class="flex w-full gap-1">
                                <div class="flex-1 rounded-t bg-indigo-600" style="height: {{ $income[$index] * 2.5 }}px;"></div>
                                <div class="flex-1 rounded-t bg-rose-400" style="height: {{ $expense[$index] * 2.5 }}px;"></div>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400">{{ $month }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </article>

    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h2 class="mb-5 text-lg font-extrabold">Expense Breakdown</h2>
        <div class="space-y-4">
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-bold text-slate-700">Operations</span>
                    <span class="text-sm font-extrabold text-slate-900">$24,200</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-indigo-600" style="width: 75%"></div>
                </div>
            </div>
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-bold text-slate-700">Marketing</span>
                    <span class="text-sm font-extrabold text-slate-900">$16,500</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-emerald-500" style="width: 60%"></div>
                </div>
            </div>
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-bold text-slate-700">Logistics</span>
                    <span class="text-sm font-extrabold text-slate-900">$12,100</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-amber-500" style="width: 45%"></div>
                </div>
            </div>
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-bold text-slate-700">Staff</span>
                    <span class="text-sm font-extrabold text-slate-900">$9,090</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-indigo-600" style="width: 35%"></div>
                </div>
            </div>
        </div>
    </article>
</section>

<section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
    <div class="mb-5 flex items-center justify-between">
        <h2 class="text-lg font-extrabold">Recent Transactions</h2>
        <div class="flex gap-2 text-sm font-extrabold">
            <button class="rounded-lg px-3 py-1.5 text-indigo-600 bg-indigo-50">All</button>
            <button class="rounded-lg px-3 py-1.5 text-slate-500 hover:bg-slate-50">Income</button>
            <button class="rounded-lg px-3 py-1.5 text-slate-500 hover:bg-slate-50">Expense</button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs font-extrabold uppercase tracking-wide text-slate-400">
                    <th class="py-3 pr-4">Transaction ID</th>
                    <th class="py-3 pr-4">Description</th>
                    <th class="py-3 pr-4">Date</th>
                    <th class="py-3 pr-4">Category</th>
                    <th class="py-3 pr-4">Amount</th>
                    <th class="py-3 pr-4">Type</th>
                    <th class="py-3 text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <tr class="hover:bg-slate-50/70">
                    <td class="py-4 pr-4 font-extrabold text-slate-900">#TRX-001</td>
                    <td class="py-4 pr-4 font-semibold text-slate-600">Order Payment - Savannah</td>
                    <td class="py-4 pr-4 text-slate-500">07/05/2025</td>
                    <td class="py-4 pr-4 text-slate-500">Sales</td>
                    <td class="py-4 pr-4 font-extrabold text-emerald-600">+$125.00</td>
                    <td class="py-4 pr-4"><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-600">Income</span></td>
                    <td class="py-4 text-right"><span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-extrabold text-sky-600">Completed</span></td>
                </tr>
                <tr class="hover:bg-slate-50/70">
                    <td class="py-4 pr-4 font-extrabold text-slate-900">#TRX-002</td>
                    <td class="py-4 pr-4 font-semibold text-slate-600">Marketing Campaign - Google Ads</td>
                    <td class="py-4 pr-4 text-slate-500">07/05/2025</td>
                    <td class="py-4 pr-4 text-slate-500">Marketing</td>
                    <td class="py-4 pr-4 font-extrabold text-rose-600">-$450.00</td>
                    <td class="py-4 pr-4"><span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-extrabold text-rose-600">Expense</span></td>
                    <td class="py-4 text-right"><span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-extrabold text-sky-600">Completed</span></td>
                </tr>
                <tr class="hover:bg-slate-50/70">
                    <td class="py-4 pr-4 font-extrabold text-slate-900">#TRX-003</td>
                    <td class="py-4 pr-4 font-semibold text-slate-600">Supplier Payment - Inventory</td>
                    <td class="py-4 pr-4 text-slate-500">06/05/2025</td>
                    <td class="py-4 pr-4 text-slate-500">Operations</td>
                    <td class="py-4 pr-4 font-extrabold text-rose-600">-$2,340.00</td>
                    <td class="py-4 pr-4"><span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-extrabold text-rose-600">Expense</span></td>
                    <td class="py-4 text-right"><span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-extrabold text-amber-600">Pending</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
@endsection
