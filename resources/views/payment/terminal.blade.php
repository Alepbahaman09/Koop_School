<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cashier Terminal – {{ config('app.name', 'Koop School') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#F0F4F8;min-height:100vh;display:flex;flex-direction:column;color:#1E293B}

        /* ── TOP BAR ── */
        .topbar{background:#fff;border-bottom:1px solid #E2E8F0;height:56px;padding:0 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:60}
        .topbar-left{display:flex;align-items:center;gap:12px}
        .topbar-logo{width:32px;height:32px;background:#2563EB;border-radius:8px;display:grid;place-items:center;color:#fff;font-weight:900;font-size:14px;flex-shrink:0}
        .topbar-title{font-weight:800;font-size:15px;color:#1E293B}
        .topbar-subtitle{font-size:11px;font-weight:600;color:#94A3B8;margin-top:1px}
        .topbar-right{display:flex;align-items:center;gap:10px}
        .topbar-time{font-size:13px;font-weight:700;color:#475569;font-variant-numeric:tabular-nums}
        .back-link{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:#64748B;text-decoration:none;padding:5px 10px;border-radius:8px;border:1.5px solid #E2E8F0;transition:background .12s}
        .back-link:hover{background:#F8FAFC}
        .back-link svg{width:14px;height:14px}

        /* ── MAIN 3-COLUMN GRID ── */
        .terminal-grid{flex:1;display:grid;grid-template-columns:1fr 340px;grid-template-rows:1fr;gap:0;max-height:calc(100vh - 56px)}
        @media(max-width:900px){.terminal-grid{grid-template-columns:1fr;grid-template-rows:auto auto}}

        /* ── LEFT: CURRENT TRANSACTION ── */
        .txn-panel{display:flex;flex-direction:column;background:#F0F4F8;overflow:hidden}

        /* No-queue state */
        .no-queue{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;text-align:center;padding:40px}
        .no-queue-icon{font-size:64px}
        .no-queue-title{font-size:22px;font-weight:800;color:#1E293B}
        .no-queue-sub{font-size:14px;font-weight:500;color:#64748B;max-width:280px;line-height:1.5}
        .no-queue-link{display:inline-flex;align-items:center;gap:6px;background:#2563EB;color:#fff;border-radius:12px;padding:10px 20px;font-size:13px;font-weight:700;text-decoration:none;margin-top:8px;transition:background .12s}
        .no-queue-link:hover{background:#1D4ED8}

        /* Student card */
        .student-card{background:#fff;margin:16px 16px 0;border-radius:16px;padding:16px 20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 4px rgba(0,0,0,.07)}
        .student-avatar{width:48px;height:48px;background:#EFF6FF;border:2px solid #BFDBFE;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:18px;color:#2563EB;flex-shrink:0}
        .student-name{font-size:18px;font-weight:800;color:#0F172A;line-height:1.2}
        .student-meta{font-size:12px;font-weight:600;color:#64748B;margin-top:2px}
        .student-badge{margin-left:auto;background:#EFF6FF;color:#1D4ED8;border-radius:8px;padding:4px 10px;font-size:11px;font-weight:700;white-space:nowrap}

        /* Cart */
        .cart-wrap{flex:1;overflow-y:auto;margin:12px 16px 0;background:#fff;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,.07)}
        .cart-header{padding:12px 16px;border-bottom:1px solid #F1F5F9;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#94A3B8;display:flex;justify-content:space-between}
        .cart-item{display:grid;grid-template-columns:1fr auto auto;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #F8FAFC}
        .cart-item:last-child{border-bottom:none}
        .cart-item-name{font-size:14px;font-weight:600;color:#1E293B}
        .cart-item-qty{font-size:12px;font-weight:700;color:#94A3B8;background:#F1F5F9;border-radius:6px;padding:3px 8px;white-space:nowrap}
        .cart-item-price{font-size:14px;font-weight:700;color:#374151;text-align:right;white-space:nowrap}
        .cart-empty{text-align:center;padding:32px;color:#CBD5E1;font-size:13px;font-weight:600}

        /* Total bar */
        .total-bar{margin:12px 16px 0;background:#0F172A;border-radius:16px;padding:16px 20px;display:flex;align-items:center;justify-content:space-between}
        .total-label{font-size:13px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.06em}
        .total-amount{font-size:36px;font-weight:900;color:#fff;letter-spacing:-1px;line-height:1}
        .order-ref{font-size:11px;font-weight:600;color:#475569;margin-top:2px}

        /* Payment actions */
        .payment-actions{margin:12px 16px 16px;display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .pay-btn{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;min-height:90px;border-radius:16px;border:none;cursor:pointer;font-family:'Inter',sans-serif;transition:transform .1s,box-shadow .12s;-webkit-tap-highlight-color:transparent;position:relative;overflow:hidden}
        .pay-btn:active{transform:scale(.97)}
        .pay-btn-icon{font-size:32px;line-height:1}
        .pay-btn-label{font-size:14px;font-weight:800;line-height:1.2;text-align:center}
        .pay-btn-sub{font-size:11px;font-weight:500;opacity:.75;text-align:center}
        .pay-btn.nfc{background:#2563EB;color:#fff;box-shadow:0 4px 16px rgba(37,99,235,.3)}
        .pay-btn.nfc:hover{box-shadow:0 6px 20px rgba(37,99,235,.4)}
        .pay-btn.cash{background:#16A34A;color:#fff;box-shadow:0 4px 16px rgba(22,163,74,.3)}
        .pay-btn.cash:hover{box-shadow:0 6px 20px rgba(22,163,74,.4)}
        .pay-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}

        /* ── RIGHT: QUEUE PANEL ── */
        .queue-panel{background:#fff;border-left:1px solid #E2E8F0;display:flex;flex-direction:column;overflow:hidden}
        .queue-header{padding:16px 20px;border-bottom:1px solid #F1F5F9;display:flex;align-items:center;justify-content:space-between}
        .queue-header-title{font-size:13px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.06em}
        .queue-count{background:#EFF6FF;color:#1D4ED8;border-radius:20px;padding:2px 10px;font-size:12px;font-weight:700}
        .queue-list{flex:1;overflow-y:auto;padding:8px 0}
        .queue-item{display:flex;align-items:center;gap:12px;padding:10px 20px;cursor:pointer;transition:background .1s;text-decoration:none}
        .queue-item:hover{background:#F8FAFC}
        .queue-item.active-order{background:#EFF6FF;border-left:3px solid #2563EB}
        .queue-num{width:24px;height:24px;border-radius:50%;background:#F1F5F9;font-size:11px;font-weight:800;color:#94A3B8;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .queue-item.active-order .queue-num{background:#2563EB;color:#fff}
        .queue-info{flex:1;min-width:0}
        .queue-student{font-size:13px;font-weight:700;color:#1E293B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .queue-class{font-size:11px;font-weight:600;color:#94A3B8}
        .queue-amt{font-size:13px;font-weight:800;color:#374151;flex-shrink:0}
        .queue-status{font-size:10px;font-weight:700;border-radius:6px;padding:2px 6px;margin-top:2px;display:inline-block}
        .queue-status.unpaid{background:#FEF9C3;color:#A16207}
        .queue-status.partial{background:#FFEDD5;color:#C2410C}
        .queue-empty{padding:32px 20px;text-align:center;color:#CBD5E1;font-size:13px;font-weight:600}

        /* ── OVERLAY SCREENS ── */
        .overlay{position:fixed;inset:0;z-index:100;display:none;flex-direction:column;align-items:center;justify-content:center;padding:24px;background:rgba(15,23,42,.6);backdrop-filter:blur(4px)}
        .overlay.active{display:flex}
        .overlay-card{background:#fff;border-radius:24px;padding:40px 32px;max-width:440px;width:100%;text-align:center;box-shadow:0 24px 64px rgba(0,0,0,.25);animation:pop-in .25s cubic-bezier(.34,1.56,.64,1)}
        @keyframes pop-in{from{opacity:0;transform:scale(.88) translateY(8px)}to{opacity:1;transform:none}}

        /* NFC waiting overlay */
        .nfc-rings{position:relative;width:140px;height:140px;margin:0 auto 24px;display:flex;align-items:center;justify-content:center}
        .nfc-ring{position:absolute;border-radius:50%;border:3px solid rgba(37,99,235,.25);animation:ring-out 2s ease-out infinite}
        .nfc-ring:nth-child(1){width:140px;height:140px;animation-delay:0s}
        .nfc-ring:nth-child(2){width:104px;height:104px;animation-delay:.35s}
        .nfc-ring:nth-child(3){width:70px;height:70px;animation-delay:.7s}
        @keyframes ring-out{0%{opacity:1;transform:scale(.5)}100%{opacity:0;transform:scale(1)}}
        .nfc-core{position:relative;z-index:2;width:72px;height:72px;background:#2563EB;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 28px rgba(37,99,235,.45)}
        .nfc-core svg{width:36px;height:36px;color:#fff}
        .overlay-title{font-size:22px;font-weight:800;color:#0F172A;margin-bottom:8px}
        .overlay-sub{font-size:14px;font-weight:500;color:#64748B;line-height:1.5;margin-bottom:20px}
        .overlay-amount{background:#EFF6FF;border:1.5px solid #BFDBFE;border-radius:100px;padding:8px 24px;font-size:22px;font-weight:900;color:#1D4ED8;letter-spacing:-.5px;display:inline-block;margin-bottom:24px}

        /* Cash confirm overlay */
        .cash-amount{font-size:52px;font-weight:900;color:#15803D;letter-spacing:-2px;line-height:1;margin:12px 0 24px}

        /* Success overlay */
        .success-icon-wrap{width:100px;height:100px;background:#F0FDF4;border:4px solid #22C55E;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;animation:pop-in .35s cubic-bezier(.34,1.56,.64,1)}
        .success-icon-wrap svg{width:52px;height:52px;color:#16A34A}
        .success-title{font-size:26px;font-weight:900;color:#0F172A;margin-bottom:4px}
        .success-amount{font-size:38px;font-weight:900;color:#16A34A;letter-spacing:-1px;margin-bottom:20px}
        .success-detail{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:14px;padding:14px 18px;margin-bottom:20px;text-align:left}
        .success-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;font-size:13px}
        .success-row+.success-row{border-top:1px solid #F1F5F9}
        .s-label{color:#64748B;font-weight:600}
        .s-value{color:#1E293B;font-weight:700}
        .s-value.green{color:#16A34A}
        .countdown-text{font-size:12px;font-weight:600;color:#94A3B8}

        /* Buttons */
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:52px;border-radius:14px;font-size:14px;font-weight:700;cursor:pointer;border:none;font-family:'Inter',sans-serif;transition:background .12s,transform .1s;padding:0 24px;width:100%;letter-spacing:-.2px}
        .btn:active{transform:scale(.97)}
        .btn-blue{background:#2563EB;color:#fff;box-shadow:0 4px 12px rgba(37,99,235,.3)}
        .btn-blue:hover{background:#1D4ED8}
        .btn-green{background:#16A34A;color:#fff;box-shadow:0 4px 12px rgba(22,163,74,.3)}
        .btn-green:hover{background:#15803D}
        .btn-outline{background:transparent;color:#475569;border:2px solid #E2E8F0}
        .btn-outline:hover{background:#F8FAFC}
        .btn-row{display:flex;gap:10px;margin-top:8px}
        .btn-row .btn{flex:1}

        /* Sim panel */
        .sim-section{margin-top:20px;border-top:1px solid #F1F5F9;padding-top:16px;text-align:left}
        .sim-title{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#F59E0B;margin-bottom:8px}
        .sim-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
        .sim-btn{background:#FFFBEB;border:1.5px solid #FDE68A;border-radius:10px;padding:8px 12px;cursor:pointer;text-align:left;transition:background .1s}
        .sim-btn:hover{background:#FEF9C3}
        .sim-name{font-size:12px;font-weight:700;color:#1E293B}
        .sim-meta{font-size:10px;color:#92400E;margin-top:2px}

        /* Hidden scanner input */
        #nfc-input{position:fixed;top:-200px;left:0;width:1px;height:1px;opacity:0}

        /* Toast */
        #toast{position:fixed;bottom:20px;left:50%;transform:translateX(-50%) translateY(80px);background:#1E293B;color:#fff;border-radius:12px;padding:11px 22px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.25);z-index:200;transition:transform .3s ease,opacity .3s ease;opacity:0;pointer-events:none;white-space:nowrap}
        #toast.show{transform:translateX(-50%) translateY(0);opacity:1}
    </style>
</head>
<body>

<!-- TOP BAR -->
<header class="topbar">
    <div class="topbar-left">
        <div class="topbar-logo">K</div>
        <div>
            <div class="topbar-title">Cashier Terminal</div>
            <div class="topbar-subtitle">KoopAll School Cooperative POS</div>
        </div>
    </div>
    <div class="topbar-right">
        <span class="topbar-time" id="live-clock">--:--</span>
        <a href="{{ route('orders.index') }}" class="back-link">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Orders
        </a>
    </div>
</header>

<!-- Hidden scanner input (catches NFC keyboard-wedge) -->
<input type="text" id="nfc-input" autocomplete="off" inputmode="none">

<div class="terminal-grid">

    {{-- ════════════ LEFT: CURRENT TRANSACTION ════════════ --}}
    <div class="txn-panel">

        @if (!$order)
            {{-- All-clear state --}}
            <div class="no-queue">
                <div class="no-queue-icon">✅</div>
                <div class="no-queue-title">Queue is clear!</div>
                <div class="no-queue-sub">All orders have been paid. No students are waiting.</div>
                <a href="{{ route('orders.index') }}" class="no-queue-link">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    View Orders
                </a>
            </div>

        @else
            {{-- Student card --}}
            <div class="student-card">
                <div class="student-avatar">
                    {{ strtoupper(substr($order->customer?->student_name ?? 'S', 0, 1)) }}
                </div>
                <div>
                    <div class="student-name">{{ $order->customer?->student_name ?? 'Walk-in Customer' }}</div>
                    <div class="student-meta">
                        {{ $order->customer?->class ? 'Class ' . $order->customer->class : '' }}
                        @if ($order->customer?->student_id)
                            &middot; ID: {{ $order->customer->student_id }}
                        @endif
                    </div>
                </div>
                <div class="student-badge"># {{ $order->order_number }}</div>
            </div>

            {{-- Cart items --}}
            <div class="cart-wrap">
                <div class="cart-header">
                    <span>Item</span>
                    <span>Total</span>
                </div>
                @forelse ($order->orderItems as $item)
                    <div class="cart-item">
                        <div class="cart-item-name">{{ $item->product?->name ?? 'Unknown item' }}</div>
                        <div class="cart-item-qty">x{{ $item->quantity }}</div>
                        <div class="cart-item-price">RM {{ number_format($item->subtotal, 2) }}</div>
                    </div>
                @empty
                    <div class="cart-empty">No items in this order.</div>
                @endforelse
            </div>

            {{-- Total --}}
            <div class="total-bar">
                <div>
                    <div class="total-label">Total Amount Due</div>
                    <div class="order-ref">{{ $order->payment_status }}</div>
                </div>
                <div class="total-amount">RM {{ number_format($order->total_amount, 2) }}</div>
            </div>

            {{-- Payment action buttons --}}
            <div class="payment-actions">
                <button class="pay-btn nfc" onclick="openNfc()" type="button">
                    <div class="pay-btn-icon">
                        <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M6 8.5C7.5 7 9.6 6 12 6s4.5 1 6 2.5"/>
                            <path d="M8.5 11C9.5 10 10.7 9.5 12 9.5s2.5.5 3.5 1.5"/>
                            <circle cx="12" cy="14" r="1.2" fill="currentColor"/>
                            <path d="M3 5.5C5.4 3.3 8.5 2 12 2s6.6 1.3 9 3.5"/>
                        </svg>
                    </div>
                    <div class="pay-btn-label">NFC Card</div>
                    <div class="pay-btn-sub">Tap student's card</div>
                </button>

                <button class="pay-btn cash" onclick="openCash()" type="button">
                    <div class="pay-btn-icon">
                        <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <rect x="1" y="4" width="22" height="16" rx="3"/>
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M1 9h2M21 9h2M1 15h2M21 15h2"/>
                        </svg>
                    </div>
                    <div class="pay-btn-label">Cash</div>
                    <div class="pay-btn-sub">Receive cash manually</div>
                </button>
            </div>
        @endif
    </div>

    {{-- ════════════ RIGHT: QUEUE PANEL ════════════ --}}
    <div class="queue-panel">
        <div class="queue-header">
            <span class="queue-header-title">Waiting Queue</span>
            <span class="queue-count">{{ $waitingQueue->count() }} waiting</span>
        </div>

        <div class="queue-list">
            @if ($order)
                {{-- Active order shown at top --}}
                <a class="queue-item active-order" href="{{ route('payment.index', ['order_id' => $order->id]) }}">
                    <div class="queue-num">●</div>
                    <div class="queue-info">
                        <div class="queue-student">{{ $order->customer?->student_name ?? 'Walk-in' }}</div>
                        <div class="queue-class">
                            {{ $order->customer?->class ? 'Class ' . $order->customer->class : '—' }}
                            <span class="queue-status {{ strtolower($order->payment_status) }}">{{ $order->payment_status }}</span>
                        </div>
                    </div>
                    <div class="queue-amt">RM {{ number_format($order->total_amount, 2) }}</div>
                </a>
            @endif

            @forelse ($waitingQueue as $i => $q)
                <a class="queue-item" href="{{ route('payment.index', ['order_id' => $q->id]) }}">
                    <div class="queue-num">{{ $i + 1 }}</div>
                    <div class="queue-info">
                        <div class="queue-student">{{ $q->customer?->student_name ?? 'Walk-in' }}</div>
                        <div class="queue-class">
                            {{ $q->customer?->class ? 'Class ' . $q->customer->class : '—' }}
                            <span class="queue-status {{ strtolower($q->payment_status) }}">{{ $q->payment_status }}</span>
                        </div>
                    </div>
                    <div class="queue-amt">RM {{ number_format($q->total_amount, 2) }}</div>
                </a>
            @empty
                @if (!$order)
                    <div class="queue-empty">No students waiting.</div>
                @endif
            @endforelse
        </div>
    </div>

</div>

{{-- ══════════ NFC OVERLAY ══════════ --}}
@if ($order)
<div class="overlay" id="overlay-nfc">
    <div class="overlay-card">
        <div class="nfc-rings">
            <div class="nfc-ring"></div>
            <div class="nfc-ring"></div>
            <div class="nfc-ring"></div>
            <div class="nfc-core">
                <svg fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M6 8.5C7.5 7 9.6 6 12 6s4.5 1 6 2.5"/>
                    <path d="M8.5 11C9.5 10 10.7 9.5 12 9.5s2.5.5 3.5 1.5"/>
                    <circle cx="12" cy="14" r="1.4" fill="currentColor"/>
                    <path d="M3 5.5C5.4 3.3 8.5 2 12 2s6.6 1.3 9 3.5"/>
                </svg>
            </div>
        </div>

        <div class="overlay-title">Tap NFC Card</div>
        <div class="overlay-sub">Please tap the student's NFC card<br>near the reader to complete payment.</div>
        <div class="overlay-amount">RM {{ number_format($order->total_amount, 2) }}</div>

        @if ($cards->count() > 0)
        <div class="sim-section">
            <div class="sim-title">⚙ Dev — Simulate card tap</div>
            <div class="sim-grid">
                @foreach ($cards as $c)
                    <button type="button" class="sim-btn" onclick="simulateTap('{{ $c->card_uid }}')">
                        <div class="sim-name">{{ $c->owner === '999999' ? 'Ali Bin Abu' : $c->owner }}</div>
                        <div class="sim-meta">{{ $c->card_uid }} · RM {{ number_format($c->balance, 2) }}</div>
                    </button>
                @endforeach
            </div>
        </div>
        @endif

        <div class="btn-row" style="margin-top:20px">
            <button class="btn btn-outline" onclick="closeNfc()" type="button">Cancel</button>
        </div>
    </div>
</div>

{{-- ══════════ CASH OVERLAY ══════════ --}}
<div class="overlay" id="overlay-cash">
    <div class="overlay-card">
        <div style="font-size:56px;margin-bottom:8px">💵</div>
        <div class="overlay-title">Cash Payment</div>
        <div class="overlay-sub">Collect cash from the student.</div>
        <div class="cash-amount">RM {{ number_format($order->total_amount, 2) }}</div>

        <div class="btn-row">
            <button class="btn btn-outline" onclick="closeCash()" type="button">Cancel</button>
            <button class="btn btn-green" onclick="confirmCash()" type="button">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Confirm Payment
            </button>
        </div>
    </div>
</div>

{{-- ══════════ SUCCESS OVERLAY ══════════ --}}
<div class="overlay" id="overlay-success">
    <div class="overlay-card">
        <div class="success-icon-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="success-title">Payment Successful</div>
        <div class="success-amount">RM {{ number_format($order->total_amount, 2) }}</div>

        <div class="success-detail">
            <div class="success-row"><span class="s-label">Student</span><span class="s-value" id="s-student">{{ $order->customer?->student_name ?? '—' }}</span></div>
            <div class="success-row"><span class="s-label">Order</span><span class="s-value">#{{ $order->order_number }}</span></div>
            <div class="success-row"><span class="s-label">Method</span><span class="s-value" id="s-method">—</span></div>
            <div class="success-row"><span class="s-label">Remaining Balance</span><span class="s-value green" id="s-balance" style="display:none">—</span><span class="s-value" id="s-balance-na" >N/A</span></div>
        </div>

        <div class="btn-row" style="margin-bottom:8px">
            <button class="btn btn-blue" onclick="nextStudent()" type="button">Next Student →</button>
        </div>
        <div class="countdown-text">Auto-advancing in <strong><span id="s-countdown">2</span>s</strong></div>
    </div>
</div>
@endif

{{-- Toast --}}
<div id="toast"></div>

<script>
    // ── Live clock ──
    (function tick() {
        const n = new Date();
        document.getElementById('live-clock').textContent =
            n.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit', hour12: true });
        setTimeout(tick, 1000);
    })();

    // ── Overlay helpers ──
    const NFC_URL  = @json(isset($order) ? route('orders.pay.nfc', $order) : '');
    const CASH_URL = @json(isset($order) ? route('orders.pay.cash', $order) : '');
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;

    function openOverlay(id) {
        document.getElementById(id).classList.add('active');
        if (id === 'overlay-nfc') {
            const inp = document.getElementById('nfc-input');
            inp.value = '';
            setTimeout(() => inp.focus(), 80);
        }
    }
    function closeOverlay(id) { document.getElementById(id).classList.remove('active'); }

    function openNfc()   { openOverlay('overlay-nfc'); }
    function closeNfc()  { closeOverlay('overlay-nfc'); }
    function openCash()  { openOverlay('overlay-cash'); }
    function closeCash() { closeOverlay('overlay-cash'); }

    // ── Keyboard-wedge NFC scanner ──
    document.addEventListener('keydown', e => {
        const nfcOpen = document.getElementById('overlay-nfc').classList.contains('active');
        if (!nfcOpen) return;
        const inp = document.getElementById('nfc-input');
        if (document.activeElement !== inp) inp.focus();
        if (e.key === 'Enter') {
            const uid = inp.value.trim();
            if (uid) doNfcPayment(uid);
            inp.value = '';
        }
    });

    function simulateTap(uid) { doNfcPayment(uid); }

    // ── NFC payment ──
    function doNfcPayment(uid) {
        const inp = document.getElementById('nfc-input');
        inp.disabled = true;

        fetch(NFC_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ card_uid: uid })
        })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            inp.disabled = false;
            if (ok && d.success) {
                closeNfc();
                document.getElementById('s-method').textContent = 'NFC Card';
                const balEl = document.getElementById('s-balance');
                const balNa = document.getElementById('s-balance-na');
                balEl.textContent = 'RM ' + d.remaining_balance;
                balEl.style.display = '';
                balNa.style.display = 'none';
                openOverlay('overlay-success');
                startCountdown();
            } else {
                toast(d.message || 'Card payment failed. Try again.');
                inp.value = ''; inp.focus();
            }
        })
        .catch(() => { inp.disabled = false; toast('Connection error. Retry.'); });
    }

    // ── Cash payment ──
    function confirmCash() {
        fetch(CASH_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                closeCash();
                document.getElementById('s-method').textContent = 'Cash';
                document.getElementById('s-balance').style.display = 'none';
                document.getElementById('s-balance-na').style.display = '';
                openOverlay('overlay-success');
                startCountdown();
            } else {
                toast(d.message || 'Payment failed. Try again.');
            }
        })
        .catch(() => toast('Connection error. Retry.'));
    }

    // ── Next student ──
    function nextStudent() { window.location.href = "{{ route('payment.index') }}"; }

    let countdownIv = null;
    function startCountdown() {
        let t = 2;
        const el = document.getElementById('s-countdown');
        el.textContent = t;
        clearInterval(countdownIv);
        countdownIv = setInterval(() => {
            t--;
            el.textContent = t;
            if (t <= 0) { clearInterval(countdownIv); nextStudent(); }
        }, 1000);
    }

    // ── Toast ──
    let toastTimer = null;
    function toast(msg) {
        const el = document.getElementById('toast');
        el.textContent = msg;
        el.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => el.classList.remove('show'), 3500);
    }
</script>
</body>
</html>
