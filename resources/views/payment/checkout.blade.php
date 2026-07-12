@extends('layouts.app')

@section('title', 'Collect Payment')
@section('page-title', 'Checkout')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8">
    <!-- Payment Container -->
    <div class="relative overflow-hidden rounded-2xl bg-white p-8 shadow-lg ring-1 ring-slate-100 min-h-[500px] flex flex-col justify-between">
        
        <!-- CSRF Token for JS AJAX requests -->
        <input type="hidden" id="csrf-token" value="{{ csrf_token() }}">

        <!-- STEP 0: SELECT METHOD -->
        <div id="step-select-method" class="flex-1 flex flex-col justify-between">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Select Payment Method</h2>
                <p class="text-sm font-medium text-slate-500 mt-2">Order #{{ $order->order_number }} &middot; Total: <span class="font-bold text-slate-900">RM {{ number_format($order->total_amount, 2) }}</span></p>
            </div>

            <!-- Large Buttons Grid -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 flex-1 items-stretch py-4">
                <!-- NFC Card Option -->
                <button type="button" onclick="showNfcScreen()" class="group flex flex-col items-center justify-center rounded-2xl border-2 border-slate-100 bg-slate-50 p-8 text-center transition-all duration-200 hover:border-indigo-600 hover:bg-indigo-50/50 hover:shadow-md active:scale-95">
                    <span class="text-6xl mb-4 group-hover:scale-110 transition-transform duration-200">💳</span>
                    <span class="text-xl font-extrabold text-slate-900">NFC CARD</span>
                    <span class="text-xs text-slate-400 font-semibold mt-2">Tap student's NFC card</span>
                </button>

                <!-- Cash Option -->
                <button type="button" onclick="showCashScreen()" class="group flex flex-col items-center justify-center rounded-2xl border-2 border-slate-100 bg-slate-50 p-8 text-center transition-all duration-200 hover:border-emerald-600 hover:bg-emerald-50/50 hover:shadow-md active:scale-95">
                    <span class="text-6xl mb-4 group-hover:scale-110 transition-transform duration-200">💵</span>
                    <span class="text-xl font-extrabold text-slate-900">CASH</span>
                    <span class="text-xs text-slate-400 font-semibold mt-2">Receive cash payment</span>
                </button>
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('orders.index') }}" class="inline-flex items-center text-sm font-bold text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                    Back to Orders
                </a>
            </div>
        </div>

        <!-- STEP 1: NFC CARD WAITING -->
        <div id="step-nfc-waiting" class="hidden flex-1 flex flex-col justify-between text-center">
            <div class="mb-4">
                <h2 class="text-xl font-extrabold text-slate-800 uppercase tracking-wider">NFC Payment</h2>
            </div>

            <div class="my-auto py-8">
                <!-- Pulsing Signal Icon -->
                <div class="relative flex justify-center items-center mb-6">
                    <div class="absolute h-24 w-24 rounded-full bg-indigo-100 animate-ping opacity-75"></div>
                    <div class="relative z-10 flex h-20 w-20 items-center justify-center rounded-full bg-indigo-600 text-white text-4xl shadow-lg">
                        📶
                    </div>
                </div>

                <p class="text-lg font-bold text-slate-700">Waiting for NFC Card...</p>
                <p class="text-sm text-slate-400 mt-1">Tap card to scan and complete transaction</p>
                
                <div class="mt-6 inline-block bg-slate-50 px-5 py-2.5 rounded-full border border-slate-100">
                    <span class="text-xs text-slate-500 font-extrabold uppercase tracking-wider">Total Amount:</span>
                    <span class="ml-1 text-lg font-black text-indigo-600">RM {{ number_format($order->total_amount, 2) }}</span>
                </div>

                <!-- Invisible Input for Card Scanner keyboard simulation -->
                <input type="text" id="nfc-scanner-input" class="absolute -top-40 opacity-0" autofocus autocomplete="off">
            </div>

            <div class="mt-8 flex flex-col gap-3">
                <button type="button" onclick="cancelToMethodSelect()" class="w-full h-12 rounded-xl border border-slate-200 bg-white font-extrabold text-slate-600 hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
            </div>
        </div>

        <!-- STEP 2: NFC SUCCESS -->
        <div id="step-nfc-success" class="hidden flex-1 flex flex-col justify-between text-center">
            <div></div>

            <div class="py-8 my-auto">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 text-4xl mb-6">
                    ✓
                </div>
                <h2 class="text-2xl font-black text-slate-900">✅ Payment Successful</h2>
                
                <div class="mt-8 max-w-sm mx-auto bg-slate-50 rounded-2xl border border-slate-100 p-6 space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-3">
                        <span class="text-sm text-slate-500 font-bold">Student</span>
                        <span id="success-student-name" class="font-extrabold text-slate-900 text-right">-</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500 font-bold">Remaining Balance</span>
                        <span id="success-remaining-balance" class="font-black text-emerald-600 text-right">-</span>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <button type="button" onclick="redirectToOrders()" class="w-full h-12 rounded-xl bg-indigo-600 font-extrabold text-white hover:bg-indigo-700 transition-colors">
                    Done
                </button>
                <p class="text-xs text-slate-400 mt-2">Redirecting to orders screen in <span id="nfc-countdown">2</span>s...</p>
            </div>
        </div>

        <!-- STEP 3: CASH PAYMENT -->
        <div id="step-cash-confirm" class="hidden flex-1 flex flex-col justify-between text-center">
            <div class="mb-4">
                <h2 class="text-xl font-extrabold text-slate-800 uppercase tracking-wider">Cash Payment</h2>
            </div>

            <div class="my-auto py-8">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 text-4xl mb-6">
                    💵
                </div>

                <p class="text-sm font-bold uppercase tracking-wider text-slate-400">Total Amount Due</p>
                <p class="text-4xl font-black text-slate-900 mt-2">RM {{ number_format($order->total_amount, 2) }}</p>
            </div>

            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                <button type="button" onclick="cancelToMethodSelect()" class="sm:flex-1 h-12 rounded-xl border border-slate-200 bg-white font-extrabold text-slate-600 hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="submitCashPayment()" class="sm:flex-1 h-12 rounded-xl bg-emerald-600 font-extrabold text-white hover:bg-emerald-700 transition-all shadow-md active:scale-95">
                    Confirm Payment
                </button>
            </div>
        </div>

        <!-- STEP 4: CASH SUCCESS -->
        <div id="step-cash-success" class="hidden flex-1 flex flex-col justify-between text-center">
            <div></div>

            <div class="py-8 my-auto">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 text-4xl mb-6">
                    ✓
                </div>
                <h2 class="text-2xl font-black text-slate-900">✅ Cash Payment Recorded</h2>
                <p class="text-slate-500 mt-2">Order #{{ $order->order_number }} has been marked as fully paid.</p>
            </div>

            <div class="mt-8">
                <button type="button" onclick="redirectToOrders()" class="w-full h-12 rounded-xl bg-indigo-600 font-extrabold text-white hover:bg-indigo-700 transition-colors">
                    Done
                </button>
                <p class="text-xs text-slate-400 mt-2">Redirecting to orders screen in <span id="cash-countdown">2</span>s...</p>
            </div>
        </div>

    </div>

    <!-- SIMULATION HELPER BOX -->
    <div class="mt-8 rounded-2xl bg-amber-50 border border-amber-200 p-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-lg">⚙️</span>
            <h3 class="font-extrabold text-amber-900 text-sm uppercase tracking-wider">Cashier Demo Simulation Helper</h3>
        </div>
        <p class="text-xs text-amber-700 mb-4 font-medium leading-relaxed">
            Since NFC card scanners simulate keyboard entry (typing UID + Enter), you can test this interface by selecting a pre-registered card below or typing a custom UID to trigger the payment instantly.
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @forelse ($cards as $c)
                <button type="button" onclick="simulateCardTap('{{ $c->card_uid }}')" class="flex flex-col items-start rounded-xl border border-amber-300 bg-white p-3 text-left transition-all hover:bg-amber-100/50 hover:border-amber-400 active:scale-95">
                    <span class="text-xs font-extrabold text-slate-800">{{ $c->owner === '999999' ? 'Ali Bin Abu' : $c->owner }}</span>
                    <span class="text-[10px] text-slate-400 font-bold mt-1">UID: {{ $c->card_uid }} &middot; Balance: RM {{ number_format($c->balance, 2) }}</span>
                </button>
            @empty
                <div class="col-span-2 text-center text-xs text-amber-600 py-2">No active cards found.</div>
            @endforelse
        </div>
    </div>
</div>

<script>
    let activeStep = 'select-method';
    let countdownInterval = null;

    function showStep(stepId) {
        document.getElementById('step-select-method').classList.add('hidden');
        document.getElementById('step-nfc-waiting').classList.add('hidden');
        document.getElementById('step-nfc-success').classList.add('hidden');
        document.getElementById('step-cash-confirm').classList.add('hidden');
        document.getElementById('step-cash-success').classList.add('hidden');

        document.getElementById(stepId).classList.remove('hidden');

        // Focus scanner input if NFC screen is active
        if (stepId === 'step-nfc-waiting') {
            const scannerInput = document.getElementById('nfc-scanner-input');
            scannerInput.value = '';
            setTimeout(() => scannerInput.focus(), 100);
        }
    }

    function showNfcScreen() {
        activeStep = 'nfc-waiting';
        showStep('step-nfc-waiting');
    }

    function showCashScreen() {
        activeStep = 'cash-confirm';
        showStep('step-cash-confirm');
    }

    function cancelToMethodSelect() {
        activeStep = 'select-method';
        showStep('step-select-method');
    }

    function redirectToOrders() {
        window.location.href = "{{ route('orders.index') }}";
    }

    // Capture NFC Scanner Keyboard inputs (simulate scanner wedge typing digits + enter)
    document.addEventListener('keydown', function(event) {
        if (activeStep !== 'nfc-waiting') return;

        const scannerInput = document.getElementById('nfc-scanner-input');
        
        // Re-focus scanner if focus is lost
        if (document.activeElement !== scannerInput) {
            scannerInput.focus();
        }

        // When enter key is pressed, submit the card UID
        if (event.key === 'Enter') {
            const cardUid = scannerInput.value.trim();
            if (cardUid) {
                processNfcPayment(cardUid);
            }
            scannerInput.value = '';
        }
    });

    // Handle helper click to simulate card tap
    function simulateCardTap(cardUid) {
        showNfcScreen();
        setTimeout(() => {
            processNfcPayment(cardUid);
        }, 300);
    }

    function processNfcPayment(cardUid) {
        const token = document.getElementById('csrf-token').value;
        
        // Show loading state or temporarily freeze input
        document.getElementById('nfc-scanner-input').disabled = true;

        fetch("{{ route('orders.pay.nfc', $order) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ card_uid: cardUid })
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            document.getElementById('nfc-scanner-input').disabled = false;
            
            if (res.status === 200 && res.body.success) {
                // Success screen setup
                document.getElementById('success-student-name').textContent = res.body.student;
                document.getElementById('success-remaining-balance').textContent = 'RM ' + res.body.remaining_balance;
                
                activeStep = 'nfc-success';
                showStep('step-nfc-success');
                
                startRedirectCountdown('nfc-countdown');
            } else {
                alert(res.body.message || 'Payment processing failed. Please try again.');
                // Re-focus input to allow card scan retry
                const scannerInput = document.getElementById('nfc-scanner-input');
                scannerInput.value = '';
                scannerInput.focus();
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('nfc-scanner-input').disabled = false;
            alert('A connection error occurred. Please try again.');
        });
    }

    function submitCashPayment() {
        const token = document.getElementById('csrf-token').value;

        fetch("{{ route('orders.pay.cash', $order) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                activeStep = 'cash-success';
                showStep('step-cash-success');
                
                startRedirectCountdown('cash-countdown');
            } else {
                alert(data.message || 'Failed to record cash payment.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('A connection error occurred. Please try again.');
        });
    }

    function startRedirectCountdown(elementId) {
        let timeLeft = 2;
        const countdownEl = document.getElementById(elementId);
        countdownEl.textContent = timeLeft;

        countdownInterval = setInterval(() => {
            timeLeft--;
            countdownEl.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                redirectToOrders();
            }
        }, 1000);
    }
</script>
@endsection
