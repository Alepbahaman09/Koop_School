@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')
@include('partials.admin-alerts')

<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold text-slate-950">Products</h1>
        <p class="mt-1 text-xs font-semibold text-slate-400">Browse and manage your cooperative product catalogue.</p>
    </div>
    <details class="group" {{ request('create') ? 'open' : '' }}>
        <summary class="inline-flex h-10 cursor-pointer list-none items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700"><span class="text-lg">+</span> Add Product</summary>
        <div class="fixed inset-0 z-40 bg-slate-950/30"></div>
        <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-3xl overflow-y-auto rounded-xl bg-white p-6 shadow-2xl">
            <div class="mb-5 flex items-center justify-between"><h2 class="text-lg font-extrabold">Add Product</h2><button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500">&times;</button></div>
            <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                @csrf
                @include('partials.product-form', ['product' => null])
                <div class="flex justify-end sm:col-span-2"><button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white">Create Product</button></div>
            </form>
        </div>
    </details>
</section>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['Total Products', $stats['total'], 'text-slate-950', 'All catalogue items'],
        ['Active', $stats['active'], 'text-slate-950', $stats['total'] > 0 ? number_format(($stats['active'] / $stats['total']) * 100, 1).'% of total' : '0% of total'],
        ['Low Stock', $stats['low'], 'text-amber-500', 'Need restock'],
        ['Out of Stock', $stats['out'], 'text-rose-500', 'Action required'],
    ] as [$label, $value, $tone, $note])
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <p class="text-xs font-bold text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-2xl font-extrabold {{ $tone }}">{{ number_format($value) }}</p>
            <p class="mt-1 text-xs font-extrabold {{ str_contains($note, 'required') ? 'text-rose-400' : (str_contains($note, 'restock') ? 'text-amber-400' : 'text-emerald-400') }}">{{ $note }}</p>
        </article>
    @endforeach
</section>

<section class="space-y-4">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('products.index', array_filter(['search' => request('search'), 'stock' => request('stock')])) }}" class="rounded-full px-4 py-1.5 text-xs font-extrabold {{ !request('category') ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}">All</a>
            @foreach ($categories as $category)
                <a href="{{ route('products.index', array_filter(['category' => $category->id, 'search' => request('search'), 'stock' => request('stock')])) }}" class="rounded-full px-4 py-1.5 text-xs font-extrabold {{ request('category') == $category->id ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}">{{ $category->name }}</a>
            @endforeach
        </div>
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="hidden" name="category" value="{{ request('category') }}">
            <input name="search" value="{{ request('search') }}" placeholder="Search name or SKU" class="h-9 rounded-lg border-slate-200 bg-white text-xs font-semibold">
            <select name="stock" class="h-9 rounded-lg border-slate-200 bg-white text-xs font-bold text-slate-600">
                <option value="">All status</option><option value="active" @selected(request('stock') === 'active')>Active</option><option value="inactive" @selected(request('stock') === 'inactive')>Inactive</option><option value="low" @selected(request('stock') === 'low')>Low stock</option><option value="out" @selected(request('stock') === 'out')>Out of stock</option>
            </select>
            <button class="h-9 rounded-lg bg-indigo-600 px-4 text-xs font-extrabold text-white">Filter</button>
        </form>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @forelse ($products as $product)
            @php $gradients = ['from-indigo-100 to-violet-200', 'from-emerald-100 to-teal-200', 'from-rose-100 to-pink-200', 'from-amber-100 to-yellow-200', 'from-sky-100 to-cyan-200']; @endphp
            <article class="group overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100 transition hover:shadow-lg">
                <div class="relative h-44 overflow-hidden bg-gradient-to-br {{ $gradients[$loop->index % count($gradients)] }}">
                    @if ($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                    @else
                        <div class="grid h-full place-items-center text-indigo-400"><svg class="h-14 w-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M4 5h16v14H4zM8 12l3-3 5 6 2-2 2 2" /></svg></div>
                    @endif
                    <span class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $product->is_active ? 'bg-white/90 text-emerald-600' : 'bg-slate-900/80 text-white' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                    <div class="absolute right-3 top-3 flex gap-1 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100">
                        <details {{ request('edit') == $product->id ? 'open' : '' }}>
                            <summary class="grid h-8 w-8 cursor-pointer list-none place-items-center rounded-lg bg-white text-indigo-600 shadow"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m4 20 4-1 11-11-3-3L5 16l-1 4ZM14 7l3 3" /></svg></summary>
                            <div class="fixed inset-0 z-40 bg-slate-950/30"></div>
                            <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-3xl overflow-y-auto rounded-xl bg-white p-6 shadow-2xl">
                                <div class="mb-5 flex items-center justify-between"><h2 class="text-lg font-extrabold">Edit {{ $product->name }}</h2><button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500">&times;</button></div>
                                <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">@csrf @method('PATCH') @include('partials.product-form', ['product' => $product])<div class="flex justify-end sm:col-span-2"><button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white">Save Changes</button></div></form>
                            </div>
                        </details>
                        <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product? Products with order history will only be deactivated.')">@csrf @method('DELETE')<button class="grid h-8 w-8 place-items-center rounded-lg bg-white text-rose-500 shadow"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg></button></form>
                    </div>
                </div>
                <div class="p-4">
                    <p class="truncate text-sm font-extrabold text-slate-900">{{ $product->name }}</p>
                    <p class="mt-0.5 truncate text-[11px] font-semibold text-slate-400">{{ $product->category?->name ?? 'Uncategorised' }} / {{ $product->sku }}</p>
                    <div class="mt-3 flex items-center justify-between gap-2">
                        <p class="text-base font-extrabold text-indigo-600">RM {{ number_format($product->price, 2) }}</p>
                        <p class="text-[11px] font-bold {{ $product->stock_quantity === 0 ? 'text-rose-500' : ($product->stock_quantity <= $product->min_stock_level ? 'text-amber-500' : 'text-slate-400') }}">{{ $product->stock_quantity === 0 ? 'Out of stock' : ($product->stock_quantity <= $product->min_stock_level ? 'Low: '.$product->stock_quantity : 'Stock: '.$product->stock_quantity) }}</p>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-xl bg-white py-20 text-center text-sm font-semibold text-slate-400 shadow-sm ring-1 ring-slate-100 sm:col-span-2 xl:col-span-4">No products match these filters.</div>
        @endforelse
    </div>
    <div>{{ $products->links() }}</div>

    <details class="w-fit">
        <summary class="cursor-pointer text-xs font-extrabold text-indigo-600">+ Add a new category</summary>
        <form method="POST" action="{{ route('categories.store') }}" enctype="multipart/form-data" class="mt-2 flex flex-wrap gap-2">
            @csrf
            <input name="name" required placeholder="Category name" class="h-9 rounded-lg border-slate-200 text-xs">
            <input name="icon" type="file" accept="image/*" class="h-9 max-w-64 rounded-lg border border-slate-200 bg-white text-xs file:mr-2 file:h-9 file:border-0 file:bg-indigo-50 file:px-3 file:font-extrabold file:text-indigo-700">
            <input type="hidden" name="is_active" value="1">
            <button class="h-9 rounded-lg bg-indigo-600 px-4 text-xs font-extrabold text-white">Add</button>
        </form>
    </details>

    <details class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
        <summary class="cursor-pointer text-xs font-extrabold text-indigo-600">Manage category names and icons</summary>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @foreach ($allCategories as $category)
                <div class="rounded-xl border border-slate-200 p-3">
                    <form method="POST" action="{{ route('categories.update', $category) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <div class="flex items-center gap-3">
                            <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-indigo-50">
                                @if ($category->icon_url)
                                    <img src="{{ $category->icon_url }}" alt="{{ $category->name }}" class="h-full w-full object-contain">
                                @else
                                    <span class="text-lg font-black text-indigo-500">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <input name="name" required value="{{ $category->name }}" class="h-9 min-w-0 flex-1 rounded-lg border-slate-200 text-xs font-semibold">
                        </div>
                        <input name="description" value="{{ $category->description }}" placeholder="Description (optional)" class="h-9 w-full rounded-lg border-slate-200 text-xs">
                        <input name="icon" type="file" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-extrabold file:text-indigo-700">
                        <div class="flex items-center justify-between gap-3">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-600">
                                <input name="is_active" value="1" type="checkbox" @checked($category->is_active) class="rounded border-slate-300 text-indigo-600">
                                Visible in app
                            </label>
                            <button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-extrabold text-white">Save changes</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('categories.destroy', $category) }}" class="mt-2 text-right" onsubmit="return confirm('Delete this category?')">
                        @csrf
                        @method('DELETE')
                        <button class="text-xs font-bold text-red-600">Delete category</button>
                    </form>
                </div>
            @endforeach
        </div>
    </details>
</section>
@endsection
