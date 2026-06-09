@if (session('success'))
    <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 ring-1 ring-rose-100">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 ring-1 ring-rose-100">{{ $errors->first() }}</div>
@endif
