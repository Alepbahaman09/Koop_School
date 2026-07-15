@extends('layouts.app')

@section('title', 'Suppliers')
@section('page-title', 'Suppliers')

@section('content')
@include('partials.admin-alerts')

{{-- Page Header --}}
<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold text-slate-950">Suppliers</h1>
        <p class="mt-1 text-xs font-semibold text-slate-400">Manage your cooperative suppliers and vendor contacts.</p>
    </div>
    <details class="group" {{ old('_supplier_create') ? 'open' : '' }}>
        <summary class="inline-flex h-10 cursor-pointer list-none items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700">
            <span class="text-lg">+</span> Add Supplier
        </summary>
        <div class="fixed inset-0 z-40 bg-slate-950/30" onclick="this.closest('details').open=false"></div>
        <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-2xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-extrabold">Add Supplier</h2>
                <button type="button" onclick="this.closest('details').open=false"
                    class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500 hover:bg-slate-200">&times;</button>
            </div>
            <form method="POST" action="{{ route('suppliers.store') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                @include('partials.supplier-form', ['supplier' => null])
                <div class="flex justify-end sm:col-span-2">
                    <button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white hover:bg-indigo-700">
                        Create Supplier
                    </button>
                </div>
            </form>
        </div>
    </details>
</section>

{{-- Stats --}}
<section class="grid gap-4 sm:grid-cols-3">
    @foreach ([
        ['Total Suppliers',    $stats['total'],    'text-slate-950',   'All registered suppliers'],
        ['Active Suppliers',   $stats['active'],   'text-emerald-500', 'Currently active'],
        ['Inactive Suppliers', $stats['inactive'], 'text-rose-500',    'Deactivated'],
    ] as [$label, $value, $tone, $note])
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <p class="text-xs font-bold text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-2xl font-extrabold {{ $tone }}">{{ number_format($value) }}</p>
            <p class="mt-1 text-xs font-extrabold {{ str_contains($note, 'active') && !str_contains($note, 'De') ? 'text-emerald-400' : (str_contains($note, 'Deact') ? 'text-rose-400' : 'text-slate-400') }}">{{ $note }}</p>
        </article>
    @endforeach
</section>

{{-- Search & Filter --}}
<section class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
    <h2 class="mb-3 text-sm font-extrabold text-slate-900">Filter Suppliers</h2>
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input name="search" value="{{ request('search') }}"
            placeholder="Search by company name"
            class="h-9 w-64 flex-none rounded-lg border-slate-200 bg-white text-xs font-semibold focus:border-indigo-400 focus:ring-indigo-200">
        <select name="status" class="h-9 w-40 flex-none rounded-lg border-slate-200 bg-white text-xs font-bold text-slate-600 focus:border-indigo-400">
            <option value="">All statuses</option>
            <option value="active"   @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
        <button class="h-9 flex-none rounded-lg bg-indigo-600 px-4 text-xs font-extrabold text-white hover:bg-indigo-700">Filter</button>
        @if (request()->anyFilled(['search', 'status']))
            <a href="{{ route('suppliers.index') }}"
                class="h-9 flex-none rounded-lg bg-slate-100 px-4 text-xs font-extrabold text-slate-600 hover:bg-slate-200 leading-9">
                Clear
            </a>
        @endif
    </form>
</section>

{{-- Suppliers Table --}}
<section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-100">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs font-bold uppercase text-slate-400">
                    <th class="px-5 py-4">Company Name</th>
                    <th class="px-5 py-4">Contact Person</th>
                    <th class="px-5 py-4">Contact Details</th>
                    <th class="px-5 py-4">Notes</th>
                    <th class="px-5 py-4">Status</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($suppliers as $supplier)
                    <tr class="group hover:bg-slate-50/60 transition">
                        {{-- Company name --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-indigo-100 text-sm font-extrabold text-indigo-600">
                                    {{ strtoupper(substr($supplier->company_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-extrabold text-slate-900">{{ $supplier->company_name }}</p>
                                    <p class="text-[11px] font-semibold text-slate-400">Added {{ $supplier->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </td>
                        {{-- Contact Person --}}
                        <td class="px-5 py-4">
                            <p class="font-semibold text-slate-700">{{ $supplier->contact_person ?: '—' }}</p>
                        </td>
                        {{-- Contact Details --}}
                        <td class="px-5 py-4">
                            <p class="font-semibold text-slate-700">{{ $supplier->email ?: '—' }}</p>
                            <p class="text-[11px] font-semibold text-slate-400">{{ $supplier->phone ?: '—' }}</p>
                        </td>
                        {{-- Notes --}}
                        <td class="px-5 py-4">
                            <p class="text-xs font-semibold text-slate-500 truncate max-w-xs" title="{{ $supplier->notes }}">{{ $supplier->notes ?: '—' }}</p>
                        </td>
                        {{-- Status --}}
                        <td class="px-5 py-4">
                            @if ($supplier->status === 'active')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600 ring-1 ring-emerald-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 ring-1 ring-slate-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Inactive
                                </span>
                            @endif
                        </td>
                        {{-- Actions --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Edit --}}
                                <details {{ request('edit') == $supplier->id ? 'open' : '' }}>
                                    <summary class="grid h-8 w-8 cursor-pointer list-none place-items-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition" title="Edit">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m4 20 4-1 11-11-3-3L5 16l-1 4ZM14 7l3 3" />
                                        </svg>
                                    </summary>
                                    <div class="fixed inset-0 z-40 bg-slate-950/30" onclick="this.closest('details').open=false"></div>
                                    <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-2xl">
                                        <div class="mb-5 flex items-center justify-between">
                                            <h2 class="text-lg font-extrabold">Edit {{ $supplier->company_name }}</h2>
                                            <button type="button" onclick="this.closest('details').open=false"
                                                class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500 hover:bg-slate-200">&times;</button>
                                        </div>
                                        <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="grid gap-4 sm:grid-cols-2">
                                            @csrf
                                            @method('PATCH')
                                            @include('partials.supplier-form', ['supplier' => $supplier])
                                            <div class="flex justify-end sm:col-span-2">
                                                <button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white hover:bg-indigo-700">
                                                    Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </details>
                                {{-- Inactivate instead of Delete --}}
                                @if($supplier->status === 'active')
                                    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}"
                                        onsubmit="return confirm('Change {{ addslashes($supplier->company_name) }} status to inactive?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="grid h-8 w-8 place-items-center rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-100 transition" title="Deactivate">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M18.36 6.64a9 9 0 1 1-12.73 0M12 2v10" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-20 text-center text-sm font-semibold text-slate-400">
                            No suppliers match your search.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($suppliers->hasPages())
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $suppliers->links() }}
        </div>
    @endif
</section>

@endsection
