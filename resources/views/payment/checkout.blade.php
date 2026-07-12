<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Collect Payment – {{ config('app.name', 'Koop School') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #F8FAFC;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── TOP BAR ── */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #E2E8F0;
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar-brand { display: flex; align-items: center; gap: 10px; }
        .topbar-logo {
            width: 34px; height: 34px;
            background: #2563EB;
            border-radius: 8px;
            display: grid; place-items: center;
            color: #fff; font-weight: 800; font-size: 14px;
        }
        .topbar-name { font-weight: 700; font-size: 15px; color: #1E293B; }
        .topbar-back {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 13px; font-weight: 600; color: #64748B;
            text-decoration: none;
            padding: 6px 12px; border-radius: 8px;
            transition: background .15s, color .15s;
        }
        .topbar-back:hover { background: #F1F5F9; color: #1E293B; }
        .topbar-back svg { width: 16px; height: 16px; }

        /* ── MAIN LAYOUT ── */
        .pos-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 32px 16px 48px;
            max-width: 860px;
            margin: 0 auto;
            width: 100%;
        }

        /* ── ORDER BADGE ── */
        .order-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1.5px solid #E2E8F0;
            border-radius: 100px;
            padding: 6px 16px;
            margin-bottom: 28px;
            font-size: 13px;
            color: #64748B;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .order-badge span { color: #1E293B; font-weight: 700; }

        /* ── AMOUNT DISPLAY ── */
        .amount-display {
            text-align: center;
            margin-bottom: 36px;
        }
        .amount-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #94A3B8;
            margin-bottom: 6px;
        }
        .amount-value {
            font-size: clamp(3rem, 8vw, 5.5rem);
            font-weight: 900;
            color: #0F172A;
            line-height: 1;
            letter-spacing: -2px;
        }

        /* ── SCREEN CONTAINER ── */
        .screen { display: none; width: 100%; flex-direction: column; align-items: center; }
        .screen.active { display: flex; }

        /* ── METHOD SELECT ── */
        .method-title {
            font-size: 17px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 20px;
            letter-spacing: -.3px;
        }
        .method-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            width: 100%;
        }
        @media (max-width: 520px) {
            .method-grid { grid-template-columns: 1fr; }
        }
        .method-card {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            min-height: 200px;
            border-radius: 20px;
            border: 2.5px solid transparent;
            cursor: pointer;
            padding: 32px 24px;
            text-align: center;
            transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.07), 0 0 0 0 transparent;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        .method-card:active { transform: scale(.97); }

        .method-card.nfc { border-color: #BFDBFE; }
        .method-card.nfc:hover {
            border-color: #2563EB;
            box-shadow: 0 8px 24px rgba(37,99,235,.18);
            transform: translateY(-2px);
        }
        .method-card.cash { border-color: #BBF7D0; }
        .method-card.cash:hover {
            border-color: #16A34A;
            box-shadow: 0 8px 24px rgba(22,163,74,.18);
            transform: translateY(-2px);
        }

        .method-icon-wrap {
            width: 88px; height: 88px;
            border-radius: 24px;
            display: flex; align-items: center; justify-content: center;
            font-size: 44px;
        }
        .method-card.nfc .method-icon-wrap { background: #EFF6FF; }
        .method-card.cash .method-icon-wrap { background: #F0FDF4; }

        .method-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.5px;
        }
        .method-card.nfc .method-name { color: #1D4ED8; }
        .method-card.cash .method-name { color: #15803D; }

        .method-hint {
            font-size: 13px;
            font-weight: 500;
            color: #94A3B8;
            line-height: 1.4;
        }

        /* ── NFC WAITING ── */
        .nfc-waiting-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            padding: 20px 0 32px;
            width: 100%;
        }
        .nfc-pulse-outer {
            position: relative;
            width: 160px; height: 160px;
            display: flex; align-items: center; justify-content: center;
        }
        .nfc-ring {
            position: absolute;
            border-radius: 50%;
            border: 3px solid rgba(37,99,235,.3);
            animation: nfc-expand 2s ease-out infinite;
        }
        .nfc-ring:nth-child(1) { width: 100%; height: 100%; animation-delay: 0s; }
        .nfc-ring:nth-child(2) { width: 75%; height: 75%; animation-delay: .4s; }
        .nfc-ring:nth-child(3) { width: 50%; height: 50%; animation-delay: .8s; }
        @keyframes nfc-expand {
            0% { opacity: 1; transform: scale(.6); }
            100% { opacity: 0; transform: scale(1); }
        }
        .nfc-icon-core {
            position: relative; z-index: 2;
            width: 90px; height: 90px;
            background: #2563EB;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 32px rgba(37,99,235,.4);
        }
        .nfc-icon-core svg { width: 46px; height: 46px; color: #fff; }

        .nfc-waiting-title {
            font-size: 22px; font-weight: 800; color: #1E293B; text-align: center;
        }
        .nfc-waiting-sub {
            font-size: 14px; font-weight: 500; color: #64748B; text-align: center;
            max-width: 300px; line-height: 1.5;
        }
        .nfc-amount-chip {
            background: #EFF6FF;
            border: 1.5px solid #BFDBFE;
            border-radius: 100px;
            padding: 10px 24px;
            font-size: 24px;
            font-weight: 800;
            color: #1D4ED8;
            letter-spacing: -.5px;
        }

        /* ── CASH CONFIRM ── */
        .cash-confirm-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            padding: 20px 0 24px;
            width: 100%;
        }
        .cash-icon-wrap {
            width: 104px; height: 104px;
            background: #F0FDF4;
            border: 3px solid #86EFAC;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 52px;
        }
        .cash-confirm-label {
            font-size: 13px; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; color: #94A3B8;
        }
        .cash-confirm-amount {
            font-size: clamp(2.8rem, 8vw, 4.5rem);
            font-weight: 900;
            color: #15803D;
            letter-spacing: -2px;
            line-height: 1;
        }
        .cash-confirm-box {
            background: #F0FDF4;
            border: 1.5px solid #BBF7D0;
            border-radius: 16px;
            padding: 20px 32px;
            text-align: center;
            width: 100%;
            max-width: 400px;
        }

        /* ── SUCCESS ── */
        .success-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            padding: 16px 0 28px;
            width: 100%;
        }
        .success-icon {
            width: 120px; height: 120px;
            background: #F0FDF4;
            border: 4px solid #22C55E;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            animation: success-pop .4s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes success-pop {
            0% { opacity: 0; transform: scale(.5); }
            100% { opacity: 1; transform: scale(1); }
        }
        .success-check { width: 60px; height: 60px; color: #16A34A; }
        .success-title {
            font-size: 28px; font-weight: 900; color: #0F172A;
            letter-spacing: -.8px; text-align: center;
        }
        .success-amount {
            font-size: 40px; font-weight: 900; color: #16A34A;
            letter-spacing: -1px; line-height: 1;
        }
        .success-detail-card {
            background: #fff;
            border: 1.5px solid #E2E8F0;
            border-radius: 16px;
            padding: 20px 24px;
            width: 100%;
            max-width: 400px;
        }
        .success-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }
        .success-row + .success-row { border-top: 1px solid #F1F5F9; }
        .success-row-label { font-size: 13px; font-weight: 600; color: #64748B; }
        .success-row-value { font-size: 14px; font-weight: 700; color: #1E293B; }
        .success-row-value.green { color: #16A34A; }
        .success-countdown {
            font-size: 12px; font-weight: 600; color: #94A3B8;
            text-align: center;
        }
        .success-countdown strong { color: #475569; }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 56px;
            border-radius: 16px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: background .15s, transform .1s, box-shadow .15s;
            padding: 0 28px;
            width: 100%;
            letter-spacing: -.2px;
            -webkit-tap-highlight-color: transparent;
        }
        .btn:active { transform: scale(.97); }

        .btn-primary {
            background: #2563EB;
            color: #fff;
            box-shadow: 0 4px 14px rgba(37,99,235,.3);
        }
        .btn-primary:hover { background: #1D4ED8; box-shadow: 0 6px 20px rgba(37,99,235,.38); }

        .btn-success {
            background: #16A34A;
            color: #fff;
            box-shadow: 0 4px 14px rgba(22,163,74,.3);
        }
        .btn-success:hover { background: #15803D; box-shadow: 0 6px 20px rgba(22,163,74,.38); }

        .btn-ghost {
            background: transparent;
            color: #475569;
            border: 2px solid #E2E8F0;
        }
        .btn-ghost:hover { background: #F8FAFC; border-color: #CBD5E1; }

        .btn-row { display: flex; gap: 12px; width: 100%; max-width: 480px; }
        .btn-row .btn { flex: 1; }
        @media (max-width: 400px) {
            .btn-row { flex-direction: column-reverse; }
        }

        /* ── SIM HELPER ── */
        .sim-panel {
            margin-top: 32px;
            background: #FFFBEB;
            border: 1.5px solid #FDE68A;
            border-radius: 16px;
            padding: 20px;
            width: 100%;
        }
        .sim-header {
            display: flex; align-items: center; gap: 8px;
            font-size: 12px; font-weight: 700;
            letter-spacing: .08em; text-transform: uppercase;
            color: #92400E; margin-bottom: 10px;
        }
        .sim-desc {
            font-size: 12px; font-weight: 500; color: #A16207;
            margin-bottom: 14px; line-height: 1.5;
        }
        .sim-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        @media (max-width: 400px) { .sim-grid { grid-template-columns: 1fr; } }
        .sim-card-btn {
            background: #fff;
            border: 1.5px solid #FDE68A;
            border-radius: 12px;
            padding: 10px 14px;
            text-align: left;
            cursor: pointer;
            transition: background .12s, border-color .12s;
        }
        .sim-card-btn:hover { background: #FEF9C3; border-color: #FCD34D; }
        .sim-card-name { font-size: 12px; font-weight: 700; color: #1E293B; }
        .sim-card-meta { font-size: 11px; color: #78716C; margin-top: 2px; }

        /* ── HIDDEN INPUT ── */
        #nfc-scanner-input {
            position: fixed; top: -100px; left: -100px;
            width: 1px; height: 1px; opacity: 0; pointer-events: none;
        }
    </style>
</head>
<body>

<!-- TOP BAR -->
<header class="topbar">
    <div class="topbar-brand">
        <div class="topbar-logo">K</div>
        <span class="topbar-name">KoopAll POS</span>
    </div>
    <a href="{{ route('orders.index') }}" class="topbar-back">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        Back to Orders
    </a>
</header>

<main class="pos-wrap">

    <!-- Hidden CSRF -->
    <input type="hidden" id="csrf-token" value="{{ csrf_token() }}">
    <!-- Hidden NFC scanner input (receives keyboard wedge data) -->
    <input type="text" id="nfc-scanner-input" autocomplete="off" inputmode="none">

    <!-- Order badge -->
    <div class="order-badge">
        Order&nbsp;<span>#{{ $order->order_number }}</span>
        &middot;
        {{ $order->customer?->student_name ?? 'Walk-in' }}
    </div>

    <!-- Amount display (shown in all steps) -->
    <div class="amount-display">
        <div class="amount-label">Total Amount Due</div>
        <div class="amount-value">RM {{ number_format($order->total_amount, 2) }}</div>
    </div>

    {{-- ───────────────────────────── STEP 0: SELECT METHOD ────────────────────── --}}
    <div id="screen-select" class="screen active">
        <p class="method-title">Select Payment Method</p>

        <div class="method-grid">
            <!-- NFC Card -->
            <button class="method-card nfc" onclick="showNfcScreen()" type="button">
                <div class="method-icon-wrap">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 7a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h16z"/>
                        <path d="M16 11.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z" fill="#2563EB"/>
                        <path d="M1 12h2M21 12h2"/>
                        <path d="M13.5 9.5c1.1.6 2 1.7 2 2.5s-.9 1.9-2 2.5"/>
                        <path d="M16.5 7.5c2 1 3.5 2.8 3.5 4.5s-1.5 3.5-3.5 4.5"/>
                    </svg>
                </div>
                <div class="method-name">NFC Card</div>
                <div class="method-hint">Tap student's card to pay instantly</div>
            </button>

            <!-- Cash -->
            <button class="method-card cash" onclick="showCashScreen()" type="button">
                <div class="method-icon-wrap">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="4" width="22" height="16" rx="3"/>
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M1 9h2M21 9h2M1 15h2M21 15h2"/>
                    </svg>
                </div>
                <div class="method-name">Cash</div>
                <div class="method-hint">Receive cash and confirm manually</div>
            </button>
        </div>
    </div>

    {{-- ───────────────────────────── STEP 1: NFC WAITING ─────────────────────── --}}
    <div id="screen-nfc" class="screen">
        <div class="nfc-waiting-wrap">
            <div class="nfc-pulse-outer">
                <div class="nfc-ring"></div>
                <div class="nfc-ring"></div>
                <div class="nfc-ring"></div>
                <div class="nfc-icon-core">
                    <svg fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M6 8.5C7.5 7 9.6 6 12 6s4.5 1 6 2.5"/>
                        <path d="M8.5 11C9.5 10 10.7 9.5 12 9.5s2.5.5 3.5 1.5"/>
                        <path d="M11 14h.01" stroke-width="2.5"/>
                        <path d="M3 5.5C5.4 3.3 8.5 2 12 2s6.6 1.3 9 3.5"/>
                    </svg>
                </div>
            </div>

            <div class="nfc-waiting-title">Please tap the student's NFC card</div>
            <div class="nfc-waiting-sub">Hold the card near the NFC reader to complete the payment automatically.</div>
            <div class="nfc-amount-chip">RM {{ number_format($order->total_amount, 2) }}</div>
        </div>

        <div class="btn-row">
            <button class="btn btn-ghost" onclick="cancelToMethodSelect()" type="button">Cancel</button>
        </div>

        <!-- Simulation helper (shown only when on NFC screen) -->
        @if ($cards->count() > 0)
        <div class="sim-panel" id="sim-panel">
            <div class="sim-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Dev: Simulate Card Tap
            </div>
            <p class="sim-desc">NFC scanners type the card UID then press Enter. Click a card below to simulate a real tap.</p>
            <div class="sim-grid">
                @foreach ($cards as $c)
                    <button type="button" class="sim-card-btn" onclick="simulateCardTap('{{ $c->card_uid }}')">
                        <div class="sim-card-name">{{ $c->owner === '999999' ? 'Ali Bin Abu' : $c->owner }}</div>
                        <div class="sim-card-meta">{{ $c->card_uid }} &middot; RM {{ number_format($c->balance, 2) }}</div>
                    </button>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ───────────────────────────── STEP 2: NFC SUCCESS ─────────────────────── --}}
    <div id="screen-nfc-success" class="screen">
        <div class="success-wrap">
            <div class="success-icon">
                <svg class="success-check" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="success-title">Payment Successful</div>
            <div class="success-amount">RM {{ number_format($order->total_amount, 2) }}</div>

            <div class="success-detail-card">
                <div class="success-row">
                    <span class="success-row-label">Student</span>
                    <span class="success-row-value" id="nfc-success-student">—</span>
                </div>
                <div class="success-row">
                    <span class="success-row-label">Method</span>
                    <span class="success-row-value">NFC Card</span>
                </div>
                <div class="success-row">
                    <span class="success-row-label">Remaining Balance</span>
                    <span class="success-row-value green" id="nfc-success-balance">—</span>
                </div>
            </div>

            <div class="btn-row" style="max-width:340px">
                <button class="btn btn-primary" onclick="redirectToOrders()" type="button">Done</button>
            </div>
            <div class="success-countdown">Returning to orders in <strong><span id="nfc-countdown">2</span>s</strong>…</div>
        </div>
    </div>

    {{-- ───────────────────────────── STEP 3: CASH CONFIRM ────────────────────── --}}
    <div id="screen-cash" class="screen">
        <div class="cash-confirm-wrap">
            <div class="cash-icon-wrap">💵</div>
            <div class="cash-confirm-box">
                <div class="cash-confirm-label">Collect from student</div>
                <div class="cash-confirm-amount" style="margin-top:8px">RM {{ number_format($order->total_amount, 2) }}</div>
            </div>
        </div>

        <div class="btn-row">
            <button class="btn btn-ghost" onclick="cancelToMethodSelect()" type="button">Cancel</button>
            <button class="btn btn-success" onclick="submitCashPayment()" type="button">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Confirm Payment
            </button>
        </div>
    </div>

    {{-- ───────────────────────────── STEP 4: CASH SUCCESS ────────────────────── --}}
    <div id="screen-cash-success" class="screen">
        <div class="success-wrap">
            <div class="success-icon">
                <svg class="success-check" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="success-title">Payment Successful</div>
            <div class="success-amount">RM {{ number_format($order->total_amount, 2) }}</div>

            <div class="success-detail-card">
                <div class="success-row">
                    <span class="success-row-label">Order</span>
                    <span class="success-row-value">#{{ $order->order_number }}</span>
                </div>
                <div class="success-row">
                    <span class="success-row-label">Method</span>
                    <span class="success-row-value">Cash</span>
                </div>
                <div class="success-row">
                    <span class="success-row-label">Status</span>
                    <span class="success-row-value green">Paid ✓</span>
                </div>
            </div>

            <div class="btn-row" style="max-width:340px">
                <button class="btn btn-primary" onclick="redirectToOrders()" type="button">Done</button>
            </div>
            <div class="success-countdown">Returning to orders in <strong><span id="cash-countdown">2</span>s</strong>…</div>
        </div>
    </div>

</main>

<script>
    let activeStep = 'select';

    const SCREENS = ['select','nfc','nfc-success','cash','cash-success'];

    function showScreen(id) {
        SCREENS.forEach(s => {
            const el = document.getElementById('screen-' + s);
            if (el) el.classList.toggle('active', s === id);
        });
        activeStep = id;

        if (id === 'nfc') {
            const inp = document.getElementById('nfc-scanner-input');
            inp.value = '';
            setTimeout(() => inp.focus(), 80);
        }
    }

    function showNfcScreen()       { showScreen('nfc'); }
    function showCashScreen()      { showScreen('cash'); }
    function cancelToMethodSelect(){ showScreen('select'); }
    function redirectToOrders()    { window.location.href = "{{ route('orders.index') }}"; }

    /* ── NFC keyboard-wedge capture ── */
    document.addEventListener('keydown', e => {
        if (activeStep !== 'nfc') return;
        const inp = document.getElementById('nfc-scanner-input');
        if (document.activeElement !== inp) inp.focus();
        if (e.key === 'Enter') {
            const uid = inp.value.trim();
            if (uid) processNfcPayment(uid);
            inp.value = '';
        }
    });

    function simulateCardTap(uid) {
        // Already on NFC screen, just process immediately
        if (activeStep !== 'nfc') showNfcScreen();
        setTimeout(() => processNfcPayment(uid), 250);
    }

    function processNfcPayment(uid) {
        const inp = document.getElementById('nfc-scanner-input');
        inp.disabled = true;

        fetch("{{ route('orders.pay.nfc', $order) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.getElementById('csrf-token').value,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ card_uid: uid })
        })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            inp.disabled = false;
            if (ok && d.success) {
                document.getElementById('nfc-success-student').textContent = d.student;
                document.getElementById('nfc-success-balance').textContent  = 'RM ' + d.remaining_balance;
                showScreen('nfc-success');
                countdown('nfc-countdown');
            } else {
                showError(d.message || 'Card payment failed. Try again.');
                inp.value = ''; inp.focus();
            }
        })
        .catch(() => {
            inp.disabled = false;
            showError('Connection error. Please retry.');
        });
    }

    function submitCashPayment() {
        fetch("{{ route('orders.pay.cash', $order) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.getElementById('csrf-token').value,
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showScreen('cash-success');
                countdown('cash-countdown');
            } else {
                showError(d.message || 'Payment failed. Try again.');
            }
        })
        .catch(() => showError('Connection error. Please retry.'));
    }

    function countdown(elId) {
        let t = 2;
        const el = document.getElementById(elId);
        el.textContent = t;
        const iv = setInterval(() => {
            t--;
            el.textContent = t;
            if (t <= 0) { clearInterval(iv); redirectToOrders(); }
        }, 1000);
    }

    /* Inline toast for errors (no alert dialogs) */
    function showError(msg) {
        let toast = document.getElementById('pos-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'pos-toast';
            Object.assign(toast.style, {
                position:'fixed', bottom:'24px', left:'50%', transform:'translateX(-50%)',
                background:'#1E293B', color:'#fff', borderRadius:'12px',
                padding:'12px 24px', fontSize:'14px', fontWeight:'600',
                boxShadow:'0 8px 24px rgba(0,0,0,.25)', zIndex:'9999',
                transition:'opacity .25s', maxWidth:'340px', textAlign:'center'
            });
            document.body.appendChild(toast);
        }
        toast.textContent = msg;
        toast.style.opacity = '1';
        clearTimeout(toast._t);
        toast._t = setTimeout(() => { toast.style.opacity = '0'; }, 3500);
    }
</script>
</body>
</html>
