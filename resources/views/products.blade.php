@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')
@include('partials.admin-alerts')

<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold">Products & Stock</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">Manage cooperative items, prices, availability, and stock levels.</p>
    </div>
    <details class="group" {{ request('create') ? 'open' : '' }}>
        <summary class="inline-flex h-10 cursor-pointer list-none items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white hover:bg-indigo-700">
            <span class="text-lg">+</span> Add Product
        </summary>
        <div class="fixed inset-0 z-40 bg-slate-950/30"></div>
        <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-3xl overflow-y-auto rounded-lg bg-white p-6 shadow-2xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-extrabold">Add Product</h2>
                <button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500" title="Close">&times;</button>
            </div>
            <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                @csrf
                @include('partials.product-form', ['product' => null])
                <div class="flex justify-end gap-2 sm:col-span-2">
                    <button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white">Create Product</button>
                </div>
            </form>
        </div>
    </details>
</section>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    @foreach ([
        ['Total Products', $stats['total'], 'text-indigo-600'],
        ['Active', $stats['active'], 'text-emerald-600'],
        ['Low Stock', $stats['low'], 'text-amber-600'],
        ['Out of Stock', $stats['out'], 'text-rose-600'],
        ['Inventory Value', 'RM '.number_format($stats['inventory_value'], 2), 'text-sky-600'],
    ] as [$label, $value, $tone])
        <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <p class="text-sm font-bold text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-2xl font-extrabold {{ $tone }}">{{ $value }}</p>
        </article>
    @endforeach
</section>

<section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
    <div class="mb-5 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <form method="GET" class="grid flex-1 gap-3 sm:grid-cols-3">
            <label class="text-xs font-extrabold uppercase text-slate-400">Search
                <input name="search" value="{{ request('search') }}" placeholder="Name or SKU" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
            </label>
            <label class="text-xs font-extrabold uppercase text-slate-400">Category
                <select name="category" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs font-extrabold uppercase text-slate-400">Status
                <select name="stock" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
                    <option value="">All products</option>
                    <option value="active" @selected(request('stock') === 'active')>Active</option>
                    <option value="inactive" @selected(request('stock') === 'inactive')>Inactive</option>
                    <option value="low" @selected(request('stock') === 'low')>Low stock</option>
                    <option value="out" @selected(request('stock') === 'out')>Out of stock</option>
                </select>
            </label>
            <div class="flex gap-2 sm:col-span-3">
                <button class="h-10 rounded-lg bg-slate-900 px-5 text-sm font-extrabold text-white">Apply Filters</button>
                <a href="{{ route('products.index') }}" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-slate-500 ring-1 ring-slate-200">Clear</a>
            </div>
        </form>

        <details>
            <summary class="cursor-pointer text-sm font-extrabold text-indigo-600">Add category</summary>
            <form method="POST" action="{{ route('categories.store') }}" class="mt-3 flex gap-2">
                @csrf
                <input name="name" required placeholder="Category name" class="h-10 rounded-lg border-slate-200 text-sm">
                <input type="hidden" name="is_active" value="1">
                <button class="h-10 rounded-lg bg-indigo-50 px-4 text-sm font-extrabold text-indigo-700">Add</button>
            </form>
        </details>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead><tr class="border-b border-slate-100 text-xs font-extrabold uppercase text-slate-400">
                <th class="py-3 pr-4">Product</th><th class="py-3 pr-4">Category</th><th class="py-3 pr-4">Price</th>
                <th class="py-3 pr-4">Stock</th><th class="py-3 pr-4">Status</th><th class="py-3 text-right">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($products as $product)
                <tr>
                    <td class="py-4 pr-4"><p class="font-extrabold">{{ $product->name }}</p><p class="text-xs font-bold text-slate-400">{{ $product->sku }}</p></td>
                    <td class="py-4 pr-4 font-semibold text-slate-600">{{ $product->category?->name ?? 'Uncategorised' }}</td>
                    <td class="py-4 pr-4 font-extrabold">RM {{ number_format($product->price, 2) }}</td>
                    <td class="py-4 pr-4">
                        <span class="font-extrabold {{ $product->stock_quantity === 0 ? 'text-rose-600' : ($product->stock_quantity <= $product->min_stock_level ? 'text-amber-600' : 'text-emerald-600') }}">{{ $product->stock_quantity }}</span>
                        <span class="text-xs text-slate-400"> / min {{ $product->min_stock_level }}</span>
                    </td>
                    <td class="py-4 pr-4"><span class="rounded-full px-3 py-1 text-xs font-extrabold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="py-4 text-right">
                        <div class="flex justify-end gap-3">
                            <details class="text-left" {{ request('edit') == $product->id ? 'open' : '' }}>
                                <summary class="cursor-pointer list-none font-extrabold text-indigo-600">Edit</summary>
                                <div class="fixed inset-0 z-40 bg-slate-950/30"></div>
                                <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-3xl overflow-y-auto rounded-lg bg-white p-6 shadow-2xl">
                                    <div class="mb-5 flex items-center justify-between">
                                        <h2 class="text-lg font-extrabold">Edit {{ $product->name }}</h2>
                                        <button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500" title="Close">&times;</button>
                                    </div>
                                    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                                        @csrf @method('PATCH')
                                        @include('partials.product-form', ['product' => $product])
                                        <div class="flex justify-end sm:col-span-2"><button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white">Save Changes</button></div>
                                    </form>
                                </div>
                            </details>
                            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product? Products with order history will only be deactivated.')">
                                @csrf @method('DELETE')
                                <button class="font-extrabold text-rose-600">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-12 text-center font-semibold text-slate-400">No products match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $products->links() }}</div>
</section>
@endsection
