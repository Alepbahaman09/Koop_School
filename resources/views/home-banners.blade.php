@extends('layouts.app')

@section('title', 'Home Banners')
@section('page-title', 'Home Banners')

@section('content')
@include('partials.admin-alerts')

@php
    $tones = [
        'blue' => 'Blue',
        'green' => 'Green',
        'orange' => 'Orange',
        'purple' => 'Purple',
    ];
@endphp

<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold text-slate-950">Home banners</h1>
        <p class="mt-1 text-xs font-semibold text-slate-400">Create announcements and promotions shown in the mobile app homepage.</p>
    </div>
    <details class="group">
        <summary class="inline-flex h-10 cursor-pointer list-none items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700">
            <span class="text-lg">+</span> Add Banner
        </summary>
        <div class="fixed inset-0 z-40 bg-slate-950/30"></div>
        <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-2xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-extrabold">Add homepage banner</h2>
                <button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500">&times;</button>
            </div>
            <form method="POST" action="{{ route('home-banners.store') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                @include('partials.home-banner-form', ['banner' => null, 'tones' => $tones])
                <div class="flex justify-end sm:col-span-2">
                    <button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white">Create Banner</button>
                </div>
            </form>
        </div>
    </details>
</section>

<section class="grid gap-4">
    @forelse ($banners as $banner)
        @php
            $toneClasses = [
                'blue' => 'bg-indigo-50 text-indigo-700 ring-indigo-100',
                'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                'orange' => 'bg-amber-50 text-amber-700 ring-amber-100',
                'purple' => 'bg-violet-50 text-violet-700 ring-violet-100',
            ][$banner->tone] ?? 'bg-indigo-50 text-indigo-700 ring-indigo-100';
        @endphp
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-3 py-1 text-xs font-extrabold ring-1 {{ $toneClasses }}">{{ $tones[$banner->tone] ?? 'Blue' }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-extrabold {{ $banner->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">{{ $banner->is_active ? 'Active' : 'Inactive' }}</span>
                        <span class="text-xs font-bold text-slate-400">Sort {{ $banner->sort_order }}</span>
                    </div>
                    <h2 class="truncate text-lg font-extrabold text-slate-950">{{ $banner->title }}</h2>
                    <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-slate-500">{{ $banner->message }}</p>
                    @if ($banner->label)
                        <p class="mt-2 text-xs font-extrabold uppercase tracking-wide text-indigo-500">{{ $banner->label }}</p>
                    @endif
                    <p class="mt-3 text-xs font-semibold text-slate-400">
                        {{ $banner->starts_at ? 'Starts '.$banner->starts_at->format('d M Y, H:i') : 'Starts immediately' }}
                        ·
                        {{ $banner->ends_at ? 'Ends '.$banner->ends_at->format('d M Y, H:i') : 'No end date' }}
                    </p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <details>
                        <summary class="grid h-9 w-9 cursor-pointer list-none place-items-center rounded-lg bg-indigo-50 text-indigo-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m4 20 4-1 11-11-3-3L5 16l-1 4ZM14 7l3 3" /></svg>
                        </summary>
                        <div class="fixed inset-0 z-40 bg-slate-950/30"></div>
                        <div class="fixed inset-x-4 top-6 z-50 mx-auto max-h-[90vh] max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-2xl">
                            <div class="mb-5 flex items-center justify-between">
                                <h2 class="text-lg font-extrabold">Edit {{ $banner->title }}</h2>
                                <button type="button" onclick="this.closest('details').open=false" class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 font-extrabold text-slate-500">&times;</button>
                            </div>
                            <form method="POST" action="{{ route('home-banners.update', $banner) }}" class="grid gap-4 sm:grid-cols-2">
                                @csrf
                                @method('PATCH')
                                @include('partials.home-banner-form', ['banner' => $banner, 'tones' => $tones])
                                <div class="flex justify-end sm:col-span-2">
                                    <button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </details>
                    <form method="POST" action="{{ route('home-banners.destroy', $banner) }}" onsubmit="return confirm('Delete this banner?')">
                        @csrf
                        @method('DELETE')
                        <button class="grid h-9 w-9 place-items-center rounded-lg bg-rose-50 text-rose-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" /></svg>
                        </button>
                    </form>
                </div>
            </div>
        </article>
    @empty
        <article class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-100">
            <p class="text-sm font-extrabold text-slate-700">No banners yet</p>
            <p class="mt-1 text-xs font-semibold text-slate-400">Create one to show an announcement or promotion in the mobile app.</p>
        </article>
    @endforelse
</section>

{{ $banners->links() }}
@endsection
