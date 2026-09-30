@php
    $terminalPayment = $order->terminalPayments->first();
    $regularPayment = $order->payments->first();

    if ($terminalPayment && $terminalPayment->payment_method === 'Cash') {
        $customerLabel = 'Cash-' . $terminalPayment->id;
        $paymentMethod = 'Cash';
    } elseif ($terminalPayment && $terminalPayment->payment_method === 'NFC Card') {
        $nfcUserName = $terminalPayment->user?->name ?? $terminalPayment->user?->username ?? 'Unknown';
        $cardOwnerName = $terminalPayment->card?->owner ?? 'Unknown card';
        $customerLabel = $nfcUserName . ' - ' . $cardOwnerName;
        $paymentMethod = 'NFC Card';
    } elseif ($regularPayment) {
        $parentName = $order->user?->name ?? $order->user?->username ?? $order->customer?->parent_name ?? 'Unknown';
        $childName = $order->student?->name ?? $order->customer?->student_name ?? 'Unknown student';
        $customerLabel = $parentName . ' - ' . $childName;
        $paymentMethod = $regularPayment->payment_method ?: 'Online';
    } else {
        $customerLabel = $order->customer?->parent_name ?? 'Walk-in';
        $paymentMethod = 'Cash';
    }

    $studentLabel = $order->student?->name
        ? $order->student->name . ($order->student->class ? ' (' . $order->student->class . ')' : '')
        : ($order->customer?->student_name && $order->customer->student_name !== '-'
            ? $order->customer->student_name . ($order->customer->class && $order->customer->class !== '-' ? ' (' . $order->customer->class . ')' : '')
            : 'General / Walk-in');

    $displayOrderId = str_starts_with($order->order_number, 'POS-') ? ('Kiosk-' . $order->id) : $order->order_number;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $displayOrderId }} - Koop School</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 30px 15px;
            display: flex;
            justify-content: center;
        }
        .receipt-card {
            background: #ffffff;
            width: 100%;
            max-width: 580px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            padding: 32px;
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            font-weight: 900;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header p {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
            font-weight: 600;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 24px;
            font-size: 12px;
        }
        .meta-item .label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 2px;
        }
        .meta-item .value {
            font-weight: 800;
            color: #1e293b;
            word-break: break-word;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .items-table th {
            text-align: left;
            padding: 8px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            border-bottom: 1.5px solid #e2e8f0;
        }
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        .item-name {
            font-weight: 800;
            color: #0f172a;
            font-size: 13px;
        }
        .item-meta {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .size-tag {
            display: inline-block;
            background: #e0e7ff;
            color: #3730a3;
            border-radius: 4px;
            padding: 1px 5px;
            font-size: 10px;
            font-weight: 800;
            margin-left: 4px;
        }
        .totals-section {
            border-top: 2px dashed #cbd5e1;
            padding-top: 14px;
            margin-top: 10px;
            font-size: 13px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-weight: 600;
            color: #475569;
        }
        .grand-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
            font-weight: 900;
            color: #0f172a;
            border-top: 1.5px solid #e2e8f0;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
        }
        .btn-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 800;
            border-radius: 10px;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .btn-primary {
            background: #4f46e5;
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #4338ca;
        }
        .btn-secondary {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background: #f8fafc;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .btn-bar {
                display: none !important;
            }
            .receipt-card {
                border: none;
                box-shadow: none;
                padding: 10px;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div style="width: 100%; max-width: 580px;">
    {{-- Action Bar --}}
    <div class="btn-bar no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8" rx="1"/>
            </svg>
            Print Receipt
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            Close
        </button>
    </div>

    {{-- Main Receipt Card --}}
    <div class="receipt-card">
        <div class="header">
            <h1>Koperasi Sekolah</h1>
            <p>Official Store Receipt & Order Confirmation</p>
        </div>

        <div class="meta-grid">
            <div class="meta-item">
                <div class="label">Order / Receipt ID</div>
                <div class="value" style="font-size: 14px; color: #4338ca;">{{ $displayOrderId }}</div>
            </div>
            <div class="meta-item">
                <div class="label">Date & Time</div>
                <div class="value">{{ $order->created_at->timezone(config('app.timezone'))->format('d M Y, h:i A') }}</div>
            </div>
            <div class="meta-item">
                <div class="label">Customer / Parent</div>
                <div class="value">{{ $customerLabel }}</div>
            </div>
            <div class="meta-item">
                <div class="label">Purchase For / Student</div>
                <div class="value">{{ $studentLabel }}</div>
            </div>
            <div class="meta-item">
                <div class="label">Payment Method</div>
                <div class="value">{{ $paymentMethod }} ({{ $order->payment_status }})</div>
            </div>
            <div class="meta-item">
                <div class="label">Order Status</div>
                <div class="value" style="color: #059669;">{{ $order->status }}</div>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 55%;">Item Details</th>
                    <th style="width: 15%; text-align: center;">Qty</th>
                    <th style="width: 15%; text-align: right;">Price</th>
                    <th style="width: 15%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->orderItems as $item)
                    <tr>
                        <td>
                            <div class="item-name">
                                {{ $item->product?->name ?? 'Item' }}
                                @if ($item->size)
                                    <span class="size-tag">{{ $item->size }}</span>
                                @endif
                            </div>

                            @if ($item->product?->sku)
                                <div class="item-meta">SKU: {{ $item->product->sku }}</div>
                            @endif
                        </td>
                        <td style="text-align: center; font-weight: 800; font-size: 13px;">
                            {{ $item->quantity }}
                        </td>
                        <td style="text-align: right; font-weight: 600;">
                            RM {{ number_format($item->unit_price, 2) }}
                        </td>
                        <td style="text-align: right; font-weight: 800; color: #1e1b4b;">
                            RM {{ number_format($item->subtotal, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-section">
            <div class="total-row">
                <span>Total Items</span>
                <span>{{ $order->orderItems->sum('quantity') }} pcs</span>
            </div>
            <div class="total-row">
                <span>Subtotal</span>
                <span>RM {{ number_format($order->subtotal ?: $order->total_amount, 2) }}</span>
            </div>
            <div class="grand-total">
                <span>Grand Total</span>
                <span style="color: #4f46e5;">RM {{ number_format($order->total_amount, 2) }}</span>
            </div>
        </div>

        <div class="footer">
            <p>Terima kasih kerana menyokong Koperasi Sekolah.</p>
            <p style="margin-top: 4px; font-size: 10px;">Printed via Koop School Management System</p>
        </div>
    </div>
</div>

<script>
    if (window.location.search.includes('print=1')) {
        window.addEventListener('load', () => window.print());
    }
</script>
</body>
</html>
