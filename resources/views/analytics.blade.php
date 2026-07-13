@extends('layouts.app')

@section('title', 'Analytics')
@section('page-title', 'Analytics')

@section('content')
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold text-slate-950">Analytics</h1>
        <p class="mt-1 text-xs font-semibold text-slate-400">{{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}</p>
    </div>
    <div class="flex gap-2">
        <form method="GET">
            <select name="days" onchange="this.form.submit()" class="h-10 rounded-lg border-0 bg-white px-4 text-sm font-bold text-slate-500 shadow-sm ring-1 ring-slate-100">
                @foreach ([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last year'] as $value => $label)
                    <option value="{{ $value }}" @selected($days === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('analytics.export', ['days' => $days]) }}" class="inline-flex h-10 items-center rounded-lg bg-white px-4 text-sm font-extrabold text-slate-700 shadow-sm ring-1 ring-slate-100 hover:text-indigo-600">Export Report</a>
    </div>
</section>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @php
        $metricIcons = [
            'indigo' => ['bg-indigo-50 text-indigo-600', 'M4 19V5m5 14V9m5 10V3m5 16v-7'],
            'sky' => ['bg-sky-50 text-sky-600', 'M6 6h15l-1.5 9h-12L6 6Zm0 0L5 3H2m7 18h.01m9 0h.01'],
            'amber' => ['bg-amber-50 text-amber-600', 'M12 3v18m5-14H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H7'],
            'emerald' => ['bg-emerald-50 text-emerald-600', 'M5 12l4 4L19 6'],
        ];
    @endphp
    @foreach ($metrics as $metric)
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-950">{{ $metric['value'] }}</p>
                </div>
                <span class="grid h-9 w-9 place-items-center rounded-lg {{ $metricIcons[$metric['tone']][0] }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $metricIcons[$metric['tone']][1] }}" /></svg>
                </span>
            </div>
            <p class="mt-2 text-xs font-extrabold {{ $metric['change']['positive'] ? 'text-emerald-500' : 'text-rose-500' }}">
                {{ $metric['change']['positive'] ? '+' : '' }}{{ number_format($metric['change']['value'], 1) }}% vs previous period
            </p>
        </article>
    @endforeach
</section>

<section class="grid gap-5 xl:grid-cols-[1.15fr_1fr]">
    <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <div class="mb-5 flex items-start justify-between">
            <div>
                <h2 class="text-sm font-extrabold text-slate-950">Revenue & Orders</h2>
                <p class="mt-1 text-xs font-semibold text-slate-400">Performance for the selected period</p>
            </div>
            <div class="flex gap-3 text-[11px] font-bold text-slate-500">
                <span class="flex items-center gap-1.5"><i class="h-2 w-2 rounded-sm bg-indigo-500"></i> Revenue</span>
                <span class="flex items-center gap-1.5"><i class="h-2 w-2 rounded-sm bg-rose-400"></i> Orders</span>
            </div>
        </div>
        <div class="h-72"><canvas id="revenueChart"></canvas></div>
    </article>

    <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h2 class="text-sm font-extrabold text-slate-950">Sales by Category</h2>
        <p class="mt-1 text-xs font-semibold text-slate-400">Non-cancelled order revenue</p>
        <div class="mt-5 grid items-center gap-5 sm:grid-cols-[1fr_1.1fr]">
            <div class="relative mx-auto h-52 w-52">
                <canvas id="categoryChart"></canvas>
                <div class="pointer-events-none absolute inset-0 grid place-items-center text-center">
                    <div><p class="text-2xl font-extrabold text-slate-950">RM {{ number_format($categorySales->sum('revenue'), 0) }}</p><p class="text-xs font-bold text-slate-400">Total sales</p></div>
                </div>
            </div>
            <div class="space-y-3">
                @forelse ($categorySales as $category)
                    <div class="flex items-center gap-2 text-xs">
                        <span class="category-key h-2.5 w-2.5 rounded-sm"></span>
                        <span class="min-w-0 flex-1 truncate font-bold text-slate-600">{{ $category->name }}</span>
                        <span class="font-extrabold text-slate-950">RM {{ number_format($category->revenue, 0) }}</span>
                    </div>
                @empty
                    <p class="text-sm font-semibold text-slate-400">No category sales yet.</p>
                @endforelse
            </div>
        </div>
    </article>
</section>

<section>
    <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h2 class="text-sm font-extrabold text-slate-950">Top Selling Items</h2>
        <p class="mt-1 text-xs font-semibold text-slate-400">Best performers in the selected period</p>
        <div class="mt-3 divide-y divide-slate-100">
            @forelse ($topProducts as $product)
                <div class="flex items-center gap-3 py-3">
                    <div class="h-11 w-11 shrink-0 overflow-hidden rounded-lg bg-gradient-to-br from-indigo-100 to-violet-50">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                        @else
                            <div class="grid h-full place-items-center text-indigo-400"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v14H4zM8 12l3-3 5 6 2-2 2 2" /></svg></div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1"><p class="truncate text-xs font-extrabold text-slate-800">{{ $product->name }}</p><p class="mt-1 text-[11px] font-semibold text-slate-400">{{ number_format($product->units) }} sold</p></div>
                    <p class="text-sm font-extrabold text-indigo-600">RM {{ number_format($product->revenue, 0) }}</p>
                </div>
            @empty
                <p class="py-10 text-center text-sm font-semibold text-slate-400">No item sales yet.</p>
            @endforelse
        </div>
    </article>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    const palette = ['#6366f1', '#10b981', '#f59e0b', '#3b82f6', '#ef4444', '#a855f7'];
    document.querySelectorAll('.category-key').forEach((key, index) => key.style.backgroundColor = palette[index % palette.length]);

    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: @json($salesTrend->pluck('label')),
            datasets: [
                { label: 'Revenue', data: @json($salesTrend->pluck('revenue')), borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,.10)', fill: true, tension: .4, pointRadius: 2, borderWidth: 2 },
                { label: 'Orders', data: @json($salesTrend->pluck('orders')), borderColor: '#fb7185', backgroundColor: 'transparent', tension: .4, pointRadius: 2, borderWidth: 2, yAxisID: 'orders' }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } }, y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, orders: { beginAtZero: true, position: 'right', grid: { display: false } } } }
    });

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: { labels: @json($categorySales->pluck('name')), datasets: [{ data: @json($categorySales->pluck('revenue')), backgroundColor: palette, borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }
    });
</script>
@endpush
