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
    <label class="mb-1 block text-xs font-extrabold text-slate-500">Banner color</label>
    <div class="flex h-11 items-center gap-3 rounded-lg border border-slate-200 px-3">
        <input name="tone" type="color" value="{{ old('tone', $banner?->tone ?? '#4f46e5') }}" class="h-8 w-12 cursor-pointer rounded border-0 bg-transparent p-0">
        <span class="text-xs font-semibold text-slate-400">Choose any color</span>
    </div>
    @error('tone') <p class="mt-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="sm:col-span-2">
    <label class="mb-1 block text-xs font-extrabold text-slate-500">Banner image</label>
    <div data-banner-image-editor class="space-y-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 p-3">
        <div data-banner-current-preview class="aspect-[30/13] w-full overflow-hidden rounded-lg bg-white ring-1 ring-slate-200">
            @if ($banner?->image_url)
                <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="h-full w-full object-cover">
            @else
                <div class="grid h-full place-items-center text-xs font-extrabold text-slate-400">IMAGE PREVIEW</div>
            @endif
        </div>

        <div data-banner-cropper class="hidden space-y-3">
            <div class="overflow-hidden rounded-lg bg-slate-900 ring-1 ring-slate-200">
                <canvas data-banner-canvas width="1200" height="520" class="block aspect-[30/13] w-full cursor-move touch-none"></canvas>
            </div>
            <label class="flex items-center gap-3 text-xs font-bold text-slate-600">
                <span>Zoom</span>
                <input data-banner-zoom type="range" min="1" max="2.5" step="0.01" value="1" class="h-2 flex-1 cursor-pointer accent-indigo-600">
            </label>
            <p class="text-xs font-semibold text-slate-400">Drag the image to choose its position. The saved image will be 1200 × 520 pixels.</p>
        </div>

        <input data-banner-image-input name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" @required(!$banner) class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-extrabold file:text-indigo-700">
        <p class="text-xs font-semibold text-slate-400">Choose any JPG, PNG, GIF or WebP up to 5 MB. It will be cropped and compressed automatically.</p>
    </div>
    @error('image') <p class="mt-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="sm:col-span-2">
    <label class="mb-1 block text-xs font-extrabold text-slate-500">Automatically delete at</label>
    <input name="expires_at" type="datetime-local" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" value="{{ old('expires_at', $banner?->expires_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border-slate-200 text-sm font-semibold">
    <p class="mt-1 text-xs font-semibold text-slate-400">Optional. Leave empty to keep the banner until you delete it.</p>
    @error('expires_at') <p class="mt-1 text-xs font-bold text-rose-500">{{ $message }}</p> @enderror
</div>

<label class="flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-3 text-sm font-bold text-slate-600 sm:col-span-2">
    <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $banner?->is_active ?? true)) class="rounded border-slate-300 text-indigo-600">
    Show this banner in the mobile app
</label>
