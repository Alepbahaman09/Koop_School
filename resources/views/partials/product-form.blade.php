@php
    $savedSizeStocks = $product?->sizes?->pluck('stock_quantity', 'size')->all() ?? [];
    $savedSizePrices = $product?->sizes?->pluck('price', 'size')->all() ?? [];
    $selectedSizes = collect(old('sizes', array_keys($savedSizeStocks)))->map(fn ($size) => (string) $size)->all();
    $sizeStocks = old('size_stock', $savedSizeStocks);
    $sizePrices = old('size_price', $savedSizePrices);
    $hasSizes = (bool) old('has_sizes', $selectedSizes !== []);
@endphp

<input type="hidden" name="_product_form" value="{{ $product ? 'edit-'.$product->id : 'create' }}">

<div class="space-y-1">
    <label class="text-xs font-extrabold uppercase text-slate-500">Product Name <span class="text-rose-500">*</span></label>
    <input name="name" required value="{{ old('name', $product?->name) }}" placeholder="e.g. School Uniform Shirt" class="h-10 w-full rounded-lg border-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-indigo-500">
</div>

<div class="space-y-1">
    <label class="text-xs font-extrabold uppercase text-slate-500">SKU / Barcode <span class="text-rose-500">*</span></label>
    <input name="sku" required value="{{ old('sku', $product?->sku) }}" placeholder="e.g. UNI-SHIRT-001" class="h-10 w-full rounded-lg border-slate-200 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
</div>

<div class="space-y-1">
    <label class="text-xs font-extrabold uppercase text-slate-500">Category</label>
    <select name="category_id" class="h-10 w-full rounded-lg border-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Uncategorised (No Category)</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
</div>

<div class="space-y-1">
    <label class="text-xs font-extrabold uppercase text-slate-500">Supplier</label>
    <select name="supplier_id" class="h-10 w-full rounded-lg border-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">No Supplier Assigned</option>
        @foreach ($suppliers as $supplier)
            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $product?->supplier_id) === (string) $supplier->id)>{{ $supplier->company_name }}</option>
        @endforeach
    </select>
</div>

<div class="space-y-1">
    <label class="text-xs font-extrabold uppercase text-slate-500">Selling Price (RM) <span class="text-rose-500">*</span></label>
    <input name="price" type="number" required min="0" step="0.01" value="{{ old('price', $product?->price) }}" placeholder="0.00" class="h-10 w-full rounded-lg border-slate-200 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
</div>

<div class="space-y-1">
    <label class="text-xs font-extrabold uppercase text-slate-500">Cost Price (RM)</label>
    <input name="cost_price" type="number" min="0" step="0.01" value="{{ old('cost_price', $product?->cost_price) }}" placeholder="0.00 (Optional)" class="h-10 w-full rounded-lg border-slate-200 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
</div>

<input type="hidden" name="unit" value="{{ old('unit', $product?->unit ?? 'pcs') }}">

<div class="space-y-1">
    <label class="text-xs font-extrabold uppercase text-slate-500">Low Stock Alert Level <span class="text-rose-500">*</span></label>
    <input name="min_stock_level" type="number" required min="0" value="{{ old('min_stock_level', $product?->min_stock_level ?? 5) }}" class="h-10 w-full rounded-lg border-slate-200 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
</div>

<div data-regular-stock class="space-y-1 sm:col-span-2">
    <label class="text-xs font-extrabold uppercase text-slate-500">Stock Quantity</label>
    <input name="stock_quantity" type="number" min="0" value="{{ old('stock_quantity', $product?->stock_quantity ?? 0) }}" @disabled($hasSizes) class="h-10 w-full rounded-lg border-slate-200 text-sm font-semibold disabled:bg-slate-100 disabled:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
    <p class="text-[11px] font-medium text-slate-400">Total individual units available in inventory.</p>
</div>

{{-- Size Inventory Section --}}
<div data-size-inventory class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:col-span-2">
    <label class="flex cursor-pointer items-start gap-3">
        <input name="has_sizes" type="hidden" value="0">
        <input data-size-toggle name="has_sizes" type="checkbox" value="1" @checked($hasSizes) class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
        <span>
            <span class="block text-sm font-extrabold text-slate-800">This product uses sizes</span>
            <span class="mt-0.5 block text-xs font-semibold text-slate-400">Enable this for clothing or products that keep separate inventory by size (XS, S, M, L, XL, etc.).</span>
        </span>
    </label>

    <div data-size-options class="mt-4 {{ $hasSizes ? '' : 'hidden' }}">
        <p class="mb-3 text-xs font-extrabold uppercase tracking-wide text-slate-400">Available sizes and stock</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($availableSizes as $size)
                @php $isSelected = in_array($size, $selectedSizes, true); @endphp
                <div data-size-option class="rounded-lg border border-slate-200 bg-white p-3 shadow-xs">
                    <label class="flex items-center gap-2 text-sm font-extrabold text-slate-700">
                        <input data-size-choice name="sizes[]" type="checkbox" value="{{ $size }}" @checked($isSelected) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ $size }}
                    </label>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <label class="block text-[10px] font-extrabold uppercase text-slate-400">Stock
                            <input data-size-stock name="size_stock[{{ $size }}]" type="number" min="0" value="{{ $sizeStocks[$size] ?? 0 }}" @disabled(! $hasSizes || ! $isSelected) class="mt-1 h-9 w-full rounded-lg border-slate-200 text-sm font-bold text-center disabled:bg-slate-100 disabled:text-slate-300">
                        </label>
                        <label class="block text-[10px] font-extrabold uppercase text-slate-400">Price (RM)
                            <input data-size-price name="size_price[{{ $size }}]" type="number" min="0" step="0.01" value="{{ $sizePrices[$size] ?? ($product?->price ?? '') }}" @disabled(! $hasSizes || ! $isSelected) placeholder="{{ $product?->price ?? '0.00' }}" class="mt-1 h-9 w-full rounded-lg border-slate-200 text-sm font-semibold text-right disabled:bg-slate-100 disabled:text-slate-300">
                        </label>
                    </div>
                    @error('size_stock.'.$size)
                        <p class="mt-1 text-xs font-semibold normal-case text-rose-500">{{ $message }}</p>
                    @enderror
                    @error('size_price.'.$size)
                        <p class="mt-1 text-xs font-semibold normal-case text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>
        @error('sizes')
            <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
        @enderror
        <p class="mt-3 text-xs font-semibold text-slate-400">The product's total stock is automatically calculated from the sum of selected sizes.</p>
    </div>
</div>

{{-- Mobile App Publishing Option --}}
<div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 sm:col-span-2">
    <label class="flex cursor-pointer items-start gap-3">
        <input type="hidden" name="is_active" value="0">
        <input name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $product ? $product->is_active : true)) class="mt-0.5 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
        <div>
            <span class="block text-sm font-extrabold text-slate-900">Publish to Mobile App</span>
            <span class="mt-0.5 block text-xs font-semibold text-slate-500">
                When checked, this item is visible to students in the mobile app. When unchecked, it stays in the admin store & database as a <strong>hidden product</strong>.
            </span>
        </div>
    </label>
</div>

<div class="space-y-1 sm:col-span-2">
    <label class="text-xs font-extrabold uppercase text-slate-500">Description</label>
    <textarea name="description" rows="3" placeholder="Enter product details, specifications, or notes..." class="w-full rounded-lg border-slate-200 text-sm font-medium focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $product?->description) }}</textarea>
</div>

<div class="space-y-1 sm:col-span-2">
    <label class="text-xs font-extrabold uppercase text-slate-500">Product Image</label>
    <div class="mt-1 flex items-center gap-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/70 p-4">
        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-white ring-1 ring-slate-200 flex items-center justify-center">
            @if ($product?->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover" onerror="this.parentElement.innerHTML='<span class=&quot;text-[10px] font-bold text-slate-400 text-center px-1&quot;>No Image</span>'">
            @else
                <svg class="h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <input name="image" type="file" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-extrabold file:text-indigo-700 hover:file:bg-indigo-100">
            <p class="mt-1.5 text-xs font-semibold text-slate-400">JPG, PNG, GIF or WebP up to 2 MB. Leave empty to keep the existing image.</p>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-size-inventory]').forEach((inventory) => {
                    const form = inventory.closest('form');
                    const toggle = inventory.querySelector('[data-size-toggle]');
                    const options = inventory.querySelector('[data-size-options]');
                    const regularStock = form.querySelector('[name="stock_quantity"]');
                    const sizeChoices = inventory.querySelectorAll('[data-size-choice]');

                    const refresh = () => {
                        options.classList.toggle('hidden', !toggle.checked);
                        regularStock.disabled = toggle.checked;
                        regularStock.required = !toggle.checked;

                        sizeChoices.forEach((choice) => {
                            const opt = choice.closest('[data-size-option]');
                            const stock = opt.querySelector('[data-size-stock]');
                            const price = opt.querySelector('[data-size-price]');
                            stock.disabled = !toggle.checked || !choice.checked;
                            stock.required = toggle.checked && choice.checked;
                            if (price) {
                                price.disabled = !toggle.checked || !choice.checked;
                            }
                        });
                    };

                    toggle.addEventListener('change', refresh);
                    sizeChoices.forEach((choice) => choice.addEventListener('change', refresh));
                    refresh();
                });
            });
        </script>
    @endpush
@endonce
