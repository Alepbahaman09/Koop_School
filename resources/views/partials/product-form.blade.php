@php
    $savedSizeStocks = $product?->sizes?->pluck('stock_quantity', 'size')->all() ?? [];
    $selectedSizes = collect(old('sizes', array_keys($savedSizeStocks)))->map(fn ($size) => (string) $size)->all();
    $sizeStocks = old('size_stock', $savedSizeStocks);
    $hasSizes = (bool) old('has_sizes', $selectedSizes !== []);
@endphp

<input type="hidden" name="_product_form" value="{{ $product ? 'edit-'.$product->id : 'create' }}">
<label class="text-xs font-extrabold uppercase text-slate-400">Name
    <input name="name" required value="{{ old('name', $product?->name) }}" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
</label>
<label class="text-xs font-extrabold uppercase text-slate-400">SKU
    <input name="sku" required value="{{ old('sku', $product?->sku) }}" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
</label>
<label class="text-xs font-extrabold uppercase text-slate-400">Category
    <select name="category_id" required class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $product?->category_id) == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
</label>
<label class="text-xs font-extrabold uppercase text-slate-400">Price (RM)
    <input name="price" type="number" required min="0" step="0.01" value="{{ old('price', $product?->price) }}" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
</label>
<label data-regular-stock class="text-xs font-extrabold uppercase text-slate-400">Stock Quantity
    <input name="stock_quantity" type="number" min="0" value="{{ old('stock_quantity', $product?->stock_quantity ?? 0) }}" @disabled($hasSizes) class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm disabled:bg-slate-100 disabled:text-slate-400">
</label>
<label class="text-xs font-extrabold uppercase text-slate-400">Low Stock Alert At
    <input name="min_stock_level" type="number" required min="0" value="{{ old('min_stock_level', $product?->min_stock_level ?? 5) }}" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
</label>
<div data-size-inventory class="rounded-lg border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
    <label class="flex cursor-pointer items-start gap-3">
        <input name="has_sizes" type="hidden" value="0">
        <input data-size-toggle name="has_sizes" type="checkbox" value="1" @checked($hasSizes) class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
        <span>
            <span class="block text-sm font-extrabold text-slate-700">This product uses sizes</span>
            <span class="mt-0.5 block text-xs font-semibold text-slate-400">Optional. Enable this for clothing or other products that keep separate stock by size.</span>
        </span>
    </label>

    <div data-size-options class="mt-4 {{ $hasSizes ? '' : 'hidden' }}">
        <p class="mb-3 text-xs font-extrabold uppercase text-slate-400">Available sizes and stock</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($availableSizes as $size)
                @php $isSelected = in_array($size, $selectedSizes, true); @endphp
                <div data-size-option class="rounded-lg border border-slate-200 bg-white p-3">
                    <label class="flex items-center gap-2 text-sm font-extrabold text-slate-700">
                        <input data-size-choice name="sizes[]" type="checkbox" value="{{ $size }}" @checked($isSelected) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ $size }}
                    </label>
                    <label class="mt-3 block text-[10px] font-extrabold uppercase text-slate-400">Stock
                        <input data-size-stock name="size_stock[{{ $size }}]" type="number" min="0" value="{{ $sizeStocks[$size] ?? 0 }}" @disabled(! $hasSizes || ! $isSelected) class="mt-1 h-9 w-full rounded-lg border-slate-200 text-sm disabled:bg-slate-100 disabled:text-slate-300">
                    </label>
                    @error('size_stock.'.$size)
                        <p class="mt-1 text-xs font-semibold normal-case text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>
        @error('sizes')
            <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
        @enderror
        <p class="mt-3 text-xs font-semibold text-slate-400">The product's total stock is calculated automatically from the selected sizes.</p>
    </div>
</div>
<label class="text-xs font-extrabold uppercase text-slate-400 sm:col-span-2">Description
    <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border-slate-200 text-sm">{{ old('description', $product?->description) }}</textarea>
</label>

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
                            const stock = choice.closest('[data-size-option]').querySelector('[data-size-stock]');
                            stock.disabled = !toggle.checked || !choice.checked;
                            stock.required = toggle.checked && choice.checked;
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
<label class="text-xs font-extrabold uppercase text-slate-400 sm:col-span-2">Product Image
    <div class="mt-2 flex items-center gap-4 rounded-lg border border-dashed border-slate-200 bg-slate-50 p-3">
        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-white ring-1 ring-slate-200">
            @if ($product?->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover" onerror="this.parentElement.innerHTML='<div class=&quot;grid h-full place-items-center px-2 text-center text-[10px] font-extrabold text-slate-400&quot;>IMAGE NOT FOUND</div>'">
            @else
                <div class="grid h-full place-items-center px-2 text-center text-[10px] font-extrabold text-slate-400">IMAGE PREVIEW</div>
            @endif
        </div>
        <div class="min-w-0">
            <input name="image" type="file" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-extrabold file:text-indigo-700">
            <p class="mt-2 text-xs normal-case font-semibold text-slate-400">JPG, PNG, GIF or WebP up to 2 MB. Leave empty to keep the current image.</p>
        </div>
    </div>
</label>
