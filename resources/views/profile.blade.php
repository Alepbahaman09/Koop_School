@extends('layouts.app')

@section('title', 'Profile')
@section('page-title', 'Profile')

@section('content')
<section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-100">
    <h1 class="text-xl font-extrabold">Administrator Profile</h1>
    <p class="mt-1 text-sm font-medium text-slate-500">This account is managed in the shared database.</p>

    <dl class="mt-6 grid gap-5 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-extrabold uppercase text-slate-400">Name</dt>
            <dd class="mt-1 font-bold text-slate-800">{{ auth()->user()->name }}</dd>
        </div>
        <div>
            <dt class="text-xs font-extrabold uppercase text-slate-400">Email</dt>
            <dd class="mt-1 font-bold text-slate-800">{{ auth()->user()->email }}</dd>
        </div>
    </dl>
</section>
@endsection
