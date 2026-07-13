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
<label class="text-xs font-extrabold uppercase text-slate-400">Stock Quantity
    <input name="stock_quantity" type="number" required min="0" value="{{ old('stock_quantity', $product?->stock_quantity ?? 0) }}" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
</label>
<label class="text-xs font-extrabold uppercase text-slate-400">Low Stock Alert At
    <input name="min_stock_level" type="number" required min="0" value="{{ old('min_stock_level', $product?->min_stock_level ?? 5) }}" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm">
</label>
<label class="text-xs font-extrabold uppercase text-slate-400 sm:col-span-2">Description
    <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border-slate-200 text-sm">{{ old('description', $product?->description) }}</textarea>
</label>
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
