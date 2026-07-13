@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
    <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h1 class="text-xl font-extrabold text-slate-900">Account Settings</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">Manage your profile information and password.</p>
    </section>

    <div class="mx-auto max-w-4xl space-y-6">
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">
            <div class="max-w-xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">
            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>
        </section>
    </div>
@endsection
