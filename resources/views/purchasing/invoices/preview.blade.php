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
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .muted {
            color: #6b7280;
        }

        .info-table,
        .items-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table {
            margin-bottom: 14px;
        }

        .info-table td,
        .items-table th,
        .items-table td,
        .summary-table td {
            border: 1px solid #111827;
            padding: 7px 8px;
            vertical-align: top;
        }

        .section-title {
            background: #f3f4f6;
            font-weight: bold;
        }

        .items-table {
            margin-top: 12px;
        }

        .items-table th {
            background: #f3f4f6;
            font-size: 11px;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .bottom-grid {
            display: table;
            width: 100%;
            margin-top: 14px;
        }

        .bottom-left,
        .bottom-right {
            display: table-cell;
            vertical-align: top;
        }

        .bottom-left {
            width: 58%;
            padding-right: 12px;
        }

        .bottom-right {
            width: 42%;
        }

        .terms {
            margin-top: 14px;
            border: 1px solid #d1d5db;
            padding: 9px;
        }

        .terms-title {
            font-weight: bold;
            margin-bottom: 5px;
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

            @if($invoice->due_date)
                <div>
                    <span class="bold">Due Date:</span>
                    {{ $invoice->due_date->format('d/m/Y') }}
                </div>
            @endif
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <div class="section-title">Supplier Details</div>

                <div><strong>Name:</strong> {{ $supplier?->name ?? '—' }}</div>

                <div>
                    <strong>Address:</strong>
                    {{ $supplier?->address ?? '—' }}
                </div>

                <div>
                    <strong>State:</strong>
                    {{ $supplier?->state ?? '—' }}
                </div>

                <div>
                    <strong>Pincode:</strong>
                    {{ $supplier?->pincode ?? '—' }}
                </div>

                <div>
                    <strong>GSTIN:</strong>
                    {{ $supplier?->gstin ?? '—' }}
                </div>

                @if($supplier?->phone)
                    <div>
                        <strong>Phone:</strong>
                        {{ $supplier->phone }}
                    </div>
                @endif

                @if($supplier?->email)
                    <div>
                        <strong>Email:</strong>
                        {{ $supplier->email }}
                    </div>
                @endif
            </td>

            <td width="50%">
                <div class="section-title">Purchase Details</div>

                <div>
                    <strong>Warehouse:</strong>
                    {{ $invoice->warehouse?->name ?? '—' }}
                </div>

                <div>
                    <strong>Purchase Order:</strong>
                    {{ $invoice->purchaseOrder?->po_no ?? '—' }}
                </div>

                <div>
                    <strong>Status:</strong>
                    {{ ucfirst($invoice->status ?? 'issued') }}
                </div>

                @if($invoice->freight_allocation_method)
                    <div>
                        <strong>Freight:</strong>
                        {{ ucfirst($invoice->freight_allocation_method) }}
                    </div>
                @endif

                @if($invoice->rate_override_reason)
                    <div>
                        <strong>Rate Override:</strong>
                        {{ $invoice->rate_override_reason }}
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%" class="center">S.No</th>
                <th width="30%">Product</th>
                <th width="10%" class="center">HSN</th>
                <th width="10%" class="right">Qty</th>
                <th width="13%" class="right">Unit Cost</th>
                <th width="12%" class="right">Tax</th>
                <th width="20%" class="right">Line Total</th>
            </tr>
        </thead>

        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="center">
                        {{ $index + 1 }}
                    </td>

                    <td>
                        {{ $item->product?->name ?? '—' }}

                        @if($item->batch_no)
                            <div class="muted">
                                Batch: {{ $item->batch_no }}
                            </div>
                        @endif

                        @if($item->expiry_date)
                            <div class="muted">
                                Expiry: {{ $item->expiry_date->format('d/m/Y') }}
                            </div>
                        @endif
                    </td>

                    <td class="center">
                        {{ $item->product?->hsn_code ?? '—' }}
                    </td>

                    <td class="right">
                        {{ number_format($item->quantity, 2) }}
                        {{ $item->uom?->code ?? '' }}
                    </td>

                    <td class="right">
                        ₹{{ number_format($item->unit_cost, 2) }}
                    </td>

                    <td class="right">
                        {{ number_format($item->tax_percent ?? 0, 2) }}%
                    </td>

                    <td class="right bold">
                        ₹{{ number_format($item->line_total, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="bottom-grid">

        <div class="bottom-left">

            @if($company?->bank_name || $company?->bank_account_no || $company?->bank_ifsc)
                <table class="info-table">
                    <tr>
                        <td>
                            <div class="section-title">Bank Details</div>

                            @if($company?->bank_name)
                                <div>
                                    <strong>Bank:</strong>
                                    {{ $company->bank_name }}
                                </div>
                            @endif

                            @if($company?->bank_account_no)
                                <div>
                                    <strong>A/c No:</strong>
                                    {{ $company->bank_account_no }}
                                </div>
                            @endif

                            @if($company?->bank_ifsc)
                                <div>
                                    <strong>IFSC:</strong>
                                    {{ $company->bank_ifsc }}
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            @endif

            @if($invoice->notes)
                <div class="terms">
                    <div class="terms-title">Notes</div>
                    <div style="white-space: pre-line;">
                        {{ $invoice->notes }}
                    </div>
                </div>
            @endif

            @if($invoice->terms_and_conditions)
                <div class="terms">
                    <div class="terms-title">Terms &amp; Conditions</div>
                    <div style="white-space: pre-line;">
                        {{ $invoice->terms_and_conditions }}
                    </div>
                </div>
            @endif

        </div>

        <div class="bottom-right">
            <table class="summary-table">
                <tr>
                    <td class="bold">Subtotal</td>
                    <td class="right">
                        ₹{{ number_format($invoice->subtotal, 2) }}
                    </td>
                </tr>

                <tr>
                    <td class="bold">Tax</td>
                    <td class="right">
                        ₹{{ number_format($invoice->tax_amount, 2) }}
                    </td>
                </tr>

                <tr>
                    <td class="bold">Grand Total</td>
                    <td class="right bold">
                        ₹{{ number_format($invoice->grand_total, 2) }}
                    </td>
                </tr>
            </table>
        </div>

    </div>

</div>

</body>
</html>