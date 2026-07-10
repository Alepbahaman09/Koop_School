<div class="sm:col-span-2">
    <label class="mb-1 block text-xs font-extrabold text-slate-500">Title</label>
    <input name="title" value="{{ old('title', $banner?->title) }}" maxlength="80" required class="w-full rounded-lg border-slate-200 text-sm font-semibold" placeholder="Example: Canteen promo today">
    @error('title') <p class="mt-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="sm:col-span-2">
    <label class="mb-1 block text-xs font-extrabold text-slate-500">Message</label>
    <textarea name="message" rows="3" maxlength="240" required class="w-full rounded-lg border-slate-200 text-sm font-semibold" placeholder="Write the announcement shown in the mobile app banner.">{{ old('message', $banner?->message) }}</textarea>
    @error('message') <p class="mt-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
</div>

<div>
    <label class="mb-1 block text-xs font-extrabold text-slate-500">Small label</label>
    <input name="label" value="{{ old('label', $banner?->label) }}" maxlength="40" class="w-full rounded-lg border-slate-200 text-sm font-semibold" placeholder="Promotion">
    @error('label') <p class="mt-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
</div>

<div>
    <label class="mb-1 block text-xs font-extrabold text-slate-500">Tone</label>
    <select name="tone" class="w-full rounded-lg border-slate-200 text-sm font-semibold">
        @foreach ($tones as $value => $label)
            <option value="{{ $value }}" @selected(old('tone', $banner?->tone ?? 'blue') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('tone') <p class="mt-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
</div>

<div>
    <label class="mb-1 block text-xs font-extrabold text-slate-500">Sort order</label>
    <input name="sort_order" type="number" min="0" max="999" value="{{ old('sort_order', $banner?->sort_order ?? 0) }}" class="w-full rounded-lg border-slate-200 text-sm font-semibold">
    @error('sort_order') <p class="mt-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
</div>

<div>
    <label class="mb-1 block text-xs font-extrabold text-slate-500">Starts at</label>
    <input name="starts_at" type="datetime-local" value="{{ old('starts_at', $banner?->starts_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border-slate-200 text-sm font-semibold">
    @error('starts_at') <p class="mt-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
</div>

<div>
    <label class="mb-1 block text-xs font-extrabold text-slate-500">Ends at</label>
    <input name="ends_at" type="datetime-local" value="{{ old('ends_at', $banner?->ends_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border-slate-200 text-sm font-semibold">
    @error('ends_at') <p class="mt-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
</div>

<label class="flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-3 text-sm font-bold text-slate-600 sm:col-span-2">
    <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $banner?->is_active ?? true)) class="rounded border-slate-300 text-indigo-600">
    Show this banner in the mobile app
</label>
