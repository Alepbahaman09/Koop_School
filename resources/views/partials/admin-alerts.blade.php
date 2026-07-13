<div data-toast-container class="pointer-events-none fixed right-4 top-4 z-[100] flex w-[calc(100%-2rem)] max-w-lg flex-col gap-3 sm:right-6 sm:top-6"></div>

<template data-toast-template="success">
    <div data-toast class="pointer-events-auto flex min-h-16 items-start gap-4 rounded-xl bg-emerald-600 px-5 py-4 text-base font-bold leading-6 text-white shadow-2xl shadow-emerald-900/25 transition duration-300">
        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-white"></span>
        <p data-toast-message class="flex-1"></p>
        <button type="button" data-toast-close class="text-2xl leading-none text-white/80 hover:text-white" aria-label="Close notification">&times;</button>
    </div>
</template>

<template data-toast-template="error">
    <div data-toast class="pointer-events-auto flex min-h-16 items-start gap-4 rounded-xl bg-rose-600 px-5 py-4 text-base font-bold leading-6 text-white shadow-2xl shadow-rose-900/25 transition duration-300">
        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-white"></span>
        <p data-toast-message class="flex-1"></p>
        <button type="button" data-toast-close class="text-2xl leading-none text-white/80 hover:text-white" aria-label="Close notification">&times;</button>
    </div>
</template>

@if (session('success'))
    <div data-initial-toast data-type="success" data-message="{{ session('success') }}"></div>
@endif

@if (session('error') || ($errors->any() && !old('_category_form')))
    <div data-initial-toast data-type="error" data-message="{{ session('error') ?? $errors->first() }}"></div>
@endif
