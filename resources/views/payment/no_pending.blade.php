@extends('layouts.app')

@section('title', 'Payments')
@section('page-title', 'Payments')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Collect Payment</h1>
        <p class="text-sm font-medium text-slate-500 mt-2">No active pending order found. Select an unpaid order below to process payment.</p>
    </div>

    <!-- Unpaid Orders Card list -->
    <div class="rounded-2xl bg-white p-6 shadow-lg ring-1 ring-slate-100 min-h-[300px] flex flex-col justify-between">
        @if ($pendingOrders->count() > 0)
            <div class="space-y-4">
                <h3 class="text-xs font-extrabold uppercase text-slate-400 tracking-wider">Unpaid / Partially Paid Orders</h3>
                
                <div class="divide-y divide-slate-100">
                    @foreach ($pendingOrders as $order)
                        <div class="py-4 flex items-center justify-between gap-4">
                            <div>
                                <span class="font-extrabold text-slate-900">{{ $order->order_number }}</span>
                                <span class="ml-2 rounded-full px-2.5 py-0.5 text-2xs font-extrabold {{ $order->payment_status === 'Partial' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600' }}">{{ $order->payment_status }}</span>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ $order->customer?->student_name ?? 'Walk-in Customer' }} &middot; {{ $order->created_at->diffForHumans() }}
                                </p>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <span class="font-extrabold text-slate-800">RM {{ number_format($order->total_amount, 2) }}</span>
                                <a href="{{ route('orders.pay', $order) }}" class="inline-flex h-9 items-center justify-center rounded-lg bg-indigo-600 px-4 text-xs font-bold text-white hover:bg-indigo-700 transition-colors">
                                    Collect
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="my-auto text-center py-12">
                <span class="text-5xl block mb-4"></span>
                <p class="text-lg font-bold text-slate-700">All caught up!</p>
                <p class="text-sm text-slate-400 mt-1">There are no unpaid orders in the database right now.</p>
                <a href="{{ route('orders.index') }}" class="mt-6 inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-5 text-xs font-extrabold text-white hover:bg-indigo-700 transition-colors">
                    Go to Orders Page
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
