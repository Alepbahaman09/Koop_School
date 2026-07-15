{{-- Reusable supplier form fields --}}
@php $s = $supplier ?? null; @endphp

<div class="space-y-1">
    <label class="text-xs font-bold text-slate-600">Contact Name <span class="text-rose-400">*</span></label>
    <input name="name" value="{{ old('name', $s?->name) }}" required
        class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
        placeholder="e.g. Ahmad Razif">
    @error('name') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="space-y-1">
    <label class="text-xs font-bold text-slate-600">Company Name</label>
    <input name="company_name" value="{{ old('company_name', $s?->company_name) }}"
        class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
        placeholder="e.g. Razif Trading Sdn Bhd">
    @error('company_name') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="space-y-1">
    <label class="text-xs font-bold text-slate-600">Email <span class="text-rose-400">*</span></label>
    <input name="email" type="email" value="{{ old('email', $s?->email) }}" required
        class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
        placeholder="supplier@example.com">
    @error('email') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="space-y-1">
    <label class="text-xs font-bold text-slate-600">Phone <span class="text-rose-400">*</span></label>
    <input name="phone" value="{{ old('phone', $s?->phone) }}" required
        class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
        placeholder="e.g. +60 12-345 6789">
    @error('phone') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="space-y-1 sm:col-span-2">
    <label class="text-xs font-bold text-slate-600">Address</label>
    <textarea name="address" rows="2"
        class="w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
        placeholder="Full postal address">{{ old('address', $s?->address) }}</textarea>
    @error('address') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="space-y-1">
    <label class="text-xs font-bold text-slate-600">Tax / SST Number</label>
    <input name="tax_number" value="{{ old('tax_number', $s?->tax_number) }}"
        class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
        placeholder="e.g. W10-1234-56789012">
    @error('tax_number') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="flex items-center gap-3">
    <label class="relative inline-flex cursor-pointer items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="peer sr-only"
            {{ old('is_active', $s?->is_active ?? true) ? 'checked' : '' }}>
        <div class="h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-focus:ring-2 peer-focus:ring-indigo-300"></div>
        <span class="text-xs font-bold text-slate-600">Active Supplier</span>
    </label>
</div>
