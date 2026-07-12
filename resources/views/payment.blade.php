@extends('layouts.app')
@section('content')
    <div>
        <h1>Payments</h1>
    </div>

    <div class="bg-white rounded-lg border shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold">Payment Methods</h2>
        <button class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">NFC Card <br> Cash</button>
    </div>

    <table class="w-full text-sm text-left border-collapse">
        <thead>
            <tr class="border-b">
                <th class="py-3 px-4">Icon</th>
                <th class="py-3 px-4">Method</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b hover:bg-gray-50">
                <td class="py-3 px-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </td>
                <td class="py-3 px-4">Credit Card</td>
                <td class="py-3 px-4">
                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Active</span>
                </td>
                <td class="py-3 px-4">
                    <button class="text-indigo-600 hover:underline">Edit</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($payments as $payment)
        <div class="bg-white rounded-lg border p-4">
            <div class="flex items-center gap-4 mb-4">
            <div class="h-10 w-10 flex items-center justify-center bg-slate-100 rounded">
                <img src="https://open.kakaopay.com/template/image/kakao_icon.png" alt="icon" class="h-5 w-5">
            </div>
            <div>
                <h3 class="font-semibold text-slate-900">{{ $payment->payment_method }}</h3>
                <p class="text-sm text-slate-500">{{ $payment->transaction_id }}</p>
            </div>
            </div>
            <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-sm text-slate-500">Amount</span>
                <span class="font-semibold">{{ $payment->amount }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm text-slate-500">Fee</span>
                <span class="font-semibold">{{ $payment->fee }}</span>
            </div>
            </div>
        </div>
        @endforeach
    </div>

@endsection