<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $order->po_no }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
            color: #0f172a;
            font-size: 9.5px;
            line-height: 1.35;
        }

        /* Top Action Toolbar */
        .toolbar {
            max-width: 900px;
            margin: 14px auto 10px;
            padding: 8px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
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
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
            transition: all 0.15s ease;
        }

        .toolbar a:hover, .toolbar button:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #94a3b8;
        }

        .toolbar .btn-primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
        }

        .toolbar .btn-primary:hover {
            background: #1d4ed8;
        }

        .toolbar .btn-success {
            background: #059669;
            border-color: #059669;
            color: #ffffff;
        }

        .toolbar .btn-success:hover {
            background: #047857;
        }

        /* Document Container */
        .doc-wrapper {
            max-width: 900px;
            margin: 0 auto 30px;
            background: #ffffff;
            border: 1.5px solid #1e293b;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08);
            page-break-after: avoid;
        }

        /* Bordered Table Grid */
        table.grid-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }

        table.grid-table td, table.grid-table th {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 9px;
            border: 1px solid #334155;
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        /* Header Banner */
        .doc-banner {
            background: #f8fafc;
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            padding: 6px 8px;
            border-bottom: 1.5px solid #1e293b;
            color: #0f172a;
        }

        .section-header {
            background: #f1f5f9;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            color: #1e293b;
            padding: 4px 6px;
            border-bottom: 1px solid #334155;
        }

        /* Header elements */
        .company-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-align: center;
            font-size: 8.5px;
            text-transform: uppercase;
            padding: 5px 3px;
            border: 1px solid #334155;
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .items-table td {
            border: 1px solid #334155;
            padding: 4px 5px;
            font-size: 8.5px;
            vertical-align: top;
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .items-table tr:nth-child(even) td {
            background: #fafbfd;
        }

        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }

        .item-name {
            font-weight: 700;
            color: #0f172a;
            font-size: 9px;
        }

        .item-sub {
            font-size: 8px;
            color: #475569;
            margin-top: 1px;
        }

        /* Totals and Summary */
        .summary-row td {
            padding: 3.5px 6px;
            font-size: 8.5px;
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .grand-total-box {
            background: #f1f5f9;
            font-weight: 800;
            font-size: 10.5px;
            color: #0f172a;
            border-top: 1.5px solid #1e293b !important;
        }

        .words-title {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 2px;
        }

        .words-val {
            font-weight: 700;
            color: #0f172a;
            font-size: 9px;
            line-height: 1.4;
        }

        /* Terms & Signatures */
        .terms-ol {
            margin: 0;
            padding-left: 16px;
            font-size: 8.5px;
            color: #1e293b;
            line-height: 1.45;
        }

        .terms-ol li {
            margin-bottom: 2px;
        }

        .system-note {
            text-align: center;
            font-size: 8px;
            color: #64748b;
            padding: 3px;
            background: #f8fafc;
            border-top: 1px solid #334155;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 9pt !important;
            }
            .toolbar, .no-print {
                display: none !important;
            }
            .doc-wrapper {
                max-width: 100% !important;
                margin: 0 !important;
                border: 1px solid #000000 !important;
                box-shadow: none !important;
            }
            table.grid-table td, table.grid-table th, .items-table td, .items-table th {
                border-color: #000000 !important;
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
    // Resolve Company & Defaults
    $company = $company ?? $order->company ?? \App\Domains\Organization\Models\Company::resolveCurrent() ?? \App\Domains\Organization\Models\Company::query()->find(auth()->user()?->company_id) ?? \App\Domains\Organization\Models\Company::query()->first();

    if (! $company || empty($company->gstin)) {
        $avitCompany = \App\Domains\Organization\Models\Company::where(function ($q) {
            $q->where('name', 'like', '%Avit%')->orWhere('code', 'AD');
        })->whereNotNull('gstin')->first();

        if ($avitCompany) {
            $company = $avitCompany;
        }
    }

    $consignee = $consignee ?? $order->resolveConsigneeDetails($company);
    $supplierDetails = $supplierDetails ?? $order->resolveSupplierDetails();

    $companyName = $consignee['company_name'];
    $companyLegalName = $consignee['company_legal_name'];
    $companyAddress = $consignee['company_address'];
    $companyPhone = $consignee['phone'] !== '—' ? $consignee['phone'] : ($company?->phone ?: '9876120738');
    $companyEmail = $consignee['email'] !== '—' ? $consignee['email'] : ($company?->email ?: 'info@avitdigital.in');
    $companyGstin = $consignee['gstin'] !== '—' ? $consignee['gstin'] : ($company?->gstin ?: '03ABGPG1954N1ZN');
    $companyPan = $consignee['pan'] !== '—' ? $consignee['pan'] : ($company?->pan ?: 'ABGPG1954N');
    $companyState = $consignee['state'] !== '—' ? $consignee['state'] : ($company?->state ?: 'Punjab');
    $companyStateCode = $consignee['state_code'] ?: ($companyGstin && strlen(trim($companyGstin)) >= 2 ? substr(trim($companyGstin), 0, 2) : '03');

    $supplier = $order->supplier;

    // Logo resolution (Base64 for DomPDF & Web reliability)
    $logoDataUri = null;
    $companyForLogo = $order->company ?? \App\Domains\Organization\Models\Company::find(1) ?? $company;
    if ($companyForLogo && $companyForLogo->hasLogo()) {
        $cleanPath = $companyForLogo->getCleanLogoPath();
        $fullPath = storage_path('app/public/' . $cleanPath);
        if (file_exists($fullPath)) {
            $mime = @mime_content_type($fullPath) ?: 'image/jpeg';
            $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
        }
    }
    if (!$logoDataUri && $companyForLogo?->logo_url) {
        $logoDataUri = $companyForLogo->logo_url;
    }

    $supplierStateCode = $supplierDetails['state_code'];
    $isInterstate = ($companyStateCode && $supplierStateCode && $companyStateCode !== $supplierStateCode);

    $destWarehouse = $consignee['warehouse'];
    $destBranch = $consignee['branch'];
    $deliveryAddress = $consignee['delivery_address'];
    $consigneeGstin = $consignee['gstin'];
    $consigneePan = $consignee['pan'];

    // Tax Breakdowns
    $cgstTotal = 0.0;
    $sgstTotal = 0.0;
    $igstTotal = 0.0;

    foreach ($order->items as $item) {
        $lineTaxable = (float) $item->quantity * (float) $item->unit_cost;
        $taxPercent = (float) $item->tax_percent;
        $itemCgstPercent = (float) ($item->cgst_percent ?? 0);
        $itemSgstPercent = (float) ($item->sgst_percent ?? 0);

        if ($itemCgstPercent > 0 || $itemSgstPercent > 0) {
            $cgstTotal += (float) ($item->cgst_amount > 0 ? $item->cgst_amount : ($lineTaxable * $itemCgstPercent / 100));
            $sgstTotal += (float) ($item->sgst_amount > 0 ? $item->sgst_amount : ($lineTaxable * $itemSgstPercent / 100));
        } elseif ($taxPercent > 0) {
            if ($isInterstate) {
                $igstTotal += ($lineTaxable * $taxPercent / 100);
            } else {
                $cgstTotal += ($lineTaxable * ($taxPercent / 2) / 100);
                $sgstTotal += ($lineTaxable * ($taxPercent / 2) / 100);
            }
        }
    }

    $computedTax = $cgstTotal + $sgstTotal + $igstTotal;
    if ($computedTax == 0 && (float) $order->tax_amount > 0) {
        if ($isInterstate) {
            $igstTotal = (float) $order->tax_amount;
        } else {
            $cgstTotal = (float) $order->tax_amount / 2;
            $sgstTotal = (float) $order->tax_amount / 2;
        }
    }

    $amountWords = \App\Support\DocumentExporter::numberToIndianWords($order->grand_total);
    $referenceNo = $order->reference_no ?? $order->ref_no ?? $order->reference_number ?? '—';
@endphp

@if(empty($isPdf))
<div class="toolbar no-print">
    <div class="toolbar-left">
        <a href="{{ route('purchasing.orders.show', $order) }}">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to PO
        </a>
        <a href="{{ route('purchasing.orders.index') }}">
            PO Listing
        </a>
    </div>

    <div class="toolbar-right">
        <button class="btn-primary" onclick="window.print()">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        <a href="{{ route('purchasing.orders.export', ['order' => $order, 'format' => 'pdf']) }}" class="btn-primary">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            PDF Download
        </a>
        <a href="{{ route('purchasing.orders.export', ['order' => $order, 'format' => 'xlsx']) }}" class="btn-success">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Excel (.xlsx)
        </a>
        <a href="{{ route('purchasing.orders.export', ['order' => $order, 'format' => 'csv']) }}">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            CSV
        </a>
    </div>
</div>
@endif

<div class="doc-wrapper">
    <!-- Document Title Banner -->
    <div class="doc-banner">
        PURCHASE ORDER
    </div>

    <!-- Section 1: Header (Company Details & Document Metadata) -->
    <table class="grid-table" style="border: none;">
        <tr>
            <!-- Company Profile (Left) -->
            <td style="width: 58%; border-top: none; border-left: none; border-right: 1px solid #334155; padding: 6px 8px;">
                @if($logoDataUri)
                    <div style="margin-bottom: 4px;">
                        <img src="{{ $logoDataUri }}" alt="{{ $companyName }}" style="max-height: 40px; max-width: 170px; object-fit: contain;">
                    </div>
                @endif
                <div class="company-title">{{ $companyName }}</div>
                @if($companyLegalName && $companyLegalName !== $companyName)
                    <div style="font-size: 8.5px; color: #475569; margin-bottom: 2px;">({{ $companyLegalName }})</div>
                @endif
                <div style="font-size: 8.5px; color: #334155; margin-bottom: 2px;">
                    {{ $companyAddress }}
                </div>
                <div style="font-size: 8.5px; color: #334155; margin-bottom: 3px;">
                    @if($companyPhone)<strong>Phone:</strong> {{ $companyPhone }} &nbsp;|&nbsp; @endif
                    @if($companyEmail)<strong>Email:</strong> {{ $companyEmail }}@endif
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 8.5px; margin-top: 3px;">
                    <tr>
                        <td style="padding: 1px 0; border: none; width: 50%;">
                            <strong>GSTIN/UIN:</strong> {{ $companyGstin }}
                        </td>
                        <td style="padding: 1px 0; border: none; width: 50%;">
                            <strong>PAN:</strong> {{ $companyPan }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 1px 0; border: none;" colspan="2">
                            <strong>State:</strong> {{ $companyState }} @if($companyStateCode)(Code: {{ $companyStateCode }})@endif
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Order Metadata Table (Right) -->
            <td style="width: 42%; border-top: none; border-right: none; padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; width: 44%; font-weight: 700; color: #334155;">PO Number:</td>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 800; color: #0f172a;">{{ $order->po_no }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 700; color: #334155;">PO Date:</td>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 600;">{{ $order->po_date?->format('d-M-Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 700; color: #334155;">Payment Terms:</td>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 600;">
                            @if($order->credit_days)
                                {{ $order->credit_days }} Days
                            @elseif($order->due_date)
                                Due on {{ $order->due_date->format('d/m/Y') }}
                            @else
                                Immediate / Net
                            @endif
                        </td>
                    </tr>
                    @if($order->due_date)
                    <tr>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 700; color: #334155;">Payment Due Date:</td>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 600;">
                            {{ $order->due_date->format('d/m/Y') }}@if($order->credit_days) ({{ $order->credit_days }} days)@endif
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 700; color: #334155;">Reference No:</td>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 600;">{{ $referenceNo }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 700; color: #334155;">Destination:</td>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 600;">
                            {{ $consignee['warehouse_name'] ?: ($consignee['branch_name'] ?: $consignee['company_name']) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 3px 6px; font-weight: 700; color: #334155;">Expected Date:</td>
                        <td style="border: none; padding: 3px 6px; font-weight: 600;">
                            {{ $order->expected_date?->format('d-M-Y') ?: '—' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Section 2: Supplier and Consignee Details (Two Column Section) -->
    <table class="grid-table" style="border: none; border-top: 1.5px solid #1e293b;">
        <tr>
            <!-- Left: Supplier / Vendor -->
            <td style="width: 50%; border-left: none; border-top: none; border-right: 1px solid #334155; border-bottom: none; padding: 0;">
                <div class="section-header">Supplier / Vendor (Order Placed To)</div>
                <div style="padding: 6px 8px; font-size: 8.5px; line-height: 1.45;">
                    <div style="font-size: 11px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">
                        {{ $supplierDetails['name'] }}
                    </div>
                    @if($supplierDetails['code'])
                        <div style="color: #64748b; font-size: 8px; margin-bottom: 2px;">Supplier Code: <strong>{{ $supplierDetails['code'] }}</strong></div>
                    @endif
                    <div style="margin-bottom: 3px; color: #334155;">
                        <strong>Billing Address:</strong><br>
                        <span style="white-space: pre-line;">{{ $supplierDetails['billing_address'] }}</span>
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 8.5px; margin-top: 3px;">
                        <tr>
                            <td style="border: none; padding: 1px 0; width: 50%;">
                                <strong>GSTIN/UIN:</strong> {{ $supplierDetails['gstin'] }}
                            </td>
                            <td style="border: none; padding: 1px 0; width: 50%;">
                                <strong>PAN:</strong> {{ $supplierDetails['pan'] }}
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 1px 0;" colspan="2">
                                <strong>State:</strong> {{ $supplierDetails['state'] }}
                                @if($supplierDetails['state_code'])(Code: {{ $supplierDetails['state_code'] }})@endif
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 1px 0;" colspan="2">
                                <strong>Contact:</strong> {{ $supplierDetails['contact_person'] }}
                                @if($supplierDetails['phone'] !== '—') | Tel: {{ $supplierDetails['phone'] }}@endif
                                @if($supplierDetails['email'] !== '—') | Email: {{ $supplierDetails['email'] }}@endif
                            </td>
                        </tr>
                    </table>
                </div>
            </td>

            <!-- Right: Consignee / Delivery Destination -->
            <td style="width: 50%; border-right: none; border-top: none; border-left: none; border-bottom: none; padding: 0;">
                <div class="section-header">Consignee / Delivery Destination</div>
                <div style="padding: 6px 8px; font-size: 8.5px; line-height: 1.45;">
                    <div style="font-size: 11px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">
                        {{ $consignee['company_name'] }}
                    </div>
                    @if($consignee['warehouse_name'])
                        <div style="font-weight: 700; color: #1e293b; font-size: 9px; margin-bottom: 2px;">
                            Warehouse: {{ $consignee['warehouse_name'] }}
                        </div>
                    @elseif($consignee['branch_name'])
                        <div style="font-weight: 700; color: #1e293b; font-size: 9px; margin-bottom: 2px;">
                            Branch: {{ $consignee['branch_name'] }}
                        </div>
                    @endif
                    <div style="margin-bottom: 3px; color: #334155;">
                        <strong>Dispatch / Delivery Address:</strong><br>
                        <span style="white-space: pre-line;">{{ $consignee['delivery_address'] }}</span>
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 8.5px; margin-top: 3px;">
                        <tr>
                            <td style="border: none; padding: 1px 0; width: 50%;">
                                <strong>GSTIN/UIN:</strong> {{ $consignee['gstin'] }}
                            </td>
                            <td style="border: none; padding: 1px 0; width: 50%;">
                                <strong>PAN:</strong> {{ $consignee['pan'] }}
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 1px 0;" colspan="2">
                                <strong>State:</strong> {{ $consignee['state'] }} @if($consignee['state_code'])(Code: {{ $consignee['state_code'] }})@endif
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 1px 0;" colspan="2">
                                <strong>Dispatch Phone:</strong> {{ $consignee['phone'] }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Section 3: Product Table -->
    <table class="items-table" style="border-top: 1.5px solid #1e293b; border-bottom: 1.5px solid #1e293b;">
        <thead>
            <tr>
                <th style="width: 24px;">S.No.</th>
                <th class="text-left" style="width: 220px;">Item Description</th>
                <th style="width: 55px;">HSN/Sac</th>
                <th class="text-right" style="width: 44px;">Qty</th>
                <th style="width: 34px;">Unit</th>
                <th class="text-right" style="width: 65px;">Rate (&#8377;)</th>
                <th class="text-right" style="width: 38px;">Disc.</th>
                <th class="text-right" style="width: 38px;">Tax %</th>
                <th class="text-right" style="width: 44px;">Cgst %</th>
                <th class="text-right" style="width: 44px;">Sgst %</th>
                <th class="text-right" style="width: 44px;">Igst %</th>
                <th class="text-right" style="width: 80px;">Line Amount (&#8377;)</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @forelse($order->items as $idx => $item)
                @php
                    $qty = (float) $item->quantity;
                    $rate = (float) $item->unit_cost;
                    $lineTaxable = $qty * $rate;
                    $totalQty += $qty;

                    $hsn = $item->product?->hsn_code ?: '—';
                    $brand = $item->product?->brand?->name;
                    $batch = $item->batch_no ?? $item->batch_name;

                    $taxP = (float) $item->tax_percent;
                    $cgstP = (float) ($item->cgst_percent ?? 0);
                    $sgstP = (float) ($item->sgst_percent ?? 0);

                    // Determine IGST %
                    $igstP = 0.0;
                    if ($isInterstate || ($cgstP == 0 && $sgstP == 0 && $taxP > 0)) {
                        $igstP = $taxP;
                        $cgstP = 0.0;
                        $sgstP = 0.0;
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-left">
                        <div class="item-name">{{ $item->product?->name ?: 'Item #' . ($idx + 1) }}</div>
                        @if($brand || $batch)
                            <div class="item-sub">
                                @if($brand)<span>Brand: <strong>{{ $brand }}</strong></span>@endif
                                @if($brand && $batch) &nbsp;|&nbsp; @endif
                                @if($batch)<span>Batch: <strong>{{ $batch }}</strong></span>@endif
                            </div>
                        @endif
                        @if($item->product?->sku && $item->product->sku !== $item->product?->name)
                            <div class="item-sub">SKU: {{ $item->product->sku }}</div>
                        @endif
                    </td>
                    <td class="text-center" style="font-size: 8.5px;">{{ $hsn }}</td>
                    <td class="text-right font-bold">{{ (float) $qty == (int) $qty ? number_format($qty, 0) : number_format($qty, 2) }}</td>
                    <td class="text-center">{{ $item->uom?->code ?: 'PCS' }}</td>
                    <td class="text-right">{{ number_format($rate, 2) }}</td>
                    <td class="text-right">—</td>
                    <td class="text-right">{{ number_format($taxP, 1) }}%</td>
                    <td class="text-right">{{ $cgstP > 0 ? number_format($cgstP, 1) . '%' : '—' }}</td>
                    <td class="text-right">{{ $sgstP > 0 ? number_format($sgstP, 1) . '%' : '—' }}</td>
                    <td class="text-right">{{ $igstP > 0 ? number_format($igstP, 1) . '%' : '—' }}</td>
                    <td class="text-right font-bold">{{ number_format($lineTaxable, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center" style="padding: 16px; color: #64748b;">No line items found.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: 700;">
                <td colspan="3" class="text-right" style="border-top: 1.5px solid #1e293b; padding: 4px 6px;">TOTALS:</td>
                <td class="text-right font-bold" style="border-top: 1.5px solid #1e293b;">{{ number_format($totalQty, 2) }}</td>
                <td colspan="7" style="border-top: 1.5px solid #1e293b;"></td>
                <td class="text-right font-bold" style="border-top: 1.5px solid #1e293b;">&#8377; {{ number_format($order->subtotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Section 4 & 6: Amount in Words, Notes, and Summary Breakdown -->
    <table class="grid-table avoid-break" style="border: none; border-bottom: 1.5px solid #1e293b;">
        <tr>
            <!-- Left Side: Amount Chargeable in Words & Notes -->
            <td style="width: 58%; border-left: none; border-top: none; border-right: 1px solid #334155; border-bottom: none; padding: 6px 8px; vertical-align: top;">
                <div class="words-title">Amount Chargeable (in words):</div>
                <div class="words-val">
                    {{ $amountWords }}
                </div>

                @if($order->is_freight_applicable || (float) $order->freight_amount > 0)
                    <div style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed #cbd5e1; font-size: 8.5px;">
                        <span style="font-weight: 700; color: #059669;">✓ Freight Charges Applicable</span>
                        @if($order->freight_notes)
                            <div style="color: #475569; margin-top: 2px;">
                                <strong>Transporter Details:</strong> {{ $order->freight_notes }}
                            </div>
                        @endif
                    </div>
                @endif

                @if($order->notes)
                    <div style="margin-top: 6px; padding-top: 4px; border-top: 1px dashed #cbd5e1; font-size: 8.5px; color: #334155;">
                        <strong>Order Notes / Special Instructions:</strong>
                        <div style="white-space: pre-line; margin-top: 2px;">{{ $order->notes }}</div>
                    </div>
                @endif
            </td>

            <!-- Right Side: Totals Summary -->
            <td style="width: 42%; border-right: none; border-top: none; border-left: none; border-bottom: none; padding: 0; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr class="summary-row">
                        <td style="border: none; border-bottom: 1px solid #e2e8f0; width: 56%; color: #334155;">Taxable Subtotal:</td>
                        <td style="border: none; border-bottom: 1px solid #e2e8f0; width: 44%;" class="text-right font-bold">&#8377; {{ number_format($order->subtotal, 2) }}</td>
                    </tr>
                    @if($cgstTotal > 0)
                    <tr class="summary-row">
                        <td style="border: none; border-bottom: 1px solid #e2e8f0; color: #334155;">CGST:</td>
                        <td style="border: none; border-bottom: 1px solid #e2e8f0;" class="text-right">&#8377; {{ number_format($cgstTotal, 2) }}</td>
                    </tr>
                    @endif
                    @if($sgstTotal > 0)
                    <tr class="summary-row">
                        <td style="border: none; border-bottom: 1px solid #e2e8f0; color: #334155;">SGST:</td>
                        <td style="border: none; border-bottom: 1px solid #e2e8f0;" class="text-right">&#8377; {{ number_format($sgstTotal, 2) }}</td>
                    </tr>
                    @endif
                    @if($igstTotal > 0)
                    <tr class="summary-row">
                        <td style="border: none; border-bottom: 1px solid #e2e8f0; color: #334155;">IGST:</td>
                        <td style="border: none; border-bottom: 1px solid #e2e8f0;" class="text-right">&#8377; {{ number_format($igstTotal, 2) }}</td>
                    </tr>
                    @endif
                    @if($order->is_freight_applicable || (float) $order->freight_amount > 0)
                    <tr class="summary-row">
                        <td style="border: none; border-bottom: 1px solid #e2e8f0; color: #334155;">Freight Charges:</td>
                        <td style="border: none; border-bottom: 1px solid #e2e8f0;" class="text-right">&#8377; {{ number_format((float) $order->freight_amount, 2) }}</td>
                    </tr>
                    @if((float) $order->freight_tax_amount > 0)
                    <tr class="summary-row">
                        <td style="border: none; border-bottom: 1px solid #e2e8f0; color: #334155;">Freight Tax (GST {{ number_format((float) $order->freight_tax_rate, 1) }}%):</td>
                        <td style="border: none; border-bottom: 1px solid #e2e8f0;" class="text-right">&#8377; {{ number_format((float) $order->freight_tax_amount, 2) }}</td>
                    </tr>
                    @endif
                    @endif
                    <tr class="summary-row grand-total-box">
                        <td style="border: none; padding: 5px 6px;">Grand Total:</td>
                        <td style="border: none; padding: 5px 6px;" class="text-right">&#8377; {{ number_format($order->grand_total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Section 5: Mandatory Terms and Conditions (Six Client Terms Preserved Exactly) -->
    <div class="avoid-break" style="border-bottom: 1.5px solid #1e293b;">
        <div class="section-header" style="background: #f8fafc;">
            Terms and Conditions
        </div>
        <div style="padding: 6px 10px;">
            <ol class="terms-ol">
                <li>Invoice should be made in the name of Avit Digital.</li>
                <li>Delivery Address as per detail.</li>
                <li>Material should be in good condtions.</li>
                <li>Please mention PO No. &amp; PO date to be mention for the payment process.</li>
                <li>Warranty: As per OEM policy, the warranty policy of OEM to be shared with PO acceptance.</li>
                <li>Jurisdiction: All Disputes are subject to Ludhiana Jurisdiction only in case of any conflict.</li>
            </ol>
        </div>
    </div>

    <!-- Section 7: Declaration and Signatures -->
    <table class="grid-table avoid-break" style="border: none;">
        <tr>
            <!-- Supplier Signature -->
            <td style="width: 50%; border-left: none; border-top: none; border-bottom: none; border-right: 1px solid #334155; padding: 6px 8px; vertical-align: top;">
                <div style="font-weight: 700; color: #334155; font-size: 8.5px; text-transform: uppercase;">
                    Supplier Acknowledgement / Acceptance:
                </div>
                <div style="height: 48px;"></div>
                <div style="border-top: 1px dashed #94a3b8; padding-top: 3px; font-size: 8px; color: #475569; text-align: center;">
                    Authorized Signatory (with Company Seal &amp; Date)
                </div>
            </td>

            <!-- Avit Digital Signature -->
            <td style="width: 50%; border-right: none; border-top: none; border-bottom: none; border-left: none; padding: 6px 8px; vertical-align: top; text-align: right;">
                <div style="font-weight: 700; color: #0f172a; font-size: 8.5px; text-transform: uppercase;">
                    For Avit Digital
                </div>
                <div style="height: 48px;"></div>
                <div style="border-top: 1px dashed #94a3b8; padding-top: 3px; font-size: 8px; color: #475569; text-align: center;">
                    Authorised Signatory
                </div>
            </td>
        </tr>
    </table>

    <!-- System Generated Footer Note -->
    <div class="system-note">
        This is a Computer Generated Purchase Order.
    </div>
</div>

</body>
</html>

