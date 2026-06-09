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
<label class="text-xs font-extrabold uppercase text-slate-400">Image
    <input name="image" type="file" accept="image/*" class="mt-1 block w-full text-sm text-slate-500">
</label>
<label class="flex items-center gap-2 self-end pb-2 text-sm font-extrabold text-slate-700">
    <input name="is_active" value="1" type="checkbox" @checked(old('is_active', $product?->is_active ?? true)) class="rounded border-slate-300 text-indigo-600"> Available in mobile app
</label>
