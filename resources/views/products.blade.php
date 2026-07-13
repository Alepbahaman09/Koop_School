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
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" onclick="document.getElementById('category-manager').showModal()" class="inline-flex h-10 items-center gap-2 rounded-lg bg-white px-4 text-sm font-extrabold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">
            <span class="text-lg leading-none">+</span> Categories
        </button>
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
    </div>
</section>

<section class="grid gap-4 sm:grid-cols-3">
    @foreach ([
        ['Total Products', $stats['total'], 'text-slate-950', 'All catalogue items'],
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
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
        <h2 class="mb-3 text-sm font-extrabold text-slate-900">Categories</h2>
        <div class="overflow-x-auto pb-1">
            <div class="flex min-w-max flex-nowrap gap-2">
                <a href="{{ route('products.index', array_filter(['search' => request('search'), 'stock' => request('stock')])) }}" class="whitespace-nowrap rounded-full px-4 py-1.5 text-xs font-extrabold {{ !request('category') ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}">All</a>
                @foreach ($categories as $category)
                    <a href="{{ route('products.index', array_filter(['category' => $category->id, 'search' => request('search'), 'stock' => request('stock')])) }}" class="whitespace-nowrap rounded-full px-4 py-1.5 text-xs font-extrabold {{ request('category') == $category->id ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}">{{ $category->name }}</a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
        <h2 class="mb-3 text-sm font-extrabold text-slate-900">Filter Products</h2>
        <div class="overflow-x-auto pb-1">
            <form method="GET" class="flex min-w-max flex-nowrap items-center gap-2">
                <input type="hidden" name="category" value="{{ request('category') }}">
                <input name="search" value="{{ request('search') }}" placeholder="Search name or SKU" class="h-9 w-56 flex-none rounded-lg border-slate-200 bg-white text-xs font-semibold">
                <select name="stock" class="h-9 w-40 flex-none rounded-lg border-slate-200 bg-white text-xs font-bold text-slate-600">
                    <option value="">All stock</option><option value="low" @selected(request('stock') === 'low')>Low stock</option><option value="out" @selected(request('stock') === 'out')>Out of stock</option>
                </select>
                <button class="h-9 flex-none rounded-lg bg-indigo-600 px-4 text-xs font-extrabold text-white">Filter</button>
            </form>
        </div>
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
                    <div class="absolute right-3 top-3 flex gap-1 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100">
                        <details {{ request('edit') == $product->id ? 'open' : '' }}>
                            <summary class="grid h-8 w-8 cursor-pointer list-none place-items-center rounded-lg bg-white text-indigo-600 shadow"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m4 20 4-1 11-11-3-3L5 16l-1 4ZM14 7l3 3" /></svg></summary>
                            <div class="fixed inset-0 z-40 bg-slate-950/30"></div>
                            <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-3xl overflow-y-auto rounded-xl bg-white p-6 shadow-2xl">
                                <div class="mb-5 flex items-center justify-between"><h2 class="text-lg font-extrabold">Edit {{ $product->name }}</h2><button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500">&times;</button></div>
                                <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">@csrf @method('PATCH') @include('partials.product-form', ['product' => $product])<div class="flex justify-end sm:col-span-2"><button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white">Save Changes</button></div></form>
                            </div>
                        </details>
                        <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Permanently delete this product?')">@csrf @method('DELETE')<button class="grid h-8 w-8 place-items-center rounded-lg bg-white text-rose-500 shadow"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg></button></form>
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
</section>

@include('partials.category-manager')
@endsection

@if (request('categories') || old('_category_form'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('category-manager').showModal();
            });
        </script>
    @endpush
@endif
