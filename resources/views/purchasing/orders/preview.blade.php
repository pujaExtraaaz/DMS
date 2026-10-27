<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

        .toolbar .btn-options.is-open .chevron {
            transform: rotate(180deg);
        }
        .toolbar .chevron {
            transition: transform 0.45s ease;
        }
        .shutter {
            max-width: 900px;
            margin: 0 auto 10px;
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.55s cubic-bezier(.22, .8, .2, 1);
        }
        .shutter.is-open {
            grid-template-rows: 1fr;
        }
        .shutter-clip {
            overflow: hidden;
            min-height: 0;
            position: relative;
        }
        .shutter-slats {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            z-index: 3;
            pointer-events: none;
        }
        .shutter-slats i {
            flex: 1;
            background: linear-gradient(#ffffff, #e2e8f0);
            border-bottom: 1px solid #94a3b8;
            transform-origin: top center;
            transform: scaleY(1);
            transition: transform 0.42s ease;
        }
        .shutter.is-open .shutter-slats i {
            transform: scaleY(0);
        }
        .shutter.is-open .shutter-slats i:nth-child(1) { transition-delay: 0.02s; }
        .shutter.is-open .shutter-slats i:nth-child(2) { transition-delay: 0.05s; }
        .shutter.is-open .shutter-slats i:nth-child(3) { transition-delay: 0.08s; }
        .shutter.is-open .shutter-slats i:nth-child(4) { transition-delay: 0.11s; }
        .shutter.is-open .shutter-slats i:nth-child(5) { transition-delay: 0.14s; }
        .shutter.is-open .shutter-slats i:nth-child(6) { transition-delay: 0.17s; }
        .shutter.is-open .shutter-slats i:nth-child(7) { transition-delay: 0.20s; }
        .shutter.is-open .shutter-slats i:nth-child(8) { transition-delay: 0.23s; }
        .print-fields {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .special-instructions {
            margin: 0;
            padding: 8px 10px;
            background: #fef3c7;
            border-bottom: 2px solid #d97706;
            border-left: 6px solid #b45309;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .special-instructions-label {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #92400e;
        }
        .special-instructions-text {
            margin-top: 3px;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.35;
            color: #1c1917;
            white-space: pre-line;
        }
        .special-instructions-edit {
            display: block;
            width: 100%;
            margin-top: 3px;
            padding: 0;
            border: 0;
            background: transparent;
            resize: vertical;
            min-height: 36px;
            font: inherit;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.35;
            color: #1c1917;
        }
        .special-instructions-edit:focus {
            outline: 1px dashed #b45309;
            background: #fffbeb;
        }
        .order-notes-status {
            margin-top: 4px;
            font-size: 10px;
            font-weight: 700;
            color: #92400e;
        }
        .print-fields h2 {
            margin: 0 0 8px;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
        }
        .print-fields p {
            margin: 0 0 8px;
            font-size: 11px;
            color: #64748b;
        }
        .print-fields .groups {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px 16px;
        }
        .print-fields h3 {
            margin: 0 0 4px;
            font-size: 10px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #475569;
        }
        .print-fields label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #1e293b;
            margin: 2px 0;
            cursor: pointer;
        }
        .print-fields .actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        .print-fields .actions button {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 11px;
            cursor: pointer;
        }
        @media (max-width: 720px) {
            .print-fields .groups { grid-template-columns: 1fr; }
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
        <button type="button" id="print-options-toggle" class="btn-options" aria-expanded="false" aria-controls="print-options-shutter">
            Print options
            <svg class="chevron" width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
        </button>
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

<div class="shutter no-print" id="print-options-shutter">
<div class="shutter-clip">
    <div class="shutter-slats" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
<div class="print-fields">
    <h2>Print fields</h2>
    <p>Untick a field to leave it off this purchase order. The choice is remembered on this browser. PO number, supplier, item, quantity, rate, line amount, and grand total stay on the print.</p>
    <div class="groups">
        <div>
            <h3>Order</h3>
            <label><input type="checkbox" data-print-field value="po-date" checked> PO Date</label>
            <label><input type="checkbox" data-print-field value="payment-terms" checked> Payment Terms</label>
            <label><input type="checkbox" data-print-field value="due-date" checked> Payment Due Date</label>
            <label><input type="checkbox" data-print-field value="reference" checked> Reference No</label>
            <label><input type="checkbox" data-print-field value="destination" checked> Destination</label>
            <label><input type="checkbox" data-print-field value="expected-date" checked> Expected Date</label>
        </div>
        <div>
            <h3>Company</h3>
            <label><input type="checkbox" data-print-field value="company-address" checked> Address</label>
            <label><input type="checkbox" data-print-field value="company-contact" checked> Phone and email</label>
            <label><input type="checkbox" data-print-field value="company-tax" checked> GSTIN and PAN</label>
            <label><input type="checkbox" data-print-field value="company-state" checked> State</label>
        </div>
        <div>
            <h3>Supplier</h3>
            <label><input type="checkbox" data-print-field value="supplier-code" checked> Supplier code</label>
            <label><input type="checkbox" data-print-field value="billing-address" checked> Billing address</label>
            <label><input type="checkbox" data-print-field value="supplier-gstin" checked> GSTIN</label>
            <label><input type="checkbox" data-print-field value="supplier-pan" checked> PAN</label>
            <label><input type="checkbox" data-print-field value="supplier-state" checked> State</label>
            <label><input type="checkbox" data-print-field value="supplier-contact" checked> Contact</label>
        </div>
        <div>
            <h3>Delivery</h3>
            <label><input type="checkbox" data-print-field value="warehouse" checked> Warehouse location</label>
            <label><input type="checkbox" data-print-field value="dispatch-address" checked> Dispatch address</label>
            <label><input type="checkbox" data-print-field value="consignee-gstin" checked> GSTIN</label>
            <label><input type="checkbox" data-print-field value="consignee-pan" checked> PAN</label>
            <label><input type="checkbox" data-print-field value="consignee-state" checked> State</label>
            <label><input type="checkbox" data-print-field value="dispatch-phone" checked> Dispatch phone</label>
        </div>
        <div>
            <h3>Line items</h3>
            <label><input type="checkbox" data-print-field value="brand" checked> Brand</label>
            <label><input type="checkbox" data-print-field value="batch" checked> Batch number</label>
            <label><input type="checkbox" data-print-field value="serial" checked> Serial number</label>
            <label><input type="checkbox" data-print-field value="part-no" checked> Part number</label>
            <label><input type="checkbox" data-print-field value="hsn" checked> HSN/SAC</label>
            <label><input type="checkbox" data-print-field value="unit" checked> Unit</label>
            <label><input type="checkbox" data-print-field value="discount" checked> Discount</label>
            <label><input type="checkbox" data-print-field value="tax" checked> Tax %</label>
            <label><input type="checkbox" data-print-field value="cgst" checked> CGST %</label>
            <label><input type="checkbox" data-print-field value="sgst" checked> SGST %</label>
            <label><input type="checkbox" data-print-field value="igst" checked> IGST %</label>
        </div>
        <div>
            <h3>Footer</h3>
            <label><input type="checkbox" data-print-field value="amount-words" checked> Amount in words</label>
            <label><input type="checkbox" data-print-field value="freight-note" checked> Freight note</label>
            <label><input type="checkbox" data-print-field value="order-notes" checked> Order notes</label>
            <label><input type="checkbox" data-print-field value="tax-breakup" checked> Tax breakup</label>
            <label><input type="checkbox" data-print-field value="freight" checked> Freight in total</label>
            <label><input type="checkbox" data-print-field value="terms" checked> Terms and conditions</label>
            <label><input type="checkbox" data-print-field value="signatures" checked> Signatures</label>
        </div>
    </div>
    <div class="actions">
        <button type="button" id="print-fields-all">Show all</button>
        <button type="button" id="print-fields-none">Hide optional fields</button>
    </div>
</div>
</div>
</div>
@endif

<div class="doc-wrapper">
    <!-- Document Title Banner -->
    <div class="doc-banner">
        PURCHASE ORDER
    </div>

    <div class="special-instructions" data-col="order-notes" id="order-notes-block">
        <div class="special-instructions-label">Order Notes / Special Instructions</div>
        @if(empty($isPdf))
            <textarea id="order-notes-input" class="special-instructions-edit" rows="2" placeholder="Type special instructions for the supplier...">{{ $order->notes }}</textarea>
            <div class="order-notes-status no-print" id="order-notes-status">Click the text to edit. It saves on this purchase order.</div>
        @elseif($order->notes)
            <div class="special-instructions-text">{{ $order->notes }}</div>
        @endif
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
                <div data-col="company-address" style="font-size: 8.5px; color: #334155; margin-bottom: 2px;">
                    {{ $companyAddress }}
                </div>
                <div data-col="company-contact" style="font-size: 8.5px; color: #334155; margin-bottom: 3px;">
                    @if($companyPhone)<strong>Phone:</strong> {{ $companyPhone }} &nbsp;|&nbsp; @endif
                    @if($companyEmail)<strong>Email:</strong> {{ $companyEmail }}@endif
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 8.5px; margin-top: 3px;">
                    <tr data-col="company-tax">
                        <td style="padding: 1px 0; border: none; width: 50%;">
                            <strong>GSTIN/UIN:</strong> {{ $companyGstin }}
                        </td>
                        <td style="padding: 1px 0; border: none; width: 50%;">
                            <strong>PAN:</strong> {{ $companyPan }}
                        </td>
                    </tr>
                    <tr data-col="company-state">
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
                    <tr data-col="po-date">
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 700; color: #334155;">PO Date:</td>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 600;">{{ $order->po_date?->format('d-M-Y') ?? '—' }}</td>
                    </tr>
                    <tr data-col="payment-terms">
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
                    <tr data-col="due-date">
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 700; color: #334155;">Payment Due Date:</td>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 600;">
                            {{ $order->due_date->format('d/m/Y') }}@if($order->credit_days) ({{ $order->credit_days }} days)@endif
                        </td>
                    </tr>
                    @endif
                    <tr data-col="reference">
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 700; color: #334155;">Reference No:</td>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 600;">{{ $referenceNo }}</td>
                    </tr>
                    <tr data-col="destination">
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 700; color: #334155;">Destination:</td>
                        <td style="border: none; border-bottom: 1px solid #334155; padding: 3px 6px; font-weight: 600;">
                            {{ $consignee['warehouse_name'] ?: ($consignee['branch_name'] ?: $consignee['company_name']) }}
                        </td>
                    </tr>
                    <tr data-col="expected-date">
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
                        <div data-col="supplier-code" style="color: #64748b; font-size: 8px; margin-bottom: 2px;">Supplier Code: <strong>{{ $supplierDetails['code'] }}</strong></div>
                    @endif
                    <div data-col="billing-address" style="margin-bottom: 3px; color: #334155;">
                        <strong>Billing Address:</strong><br>
                        <span style="white-space: pre-line;">{{ $supplierDetails['billing_address'] }}</span>
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 8.5px; margin-top: 3px;">
                        <tr>
                            <td data-col="supplier-gstin" style="border: none; padding: 1px 0; width: 50%;">
                                <strong>GSTIN/UIN:</strong> {{ $supplierDetails['gstin'] }}
                            </td>
                            <td data-col="supplier-pan" style="border: none; padding: 1px 0; width: 50%;">
                                <strong>PAN:</strong> {{ $supplierDetails['pan'] }}
                            </td>
                        </tr>
                        <tr data-col="supplier-state">
                            <td style="border: none; padding: 1px 0;" colspan="2">
                                <strong>State:</strong> {{ $supplierDetails['state'] }}
                                @if($supplierDetails['state_code'])(Code: {{ $supplierDetails['state_code'] }})@endif
                            </td>
                        </tr>
                        <tr data-col="supplier-contact">
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
                        <div data-col="warehouse" style="font-weight: 700; color: #1e293b; font-size: 9px; margin-bottom: 2px;">
                            Warehouse: {{ $consignee['warehouse_name'] }}
                        </div>
                    @elseif($consignee['branch_name'])
                        <div data-col="warehouse" style="font-weight: 700; color: #1e293b; font-size: 9px; margin-bottom: 2px;">
                            Branch: {{ $consignee['branch_name'] }}
                        </div>
                    @endif
                    <div data-col="dispatch-address" style="margin-bottom: 3px; color: #334155;">
                        <strong>Dispatch / Delivery Address:</strong><br>
                        <span style="white-space: pre-line;">{{ $consignee['delivery_address'] }}</span>
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 8.5px; margin-top: 3px;">
                        <tr>
                            <td data-col="consignee-gstin" style="border: none; padding: 1px 0; width: 50%;">
                                <strong>GSTIN/UIN:</strong> {{ $consignee['gstin'] }}
                            </td>
                            <td data-col="consignee-pan" style="border: none; padding: 1px 0; width: 50%;">
                                <strong>PAN:</strong> {{ $consignee['pan'] }}
                            </td>
                        </tr>
                        <tr data-col="consignee-state">
                            <td style="border: none; padding: 1px 0;" colspan="2">
                                <strong>State:</strong> {{ $consignee['state'] }} @if($consignee['state_code'])(Code: {{ $consignee['state_code'] }})@endif
                            </td>
                        </tr>
                        <tr data-col="dispatch-phone">
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
                <th data-col="hsn" style="width: 55px;">HSN/Sac</th>
                <th class="text-right" style="width: 44px;">Qty</th>
                <th data-col="unit" style="width: 34px;">Unit</th>
                <th class="text-right" style="width: 65px;">Rate (&#8377;)</th>
                <th data-col="discount" class="text-right" style="width: 38px;">Disc.</th>
                <th data-col="tax" class="text-right" style="width: 38px;">Tax %</th>
                <th data-col="cgst" class="text-right" style="width: 44px;">Cgst %</th>
                <th data-col="sgst" class="text-right" style="width: 44px;">Sgst %</th>
                <th data-col="igst" class="text-right" style="width: 44px;">Igst %</th>
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
                    $serialNo = $item->serial_no ?? null;

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
                        @if($brand || $batch || $serialNo)
                            <div class="item-sub">
                                @if($brand)<span data-col="brand">Brand: <strong>{{ $brand }}</strong></span>@endif
                                @if($batch)<span data-col="batch">@if($brand) | @endif Batch: <strong>{{ $batch }}</strong></span>@endif
                                @if($serialNo)<span data-col="serial">@if($brand || $batch) | @endif Serial: <strong>{{ $serialNo }}</strong></span>@endif
                            </div>
                        @endif
                        @if($item->product?->sku && $item->product->sku !== $item->product?->name)
                            <div class="item-sub" data-col="part-no">Part No: {{ $item->product->sku }}</div>
                        @endif
                    </td>
                    <td data-col="hsn" class="text-center" style="font-size: 8.5px;">{{ $hsn }}</td>
                    <td class="text-right font-bold">{{ (float) $qty == (int) $qty ? number_format($qty, 0) : number_format($qty, 2) }}</td>
                    <td data-col="unit" class="text-center">{{ $item->uom?->code ?: 'PCS' }}</td>
                    <td class="text-right">{{ number_format($rate, 2) }}</td>
                    <td data-col="discount" class="text-right">—</td>
                    <td data-col="tax" class="text-right">{{ number_format($taxP, 1) }}%</td>
                    <td data-col="cgst" class="text-right">{{ $cgstP > 0 ? number_format($cgstP, 1) . '%' : '—' }}</td>
                    <td data-col="sgst" class="text-right">{{ $sgstP > 0 ? number_format($sgstP, 1) . '%' : '—' }}</td>
                    <td data-col="igst" class="text-right">{{ $igstP > 0 ? number_format($igstP, 1) . '%' : '—' }}</td>
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
                <td style="border-top: 1.5px solid #1e293b;"></td>
                <td class="text-right" style="border-top: 1.5px solid #1e293b; padding: 4px 6px;">TOTALS:</td>
                <td data-col="hsn" style="border-top: 1.5px solid #1e293b;"></td>
                <td class="text-right font-bold" style="border-top: 1.5px solid #1e293b;">{{ number_format($totalQty, 2) }}</td>
                <td data-col="unit" style="border-top: 1.5px solid #1e293b;"></td>
                <td style="border-top: 1.5px solid #1e293b;"></td>
                <td data-col="discount" style="border-top: 1.5px solid #1e293b;"></td>
                <td data-col="tax" style="border-top: 1.5px solid #1e293b;"></td>
                <td data-col="cgst" style="border-top: 1.5px solid #1e293b;"></td>
                <td data-col="sgst" style="border-top: 1.5px solid #1e293b;"></td>
                <td data-col="igst" style="border-top: 1.5px solid #1e293b;"></td>
                <td class="text-right font-bold" style="border-top: 1.5px solid #1e293b;">&#8377; {{ number_format($order->subtotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Section 4 & 6: Amount in Words, Notes, and Summary Breakdown -->
    <table class="grid-table avoid-break" style="border: none; border-bottom: 1.5px solid #1e293b;">
        <tr>
            <!-- Left Side: Amount Chargeable in Words & Notes -->
            <td style="width: 58%; border-left: none; border-top: none; border-right: 1px solid #334155; border-bottom: none; padding: 6px 8px; vertical-align: top;">
                <div data-col="amount-words">
                    <div class="words-title">Amount Chargeable (in words):</div>
                    <div class="words-val">
                        {{ $amountWords }}
                    </div>
                </div>

                @if($order->is_freight_applicable || (float) $order->freight_amount > 0)
                    <div data-col="freight-note" style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed #cbd5e1; font-size: 8.5px;">
                        <span style="font-weight: 700; color: #059669;">✓ Freight Charges Applicable</span>
                        @if($order->freight_notes)
                            <div style="color: #475569; margin-top: 2px;">
                                <strong>Transporter Details:</strong> {{ $order->freight_notes }}
                            </div>
                        @endif
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
                    <tr class="summary-row" data-col="tax-breakup">
                        <td style="border: none; border-bottom: 1px solid #e2e8f0; color: #334155;">CGST:</td>
                        <td style="border: none; border-bottom: 1px solid #e2e8f0;" class="text-right">&#8377; {{ number_format($cgstTotal, 2) }}</td>
                    </tr>
                    @endif
                    @if($sgstTotal > 0)
                    <tr class="summary-row" data-col="tax-breakup">
                        <td style="border: none; border-bottom: 1px solid #e2e8f0; color: #334155;">SGST:</td>
                        <td style="border: none; border-bottom: 1px solid #e2e8f0;" class="text-right">&#8377; {{ number_format($sgstTotal, 2) }}</td>
                    </tr>
                    @endif
                    @if($igstTotal > 0)
                    <tr class="summary-row" data-col="tax-breakup">
                        <td style="border: none; border-bottom: 1px solid #e2e8f0; color: #334155;">IGST:</td>
                        <td style="border: none; border-bottom: 1px solid #e2e8f0;" class="text-right">&#8377; {{ number_format($igstTotal, 2) }}</td>
                    </tr>
                    @endif
                    @if($order->is_freight_applicable || (float) $order->freight_amount > 0)
                    <tr class="summary-row" data-col="freight">
                        <td style="border: none; border-bottom: 1px solid #e2e8f0; color: #334155;">
                            Freight Charges
                            @if(($order->freight_mode ?? 'amount') === 'percent')
                                ({{ rtrim(rtrim(number_format((float) $order->freight_percent, 2), '0'), '.') }}%)
                            @endif
                            :
                        </td>
                        <td style="border: none; border-bottom: 1px solid #e2e8f0;" class="text-right">&#8377; {{ number_format((float) $order->freight_amount, 2) }}</td>
                    </tr>
                    @if((float) $order->freight_tax_amount > 0)
                    <tr class="summary-row" data-col="freight">
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
    <div class="avoid-break" data-col="terms" style="border-bottom: 1.5px solid #1e293b;">
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
    <table class="grid-table avoid-break" data-col="signatures" style="border: none;">
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

@if(empty($isPdf))
<script>
(function () {
    var key = 'po-print-fields';
    var boxes = document.querySelectorAll('[data-print-field]');

    function read() {
        try {
            return JSON.parse(localStorage.getItem(key) || '{}') || {};
        } catch (e) {
            return {};
        }
    }

    function apply() {
        var saved = read();
        boxes.forEach(function (box) {
            box.checked = saved[box.value] !== false;
        });
        document.querySelectorAll('[data-col]').forEach(function (el) {
            el.style.display = saved[el.getAttribute('data-col')] === false ? 'none' : '';
        });
    }

    function save() {
        var saved = {};
        boxes.forEach(function (box) {
            saved[box.value] = box.checked;
        });
        localStorage.setItem(key, JSON.stringify(saved));
        apply();
    }

    boxes.forEach(function (box) {
        box.addEventListener('change', save);
    });
    var showAll = document.getElementById('print-fields-all');
    var hideOptional = document.getElementById('print-fields-none');
    if (showAll) {
        showAll.addEventListener('click', function () {
            boxes.forEach(function (box) { box.checked = true; });
            save();
        });
    }
    if (hideOptional) {
        hideOptional.addEventListener('click', function () {
            boxes.forEach(function (box) { box.checked = false; });
            save();
        });
    }
    apply();

    var notesInput = document.getElementById('order-notes-input');
    var notesStatus = document.getElementById('order-notes-status');
    var notesBlock = document.getElementById('order-notes-block');
    var notesToken = document.querySelector('meta[name="csrf-token"]');
    function hideEmptyNotesForPrint() {
        if (!notesBlock || !notesInput) return;
        notesBlock.dataset.hiddenByChoice = notesBlock.style.display === 'none' ? '1' : '0';
        if (notesBlock.dataset.hiddenByChoice !== '1' && notesInput.value.trim() === '') {
            notesBlock.style.display = 'none';
        }
    }
    window.addEventListener('beforeprint', hideEmptyNotesForPrint);
    window.addEventListener('afterprint', function () {
        if (!notesBlock || notesBlock.dataset.hiddenByChoice === '1') return;
        notesBlock.style.display = '';
    });
    if (notesInput && notesToken) {
        var notesTimer;
        function saveNotes() {
            if (notesStatus) notesStatus.textContent = 'Saving...';
            fetch(@json(route('purchasing.orders.notes', $order)), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': notesToken.getAttribute('content')
                },
                body: JSON.stringify({ notes: notesInput.value })
            }).then(function (res) {
                if (!res.ok) throw new Error('save failed');
                if (notesStatus) notesStatus.textContent = 'Saved on this purchase order';
            }).catch(function () {
                if (notesStatus) notesStatus.textContent = 'Could not save. Try again.';
            });
        }
        notesInput.addEventListener('input', function () {
            clearTimeout(notesTimer);
            notesTimer = setTimeout(saveNotes, 500);
        });
        notesInput.addEventListener('blur', function () {
            clearTimeout(notesTimer);
            saveNotes();
        });
    }

    var toggle = document.getElementById('print-options-toggle');
    var shutter = document.getElementById('print-options-shutter');
    if (toggle && shutter) {
        toggle.addEventListener('click', function () {
            var open = shutter.classList.toggle('is-open');
            toggle.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }
})();
</script>
@endif
</body>
</html>

