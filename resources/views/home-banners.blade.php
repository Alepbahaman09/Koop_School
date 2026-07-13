@extends('layouts.app')

@section('title', 'Home Banners')
@section('page-title', 'Home Banners')

@section('content')
@include('partials.admin-alerts')

<section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-extrabold text-slate-950">Home banners</h1>
        <p class="mt-1 text-xs font-semibold text-slate-400">Create announcements and promotions shown on the mobile app homepage.</p>
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
            <form method="POST" action="{{ route('home-banners.store') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                @csrf
                @include('partials.home-banner-form', ['banner' => null])
                <div class="flex justify-end sm:col-span-2">
                    <button class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-extrabold text-white">Create Banner</button>
                </div>
            </form>
        </div>
    </details>
</section>

<section data-banner-list data-cleanup-url="{{ route('home-banners.cleanup-expired') }}" class="grid gap-4">
    @forelse ($banners as $banner)
        <article data-banner-id="{{ $banner->id }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 flex-1 flex-col gap-4 sm:flex-row">
                    @if ($banner->image_url)
                        <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="h-36 w-full shrink-0 rounded-xl object-cover ring-1 ring-slate-100 sm:w-56">
                    @else
                        <div class="grid h-36 w-full shrink-0 place-items-center rounded-xl bg-slate-100 text-xs font-extrabold text-slate-400 sm:w-56">NO IMAGE</div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1 text-xs font-extrabold text-slate-600 ring-1 ring-slate-200">
                                <span class="h-3 w-3 rounded-full" style="background-color: {{ $banner->tone }}"></span>
                                {{ strtoupper($banner->tone) }}
                            </span>
                            <span class="rounded-full px-3 py-1 text-xs font-extrabold {{ $banner->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">{{ $banner->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <h2 class="truncate text-lg font-extrabold text-slate-950">{{ $banner->title }}</h2>
                        <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-slate-500">{{ $banner->message }}</p>
                        @if ($banner->label)
                            <p class="mt-2 text-xs font-extrabold uppercase tracking-wide text-indigo-500">{{ $banner->label }}</p>
                        @endif
                        @if ($banner->expires_at)
                            <p class="mt-2 text-xs font-bold text-amber-600">Deletes automatically {{ $banner->expires_at->format('d M Y, g:i A') }}</p>
                        @endif
                    </div>
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
                            <form method="POST" action="{{ route('home-banners.update', $banner) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                                @csrf
                                @method('PATCH')
                                @include('partials.home-banner-form', ['banner' => $banner])
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
    @if ($banners->isNotEmpty())
        <article data-banner-empty-state class="hidden rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-100">
            <p class="text-sm font-extrabold text-slate-700">No banners yet</p>
            <p class="mt-1 text-xs font-semibold text-slate-400">Create one to show an announcement or promotion in the mobile app.</p>
        </article>
    @endif
</section>

{{ $banners->links() }}
@endsection
