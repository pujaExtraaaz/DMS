<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $order->po_no }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            font-size: 11.5px;
            line-height: 1.4;
        }

        .toolbar {
            max-width: 960px;
            margin: 16px auto;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .toolbar-left, .toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toolbar a, .toolbar button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
            transition: all 0.15s ease;
        }

        .toolbar a:hover, .toolbar button:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .toolbar .btn-primary {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #ffffff;
        }

        .toolbar .btn-primary:hover {
            background: #4338ca;
        }

        .toolbar .btn-success {
            background: #059669;
            border-color: #059669;
            color: #ffffff;
        }

        .toolbar .btn-success:hover {
            background: #047857;
        }

        .doc-wrapper {
            max-width: 960px;
            margin: 0 auto 32px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        /* Document Header */
        .doc-header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 14px;
        }

        .header-cell {
            display: table-cell;
            vertical-align: top;
        }

        .header-left {
            width: 58%;
        }

        .header-right {
            width: 42%;
            text-align: right;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .doc-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #4f46e5;
            text-transform: uppercase;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .meta-table td {
            padding: 2px 0;
            font-size: 11px;
        }

        /* Parties & Addresses */
        .parties-grid {
            display: table;
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 14px;
        }

        .party-col {
            display: table-cell;
            width: 50%;
            padding: 10px 12px;
            vertical-align: top;
        }

        .party-col:first-child {
            border-right: 1px solid #cbd5e1;
        }

        .box-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .party-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 3px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .items-table th, .items-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 10.5px;
        }

        .items-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 600;
            text-align: left;
            text-transform: uppercase;
            font-size: 9.5px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .font-bold { font-weight: 700; }

        /* Summary Layout */
        .bottom-layout {
            display: table;
            width: 100%;
            margin-top: 10px;
        }

        .bottom-left {
            display: table-cell;
            width: 56%;
            vertical-align: top;
            padding-right: 14px;
        }

        .bottom-right {
            display: table-cell;
            width: 44%;
            vertical-align: top;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }

        .summary-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 11px;
        }

        .summary-table .grand-total-row {
            background: #f8fafc;
            font-weight: 700;
            font-size: 13px;
            color: #0f172a;
            border-top: 2px solid #0f172a;
        }

        .info-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 8px;
            background: #f8fafc;
            font-size: 10.5px;
        }

        .sign-area {
            display: table;
            width: 100%;
            margin-top: 36px;
            page-break-inside: avoid;
        }

        .sign-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding-top: 45px;
        }

        .sign-line {
            display: inline-block;
            width: 80%;
            border-top: 1px dashed #64748b;
            padding-top: 4px;
            font-size: 10px;
            color: #475569;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 10pt !important;
            }
            .toolbar, .no-print {
                display: none !important;
            }
            .doc-wrapper {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
            .items-table th {
                background: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            thead {
                display: table-header-group;
            }
            tr {
                page-break-inside: avoid;
            }
            .avoid-break {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

@php
    $companyName = $company?->name ?? config('app.name');
    $supplier = $order->supplier;
    $amountWords = \App\Support\DocumentExporter::numberToIndianWords($order->grand_total);
    $billingAddr = $order->billing_address ?: ($supplier?->address ?: '—');
    $deliveryAddr = $order->shipping_address ?: ($order->billing_address ?: ($supplier?->address ?: '—'));
@endphp

@if(empty($isPdf))
<div class="toolbar no-print">
    <div class="toolbar-left">
        <a href="{{ route('purchasing.orders.show', $order) }}">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to PO
        </a>
        <a href="{{ route('purchasing.orders.index') }}">
            PO Listing
        </a>
    </div>

    <div class="toolbar-right">
        <button class="btn-primary" onclick="window.print()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        <a href="{{ route('purchasing.orders.export', ['order' => $order, 'format' => 'pdf']) }}" class="btn-primary">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            PDF
        </a>
        <a href="{{ route('purchasing.orders.export', ['order' => $order, 'format' => 'xlsx']) }}" class="btn-success">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Excel (.xlsx)
        </a>
        <a href="{{ route('purchasing.orders.export', ['order' => $order, 'format' => 'csv']) }}">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            CSV
        </a>
    </div>
</div>
@endif

<div class="doc-wrapper">
    <!-- Header -->
    <div class="doc-header">
        <div class="header-cell header-left">
            @if($company?->logo_url)
                <div style="margin-bottom: 6px;">
                    <img src="{{ $company->logo_url }}" alt="{{ $companyName }}" style="max-height: 48px; max-width: 180px; object-fit: contain;">
                </div>
            @endif
            <div class="company-name">{{ $companyName }}</div>
            @if($company?->legal_name && $company->legal_name !== $companyName)
                <div style="color: #475569; font-size: 10.5px;">{{ $company->legal_name }}</div>
            @endif
            @if($company?->gstin)
                <div><strong>GSTIN:</strong> {{ $company->gstin }}</div>
            @endif
            @if($company?->pan)
                <div><strong>PAN:</strong> {{ $company->pan }}</div>
            @endif
            @if($company?->address)
                <div>{{ $company->address }}</div>
            @endif
            @if($company?->state)
                <div>{{ $company->state }} @if($company?->pincode)- {{ $company->pincode }}@endif</div>
            @endif
            @if($company?->phone || $company?->email)
                <div style="color: #475569;">
                    @if($company?->phone) Tel: {{ $company->phone }} @endif
                    @if($company?->email) | Email: {{ $company->email }} @endif
                </div>
            @endif
        </div>

        <div class="header-cell header-right">
            <div class="doc-title">PURCHASE ORDER</div>
            <table class="meta-table">
                <tr>
                    <td class="font-bold text-right" style="width: 50%;">PO Number:</td>
                    <td class="text-right font-mono" style="width: 50%; font-weight: 700;">{{ $order->po_no }}</td>
                </tr>
                <tr>
                    <td class="font-bold text-right">PO Date:</td>
                    <td class="text-right">{{ $order->po_date?->format('d/m/Y') }}</td>
                </tr>
                @if($order->expected_date)
                <tr>
                    <td class="font-bold text-right">Expected Date:</td>
                    <td class="text-right">{{ $order->expected_date?->format('d/m/Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td class="font-bold text-right">Status:</td>
                    <td class="text-right font-bold" style="text-transform: uppercase; color: #4f46e5;">
                        {{ str_replace('_', ' ', $order->status) }}
                    </td>
                </tr>
                @if($order->warehouse)
                <tr>
                    <td class="font-bold text-right">Warehouse:</td>
                    <td class="text-right">{{ $order->warehouse->name }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Parties Grid -->
    <div class="parties-grid">
        <div class="party-col">
            <div class="box-title">Supplier / Vendor (Order Placed To)</div>
            <div class="party-name">{{ $supplier?->name }}</div>
            @if($supplier?->code)
                <div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">Code: {{ $supplier->code }}</div>
            @endif
            @if($supplier?->gstin)
                <div><strong>GSTIN:</strong> {{ $supplier->gstin }}</div>
            @endif
            @if($supplier?->pan)
                <div><strong>PAN:</strong> {{ $supplier->pan }}</div>
            @endif
            @if($supplier?->phone)
                <div>Phone: {{ $supplier->phone }}</div>
            @endif
            @if($supplier?->email)
                <div>Email: {{ $supplier->email }}</div>
            @endif
            <div style="margin-top: 6px;">
                <div class="box-title" style="margin-bottom: 2px;">Billing Address:</div>
                <div style="white-space: pre-line;">{{ $billingAddr }}</div>
            </div>
        </div>

        <div class="party-col">
            <div class="box-title">Delivery / Shipping Destination</div>
            <div class="party-name">{{ $companyName }}</div>
            @if($order->warehouse)
                <div style="font-size: 11px; font-weight: 600; color: #334155;">{{ $order->warehouse->name }}</div>
            @endif
            <div style="margin-top: 4px;">
                <div class="box-title" style="margin-bottom: 2px;">Dispatch / Delivery Address:</div>
                <div style="white-space: pre-line;">{{ $deliveryAddr }}</div>
            </div>
            @if($order->creator)
                <div style="margin-top: 8px; font-size: 10px; color: #64748b;">
                    Prepared by: {{ $order->creator->name }}
                </div>
            @endif
        </div>
    </div>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 32px;" class="text-center">#</th>
                <th>Item Description</th>
                <th style="width: 120px;">Batch Name</th>
                <th style="width: 55px;" class="text-center">UOM</th>
                <th style="width: 65px;" class="text-right">Qty</th>
                <th style="width: 80px;" class="text-right">Unit Rate (₹)</th>
                <th style="width: 55px;" class="text-right">Tax %</th>
                <th style="width: 55px;" class="text-right">CGST %</th>
                <th style="width: 55px;" class="text-right">SGST %</th>
                <th style="width: 95px;" class="text-right">Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <div class="font-bold">{{ $item->product?->name }}</div>
                        @if($item->product?->code)
                            <div style="font-size: 9.5px; color: #64748b;">Code: {{ $item->product->code }}</div>
                        @endif
                    </td>
                    <td class="font-mono">{{ $item->batch_no ?? $item->batch_name ?? '—' }}</td>
                    <td class="text-center">{{ $item->uom?->code ?? 'PCS' }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="text-right">{{ number_format($item->tax_percent, 1) }}%</td>
                    <td class="text-right">{{ number_format($item->cgst_percent, 1) }}%</td>
                    <td class="text-right">{{ number_format($item->sgst_percent, 1) }}%</td>
                    <td class="text-right font-bold">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 16px;">No line items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Bottom Layout (Summary, Notes, Words) -->
    <div class="bottom-layout avoid-break">
        <div class="bottom-left">
            <div class="info-card">
                <div class="box-title">Amount in Words</div>
                <div class="font-bold" style="color: #1e293b; font-size: 11px;">{{ $amountWords }}</div>
            </div>

            @if($order->notes)
                <div class="info-card">
                    <div class="box-title">Notes / Instructions</div>
                    <div style="white-space: pre-line;">{{ $order->notes }}</div>
                </div>
            @endif

            @if($company?->purchase_terms_and_conditions)
                <div class="info-card">
                    <div class="box-title">Terms & Conditions</div>
                    <div style="white-space: pre-line; font-size: 10px; color: #475569;">{{ $company->purchase_terms_and_conditions }}</div>
                </div>
            @endif

            @if($company?->bank_name || $company?->upi_id)
                <div class="info-card">
                    <div class="box-title">Company Bank & Payment Details</div>
                    @if($company?->bank_name)
                        <div><strong>Bank:</strong> {{ $company->bank_name }} | <strong>A/C:</strong> {{ $company->bank_account_no }} | <strong>IFSC:</strong> {{ $company->bank_ifsc }}</div>
                    @endif
                    @if($company?->upi_id)
                        <div><strong>UPI ID:</strong> {{ $company->upi_id }}</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="bottom-right">
            <table class="summary-table">
                <tr>
                    <td style="width: 55%; color: #475569;">Subtotal (Taxable Amount):</td>
                    <td class="text-right font-mono" style="width: 45%;">₹{{ number_format($order->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td style="color: #475569;">Total Tax (GST):</td>
                    <td class="text-right font-mono">₹{{ number_format($order->tax_amount, 2) }}</td>
                </tr>
                <tr class="grand-total-row">
                    <td>Grand Total:</td>
                    <td class="text-right font-mono">₹{{ number_format($order->grand_total, 2) }}</td>
                </tr>
            </table>

            <div class="sign-area">
                <div class="sign-box">
                    <span class="sign-line">Supplier Acknowledgement</span>
                </div>
                <div class="sign-box">
                    <span class="sign-line">For {{ $companyName }}<br>Authorized Signatory</span>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

