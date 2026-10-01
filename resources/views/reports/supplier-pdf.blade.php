<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ isset($selectedSupplier) ? 'Supplier Audit - ' . $selectedSupplier->company_name : 'Suppliers & Purchases Intake Report' }}</title>
    <style>
        @page {
            margin: 18mm 15mm 18mm 15mm;
            size: a4 landscape;
        }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header table {
            width: 100%;
        }
        .header h1 {
            font-size: 18px;
            color: #0f172a;
            margin: 0 0 4px 0;
            font-weight: bold;
        }
        .header p {
            margin: 0;
            color: #64748b;
            font-size: 9px;
        }
        .stats-table {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }
        .stats-table td {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            width: 33.33%;
        }
        .stats-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }
        .stats-value {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 3px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin: 14px 0 6px 0;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .info-box td {
            padding: 6px 10px;
            font-size: 9px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .info-title {
            font-weight: bold;
            color: #475569;
            font-size: 8px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .info-data {
            font-weight: bold;
            color: #0f172a;
            font-size: 9.5px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 8px;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            font-weight: bold;
            text-align: left;
        }
        .data-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 8.5px;
        }
        .data-table tfoot td {
            background-color: #f8fafc;
            border-top: 2px solid #cbd5e1;
            font-weight: bold;
            font-size: 9px;
            padding: 6px 8px;
        }
        .data-table tr:nth-child(even) td {
            background-color: #fafbfc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-green {
            background-color: #dcfce7;
            color: #15803d;
        }
        .badge-amber {
            background-color: #fef3c7;
            color: #b45309;
        }
        .badge-red {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .badge-indigo {
            background-color: #e0e7ff;
            color: #4338ca;
        }
        .badge-slate {
            background-color: #f1f5f9;
            color: #475569;
        }
        .signoff-table {
            width: 100%;
            margin-top: 24px;
            border-collapse: collapse;
        }
        .signoff-box {
            width: 33.33%;
            padding: 10px 14px;
            border: 1px dashed #cbd5e1;
            background-color: #fafbfc;
            vertical-align: top;
        }
        .signoff-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 25px;
        }
        .signoff-line {
            border-top: 1px solid #0f172a;
            padding-top: 4px;
            font-size: 8px;
            font-weight: bold;
            color: #0f172a;
        }
        .footer {
            margin-top: 16px;
            padding-top: 6px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

@if (isset($selectedSupplier) && $selectedSupplier)
    {{-- ==============================================================
         SINGLE SELECTED SUPPLIER AUDIT REPORT
         ============================================================== --}}
    <div class="header">
        <table>
            <tr>
                <td>
                    <h1>KOPERASI SEKOLAH</h1>
                    <p><strong>SUPPLIER INTAKE & EXPENDITURE AUDIT REPORT</strong> &bull; Official Cooperative Audit</p>
                </td>
                <td class="text-right">
                    <p><strong>Generated On:</strong> {{ date('d M Y, h:i A') }}</p>
                    <p><strong>Audited By:</strong> {{ auth()->user()->name ?? 'System Administrator' }}</p>
                    <p><strong>Target Vendor:</strong> {{ $selectedSupplier->company_name }} ({{ $selectedSupplier->supplier_id }})</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- Overview Stats Box (3 Cards) --}}
    <table class="stats-table">
        <tr>
            <td>
                <div class="stats-label">Total Purchase Orders</div>
                <div class="stats-value" style="color: #0f172a;">{{ number_format($stats['total_purchases']) }} Orders</div>
            </td>
            <td>
                <div class="stats-label">Total Stock Units Received</div>
                <div class="stats-value" style="color: #059669;">{{ number_format($stats['total_units']) }} Units</div>
            </td>
            <td>
                <div class="stats-label">Cumulative Supplier Expenditure</div>
                <div class="stats-value" style="color: #4338ca;">RM {{ number_format($stats['total_spent'], 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- Vendor Profile Verification Card --}}
    <div class="section-title">Vendor Profile & Contact Verification</div>
    <table class="info-box">
        <tr>
            <td style="width: 25%;">
                <span class="info-title">Supplier ID</span>
                <span class="info-data" style="color: #4338ca; font-family: monospace;">{{ $selectedSupplier->supplier_id }}</span>
            </td>
            <td style="width: 35%;">
                <span class="info-title">Company Name</span>
                <span class="info-data">{{ $selectedSupplier->company_name }}</span>
            </td>
            <td style="width: 20%;">
                <span class="info-title">Contact Person</span>
                <span class="info-data">{{ $selectedSupplier->contact_person ?: '—' }}</span>
            </td>
            <td style="width: 20%;">
                <span class="info-title">Contact Phone</span>
                <span class="info-data">{{ $selectedSupplier->phone ?: '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="info-title">Email Address</span>
                <span class="info-data">{{ $selectedSupplier->email ?: '—' }}</span>
            </td>
            <td colspan="2">
                <span class="info-title">Business Address</span>
                <span class="info-data">{{ $selectedSupplier->address ?: '—' }}</span>
            </td>
            <td>
                <span class="info-title">Registered Date</span>
                <span class="info-data">{{ $selectedSupplier->created_at?->format('d M Y') ?? '—' }}</span>
            </td>
        </tr>
    </table>

    {{-- Stock Purchases & Purchased Items Detail Table --}}
    <div class="section-title">Stock Intake & Purchased Items History</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Date & PO Ref</th>
                <th style="width: 28%;">Product / Item Description</th>
                <th style="width: 14%;">Category</th>
                <th style="width: 10%;" class="text-center">Intake Qty</th>
                <th style="width: 11%;" class="text-right">Cost Price (RM)</th>
                <th style="width: 11%;" class="text-right">Selling Price (RM)</th>
                <th style="width: 14%;" class="text-right">Subtotal (RM)</th>
            </tr>
        </thead>
        <tbody>
            @php $itemCount = 0; @endphp
            @forelse ($selectedSupplier->stockPurchases as $purchase)
                @forelse ($purchase->items as $item)
                    @php $itemCount++; @endphp
                    <tr>
                        <td>
                            <strong>{{ $purchase->purchase_date?->format('d/m/Y') ?? ($purchase->created_at?->format('d/m/Y') ?? '—') }}</strong>
                            <div style="color: #64748b; font-size: 7.5px;">PO #{{ $purchase->id }}</div>
                        </td>
                        <td>
                            <strong>{{ $item->item_name }}</strong>
                            @if ($item->item_sku)
                                <div style="color: #64748b; font-family: monospace; font-size: 7.5px;">SKU: {{ $item->item_sku }}</div>
                            @endif
                            @if ($item->product && $item->product->sizes && $item->product->sizes->isNotEmpty())
                                <div style="color: #4338ca; font-size: 7.5px; font-weight: bold;">
                                    Sizes: {{ $item->product->sizes->pluck('size')->join(', ') }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $item->product?->category?->name ?: '—' }}</td>
                        <td class="text-center font-bold" style="color: #4338ca;">{{ number_format($item->quantity) }}</td>
                        <td class="text-right">{{ number_format($item->purchase_price, 2) }}</td>
                        <td class="text-right" style="color: #64748b;">{{ number_format($item->selling_price, 2) }}</td>
                        <td class="text-right font-bold" style="color: #059669;">{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td><strong>{{ $purchase->purchase_date?->format('d/m/Y') ?? ($purchase->created_at?->format('d/m/Y') ?? '—') }}</strong></td>
                        <td colspan="5" style="color: #64748b;">Purchase recorded without individual items: {{ $purchase->notes ?: '—' }}</td>
                        <td class="text-right font-bold">{{ number_format($purchase->total_amount, 2) }}</td>
                    </tr>
                @endforelse
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 18px; color: #94a3b8;">
                        No stock purchase records found for this supplier.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($itemCount > 0 || $selectedSupplier->stockPurchases->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right" style="text-transform: uppercase; font-size: 8px; color: #64748b;">Grand Total:</td>
                    <td class="text-center font-bold" style="color: #4338ca;">{{ number_format($stats['total_units']) }} pcs</td>
                    <td colspan="2"></td>
                    <td class="text-right font-bold" style="color: #059669;">RM {{ number_format($stats['total_spent'], 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- Activity & Audit Trail for this Supplier --}}
    @if ($withActions && $activityLogs->isNotEmpty())
        <div class="section-title">Vendor Audit & Intake Activity Trail</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 14%;">Date & Time</th>
                    <th style="width: 10%;" class="text-center">Action</th>
                    <th style="width: 14%;">Entity</th>
                    <th style="width: 22%;">Activity / Item Name</th>
                    <th style="width: 28%;">Audit Details</th>
                    <th style="width: 12%;">Admin</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($activityLogs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d/m/Y h:i A') }}</td>
                        <td class="text-center">
                            @if ($log->action === 'created')
                                <span class="badge badge-green">Added</span>
                            @elseif ($log->action === 'updated')
                                <span class="badge badge-indigo">Edited</span>
                            @elseif ($log->action === 'deleted')
                                <span class="badge badge-red">Deleted</span>
                            @else
                                <span class="badge badge-slate">{{ $log->action }}</span>
                            @endif
                        </td>
                        <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $log->entity_type) }}</td>
                        <td><strong>{{ $log->entity_name }}</strong></td>
                        <td>{{ $log->details ?: 'No remarks' }}</td>
                        <td>{{ $log->admin_name ?: 'System' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

@else
    {{-- ==============================================================
         ALL SUPPLIERS SUMMARY & DIRECTORY REPORT
         ============================================================== --}}
    <div class="header">
        <table>
            <tr>
                <td>
                    <h1>KOPERASI SEKOLAH</h1>
                    <p>Cooperative Suppliers Directory & Stock Purchases Summary Report</p>
                </td>
                <td class="text-right">
                    <p><strong>Generated On:</strong> {{ date('d M Y, h:i A') }}</p>
                    <p><strong>Generated By:</strong> {{ auth()->user()->name ?? 'System Administrator' }}</p>
                    <p><strong>Report Scope:</strong> All Registered Suppliers</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- Overview Stats Box (3 Cards) --}}
    <table class="stats-table">
        <tr>
            <td>
                <div class="stats-label">Total Registered Suppliers</div>
                <div class="stats-value">{{ number_format($stats['total_suppliers']) }} Vendors</div>
            </td>
            <td>
                <div class="stats-label">Total Stock Purchases Recorded</div>
                <div class="stats-value" style="color: #059669;">{{ number_format($stats['total_purchases']) }} Orders</div>
            </td>
            <td>
                <div class="stats-label">Cumulative Supplier Expenditure</div>
                <div class="stats-value" style="color: #4338ca;">RM {{ number_format($stats['total_spent'], 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- Suppliers Breakdown Table --}}
    <div class="section-title">Suppliers Directory & Purchase Volume</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 11%;">Supplier ID</th>
                <th style="width: 25%;">Company Name</th>
                <th style="width: 15%;">Contact Person</th>
                <th style="width: 12%;">Phone</th>
                <th style="width: 15%;">Email</th>
                <th style="width: 8%;" class="text-center">Orders</th>
                <th style="width: 14%;" class="text-right">Total Spent (RM)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($suppliers as $s)
                @php
                    $spent = $s->stockPurchases->sum('total_amount');
                @endphp
                <tr>
                    <td style="font-family: monospace; font-weight: bold; color: #4338ca;">{{ $s->supplier_id }}</td>
                    <td><strong>{{ $s->company_name }}</strong></td>
                    <td>{{ $s->contact_person ?: '—' }}</td>
                    <td>{{ $s->phone ?: '—' }}</td>
                    <td>{{ $s->email ?: '—' }}</td>
                    <td class="text-center font-bold">{{ $s->stockPurchases->count() }}</td>
                    <td class="text-right font-bold">RM {{ number_format($spent, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right" style="text-transform: uppercase; font-size: 8px; color: #64748b;">Grand Total:</td>
                <td class="text-center font-bold">{{ number_format($stats['total_purchases']) }}</td>
                <td class="text-right font-bold" style="color: #4338ca;">RM {{ number_format($stats['total_spent'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Optional Action & Audit Trail Report --}}
    @if ($withActions && $activityLogs->isNotEmpty())
        <div style="page-break-before: always;"></div>
        <div class="header">
            <table>
                <tr>
                    <td>
                        <h1 style="color: #4f46e5;">SUPPLIER & INTAKE ACTIVITY AUDIT LOG</h1>
                        <p>Complete historical trace of vendor registrations, stock intakes, and vendor updates</p>
                    </td>
                    <td class="text-right">
                        <p><strong>Total Logged Events:</strong> {{ $activityLogs->count() }}</p>
                    </td>
                </tr>
            </table>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 14%;">Date & Time</th>
                    <th style="width: 9%;" class="text-center">Action</th>
                    <th style="width: 14%;">Entity Type</th>
                    <th style="width: 20%;">Vendor / Item Name</th>
                    <th style="width: 31%;">Action Details / Reason</th>
                    <th style="width: 12%;">Admin</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($activityLogs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d/m/Y h:i A') }}</td>
                        <td class="text-center">
                            @if ($log->action === 'created')
                                <span class="badge badge-green">Added</span>
                            @elseif ($log->action === 'updated')
                                <span class="badge badge-indigo">Edited</span>
                            @elseif ($log->action === 'deleted')
                                <span class="badge badge-red">Deleted</span>
                            @else
                                <span class="badge">{{ $log->action }}</span>
                            @endif
                        </td>
                        <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $log->entity_type) }}</td>
                        <td><strong>{{ $log->entity_name }}</strong></td>
                        <td>{{ $log->details ?: 'No notes' }}</td>
                        <td>{{ $log->admin_name ?: 'System' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endif

    {{-- Audit Sign-off Section (Standard for official school auditing) --}}
    <table class="signoff-table">
        <tr>
            <td class="signoff-box">
                <div class="signoff-title">Prepared By (Store Officer)</div>
                <div class="signoff-line">Signature & Date:</div>
            </td>
            <td style="width: 2%;"></td>
            <td class="signoff-box">
                <div class="signoff-title">Verified By (Internal Auditor)</div>
                <div class="signoff-line">Signature & Date:</div>
            </td>
            <td style="width: 2%;"></td>
            <td class="signoff-box">
                <div class="signoff-title">Approved By (Cooperative Chairman)</div>
                <div class="signoff-line">Signature & Date:</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        School Cooperative Management System &bull; Official Procurement & Spending Record &bull; Generated automatically
    </div>

</body>
</html>
