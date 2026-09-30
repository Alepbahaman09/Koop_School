@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')

{{-- 1. Page Header with Title and Report Button --}}
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold text-slate-950">Product Management</h1>
        <p class="mt-1 text-xs font-semibold text-slate-400">Browse, organize, and trace cooperative products, stock levels, and store categories.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        {{-- Single Combined Product Report Button --}}
        <button type="button" onclick="document.getElementById('product-report-modal').showModal()"
            class="inline-flex h-10 items-center gap-2 rounded-xl bg-white px-3.5 text-xs font-extrabold text-indigo-700 shadow-xs ring-1 ring-slate-200 hover:bg-slate-50 transition">
            <svg class="h-4 w-4 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Product Report
        </button>
    </div>
</section>

{{-- 2. Top Metric Cards: Total Product, Low Stock, Out of Stock, Hidden Product --}}
<section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    {{-- Total Products --}}
    <a href="{{ route('products.index') }}" class="group rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-100 hover:ring-indigo-300 transition">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Products</span>
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </span>
        </div>
        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($stats['total']) }}</p>
        <p class="mt-1 text-xs font-semibold text-slate-400">All registered items</p>
    </a>

    {{-- Low Stock --}}
    <a href="{{ route('products.index', ['stock' => 'low']) }}" class="group rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-100 hover:ring-amber-300 transition {{ request('stock') === 'low' ? 'ring-2 ring-amber-400' : '' }}">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-amber-600">Low Stock</span>
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-100">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </span>
        </div>
        <p class="mt-2 text-2xl font-black text-amber-500">{{ number_format($stats['low']) }}</p>
        <p class="mt-1 text-xs font-semibold text-amber-600">Below alert threshold</p>
    </a>

    {{-- Out of Stock --}}
    <a href="{{ route('products.index', ['stock' => 'out']) }}" class="group rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-100 hover:ring-rose-300 transition {{ request('stock') === 'out' ? 'ring-2 ring-rose-400' : '' }}">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Out of Stock</span>
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-rose-50 text-rose-600 group-hover:bg-rose-100">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </span>
        </div>
        <p class="mt-2 text-2xl font-black text-rose-500">{{ number_format($stats['out']) }}</p>
        <p class="mt-1 text-xs font-semibold text-rose-500">Immediate action needed</p>
    </a>

    {{-- Hidden Products (Not appearing in app, in admin & db only) --}}
    <a href="{{ route('products.index', ['stock' => 'hidden']) }}" class="group rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-100 hover:ring-slate-400 transition {{ request('stock') === 'hidden' ? 'ring-2 ring-slate-600' : '' }}">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Hidden Products</span>
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-slate-600 group-hover:bg-slate-200">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                </svg>
            </span>
        </div>
        <p class="mt-2 text-2xl font-black text-slate-700">{{ number_format($stats['hidden']) }}</p>
        <p class="mt-1 text-xs font-semibold text-slate-400">Admin only (not in mobile app)</p>
    </a>
</section>

{{-- 3. Section BEFORE Category: Action Buttons for Add Category and Add Product --}}
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-2xl bg-gradient-to-r from-indigo-900 to-indigo-950 px-6 py-4 text-white shadow-md">
    <div>
        <h2 class="text-base font-extrabold tracking-tight">Catalogue Quick Actions</h2>
        <p class="text-xs text-indigo-200 mt-0.5">Manage store categories or add a new product directly into the cooperative store.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
        {{-- Add Category Button --}}
        <button type="button" onclick="document.getElementById('category-manager').showModal()"
            class="inline-flex h-10 items-center gap-2 rounded-xl bg-white/10 px-4 text-xs font-extrabold text-white backdrop-blur-xs ring-1 ring-white/20 hover:bg-white/20 transition">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Manage Categories
        </button>

        {{-- Add Product Button (Modal) --}}
        <details class="group" {{ request('create') || old('_product_form') === 'create' ? 'open' : '' }}>
            <summary class="inline-flex h-10 cursor-pointer list-none items-center gap-2 rounded-xl bg-indigo-500 px-4 text-xs font-extrabold text-white shadow-sm hover:bg-indigo-400 transition">
                <span class="text-base leading-none font-black">+</span> Add Product
            </summary>
            <div class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-xs" onclick="this.closest('details').open=false"></div>
            <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl text-left text-slate-900" onclick="event.stopPropagation()">
                <div class="mb-5 flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">Add New Product</h2>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Enter product specifications, inventory, pricing, and app visibility.</p>
                    </div>
                    <button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500 hover:bg-slate-200">&times;</button>
                </div>
                <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @include('partials.product-form', ['product' => null])
                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4 sm:col-span-2">
                        <button type="button" onclick="this.closest('details').open=false" class="h-10 rounded-xl bg-slate-100 px-5 text-xs font-extrabold text-slate-600 hover:bg-slate-200">
                            Cancel
                        </button>
                        <button type="submit" class="h-10 rounded-xl bg-indigo-600 px-6 text-xs font-extrabold text-white shadow-sm hover:bg-indigo-700">
                            Create Product
                        </button>
                    </div>
                </form>
            </div>
        </details>
    </div>
</section>

{{-- 4. Display Categories: Category Pills Section --}}
<section class="rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-100">
    <div class="mb-2.5 flex items-center justify-between">
        <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Categories</h2>
        <span class="text-[11px] font-semibold text-slate-400">Click to filter by category</span>
    </div>
    <div class="overflow-x-auto pb-1">
        <div class="flex min-w-max flex-nowrap items-center gap-2">
            {{-- All Categories Chip --}}
            <a href="{{ route('products.index', array_filter(['search' => request('search'), 'stock' => request('stock'), 'supplier' => request('supplier')])) }}"
               class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl px-4 py-2 text-xs font-extrabold transition {{ !request('category') ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 ring-1 ring-slate-200/60' }}">
                <span>All Products</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ !request('category') ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $stats['total'] }}</span>
            </a>

            {{-- Dynamic Active Categories --}}
            @foreach ($allCategories as $cat)
                <a href="{{ route('products.index', array_filter(['category' => $cat->id, 'search' => request('search'), 'stock' => request('stock'), 'supplier' => request('supplier')])) }}"
                   class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl px-4 py-2 text-xs font-extrabold transition {{ (string) request('category') === (string) $cat->id ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 ring-1 ring-slate-200/60' }}">
                    <span>{{ $cat->name }}</span>
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ (string) request('category') === (string) $cat->id ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $cat->products_count }}</span>
                </a>
            @endforeach

            {{-- Uncategorised Chip --}}
            <a href="{{ route('products.index', array_filter(['category' => 'uncategorized', 'search' => request('search'), 'stock' => request('stock'), 'supplier' => request('supplier')])) }}"
               class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl px-4 py-2 text-xs font-extrabold transition {{ request('category') === 'uncategorized' ? 'bg-amber-500 text-white shadow-xs' : 'bg-amber-50/60 text-amber-800 hover:bg-amber-100 ring-1 ring-amber-200/60' }}">
                <span>Uncategorised</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ request('category') === 'uncategorized' ? 'bg-amber-600 text-white' : 'bg-amber-200 text-amber-900' }}">{{ $uncategorizedCount }}</span>
            </a>
        </div>
    </div>
</section>

{{-- 5. Search and Filter Bar --}}
<section class="rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-100">
    <form method="GET" class="flex flex-wrap items-center gap-3">
        <input type="hidden" name="category" value="{{ request('category') }}">

        {{-- Search Input --}}
        <div class="relative min-w-[240px] flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input name="search" value="{{ request('search') }}" placeholder="Search by product name or SKU..."
                   class="h-10 w-full rounded-xl border-slate-200 bg-slate-50/50 pl-9 pr-3 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        {{-- Stock / Visibility Dropdown --}}
        <select name="stock" class="h-10 min-w-[170px] rounded-xl border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All Stock & Status</option>
            <option value="low" @selected(request('stock') === 'low')>⚠️ Low Stock</option>
            <option value="out" @selected(request('stock') === 'out')>🔴 Out of Stock</option>
            <option value="hidden" @selected(request('stock') === 'hidden')>👁️ Hidden from App</option>
            <option value="visible" @selected(request('stock') === 'visible')>📱 Visible in App</option>
        </select>

        {{-- Supplier Filter Dropdown --}}
        <select name="supplier" class="h-10 min-w-[170px] rounded-xl border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All Suppliers</option>
            @foreach ($suppliers as $sup)
                <option value="{{ $sup->id }}" @selected((string) request('supplier') === (string) $sup->id)>{{ $sup->company_name }}</option>
            @endforeach
        </select>

        {{-- Submit & Clear Buttons --}}
        <button type="submit" class="h-10 rounded-xl bg-indigo-600 px-5 text-xs font-extrabold text-white hover:bg-indigo-700 transition shadow-xs">
            Filter
        </button>

        @if (request()->hasAny(['search', 'stock', 'category', 'supplier']))
            <a href="{{ route('products.index') }}" class="inline-flex h-10 items-center rounded-xl bg-slate-100 px-4 text-xs font-extrabold text-slate-600 hover:bg-slate-200 transition">
                Reset
            </a>
        @endif
    </form>
</section>

{{-- 6. Horizontal Product View: Displaying All Products in a Horizontal Layout --}}
<section class="space-y-3.5">
    <div class="flex items-center justify-between px-1">
        <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Products Listing ({{ $products->total() }})</h2>
        <span class="text-xs font-semibold text-slate-400">Showing page {{ $products->currentPage() }} of {{ $products->lastPage() ?: 1 }}</span>
    </div>

    <div class="flex flex-col gap-3">
        @forelse ($products as $product)
            {{-- Horizontal Product Card --}}
            <article class="flex flex-col gap-4 rounded-2xl bg-white p-4 shadow-xs ring-1 ring-slate-100 transition hover:shadow-md hover:ring-indigo-200 lg:flex-row lg:items-center lg:justify-between">
                {{-- Left: Image & Info --}}
                <div class="flex items-start gap-4 min-w-0 flex-1">
                    {{-- Product Image --}}
                    <div class="relative h-20 w-20 sm:h-24 sm:w-24 shrink-0 overflow-hidden rounded-xl bg-slate-50 ring-1 ring-slate-200/80 flex items-center justify-center">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" onerror="this.parentElement.innerHTML='<span class=&quot;text-[10px] font-bold text-slate-400&quot;>No Image</span>'">
                        @else
                            <svg class="h-8 w-8 text-indigo-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        @endif
                    </div>

                    {{-- Product Details --}}
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-extrabold text-slate-900 truncate" title="{{ $product->name }}">{{ $product->name }}</h3>

                            {{-- App Visibility Status Badge --}}
                            @if ($product->is_active)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-700 ring-1 ring-emerald-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> In Mobile App
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-extrabold text-slate-600 ring-1 ring-slate-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Hidden (Admin Only)
                                </span>
                            @endif
                        </div>

                        {{-- Metadata Chips --}}
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            {{-- Category Badge --}}
                            @if ($product->category)
                                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-extrabold text-indigo-700">
                                    {{ $product->category->name }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-extrabold text-amber-700">
                                    Uncategorised
                                </span>
                            @endif

                            {{-- SKU --}}
                            <span class="font-mono text-slate-500 font-semibold text-xs bg-slate-100 px-2 py-0.5 rounded-md">
                                SKU: {{ $product->sku }}
                            </span>

                            {{-- Supplier --}}
                            @if ($product->supplier)
                                <span class="text-slate-500 font-semibold text-xs">
                                    Vendor: <span class="text-slate-800 font-bold">{{ $product->supplier->company_name }}</span>
                                </span>
                            @endif
                        </div>

                        {{-- Sizes Available --}}
                        @if ($product->sizes->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-1 pt-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Sizes:</span>
                                @foreach ($product->sizes as $productSize)
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-700">
                                        {{ $productSize->size }}: <strong class="text-indigo-600">{{ $productSize->stock_quantity }}</strong>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if ($product->description)
                            <p class="text-xs text-slate-500 line-clamp-1 max-w-xl">{{ $product->description }}</p>
                        @endif
                    </div>
                </div>

                {{-- Middle & Right: Pricing, Stock, and Actions --}}
                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-3 lg:border-t-0 lg:pt-0 lg:justify-end">
                    {{-- Price and Unit --}}
                    <div class="text-left lg:text-right min-w-[110px]">
                        <p class="text-xs font-bold uppercase text-slate-400">Selling Price</p>
                        <p class="text-lg font-black text-indigo-600">RM {{ number_format($product->price, 2) }}</p>
                        <p class="text-[11px] font-semibold text-slate-400">per {{ $product->unit ?: 'pcs' }}</p>
                    </div>

                    {{-- Stock Status Badge --}}
                    <div class="text-left lg:text-center min-w-[130px]">
                        <p class="text-xs font-bold uppercase text-slate-400">Inventory</p>
                        @if ($product->stock_quantity === 0)
                            <span class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-rose-50 px-3 py-1 text-xs font-extrabold text-rose-600 ring-1 ring-rose-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Out of Stock
                            </span>
                        @elseif ($product->stock_quantity <= $product->min_stock_level)
                            <span class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 text-xs font-extrabold text-amber-700 ring-1 ring-amber-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> {{ $product->stock_quantity }} {{ $product->unit ?: 'pcs' }}
                            </span>
                        @else
                            <span class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-700 ring-1 ring-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> {{ $product->stock_quantity }} {{ $product->unit ?: 'pcs' }}
                            </span>
                        @endif
                    </div>

                    {{-- Actions: Edit & Delete --}}
                    <div class="flex items-center gap-2">
                        {{-- Edit Product Details Modal --}}
                        <details {{ request('edit') == $product->id || old('_product_form') === 'edit-'.$product->id ? 'open' : '' }}>
                            <summary class="inline-flex h-9 cursor-pointer list-none items-center gap-1.5 rounded-xl bg-indigo-50 px-3 text-xs font-extrabold text-indigo-700 hover:bg-indigo-100 transition" title="Edit Product">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m4 20 4-1 11-11-3-3L5 16l-1 4ZM14 7l3 3" /></svg>
                                <span>Edit</span>
                            </summary>
                            <div class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-xs" onclick="this.closest('details').open=false"></div>
                            <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl text-left text-slate-900" onclick="event.stopPropagation()">
                                <div class="mb-5 flex items-center justify-between border-b border-slate-100 pb-4">
                                    <div>
                                        <h2 class="text-lg font-black text-slate-950">Edit {{ $product->name }}</h2>
                                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Modify pricing, SKU, category, stock and publishing status.</p>
                                    </div>
                                    <button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500 hover:bg-slate-200">&times;</button>
                                </div>
                                <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                                    @csrf
                                    @method('PATCH')
                                    @include('partials.product-form', ['product' => $product])
                                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4 sm:col-span-2">
                                        <button type="button" onclick="this.closest('details').open=false" class="h-10 rounded-xl bg-slate-100 px-5 text-xs font-extrabold text-slate-600 hover:bg-slate-200">
                                            Cancel
                                        </button>
                                        <button type="submit" class="h-10 rounded-xl bg-indigo-600 px-6 text-xs font-extrabold text-white shadow-sm hover:bg-indigo-700">
                                            Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </details>

                        {{-- Delete Product Form --}}
                        <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Permanently delete product \'{{ addslashes($product->name) }}\'?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="grid h-9 w-9 place-items-center rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition" title="Delete Product">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl bg-white py-16 text-center shadow-xs ring-1 ring-slate-100">
                <svg class="mx-auto h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p class="mt-3 text-sm font-extrabold text-slate-700">No products match your filters</p>
                <p class="mt-1 text-xs text-slate-400">Try changing your search term, category or stock filter.</p>
                <div class="mt-4">
                    <a href="{{ route('products.index') }}" class="inline-flex h-9 items-center rounded-xl bg-indigo-600 px-4 text-xs font-extrabold text-white hover:bg-indigo-700">
                        View All Products
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($products->hasPages())
        <div class="pt-2">
            {{ $products->links() }}
        </div>
    @endif
</section>

{{-- ============================================================
     PRODUCT REPORT & AUDIT TRACE MODAL
     ============================================================ --}}
<dialog id="product-report-modal" class="m-auto max-h-[92vh] w-[calc(100%-2rem)] max-w-5xl overflow-hidden rounded-2xl bg-white p-0 shadow-2xl backdrop:bg-slate-950/40">
    <div class="relative flex max-h-[92vh] flex-col">
        {{-- Header --}}
        <div class="relative border-b border-slate-100 px-6 py-4 pr-16 space-y-3.5">
            {{-- Title Section --}}
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-lg font-black text-slate-950">Product Report & Activity Audit Trace</h2>
                    <p class="text-xs font-semibold text-slate-400">Comprehensive overview of all products, stock valuation, and logged changes.</p>
                </div>
            </div>

            {{-- Action Buttons: Bottom of Title Section (checkbox, download, print) --}}
            <div class="flex flex-wrap items-center gap-2 pt-0.5">
                {{-- Toggle Include Action & Audit Report --}}
                <label class="inline-flex items-center gap-2 cursor-pointer rounded-xl bg-slate-50 px-3 py-1.5 text-xs font-extrabold text-slate-700 ring-1 ring-slate-200">
                    <input type="checkbox" id="toggle-prod-actions" checked onchange="toggleProdActionSection(this.checked)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Include Action & Audit Report</span>
                </label>

                {{-- Download PDF Report Button --}}
                <a id="btn-download-prod-pdf" href="{{ route('products.report.pdf', ['with_actions' => 1]) }}"
                   class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 text-xs font-extrabold text-white hover:bg-rose-700 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Download PDF
                </a>

                {{-- Print Button (Streams exact PDF layout in browser window) --}}
                <button type="button" onclick="printProductPdfReport()" class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-slate-100 px-3.5 text-xs font-extrabold text-slate-700 hover:bg-slate-200 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </button>
            </div>

            {{-- Close Button (Top right corner like usual) --}}
            <form method="dialog" class="absolute top-4 right-5 m-0">
                <button class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition font-extrabold text-lg" title="Close report dialog">&times;</button>
            </form>
        </div>

        {{-- Body with Tabs --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            {{-- Summary Metric Row --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Total Products</p>
                    <p class="mt-1 text-xl font-black text-slate-900">{{ number_format($stats['total']) }} SKUs</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Total Units in Stock</p>
                    <p class="mt-1 text-xl font-black text-indigo-600">{{ number_format($stats['total_units']) }} units</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Inventory Valuation</p>
                    <p class="mt-1 text-xl font-black text-emerald-600">RM {{ number_format($stats['inventory_value'], 2) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Categories / Uncategorised</p>
                    <p class="mt-1 text-xl font-black text-slate-900">{{ $categoryStats['total'] }} / {{ $uncategorizedCount }}</p>
                </div>
            </div>

            {{-- Activity & Audit Trail Section --}}
            <div id="prod-audit-section" class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Change & Activity Audit Trail</h3>
                        <p class="text-xs text-slate-400">Automatic trace of all additions, updates, and deletions across products and categories.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                        {{ $activityLogs->count() }} recent events
                    </span>
                </div>

                <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-100">
                    @forelse ($activityLogs as $log)
                        <div class="flex items-start justify-between gap-3 p-3 hover:bg-slate-50/60 transition">
                            <div class="flex items-start gap-2.5">
                                @if ($log->action === 'created')
                                    <span class="mt-0.5 inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-700">Added</span>
                                @elseif ($log->action === 'updated')
                                    <span class="mt-0.5 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-indigo-700">Edited</span>
                                @elseif ($log->action === 'deleted')
                                    <span class="mt-0.5 inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-rose-700">Deleted</span>
                                @else
                                    <span class="mt-0.5 inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-slate-700">{{ $log->action }}</span>
                                @endif
                                <div>
                                    <p class="text-xs font-extrabold text-slate-900">
                                        <span class="capitalize text-slate-500 font-bold">[{{ $log->entity_type }}]</span> {{ $log->entity_name }}
                                    </p>
                                    @if ($log->details)
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $log->details }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right shrink-0 text-[11px] text-slate-400">
                                <span class="font-semibold text-slate-600">{{ $log->admin_name ?: 'Admin' }}</span>
                                <span class="block">{{ $log->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs font-semibold text-slate-400">
                            No logged activity yet. Changes made from this point onwards will be traced here automatically.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Full Inventory Catalogue Table --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Catalogue Inventory Breakdown</h3>
                        <p class="text-xs text-slate-400">Complete item listing with stock quantity, pricing, and app visibility status.</p>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-80 overflow-y-auto rounded-xl border border-slate-100">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-slate-50 text-[11px] font-extrabold uppercase text-slate-400 border-b border-slate-200">
                            <tr>
                                <th class="p-3">SKU</th>
                                <th class="p-3">Product Name</th>
                                <th class="p-3">Category</th>
                                <th class="p-3">Stock</th>
                                <th class="p-3 text-right">Selling Price</th>
                                <th class="p-3 text-right">Total Value</th>
                                <th class="p-3 text-center">App Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            @foreach ($reportProducts as $rp)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3 font-mono text-slate-500 font-bold">{{ $rp->sku }}</td>
                                    <td class="p-3 font-extrabold text-slate-900">{{ $rp->name }}</td>
                                    <td class="p-3">
                                        @if ($rp->category)
                                            <span class="rounded bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700">{{ $rp->category->name }}</span>
                                        @else
                                            <span class="rounded bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">Uncategorised</span>
                                        @endif
                                    </td>
                                    <td class="p-3 font-bold {{ $rp->stock_quantity === 0 ? 'text-rose-600' : ($rp->stock_quantity <= $rp->min_stock_level ? 'text-amber-600' : 'text-slate-800') }}">
                                        {{ $rp->stock_quantity }} {{ $rp->unit ?: 'pcs' }}
                                    </td>
                                    <td class="p-3 text-right font-bold text-slate-900">RM {{ number_format($rp->price, 2) }}</td>
                                    <td class="p-3 text-right font-extrabold text-emerald-600">RM {{ number_format($rp->price * $rp->stock_quantity, 2) }}</td>
                                    <td class="p-3 text-center">
                                        @if ($rp->is_active)
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Published</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">Hidden</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</dialog>

@include('partials.category-manager')
@endsection

@push('scripts')
<script>
    window.toggleProdActionSection = function(show) {
        const sec = document.getElementById('prod-audit-section');
        if (sec) sec.style.display = show ? 'block' : 'none';
        const btn = document.getElementById('btn-download-prod-pdf');
        if (btn) {
            btn.href = "{{ route('products.report.pdf') }}?with_actions=" + (show ? '1' : '0');
        }
    };

    window.printProductPdfReport = function() {
        const withActions = document.getElementById('toggle-prod-actions')?.checked ? 1 : 0;
        const url = "{{ route('products.report.pdf') }}?stream=1&with_actions=" + withActions;
        window.open(url, '_blank');
    };

    @if (request('categories') || old('_category_form'))
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('category-manager').showModal();
        });
    @endif
</script>
@endpush
