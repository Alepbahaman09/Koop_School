<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cashier Terminal – {{ config('app.name', 'Koop School') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ── Base ── */
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Figtree', sans-serif; background: #f0f4f8; min-height: 100vh; overflow: hidden; }

        /* ── POS Grid ── */
        .pos-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            height: calc(100vh - 60px);
        }
        @media (max-width: 1024px) {
            .pos-grid { grid-template-columns: 1fr; height: auto; overflow: auto; }
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }

        /* ── Product cards ── */
        .product-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 12px rgba(0,0,0,.04);
            overflow: hidden;
            transition: transform .15s ease, box-shadow .15s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(37,99,235,.13);
        }
        .product-card:active { transform: translateY(0); }
        .product-img {
            width: 100%; aspect-ratio: 1/1;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; user-select: none;
        }
        .add-btn {
            width: 100%; background: #2563eb; color: #fff;
            border: none; border-radius: 10px; padding: 9px 0;
            font-size: 13px; font-weight: 700; cursor: pointer;
            transition: background .12s, transform .1s;
            font-family: inherit;
        }
        .add-btn:hover { background: #1d4ed8; }
        .add-btn:active { transform: scale(.97); }
        .add-btn:disabled { background: #94a3b8; cursor: not-allowed; }

        /* ── Category pills ── */
        .cat-pill {
            padding: 7px 16px; border-radius: 99px;
            border: 1.5px solid #e2e8f0; background: #fff;
            font-size: 13px; font-weight: 600; color: #64748b;
            cursor: pointer; transition: all .12s; white-space: nowrap;
            font-family: inherit;
        }
        .cat-pill.active, .cat-pill:hover {
            background: #2563eb; border-color: #2563eb; color: #fff;
        }

        /* ── Cart item ── */
        .cart-item-row {
            animation: slide-in .2s ease;
        }
        @keyframes slide-in { from { opacity: 0; transform: translateX(12px); } to { opacity: 1; transform: none; } }

        /* ── Qty stepper ── */
        .qty-btn {
            width: 28px; height: 28px; border-radius: 8px;
            border: 1.5px solid #e2e8f0; background: #f8fafc;
            font-size: 16px; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background .1s, border-color .1s; color: #374151;
            font-family: inherit;
        }
        .qty-btn:hover { background: #eff6ff; border-color: #93c5fd; color: #2563eb; }

        /* ── Payment method tabs ── */
        .pay-tab {
            flex: 1; padding: 10px 4px; border-radius: 10px;
            border: 1.5px solid #e2e8f0; background: #f8fafc;
            font-size: 11px; font-weight: 700; cursor: pointer;
            transition: all .12s; text-align: center; color: #64748b;
            font-family: inherit;
        }
        .pay-tab.active {
            background: #eff6ff; border-color: #2563eb; color: #2563eb;
        }
        .pay-tab:hover:not(.active):not(:disabled) {
            background: #f1f5f9; border-color: #cbd5e1;
        }
        .pay-tab:disabled { opacity: .45; cursor: not-allowed; }

        /* ── Checkout btn ── */
        .checkout-btn {
            width: 100%; padding: 16px; border-radius: 14px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff; border: none; font-size: 16px; font-weight: 800;
            cursor: pointer; transition: opacity .15s, transform .1s;
            box-shadow: 0 4px 16px rgba(37,99,235,.35);
            font-family: inherit; letter-spacing: -.2px;
        }
        .checkout-btn:hover { opacity: .92; }
        .checkout-btn:active { transform: scale(.98); }
        .checkout-btn:disabled {
            background: #94a3b8; box-shadow: none; cursor: not-allowed; opacity: 1;
        }

        /* ── Modals ── */
        .modal-backdrop {
            position: fixed; inset: 0; z-index: 100;
            background: rgba(15,23,42,.55); backdrop-filter: blur(4px);
            display: none; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-backdrop.open { display: flex; }
        .modal-card {
            background: #fff; border-radius: 24px; width: 100%; max-width: 480px;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 24px 64px rgba(0,0,0,.22);
            animation: modal-pop .25s cubic-bezier(.34,1.56,.64,1);
        }
        @keyframes modal-pop {
            from { opacity: 0; transform: scale(.88) translateY(10px); }
            to   { opacity: 1; transform: none; }
        }

        /* ── History modal wide ── */
        .modal-card.wide { max-width: 760px; }

        /* ── Toast ── */
        #toast {
            position: fixed; bottom: 24px; left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: #1e293b; color: #fff; border-radius: 12px;
            padding: 11px 22px; font-size: 13px; font-weight: 600;
            box-shadow: 0 8px 24px rgba(0,0,0,.25); z-index: 200;
            transition: transform .3s ease, opacity .3s ease;
            opacity: 0; pointer-events: none; white-space: nowrap;
        }
        #toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }

        /* ── Stock badge ── */
        .stock-low  { background: #fef9c3; color: #92400e; }
        .stock-ok   { background: #dcfce7; color: #166534; }
        .stock-out  { background: #fee2e2; color: #991b1b; }

        /* ── Receipt table ── */
        .receipt-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .receipt-table th { text-align: left; padding: 6px 8px; font-weight: 700; color: #64748b; border-bottom: 1px solid #f1f5f9; }
        .receipt-table td { padding: 7px 8px; border-bottom: 1px solid #f8fafc; }

        /* ── History table ── */
        .hist-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .hist-table th { text-align: left; padding: 10px 12px; font-weight: 700; color: #64748b; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .hist-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .hist-table tr:hover td { background: #f8fafc; }

        /* ── QR Placeholder ── */
        .qr-box {
            width: 140px; height: 140px; border: 3px dashed #93c5fd;
            border-radius: 16px; display: flex; align-items: center;
            justify-content: center; margin: 0 auto;
            background: repeating-linear-gradient(
                45deg, #eff6ff, #eff6ff 5px, #dbeafe 5px, #dbeafe 10px
            );
        }

        /* ── Scrollable sections ── */
        .products-scroll { flex: 1; overflow-y: auto; padding: 0 16px 16px; }
        .cart-scroll { flex: 1; overflow-y: auto; }

        /* ── Empty cart ── */
        .empty-cart {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            color: #cbd5e1; gap: 10px; padding: 32px;
        }

        /* Search bar */
        .search-wrap { position: relative; }
        .search-wrap svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #94a3b8; pointer-events: none; }
        .search-input { width: 100%; padding: 11px 14px 11px 42px; border-radius: 12px; border: 1.5px solid #e2e8f0; font-size: 14px; font-family: inherit; background: #fff; outline: none; transition: border-color .12s; }
        .search-input:focus { border-color: #2563eb; }

        /* ── Pop-out button ── */
        .popout-btn {
            display: flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 11px;
            border: 1.5px solid #e2e8f0; background: #f8fafc;
            font-size: 12px; font-weight: 700; color: #475569;
            cursor: pointer; transition: all .15s; font-family: inherit;
            white-space: nowrap;
        }
        .popout-btn:hover { background: #eff6ff; border-color: #93c5fd; color: #2563eb; }
        .popout-btn.docked { background: #eff6ff; border-color: #2563eb; color: #2563eb; }
        .popout-btn svg { width: 15px; height: 15px; flex-shrink: 0; }

        /* ── Docked-window notice banner ── */
        #docked-banner {
            display: none;
            position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
            background: linear-gradient(90deg,#2563eb,#1d4ed8);
            color: #fff; text-align: center;
            font-size: 12px; font-weight: 700; padding: 5px 12px;
            letter-spacing: .03em;
        }
        body.is-docked-window #docked-banner { display: block; }
        body.is-docked-window header { top: 30px; }
        body.is-docked-window .pos-grid { height: calc(100vh - 90px); margin-top: 30px; }
    </style>
</head>
<body>

{{-- ══════════ TOP BAR ══════════ --}}
<header class="bg-white border-b border-slate-200 px-5 flex items-center justify-between gap-4" style="height:60px; position:sticky; top:0; z-index:50;">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-black text-sm shrink-0"
             style="background: linear-gradient(135deg,#2563eb,#1d4ed8); box-shadow:0 4px 12px rgba(37,99,235,.35);">K</div>
        <div>
            <div class="font-black text-slate-800 text-sm leading-tight">Cashier Terminal</div>
            <div class="text-xs font-semibold text-slate-400 leading-tight">Koop School POS</div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        {{-- Live clock --}}
        <div class="hidden sm:flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
            </svg>
            <span id="live-clock" class="text-sm font-bold text-slate-700 tabular-nums">--:--</span>
        </div>

        {{-- Pop-out / Dock button --}}
        <button id="popout-btn" class="popout-btn" onclick="popOutWindow()" title="Open in a separate window — drag to another monitor">
            <svg id="popout-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            <span id="popout-label">Pop Out</span>
        </button>

        {{-- Order History --}}
        <button onclick="openHistoryModal()" class="flex items-center gap-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl px-4 py-2 text-sm font-700 transition-colors" style="font-weight:700;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Order History
        </button>

        {{-- User avatar --}}
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-bold text-sm shrink-0">
            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
        </div>
    </div>
</header>

{{-- Docked-window notice (only visible when running as a pop-out) --}}
<div id="docked-banner">📺 Running on external display — Cashier Terminal</div>

{{-- ══════════ MAIN POS GRID ══════════ --}}
<main class="pos-grid">

    {{-- ════════ LEFT: PRODUCTS PANEL ════════ --}}
    <div class="flex flex-col" style="background:#f0f4f8; overflow:hidden;">

        {{-- Search + Category bar --}}
        <div class="px-4 pt-4 pb-3 bg-white border-b border-slate-100">
            {{-- Search --}}
            <div class="search-wrap mb-3">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                </svg>
                <input id="search-input" type="text" class="search-input" placeholder="Search products by name…" oninput="filterProducts()">
            </div>

            {{-- Category pills – dynamically built from real DB categories --}}
            @php
                $catEmoji = [
                    'Snacks'       => '🍿',
                    'Drinks'       => '🥤',
                    'Stationery'   => '✏️',
                    'Instant Food' => '🍜',
                    'Food'         => '🍱',
                    'Beverages'    => '🧃',
                    'Others'       => '🛒',
                ];
            @endphp
            <div class="flex gap-2 overflow-x-auto pb-1 hide-scroll" id="category-pills">
                <button class="cat-pill active" data-cat="All" onclick="selectCat(this)">All</button>
                @foreach($categories as $cat)
                    <button class="cat-pill" data-cat="{{ $cat }}" onclick="selectCat(this)">
                        {{ $catEmoji[$cat] ?? '🏷️' }} {{ $cat }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Product Grid --}}
        <div class="products-scroll">
            <div class="text-xs font-semibold text-slate-400 mt-4 mb-3" id="product-count-label">Loading…</div>
            <div id="product-grid" class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));"></div>
            <div id="no-products" class="hidden text-center py-16 text-slate-400">
                <div class="text-5xl mb-3">🔍</div>
                <div class="font-semibold">No products found</div>
                <div class="text-sm">Try a different search or category</div>
            </div>
        </div>
    </div>

    {{-- ════════ RIGHT: CART PANEL ════════ --}}
    <div class="flex flex-col bg-white border-l border-slate-200" style="overflow:hidden;">

        {{-- Cart header --}}
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path stroke-linecap="round" d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
                <span class="font-black text-slate-800 text-base">Shopping Cart</span>
            </div>
            <div class="flex items-center gap-2">
                <span id="cart-count-badge" class="hidden bg-blue-600 text-white rounded-full px-2.5 py-0.5 text-xs font-bold">0</span>
                <button onclick="clearCart()" id="clear-cart-btn" class="hidden text-xs font-semibold text-slate-400 hover:text-red-500 transition-colors">Clear</button>
            </div>
        </div>

        {{-- Cart items --}}
        <div class="cart-scroll px-4 py-2" id="cart-items-wrap">
            {{-- Empty state --}}
            <div class="empty-cart" id="empty-cart-msg">
                <svg class="w-16 h-16 text-slate-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path stroke-linecap="round" d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
                <div class="font-semibold text-slate-400 text-sm">Cart is empty</div>
                <div class="text-xs text-slate-300">Add products from the left panel</div>
            </div>
            {{-- Items injected here by JS --}}
        </div>

        {{-- Totals --}}
        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50 shrink-0" id="totals-section" style="display:none;">
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between text-slate-500">
                    <span class="font-semibold">Subtotal</span>
                    <span class="font-bold" id="subtotal-val">RM0.00</span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span class="font-semibold">Discount</span>
                    <span class="font-bold text-emerald-600" id="discount-val">RM0.00</span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span class="font-semibold">Tax (0%)</span>
                    <span class="font-bold" id="tax-val">RM0.00</span>
                </div>
            </div>
            <div class="mt-2.5 pt-2.5 border-t border-slate-200 flex justify-between items-center">
                <span class="font-black text-slate-800 text-base">TOTAL</span>
                <span class="font-black text-blue-600 text-2xl leading-tight" id="total-val">RM0.00</span>
            </div>
        </div>

        {{-- Payment Methods --}}
        <div class="px-4 pb-3 shrink-0 border-t border-slate-100" id="payment-section" style="display:none;">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-3 mb-2">Payment Method</div>
            <div class="flex gap-2 mb-3" id="pay-tabs">
                <button class="pay-tab active" data-method="Cash" onclick="selectPayMethod(this)">
                    💵<br>Cash
                </button>
                <button class="pay-tab" data-method="DuitNow" onclick="selectPayMethod(this)">
                    📱<br>DuitNow QR
                </button>
                <button class="pay-tab" data-method="Card" onclick="selectPayMethod(this)">
                    💳<br>Card
                </button>
                <button class="pay-tab" data-method="Wallet" disabled title="Coming soon">
                    🎒<br><span class="opacity-60">Wallet</span>
                </button>
            </div>

            {{-- Cash panel --}}
            <div id="panel-Cash">
                <div class="mb-2">
                    <label class="text-xs font-bold text-slate-500 block mb-1">Amount Received (RM)</label>
                    <input id="cash-received" type="number" min="0" step="0.50" placeholder="0.00"
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-lg font-bold text-slate-800 outline-none focus:border-blue-400 transition-colors"
                        oninput="calcChange()">
                </div>
                <div class="flex justify-between items-center bg-emerald-50 rounded-xl px-4 py-2.5">
                    <span class="text-sm font-bold text-emerald-700">Change</span>
                    <span class="text-lg font-black text-emerald-700" id="change-val">RM0.00</span>
                </div>
            </div>

            {{-- DuitNow panel --}}
            <div id="panel-DuitNow" style="display:none;">
                <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 text-center">
                    <div class="text-sm font-bold text-blue-700 mb-3">Show QR to Student</div>
                    <div class="qr-box mb-3">
                        <svg class="w-10 h-10 text-blue-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                            <path stroke-linecap="round" d="M14 14h2v2h-2zm4 0h3v3h-3zm0 4h3v3h-3zm-4 2h2v2h-2z"/>
                        </svg>
                    </div>
                    <div class="text-xs text-blue-400 font-semibold">QR Code will appear here</div>
                </div>
            </div>

            {{-- Card panel --}}
            <div id="panel-Card" style="display:none;">
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">💳</div>
                    <div class="text-sm font-bold text-slate-700">Tap Card to Continue</div>
                    <div class="text-xs text-slate-400 mt-1">Ask student to tap their card on the reader</div>
                    <div class="mt-3 flex gap-1 justify-center">
                        <div class="w-2 h-2 rounded-full bg-blue-400 animate-bounce" style="animation-delay:0s"></div>
                        <div class="w-2 h-2 rounded-full bg-blue-400 animate-bounce" style="animation-delay:.15s"></div>
                        <div class="w-2 h-2 rounded-full bg-blue-400 animate-bounce" style="animation-delay:.30s"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Checkout button --}}
        <div class="px-4 pb-4 shrink-0" id="checkout-section" style="display:none;">
            <button id="checkout-btn" class="checkout-btn" onclick="doCheckout()">
                <span class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Complete Payment
                </span>
            </button>
        </div>

    </div>
</main>

{{-- ══════════ RECEIPT MODAL ══════════ --}}
<div class="modal-backdrop" id="receipt-modal">
    <div class="modal-card">
        <div class="p-6 text-center border-b border-slate-100">
            {{-- Success icon --}}
            <div class="w-20 h-20 bg-emerald-50 border-4 border-emerald-400 rounded-full flex items-center justify-center mx-auto mb-4"
                 style="animation: modal-pop .4s cubic-bezier(.34,1.56,.64,1);">
                <svg class="w-10 h-10 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="text-2xl font-black text-slate-800 mb-1">Payment Successful!</div>
            <div class="text-3xl font-black text-emerald-500 leading-tight" id="r-total">RM0.00</div>
        </div>

        <div class="p-6">
            {{-- Transaction details --}}
            <div class="bg-slate-50 rounded-2xl border border-slate-100 p-4 mb-4">
                <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                    <div>
                        <div class="text-slate-400 font-semibold text-xs mb-0.5">Transaction ID</div>
                        <div class="font-black text-slate-800" id="r-txn-id">—</div>
                    </div>
                    <div>
                        <div class="text-slate-400 font-semibold text-xs mb-0.5">Date & Time</div>
                        <div class="font-bold text-slate-700" id="r-date">—</div>
                    </div>
                    <div>
                        <div class="text-slate-400 font-semibold text-xs mb-0.5">Payment Method</div>
                        <div class="font-bold text-slate-700" id="r-method">—</div>
                    </div>
                    <div>
                        <div class="text-slate-400 font-semibold text-xs mb-0.5">Cashier</div>
                        <div class="font-bold text-slate-700" id="r-cashier">—</div>
                    </div>
                </div>
                {{-- Cash change row --}}
                <div id="r-change-row" class="hidden border-t border-slate-200 pt-2 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <div class="text-slate-400 font-semibold text-xs mb-0.5">Amount Received</div>
                        <div class="font-bold text-slate-700" id="r-received">—</div>
                    </div>
                    <div>
                        <div class="text-slate-400 font-semibold text-xs mb-0.5">Change</div>
                        <div class="font-bold text-emerald-600" id="r-change">—</div>
                    </div>
                </div>
            </div>

            {{-- Items table --}}
            <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Items Purchased</div>
            <div class="border border-slate-100 rounded-xl overflow-hidden mb-5">
                <table class="receipt-table">
                    <thead>
                        <tr class="bg-slate-50">
                            <th>Item</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Price</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody id="r-items"></tbody>
                </table>
            </div>

            {{-- Action buttons --}}
            <div class="flex gap-3">
                <button onclick="printReceipt()" class="flex-1 flex items-center justify-center gap-2 border-2 border-slate-200 rounded-2xl py-3 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                        <rect x="6" y="14" width="12" height="8" rx="1"/>
                    </svg>
                    Print Receipt
                </button>
                <button onclick="newSale()" class="flex-1 flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl py-3 text-sm font-bold transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Sale
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════ ORDER HISTORY MODAL ══════════ --}}
<div class="modal-backdrop" id="history-modal">
    <div class="modal-card wide">
        <div class="flex items-center justify-between p-6 border-b border-slate-100">
            <div>
                <div class="font-black text-slate-800 text-lg">Order History</div>
                <div class="text-xs text-slate-400 font-semibold mt-0.5">Recent POS transactions</div>
            </div>
            <button onclick="closeHistoryModal()" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Search bar inside history --}}
        <div class="px-6 pt-4 pb-3">
            <div class="search-wrap">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                </svg>
                <input id="hist-search" type="text" class="search-input" placeholder="Search by Transaction ID, method…" oninput="filterHistory()">
            </div>
        </div>

        <div class="overflow-x-auto px-6 pb-6">
            <table class="hist-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="history-tbody"></tbody>
            </table>
        </div>
    </div>
</div>

{{-- ══════════ TOAST ══════════ --}}
<div id="toast"></div>

{{-- ══════════ JAVASCRIPT ══════════ --}}
<script>
// ─────────────────────────────────────────────
// PRODUCT DATA (live from database via Blade)
// ─────────────────────────────────────────────
const PRODUCTS = @json($products);

// Category-emoji fallback map (used when a product has no image)
const CAT_EMOJI = {
    'Snacks':       '🍟',
    'Drinks':       '🥤',
    'Stationery':   '✏️',
    'Instant Food': '🍜',
    'Food':         '🍱',
    'Beverages':    '🧃',
    'Others':       '🛒',
};

const SAMPLE_HISTORY = [
    { id:'POS-000042', date:'2026-07-13 09:14', total:12.50, method:'Cash',     status:'Paid',      cashier:'Ahmad Rozi' },
    { id:'POS-000041', date:'2026-07-13 08:55', total:8.70,  method:'DuitNow',  status:'Paid',      cashier:'Ahmad Rozi' },
    { id:'POS-000040', date:'2026-07-12 14:30', total:3.00,  method:'Card',     status:'Paid',      cashier:'Nurul Ain'  },
    { id:'POS-000039', date:'2026-07-12 11:18', total:17.40, method:'Cash',     status:'Paid',      cashier:'Ahmad Rozi' },
    { id:'POS-000038', date:'2026-07-12 10:02', total:5.50,  method:'Cash',     status:'Paid',      cashier:'Nurul Ain'  },
    { id:'POS-000037', date:'2026-07-11 15:45', total:22.00, method:'DuitNow',  status:'Paid',      cashier:'Ahmad Rozi' },
    { id:'POS-000036', date:'2026-07-11 13:22', total:9.10,  method:'Card',     status:'Paid',      cashier:'Ahmad Rozi' },
    { id:'POS-000035', date:'2026-07-11 09:08', total:4.50,  method:'Cash',     status:'Paid',      cashier:'Nurul Ain'  },
    { id:'POS-000034', date:'2026-07-10 16:55', total:14.20, method:'Cash',     status:'Paid',      cashier:'Ahmad Rozi' },
    { id:'POS-000033', date:'2026-07-10 12:30', total:6.90,  method:'DuitNow',  status:'Paid',      cashier:'Ahmad Rozi' },
];

// ─────────────────────────────────────────────
// STATE
// ─────────────────────────────────────────────
let cart       = [];   // { product, qty }
let payMethod  = 'Cash';
let activeCat  = 'All';
let txnCounter = 43;

// ─────────────────────────────────────────────
// LIVE CLOCK
// ─────────────────────────────────────────────
(function tick() {
    const n = new Date();
    const el = document.getElementById('live-clock');
    if (el) el.textContent = n.toLocaleTimeString('en-MY', { hour:'2-digit', minute:'2-digit', hour12:true });
    setTimeout(tick, 1000);
})();

// ─────────────────────────────────────────────
// PRODUCT GRID
// ─────────────────────────────────────────────
function renderProducts() {
    const q   = document.getElementById('search-input').value.toLowerCase().trim();
    const grid = document.getElementById('product-grid');
    const noP  = document.getElementById('no-products');
    const lbl  = document.getElementById('product-count-label');

    let list = PRODUCTS;
    if (activeCat !== 'All') list = list.filter(p => p.category === activeCat);
    if (q) list = list.filter(p => p.name.toLowerCase().includes(q) || p.category.toLowerCase().includes(q));

    lbl.textContent = `${list.length} product${list.length !== 1 ? 's' : ''} found`;

    if (list.length === 0) {
        grid.innerHTML = '';
        noP.classList.remove('hidden');
        return;
    }
    noP.classList.add('hidden');

    grid.innerHTML = list.map(p => {
        const stockClass = p.stock === 0 ? 'stock-out' : p.stock <= 5 ? 'stock-low' : 'stock-ok';
        const stockLabel = p.stock === 0 ? 'Out of stock' : `Stock: ${p.stock}`;
        const disabled   = p.stock === 0 ? 'disabled' : '';
        const fallback   = CAT_EMOJI[p.category] ?? '🛒';

        // Use real image if available, otherwise category emoji
        const imgHtml = p.image
            ? `<img src="${p.image}" alt="${p.name}"
                   style="width:100%;height:100%;object-fit:cover;border-radius:0;"
                   onerror="this.parentElement.innerHTML='<span style=\'font-size:2.5rem\'>${fallback}</span>'">`
            : `<span style="font-size:2.5rem">${fallback}</span>`;

        return `
        <div class="product-card" onclick="${p.stock > 0 ? `addToCart(${p.id})` : ''}">
            <div class="product-img" style="background:${catColor(p.category)};">${imgHtml}</div>
            <div class="p-3 flex flex-col gap-2 flex-1">
                <div>
                    <div class="font-bold text-slate-800 text-sm leading-tight">${p.name}</div>
                    <div class="text-xs text-slate-400 font-semibold mt-0.5">${p.category}</div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="font-black text-blue-600 text-base">RM${p.price.toFixed(2)}</div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full ${stockClass}">${stockLabel}</span>
                </div>
                <button class="add-btn" ${disabled} onclick="event.stopPropagation(); ${p.stock > 0 ? `addToCart(${p.id})` : ''}">
                    ${p.stock === 0 ? '— Unavailable' : '+ Add'}
                </button>
            </div>
        </div>`;
    }).join('');
}

function catColor(cat) {
    const m = {
        'Snacks':       '#fef3c7',
        'Drinks':       '#dbeafe',
        'Stationery':   '#ede9fe',
        'Instant Food': '#fce7f3',
        'Food':         '#fef9c3',
        'Beverages':    '#cffafe',
        'Others':       '#dcfce7',
    };
    return m[cat] || '#f1f5f9';
}

function filterProducts() { renderProducts(); }

function selectCat(el) {
    document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    activeCat = el.dataset.cat;
    renderProducts();
}

// ─────────────────────────────────────────────
// CART
// ─────────────────────────────────────────────
function addToCart(id) {
    const p = PRODUCTS.find(x => x.id === id);
    if (!p || p.stock === 0) return;

    const existing = cart.find(c => c.product.id === id);
    if (existing) {
        if (existing.qty >= p.stock) { toast(`Max stock reached (${p.stock})`); return; }
        existing.qty++;
    } else {
        cart.push({ product: p, qty: 1 });
    }
    renderCart();
    toast(`${p.name} added ✓`);
}

function changeQty(id, delta) {
    const item = cart.find(c => c.product.id === id);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) {
        cart = cart.filter(c => c.product.id !== id);
    } else if (item.qty > item.product.stock) {
        item.qty = item.product.stock;
        toast(`Max stock: ${item.product.stock}`);
    }
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(c => c.product.id !== id);
    renderCart();
}

function clearCart() {
    if (!cart.length) return;
    cart = [];
    renderCart();
    toast('Cart cleared');
}

function renderCart() {
    const wrap   = document.getElementById('cart-items-wrap');
    const empty  = document.getElementById('empty-cart-msg');
    const badge  = document.getElementById('cart-count-badge');
    const clearB = document.getElementById('clear-cart-btn');
    const totSec = document.getElementById('totals-section');
    const paySec = document.getElementById('payment-section');
    const chkSec = document.getElementById('checkout-section');

    if (cart.length === 0) {
        empty.style.display = 'flex';
        wrap.querySelectorAll('.cart-item-row').forEach(el => el.remove());
        badge.classList.add('hidden');
        clearB.classList.add('hidden');
        totSec.style.display = 'none';
        paySec.style.display = 'none';
        chkSec.style.display = 'none';
        return;
    }

    empty.style.display = 'none';
    badge.classList.remove('hidden');
    clearB.classList.remove('hidden');
    totSec.style.display = 'block';
    paySec.style.display = 'block';
    chkSec.style.display = 'block';

    const totalItems = cart.reduce((s, c) => s + c.qty, 0);
    badge.textContent = totalItems;

    // Rebuild items
    wrap.querySelectorAll('.cart-item-row').forEach(el => el.remove());
    const frag = document.createDocumentFragment();
    cart.forEach(({ product: p, qty }) => {
        const lineTotal = (p.price * qty).toFixed(2);
        const fallback = CAT_EMOJI[p.category] ?? '🛒';
        const imgHtml = p.image
            ? `<img src="${p.image}" alt="${p.name}" class="w-full h-full object-cover rounded-xl" onerror="this.parentElement.innerHTML='${fallback}'">`
            : fallback;

        const div = document.createElement('div');
        div.className = 'cart-item-row py-3 border-b border-slate-100 last:border-0';
        div.dataset.id = p.id;
        div.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0" style="background:${catColor(p.category)};">${imgHtml}</div>
            <div class="flex-1 min-w-0">
                <div class="font-bold text-slate-800 text-sm leading-tight truncate">${p.name}</div>
                <div class="text-xs text-slate-400 font-semibold">RM${p.price.toFixed(2)} each</div>
                <div class="flex items-center gap-3 mt-2">
                    <button class="qty-btn" onclick="changeQty(${p.id}, -1)">−</button>
                    <span class="font-black text-slate-700 text-sm w-5 text-center">${qty}</span>
                    <button class="qty-btn" onclick="changeQty(${p.id}, 1)">+</button>
                    <span class="ml-auto font-black text-blue-600 text-base">RM${lineTotal}</span>
                </div>
            </div>
        </div>
        <button onclick="removeFromCart(${p.id})" class="mt-1.5 ml-12 text-xs font-semibold text-slate-300 hover:text-red-400 transition-colors">
            Remove
        </button>`;
        frag.appendChild(div);
    });
    wrap.appendChild(frag);

    updateTotals();
    calcChange();
    updateCheckoutBtn();
}

function updateTotals() {
    const subtotal = cart.reduce((s, c) => s + c.product.price * c.qty, 0);
    document.getElementById('subtotal-val').textContent = `RM${subtotal.toFixed(2)}`;
    document.getElementById('discount-val').textContent = `RM0.00`;
    document.getElementById('tax-val').textContent       = `RM0.00`;
    document.getElementById('total-val').textContent     = `RM${subtotal.toFixed(2)}`;
}

function getTotal() {
    return cart.reduce((s, c) => s + c.product.price * c.qty, 0);
}

// ─────────────────────────────────────────────
// PAYMENT METHOD
// ─────────────────────────────────────────────
function selectPayMethod(el) {
    document.querySelectorAll('.pay-tab').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    payMethod = el.dataset.method;

    ['Cash','DuitNow','Card'].forEach(m => {
        const p = document.getElementById(`panel-${m}`);
        if (p) p.style.display = m === payMethod ? 'block' : 'none';
    });
    updateCheckoutBtn();
}

function calcChange() {
    const received = parseFloat(document.getElementById('cash-received').value) || 0;
    const total    = getTotal();
    const change   = received - total;
    document.getElementById('change-val').textContent = change >= 0 ? `RM${change.toFixed(2)}` : `RM0.00`;
    updateCheckoutBtn();
}

function updateCheckoutBtn() {
    const btn   = document.getElementById('checkout-btn');
    if (!btn) return;
    const total = getTotal();
    let disabled = cart.length === 0;

    if (payMethod === 'Cash' && !disabled) {
        const received = parseFloat(document.getElementById('cash-received').value) || 0;
        disabled = received < total;
        btn.textContent = disabled ? `Enter RM${total.toFixed(2)} or more` : 'Complete Payment';
    } else {
        btn.innerHTML = `<span class="flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>Complete Payment</span>`;
    }
    btn.disabled = disabled;
}

// ─────────────────────────────────────────────
// CHECKOUT
// ─────────────────────────────────────────────
function doCheckout() {
    if (cart.length === 0) return;
    const total    = getTotal();
    const received = parseFloat(document.getElementById('cash-received').value) || 0;
    const change   = payMethod === 'Cash' ? (received - total) : 0;
    const txnId    = `POS-${String(txnCounter++).padStart(6,'0')}`;
    const now      = new Date();
    const dateStr  = now.toLocaleDateString('en-MY', { day:'2-digit', month:'short', year:'numeric' })
                   + ' ' + now.toLocaleTimeString('en-MY', { hour:'2-digit', minute:'2-digit', hour12:true });

    // Populate receipt
    document.getElementById('r-total').textContent   = `RM${total.toFixed(2)}`;
    document.getElementById('r-txn-id').textContent  = txnId;
    document.getElementById('r-date').textContent    = dateStr;
    document.getElementById('r-method').textContent  = payMethod === 'DuitNow' ? 'DuitNow QR' : payMethod;
    document.getElementById('r-cashier').textContent = '{{ auth()->user()->name ?? "Cashier" }}';

    const changeRow = document.getElementById('r-change-row');
    if (payMethod === 'Cash') {
        changeRow.classList.remove('hidden');
        changeRow.style.display = 'grid';
        document.getElementById('r-received').textContent = `RM${received.toFixed(2)}`;
        document.getElementById('r-change').textContent   = `RM${change.toFixed(2)}`;
    } else {
        changeRow.classList.add('hidden');
        changeRow.style.display = 'none';
    }

    // Receipt items
    const tbody = document.getElementById('r-items');
    tbody.innerHTML = cart.map(({ product:p, qty }) => `
        <tr>
            <td>${p.emoji} ${p.name}</td>
            <td class="text-right font-semibold">${qty}</td>
            <td class="text-right">RM${p.price.toFixed(2)}</td>
            <td class="text-right font-bold text-slate-800">RM${(p.price * qty).toFixed(2)}</td>
        </tr>`).join('') + `
        <tr class="bg-blue-50">
            <td colspan="3" class="font-black text-blue-800 text-right">TOTAL</td>
            <td class="text-right font-black text-blue-800">RM${total.toFixed(2)}</td>
        </tr>`;

    // Add to sample history
    SAMPLE_HISTORY.unshift({
        id: txnId, date: dateStr, total,
        method: payMethod === 'DuitNow' ? 'DuitNow QR' : payMethod,
        status: 'Paid', cashier: '{{ auth()->user()->name ?? "Cashier" }}'
    });

    openModal('receipt-modal');
}

function printReceipt() {
    toast('🖨️ Sending to printer… (demo)');
}

function newSale() {
    closeModal('receipt-modal');
    cart = [];
    document.getElementById('cash-received').value = '';
    renderCart();
    toast('✅ Ready for new sale!');
}

// ─────────────────────────────────────────────
// ORDER HISTORY
// ─────────────────────────────────────────────
function openHistoryModal() {
    renderHistory(SAMPLE_HISTORY);
    openModal('history-modal');
}
function closeHistoryModal() { closeModal('history-modal'); }

function renderHistory(list) {
    const tbody = document.getElementById('history-tbody');
    const statusBadge = s => s === 'Paid'
        ? '<span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 text-xs font-bold px-2.5 py-1 rounded-full">✓ Paid</span>'
        : '<span class="inline-flex items-center bg-amber-50 text-amber-700 text-xs font-bold px-2.5 py-1 rounded-full">Pending</span>';
    const methodIcon = m => ({ Cash:'💵', DuitNow:'📱','DuitNow QR':'📱', Card:'💳' }[m] || '💰');

    tbody.innerHTML = list.length === 0
        ? `<tr><td colspan="6" class="text-center py-8 text-slate-400 font-semibold">No transactions found</td></tr>`
        : list.map(h => `
        <tr>
            <td><span class="font-black text-blue-600">${h.id}</span></td>
            <td class="text-slate-600">${h.date}</td>
            <td><span class="font-black text-slate-800">RM${parseFloat(h.total).toFixed(2)}</span></td>
            <td><span class="font-semibold">${methodIcon(h.method)} ${h.method}</span></td>
            <td>${statusBadge(h.status)}</td>
            <td><button onclick="toast('Details for ${h.id}')" class="text-xs font-bold text-blue-500 hover:text-blue-700">View</button></td>
        </tr>`).join('');
}

function filterHistory() {
    const q = document.getElementById('hist-search').value.toLowerCase();
    const filtered = SAMPLE_HISTORY.filter(h =>
        h.id.toLowerCase().includes(q) ||
        h.method.toLowerCase().includes(q) ||
        h.cashier.toLowerCase().includes(q) ||
        h.status.toLowerCase().includes(q)
    );
    renderHistory(filtered);
}

// ─────────────────────────────────────────────
// MODAL HELPERS
// ─────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Close on backdrop click
document.querySelectorAll('.modal-backdrop').forEach(el => {
    el.addEventListener('click', e => {
        if (e.target === el) el.classList.remove('open');
    });
});

// ─────────────────────────────────────────────
// TOAST
// ─────────────────────────────────────────────
let _toastTimer = null;
function toast(msg) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(() => el.classList.remove('show'), 2800);
}

// ─────────────────────────────────────────────
// POP-OUT / DOCK TO MONITOR
// ─────────────────────────────────────────────
let _popWin = null;

function popOutWindow() {
    const btn   = document.getElementById('popout-btn');
    const label = document.getElementById('popout-label');
    const icon  = document.getElementById('popout-icon');

    // If already open, close / bring to front
    if (_popWin && !_popWin.closed) {
        _popWin.close();
        _popWin = null;
        _restorePopoutBtn(btn, label, icon);
        return;
    }

    // Detect available screens (Screen Capture API, best-effort)
    const sw = window.screen.availWidth;
    const sh = window.screen.availHeight;

    // Try to open on a second monitor by offsetting by primary screen width
    // If there's only one screen this will just open a large popup on screen 1
    const secondary = window.screen.width; // left edge of secondary (heuristic)
    const winW  = Math.min(1400, sw);
    const winH  = sh;
    const left  = secondary;  // opens starting at right edge of primary monitor
    const top   = 0;

    const features = [
        `width=${winW}`,
        `height=${winH}`,
        `left=${left}`,
        `top=${top}`,
        'resizable=yes',
        'scrollbars=no',
        'status=no',
        'toolbar=no',
        'menubar=no',
        'location=no',
    ].join(',');

    _popWin = window.open(window.location.href + '?popout=1', 'CashierTerminal', features);

    if (!_popWin) {
        toast('⚠️ Pop-up blocked! Allow pop-ups for this site.');
        return;
    }

    // Mark the child window so it shows the docked banner
    _popWin.addEventListener('load', () => {
        try {
            _popWin.document.body.classList.add('is-docked-window');
            // Hide the pop-out button inside the child window (no nesting)
            const childBtn = _popWin.document.getElementById('popout-btn');
            if (childBtn) childBtn.style.display = 'none';
        } catch(e) { /* cross-origin guard */ }
    });

    // Update parent button state
    btn.classList.add('docked');
    label.textContent = 'Close External';
    icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>`;

    // Watch for child window close
    const watcher = setInterval(() => {
        if (_popWin && _popWin.closed) {
            clearInterval(watcher);
            _popWin = null;
            _restorePopoutBtn(btn, label, icon);
        }
    }, 800);

    toast('📺 Terminal opened on external display!');
}

function _restorePopoutBtn(btn, label, icon) {
    btn.classList.remove('docked');
    label.textContent = 'Pop Out';
    icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>`;
    toast('📺 External window closed.');
}

// If this IS the popped-out window, apply the docked class immediately
(function() {
    if (new URLSearchParams(window.location.search).get('popout') === '1') {
        document.body.classList.add('is-docked-window');
        const btn = document.getElementById('popout-btn');
        if (btn) btn.style.display = 'none';
    }
})();

// ─────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────
renderProducts();
renderCart();
</script>
</body>
</html>
