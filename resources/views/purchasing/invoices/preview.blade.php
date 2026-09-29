<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Purchase Invoice - {{ $invoice->invoice_no }}</title>

    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #111827;
            font-size: 12px;
        }

        .toolbar {
            max-width: 1000px;
            margin: 20px auto 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .toolbar-left {
            display: flex;
            gap: 8px;
        }

        .toolbar a,
        .toolbar button {
            border: 1px solid #d1d5db;
            background: #fff;
            color: #111827;
            padding: 9px 15px;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
        }

        .toolbar .primary {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #fff;
        }

        .invoice {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto 30px;
            background: #fff;
            border: 1px solid #111827;
            padding: 24px;
        }

        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #111827;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }

        .header-left,
        .header-center,
        .header-right {
            display: table-cell;
            vertical-align: middle;
        }

        .header-left {
            width: 30%;
        }

        .header-center {
            width: 40%;
            text-align: center;
        }

        .header-right {
            width: 30%;
            text-align: right;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .muted {
            color: #6b7280;
            font-size: 11px;
        }

        .bold {
            font-weight: bold;
        }

        .two-col {
            display: table;
            width: 100%;
            margin-bottom: 16px;
        }

        .col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .box {
            border: 1px solid #d1d5db;
            padding: 10px;
            margin-right: 8px;
            min-height: 80px;
        }

        .box-title {
            font-size: 11px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 16px;
        }

        table.items th,
        table.items td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            font-size: 11px;
        }

        table.items th {
            background: #f9fafb;
            font-weight: bold;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            width: 40%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .totals td {
            padding: 4px 8px;
            font-size: 11px;
        }

        .totals tr.grand-total td {
            border-top: 2px solid #111827;
            border-bottom: 2px solid #111827;
            font-size: 13px;
            font-weight: bold;
        }

        .footer {
            margin-top: 24px;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
            font-size: 10px;
            color: #6b7280;
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            width: 70%;
        }

        .footer-right {
            display: table-cell;
            width: 30%;
            text-align: right;
        }

        @media print {
            html,
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .invoice {
                max-width: none;
                margin: 0;
                border: none;
                padding: 0;
            }
        }
    </style>
</head>

<body>

@php
    $companyName = $company?->name ?? config('app.name');
    $supplier = $invoice->supplier;
@endphp

<div class="toolbar">
    <div class="toolbar-left">
        <a href="{{ route('purchasing.invoices.show', $invoice) }}">
            Back to Invoice
        </a>
    </div>

    <button class="primary" onclick="window.print()">
        Print Invoice
    </button>
</div>

<div class="invoice">

    <div class="header">
        <div class="header-left">
            @if(! empty($invoiceQrDataUri))
                <div style="margin-bottom: 8px;">
                    <img
                        src="{{ $invoiceQrDataUri }}"
                        alt="Scan to view invoice"
                        width="90"
                        height="90"
                        style="border:1px solid #ccc;"
                    >
                    <div style="font-size:8px; color:#6b7280; margin-top:2px;">
                        Scan to view invoice
                    </div>
                </div>
            @endif
            <div class="company-name">{{ $companyName }}</div>

            @if($company?->gstin)
                <div>GSTIN: {{ $company->gstin }}</div>
            @endif

            @if($company?->address)
                <div>{{ $company->address }}</div>
            @endif

            @if($company?->state)
                <div>
                    {{ $company->state }}
                    @if($company?->pincode)
                        - {{ $company->pincode }}
                    @endif
                </div>
            @endif
        </div>

        <div class="header-center">
            <div class="muted">PURCHASE</div>
            <div class="title">Invoice</div>
        </div>

        <div class="header-right">
            <div>
                <span class="bold">Invoice No:</span>
                {{ $invoice->invoice_no }}
            </div>

            @if($invoice->supplier_invoice_no)
                <div>
                    <span class="bold">Supplier Invoice:</span>
                    {{ $invoice->supplier_invoice_no }}
                </div>
            @endif

            <div>
                <span class="bold">Date:</span>
                {{ $invoice->invoice_date?->format('d/m/Y') }}
            </div>

            @if($invoice->credit_days !== null)
                <div>
                    <span class="bold">Credit Days:</span>
                    {{ $invoice->credit_days }} Days
                </div>
            @endif

            @if($invoice->due_date)
                <div>
                    <span class="bold">Due Date:</span>
                    {{ $invoice->due_date->format('d/m/Y') }}
                </div>
            @endif
        </div>
    </div>

    <div class="two-col">
        <div class="col">
            <div class="box">
                <div class="box-title">Supplier (Vendor)</div>
                <div class="bold">{{ $supplier?->name ?? '—' }}</div>
                @if($supplier?->address)
                    <div>{{ $supplier->address }}</div>
                @endif
                @if($supplier?->city || $supplier?->state)
                    <div>{{ implode(', ', array_filter([$supplier?->city, $supplier?->state, $supplier?->pincode])) }}</div>
                @endif
                @if($supplier?->gstin)
                    <div>GSTIN: {{ $supplier->gstin }}</div>
                @endif
                @if($supplier?->phone)
                    <div>Phone: {{ $supplier->phone }}</div>
                @endif
            </div>
        </div>

        <div class="col">
            <div class="box" style="margin-right: 0; margin-left: 8px;">
                <div class="box-title">Ship To / Warehouse</div>
                <div class="bold">{{ $invoice->warehouse?->name ?? 'Primary Warehouse' }}</div>
                @if($invoice->warehouse?->address)
                    <div>{{ $invoice->warehouse->address }}</div>
                @endif
                @if($invoice->purchaseOrder)
                    <div style="margin-top: 6px;">
                        <span class="bold">Ref PO:</span>
                        {{ $invoice->purchaseOrder->po_no }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">Product</th>
                <th style="width: 10%;">HSN</th>
                <th style="width: 8%;" class="text-right">Qty</th>
                <th style="width: 7%;">UOM</th>
                <th style="width: 10%;" class="text-right">Rate</th>
                <th style="width: 8%;" class="text-right">Tax %</th>
                <th style="width: 17%;" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <div class="bold">{{ $item->product?->name }}</div>
                        @if($item->batch_no)
                            <div class="muted">Batch: {{ $item->batch_no }} @if($item->expiry_date) | Exp: {{ \Carbon\Carbon::parse($item->expiry_date)->format('m/Y') }} @endif</div>
                        @endif
                        @if($item->serials->isNotEmpty())
                            <div class="muted" style="margin-top: 2px;">
                                <span class="bold">S/N:</span> {{ $item->serials->pluck('serial_number')->implode(', ') }}
                            </div>
                        @endif
                    </td>
                    <td>{{ $item->product?->hsn_code ?? '—' }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td>{{ $item->uom?->code ?? $item->uom?->name }}</td>
                    <td class="text-right">₹{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="text-right">{{ number_format($item->tax_percent, 2) }}%</td>
                    <td class="text-right">₹{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="bold">Subtotal:</td>
            <td class="text-right">₹{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="bold">Tax Amount:</td>
            <td class="text-right">₹{{ number_format($invoice->tax_amount, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Grand Total:</td>
            <td class="text-right">₹{{ number_format($invoice->grand_total, 2) }}</td>
        </tr>
    </table>

    @if($invoice->notes)
        <div style="margin-top: 10px;">
            <span class="bold">Notes:</span>
            {{ $invoice->notes }}
        </div>
    @endif

    @if($invoice->terms_and_conditions)
        <div style="margin-top: 10px; font-size: 10px; color: #4b5563;">
            <div class="bold" style="color: #111827;">Terms &amp; Conditions:</div>
            {!! nl2br(e($invoice->terms_and_conditions)) !!}
        </div>
    @endif

    <div class="footer">
        <div class="footer-left">
            This is a computer-generated Purchase Invoice.
        </div>
        <div class="footer-right">
            Generated on {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

</div>

</body>
</html>