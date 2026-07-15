{{-- Reusable supplier form fields --}}
@php $s = $supplier ?? null; @endphp

<div class="space-y-1">
    <label class="text-xs font-bold text-slate-600">Company Name <span class="text-rose-400">*</span></label>
    <input name="company_name" value="{{ old('company_name', $s?->company_name) }}" required
        class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
        placeholder="e.g. Nestlé Malaysia">
    @error('company_name') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="space-y-1">
    <label class="text-xs font-bold text-slate-600">Contact Person</label>
    <input name="contact_person" value="{{ old('contact_person', $s?->contact_person) }}"
        class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
        placeholder="e.g. Ahmad Razif">
    @error('contact_person') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="space-y-1">
    <label class="text-xs font-bold text-slate-600">Email</label>
    <input name="email" type="email" value="{{ old('email', $s?->email) }}"
        class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
        placeholder="supplier@example.com">
    @error('email') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="space-y-1">
    <label class="text-xs font-bold text-slate-600">Phone</label>
    <input name="phone" value="{{ old('phone', $s?->phone) }}"
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

<div class="space-y-1 sm:col-span-2">
    <label class="text-xs font-bold text-slate-600">Notes</label>
    <textarea name="notes" rows="2"
        class="w-full rounded-lg border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200"
        placeholder="Internal notes/terms">{{ old('notes', $s?->notes) }}</textarea>
    @error('notes') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
</div>

<div class="space-y-1 sm:col-span-2">
    <label class="text-xs font-bold text-slate-600">Status</label>
    <select name="status" class="h-10 w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-indigo-200">
        <option value="active" {{ old('status', $s?->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ old('status', $s?->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
    @error('status') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
</div>
