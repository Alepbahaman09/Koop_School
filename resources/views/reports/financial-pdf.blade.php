<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial & Revenue Audit Report</title>
    <style>
        @page {
            margin: 16mm 14mm 16mm 14mm;
            size: a4 landscape;
        }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            color: #1e293b;
            line-height: 1.35;
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .header table {
            width: 100%;
        }
        .header h1 {
            font-size: 17px;
            color: #0f172a;
            margin: 0 0 3px 0;
            font-weight: bold;
        }
        .header p {
            margin: 0;
            color: #64748b;
            font-size: 8.5px;
        }
        .stats-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .stats-table td {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 7px 10px;
            width: 25%;
        }
        .stats-label {
            font-size: 7.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }
        .stats-value {
            font-size: 13.5px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .section-title {
            font-size: 10.5px;
            font-weight: bold;
            color: #0f172a;
            margin: 12px 0 5px 0;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 7.5px;
            text-transform: uppercase;
            padding: 5px 7px;
            border: 1px solid #cbd5e1;
            font-weight: bold;
            text-align: left;
        }
        .data-table td {
            padding: 4.5px 7px;
            border: 1px solid #e2e8f0;
            font-size: 8px;
        }
        .data-table tfoot td {
            background-color: #f8fafc;
            border-top: 2px solid #cbd5e1;
            font-weight: bold;
            font-size: 8.5px;
            padding: 5px 7px;
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
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
        }
        .badge-green {
            background-color: #dcfce7;
            color: #15803d;
        }
        .badge-rose {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .badge-indigo {
            background-color: #e0e7ff;
            color: #4338ca;
        }
        .badge-amber {
            background-color: #fef3c7;
            color: #b45309;
        }
        .signoff-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .signoff-box {
            width: 33.33%;
            padding: 8px 12px;
            border: 1px dashed #cbd5e1;
            background-color: #fafbfc;
            vertical-align: top;
        }
        .signoff-title {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 22px;
        }
        .signoff-line {
            border-top: 1px solid #0f172a;
            padding-top: 3px;
            font-size: 7.5px;
            font-weight: bold;
            color: #0f172a;
        }
        .footer {
            margin-top: 14px;
            padding-top: 5px;
            border-top: 1px solid #e2e8f0;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <table>
            <tr>
                <td>
                    <h1>KOPERASI SEKOLAH</h1>
                    <p><strong>COMPREHENSIVE FINANCIAL AUDIT & REVENUE REPORT</strong> &bull; Official Cooperative Audit</p>
                </td>
                <td class="text-right">
                    <p><strong>Reporting Period:</strong> {{ $start->format('d M Y') }} &mdash; {{ $end->format('d M Y') }}</p>
                    <p><strong>Generated On:</strong> {{ date('d M Y, h:i A') }}</p>
                    <p><strong>Audited By:</strong> {{ auth()->user()->name ?? 'System Administrator' }}</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- Executive Summary Stats Box (4 Cards) --}}
    <table class="stats-table">
        <tr>
            <td>
                <div class="stats-label">Total Revenue (Income)</div>
                <div class="stats-value" style="color: #059669;">RM {{ number_format($totalIncome, 2) }}</div>
                <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">{{ $totalSalesTransactions }} Transactions</div>
            </td>
            <td>
                <div class="stats-label">Total Procurement (Expenses)</div>
                <div class="stats-value" style="color: #e11d48;">RM {{ number_format($totalExpenses, 2) }}</div>
                <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">{{ $totalPurchasesCount }} Stock Purchase Orders</div>
            </td>
            <td>
                <div class="stats-label">Net Profit / Surplus</div>
                <div class="stats-value" style="color: {{ $netProfit >= 0 ? '#4338ca' : '#e11d48' }};">
                    RM {{ number_format($netProfit, 2) }}
                </div>
                <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">{{ $netProfit >= 0 ? 'Operating Surplus' : 'Operating Deficit' }}</div>
            </td>
            <td>
                <div class="stats-label">Net Profit Margin</div>
                <div class="stats-value" style="color: {{ $netProfitMargin >= 0 ? '#059669' : '#e11d48' }};">
                    {{ number_format($netProfitMargin, 1) }}%
                </div>
                <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">Net Return on Revenue</div>
            </td>
        </tr>
    </table>

    {{-- Revenue by Channel & Category Performance (Two-column layout) --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            {{-- Left column: Income Channels --}}
            <td style="width: 35%; vertical-align: top; padding-right: 10px;">
                <div class="section-title">Revenue by Sales Channel</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Channel Source</th>
                            <th class="text-right">Collected (RM)</th>
                            <th class="text-right">Share (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Online Checkout</strong></td>
                            <td class="text-right font-bold" style="color: #059669;">RM {{ number_format($currentIncomePayments, 2) }}</td>
                            <td class="text-right">{{ $totalIncome > 0 ? number_format(($currentIncomePayments / $totalIncome) * 100, 1) : 0 }}%</td>
                        </tr>
                        <tr>
                            <td><strong>POS Cashier Terminal</strong></td>
                            <td class="text-right font-bold" style="color: #4338ca;">RM {{ number_format($currentIncomeTerminal, 2) }}</td>
                            <td class="text-right">{{ $totalIncome > 0 ? number_format(($currentIncomeTerminal / $totalIncome) * 100, 1) : 0 }}%</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Grand Total:</td>
                            <td class="text-right font-bold">RM {{ number_format($totalIncome, 2) }}</td>
                            <td class="text-right">100.0%</td>
                        </tr>
                    </tfoot>
                </table>
            </td>

            {{-- Right column: Category Performance --}}
            <td style="width: 65%; vertical-align: top; padding-left: 10px;">
                <div class="section-title">Sales Performance by Category (Profit Audit)</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 24%;">Category</th>
                            <th style="width: 12%;" class="text-center">Units Sold</th>
                            <th style="width: 16%;" class="text-right">Revenue (RM)</th>
                            <th style="width: 18%;" class="text-right">Expenses (Sold Units)</th>
                            <th style="width: 16%;" class="text-right">Net Profit (RM)</th>
                            <th style="width: 14%;" class="text-center">Margin (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $catTotalRev = 0;
                            $catTotalExp = 0;
                            $catTotalProfit = 0;
                            $catTotalUnits = 0;
                        @endphp
                        @forelse ($categorySales as $cat)
                            @php
                                $catTotalRev += $cat->revenue;
                                $catTotalExp += $cat->expenses;
                                $catTotalProfit += $cat->profit;
                                $catTotalUnits += $cat->units;
                            @endphp
                            <tr>
                                <td><strong>{{ $cat->name }}</strong></td>
                                <td class="text-center font-bold">{{ number_format($cat->units) }}</td>
                                <td class="text-right font-bold">RM {{ number_format($cat->revenue, 2) }}</td>
                                <td class="text-right" style="color: #e11d48;">RM {{ number_format($cat->expenses, 2) }}</td>
                                <td class="text-right font-bold" style="color: {{ $cat->profit >= 0 ? '#059669' : '#e11d48' }};">
                                    RM {{ number_format($cat->profit, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $cat->margin >= 0 ? 'badge-green' : 'badge-rose' }}">
                                        {{ $cat->margin > 0 ? '+' : '' }}{{ number_format($cat->margin, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center" style="color: #94a3b8; padding: 10px;">No category sales recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($categorySales) > 0)
                        <tfoot>
                            <tr>
                                <td>Total:</td>
                                <td class="text-center font-bold">{{ number_format($catTotalUnits) }}</td>
                                <td class="text-right font-bold">RM {{ number_format($catTotalRev, 2) }}</td>
                                <td class="text-right font-bold" style="color: #e11d48;">RM {{ number_format($catTotalExp, 2) }}</td>
                                <td class="text-right font-bold" style="color: {{ $catTotalProfit >= 0 ? '#059669' : '#e11d48' }};">RM {{ number_format($catTotalProfit, 2) }}</td>
                                <td class="text-center font-bold">{{ $catTotalRev > 0 ? number_format(($catTotalProfit / $catTotalRev) * 100, 1) : 0 }}%</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Top Selling Products --}}
    <div class="section-title">Top Selling Products (Performance & Profit Audit)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">Rank</th>
                <th style="width: 23%;">Product Description & SKU</th>
                <th style="width: 12%;">Category</th>
                <th style="width: 8%;" class="text-center">Units Sold</th>
                <th style="width: 7%;" class="text-center">Current Stock</th>
                <th style="width: 9%;" class="text-right">Avg Price (RM)</th>
                <th style="width: 13%;" class="text-right">Expenses (Sold Units)</th>
                <th style="width: 12%;" class="text-right">Revenue (RM)</th>
                <th style="width: 12%;" class="text-right">Net Profit (RM)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $topTotalUnits = 0;
                $topTotalExp = 0;
                $topTotalRev = 0;
                $topTotalProfit = 0;
            @endphp
            @forelse ($topProducts as $idx => $prod)
                @php
                    $topTotalUnits += $prod->units;
                    $topTotalExp += $prod->expenses;
                    $topTotalRev += $prod->revenue;
                    $topTotalProfit += $prod->profit;
                @endphp
                <tr>
                    <td class="text-center font-bold" style="color: #4338ca;">#{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $prod->name }}</strong>
                        @if ($prod->sku)
                            <span style="color: #64748b; font-family: monospace; font-size: 7px;">({{ $prod->sku }})</span>
                        @endif
                    </td>
                    <td>{{ $prod->category }}</td>
                    <td class="text-center font-bold" style="color: #059669;">{{ number_format($prod->units) }}</td>
                    <td class="text-center">{{ number_format($prod->stock_quantity) }}</td>
                    <td class="text-right">{{ number_format($prod->avg_price, 2) }}</td>
                    <td class="text-right" style="color: #e11d48;">RM {{ number_format($prod->expenses, 2) }}</td>
                    <td class="text-right font-bold" style="color: #0f172a;">RM {{ number_format($prod->revenue, 2) }}</td>
                    <td class="text-right font-bold" style="color: {{ $prod->profit >= 0 ? '#059669' : '#e11d48' }};">
                        RM {{ number_format($prod->profit, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="color: #94a3b8; padding: 12px;">No product sales recorded in this period.</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($topProducts) > 0)
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right" style="text-transform: uppercase; font-size: 7.5px; color: #64748b;">Top Items Total:</td>
                    <td class="text-center font-bold" style="color: #059669;">{{ number_format($topTotalUnits) }}</td>
                    <td colspan="2"></td>
                    <td class="text-right font-bold" style="color: #e11d48;">RM {{ number_format($topTotalExp, 2) }}</td>
                    <td class="text-right font-bold">RM {{ number_format($topTotalRev, 2) }}</td>
                    <td class="text-right font-bold" style="color: {{ $topTotalProfit >= 0 ? '#059669' : '#e11d48' }};">RM {{ number_format($topTotalProfit, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- Item Purchase Product Analysis --}}
    <div class="section-title">Item Purchase Product Analysis (Procurement & Profit Margins)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Product / Item & SKU</th>
                <th style="width: 13%;">Category</th>
                <th style="width: 15%;">Supplier Sourced</th>
                <th style="width: 9%;" class="text-center">Intake Units</th>
                <th style="width: 9%;" class="text-right">Avg Cost (RM)</th>
                <th style="width: 9%;" class="text-right">Selling Price (RM)</th>
                <th style="width: 9%;" class="text-right">Margin Spread</th>
                <th style="width: 11%;" class="text-right">Total Spend (RM)</th>
            </tr>
        </thead>
        <tbody>
            @php $totalProcurementSpend = 0; $totalProcurementUnits = 0; @endphp
            @forelse ($purchaseProductAnalysis as $pa)
                @php
                    $totalProcurementSpend += $pa->total_spent;
                    $totalProcurementUnits += $pa->units_purchased;
                @endphp
                <tr>
                    <td>
                        <strong>{{ $pa->name }}</strong>
                        @if ($pa->sku)
                            <div style="color: #64748b; font-family: monospace; font-size: 7px;">SKU: {{ $pa->sku }}</div>
                        @endif
                    </td>
                    <td>{{ $pa->category }}</td>
                    <td>{{ $pa->suppliers }}</td>
                    <td class="text-center font-bold" style="color: #4338ca;">{{ number_format($pa->units_purchased) }}</td>
                    <td class="text-right">{{ number_format($pa->avg_cost, 2) }}</td>
                    <td class="text-right">{{ number_format($pa->selling_price, 2) }}</td>
                    <td class="text-right font-bold" style="color: {{ $pa->margin >= 0 ? '#059669' : '#e11d48' }};">
                        RM {{ number_format($pa->margin, 2) }}
                        <div style="font-size: 7px; color: #64748b;">({{ number_format($pa->margin_percent, 1) }}%)</div>
                    </td>
                    <td class="text-right font-bold" style="color: #e11d48;">RM {{ number_format($pa->total_spent, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="color: #94a3b8; padding: 12px;">No stock intake purchases recorded in this period.</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($purchaseProductAnalysis) > 0)
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right" style="text-transform: uppercase; font-size: 7.5px; color: #64748b;">Total Procurement:</td>
                    <td class="text-center font-bold" style="color: #4338ca;">{{ number_format($totalProcurementUnits) }} pcs</td>
                    <td colspan="3"></td>
                    <td class="text-right font-bold" style="color: #e11d48;">RM {{ number_format($totalProcurementSpend, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- Optional Detailed Transaction & Cash Audit Ledger --}}
    @if ($withAudit && count($auditTransactions) > 0)
        <div style="page-break-before: always;"></div>
        <div class="header">
            <table>
                <tr>
                    <td>
                        <h1 style="color: #4f46e5;">FINANCIAL TRANSACTION & CASH AUDIT LEDGER</h1>
                        <p>Detailed chronological register of cash flows, online app checkouts, cashier payments, and procurement expenses</p>
                    </td>
                    <td class="text-right">
                        <p><strong>Total Recorded Entries:</strong> {{ count($auditTransactions) }} transactions</p>
                    </td>
                </tr>
            </table>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 14%;">Date & Time</th>
                    <th style="width: 18%;">Order ID</th>
                    <th style="width: 32%;">Channel / Source / Supplier</th>
                    <th style="width: 10%;" class="text-center">Cash Flow Type</th>
                    <th style="width: 14%;" class="text-right">Amount (RM)</th>
                    <th style="width: 12%;" class="text-center">Audit Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($auditTransactions as $t)
                    <tr>
                        <td>{{ $t->date_str }}</td>
                        <td style="font-family: monospace; font-weight: bold; color: #4338ca;">{{ $t->order_id }}</td>
                        <td>{{ $t->source }}</td>
                        <td class="text-center">
                            @if ($t->type === 'Income')
                                <span class="badge badge-green">+ Income</span>
                            @else
                                <span class="badge badge-rose">- Expense</span>
                            @endif
                        </td>
                        <td class="text-right font-bold" style="color: {{ $t->type === 'Income' ? '#059669' : '#e11d48' }};">
                            {{ $t->type === 'Income' ? '+' : '-' }}RM {{ number_format($t->amount, 2) }}
                        </td>
                        <td class="text-center">
                            <span class="badge badge-indigo">{{ $t->status }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Official Audit Sign-Off Section --}}
    <table class="signoff-table">
        <tr>
            <td class="signoff-box">
                <div class="signoff-title">Prepared By (Finance Officer / Treasurer)</div>
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
        School Cooperative Management System &bull; Official Financial & Revenue Audit Statement &bull; Generated automatically
    </div>

</body>
</html>
