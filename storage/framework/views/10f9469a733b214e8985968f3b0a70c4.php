<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - <?php echo e($invoice->invoice_no); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
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
            font-size: 11px;
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

        .toolbar-divider {
            width: 1px;
            height: 20px;
            background: #e2e8f0;
            margin: 0 4px;
        }

        .toolbar-meta {
            font-size: 11px;
            color: #475569;
        }

        .meta-label {
            color: #64748b;
            font-weight: 500;
        }

        .copy-selector {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-copy {
            padding: 4px 8px !important;
            font-size: 11px !important;
            border-radius: 4px !important;
            background: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            color: #475569 !important;
            cursor: pointer;
        }

        .btn-copy.active {
            background: #e0e7ff !important;
            border-color: #6366f1 !important;
            color: #3730a3 !important;
            font-weight: 700 !important;
        }

        .doc-wrapper {
            max-width: 960px;
            margin: 0 auto 32px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 20px 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .header-top {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px dashed #cbd5e1;
        }

        .badge-recipient {
            display: inline-block;
            border: 1px solid #475569;
            padding: 2px 8px;
            font-weight: 700;
            font-size: 9.5px;
            text-transform: uppercase;
            border-radius: 3px;
        }

        /* Document Header */
        .doc-header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 12px;
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
            font-size: 17px;
            font-weight: 800;
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
            font-size: 10.5px;
        }

        /* Parties & Addresses */
        .parties-grid {
            display: table;
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .party-col {
            display: table-cell;
            width: 50%;
            padding: 8px 12px;
            vertical-align: top;
        }

        .party-col:first-child {
            border-right: 1px solid #cbd5e1;
        }

        .box-title {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 3px;
        }

        .party-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .items-table th, .items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            font-size: 10px;
        }

        .items-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: left;
            text-transform: uppercase;
            font-size: 9px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .font-bold { font-weight: 700; }

        /* Summary Layout */
        .bottom-layout {
            display: table;
            width: 100%;
            margin-top: 8px;
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
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10.5px;
        }

        .summary-table .grand-total-row {
            background: #f8fafc;
            font-weight: 700;
            font-size: 12.5px;
            color: #0f172a;
            border-top: 2px solid #0f172a;
        }

        .info-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 7px 10px;
            margin-bottom: 7px;
            background: #f8fafc;
            font-size: 10px;
        }

        .qr-section {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-top: 8px;
        }

        .qr-card {
            display: inline-block;
            text-align: center;
            border: 1px solid #cbd5e1;
            padding: 6px;
            background: #fff;
            border-radius: 4px;
        }

        .sign-area {
            display: table;
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .sign-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding-top: 35px;
        }

        .sign-line {
            display: inline-block;
            width: 80%;
            border-top: 1px dashed #64748b;
            padding-top: 4px;
            font-size: 9.5px;
            color: #475569;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 9.5pt !important;
            }
            .toolbar, .no-print, #configureModal {
                display: none !important;
            }
            .doc-wrapper {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 12px 16px !important;
                border: none !important;
                box-shadow: none !important;
                page-break-after: always !important;
                break-after: page !important;
            }
            .doc-wrapper:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
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

<?php
    $companyName = $company?->name ?? config('app.name');
    $customer = $invoice->customer;
    $amountWords = \App\Support\DocumentExporter::numberToIndianWords($invoice->grand_total);
    $billingAddr = $invoice->billing_address ?: ($customer?->address ?: '—');
    $deliveryAddr = $invoice->shipping_address ?: ($invoice->billing_address ?: ($customer?->shipping_address ?: ($customer?->address ?: '—')));
    $copyType = $copyType ?? request('copy_type', request('copy', 'all'));
    $copies = (int) ($copies ?? request('copies', ($copyType === 'all' ? 3 : 1)));
    $copiesList = $copiesList ?? \App\Http\Controllers\Sales\InvoiceController::resolveCopies($copyType, $copies);
    $autoPrint = $autoPrint ?? request()->boolean('auto_print');
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($isPdf)): ?>
<div class="toolbar no-print">
    <div class="toolbar-left">
        <a href="<?php echo e(route('invoices.show', $invoice)); ?>" title="Return to Invoice Details">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
        </a>
        <div class="toolbar-divider"></div>
        <form method="GET" action="<?php echo e(route('invoices.preview', $invoice)); ?>" class="copy-selector" id="printSettingsForm">
            <span class="meta-label">Type of Copy:</span>
            <select name="copy_type" id="selectCopyType" onchange="document.getElementById('printSettingsForm').submit()" style="padding: 4px 8px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;">
                <option value="all" <?php echo e(($copyType ?? 'all') === 'all' ? 'selected' : ''); ?>>All Copies (Original, Duplicate, Triplicate)</option>
                <option value="original" <?php echo e(($copyType ?? '') === 'original' ? 'selected' : ''); ?>>Original for Recipient</option>
                <option value="duplicate" <?php echo e(($copyType ?? '') === 'duplicate' ? 'selected' : ''); ?>>Duplicate for Transporter</option>
                <option value="triplicate" <?php echo e(($copyType ?? '') === 'triplicate' ? 'selected' : ''); ?>>Triplicate for Supplier</option>
                <option value="extra" <?php echo e(($copyType ?? '') === 'extra' ? 'selected' : ''); ?>>Extra Copy</option>
                <option value="na" <?php echo e(($copyType ?? '') === 'na' ? 'selected' : ''); ?>>Not Applicable (No copy mark)</option>
            </select>

            <span class="meta-label" style="margin-left: 6px;">Copies:</span>
            <input type="number" name="copies" id="inputCopies" value="<?php echo e($copies ?? 1); ?>" min="1" max="10" style="width: 50px; padding: 4px 6px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; text-align: center;">

            <button type="submit" style="padding: 4px 8px; font-size: 11px; border-radius: 4px; background: #f1f5f9; border: 1px solid #cbd5e1; color: #334155; font-weight: 600; cursor: pointer;">Apply</button>
        </form>
        <div class="toolbar-divider"></div>
        <button type="button" onclick="openConfigureModal()" style="padding: 4px 8px; font-size: 11px; border-radius: 4px; background: #f8fafc; border: 1px solid #cbd5e1; color: #475569; cursor: pointer;">
            ⚙ Configure
        </button>
        <div class="toolbar-divider"></div>
        <div class="toolbar-meta">
            <span class="meta-label">Template:</span> Standard GST Tax Invoice
            <span class="meta-label" style="margin-left: 8px;">Format:</span> A4 Portrait
        </div>
    </div>

    <div class="toolbar-right">
        <button class="btn-primary" onclick="window.print()" title="Print selected copies">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        <a href="<?php echo e(route('invoices.export', ['invoice' => $invoice, 'format' => 'pdf', 'copy_type' => $copyType ?? 'all', 'copies' => $copies ?? 1])); ?>" class="btn-primary" title="Export as PDF document">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            PDF
        </a>
        <a href="<?php echo e(route('invoices.export', ['invoice' => $invoice, 'format' => 'xlsx'])); ?>" class="btn-success" title="Export as Excel spreadsheet">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Excel
        </a>
        <a href="<?php echo e(route('invoices.export', ['invoice' => $invoice, 'format' => 'csv'])); ?>" title="Export as CSV tabular data">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            CSV
        </a>
    </div>
</div>

<!-- Quick Configure Modal -->
<div id="configureModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; border-radius: 12px; width: 100%; max-width: 440px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="font-weight: 700; font-size: 13px; color: #0f172a;">Print Configuration Options</div>
            <button type="button" onclick="closeConfigureModal()" style="background: none; border: none; font-size: 16px; cursor: pointer; color: #64748b;">✕</button>
        </div>
        <div style="padding: 18px 20px; font-size: 11.5px; color: #334155; line-height: 1.6;">
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-weight: 600; color: #475569; margin-bottom: 3px;">Template Name</label>
                <div style="padding: 6px 10px; background: #f1f5f9; border-radius: 6px; border: 1px solid #cbd5e1; font-weight: 600;">Standard GST Tax Invoice & e-Invoice</div>
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-weight: 600; color: #475569; margin-bottom: 3px;">Invoice Title</label>
                <div style="padding: 6px 10px; background: #f1f5f9; border-radius: 6px; border: 1px solid #cbd5e1; font-weight: 600;">TAX INVOICE (Under Sec 31 CGST Act)</div>
            </div>
            <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                <div style="flex: 1;">
                    <label style="display: block; font-weight: 600; color: #475569; margin-bottom: 3px;">Paper Size</label>
                    <div style="padding: 6px 10px; background: #f1f5f9; border-radius: 6px; border: 1px solid #cbd5e1;">A4 (210 x 297 mm)</div>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-weight: 600; color: #475569; margin-bottom: 3px;">Layout</label>
                    <div style="padding: 6px 10px; background: #f1f5f9; border-radius: 6px; border: 1px solid #cbd5e1;">Portrait</div>
                </div>
            </div>
            <div style="background: #eff6ff; padding: 10px; border-radius: 6px; border: 1px solid #bfdbfe; font-size: 10.5px; color: #1e40af;">
                <strong>Statutory Copies:</strong> When printing 'All Copies', 3 independent pages (Original, Duplicate, Triplicate) are printed consecutively with scannable QR verification.
            </div>
        </div>
        <div style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right;">
            <button type="button" onclick="closeConfigureModal()" style="padding: 6px 14px; background: #4f46e5; color: #fff; font-weight: 600; font-size: 11px; border: none; border-radius: 6px; cursor: pointer;">Done</button>
        </div>
    </div>
</div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $copiesList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $copyIdx => $copyDesignation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
<div class="doc-wrapper invoice-copy-page">
    <!-- Header Top / Copy Mark -->
    <div class="header-top">
        <div style="display: table-cell; vertical-align: middle; color: #64748b; font-size: 9px;">
            Tax Scheme: GST <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($copyDesignation)): ?> &nbsp;|&nbsp; <span><?php echo e($copyDesignation); ?></span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <div style="display: table-cell; text-align: right; vertical-align: middle;">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($copyDesignation)): ?>
                <span class="badge-recipient"><?php echo e($copyDesignation); ?></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <!-- Document Header -->
    <div class="doc-header">
        <div class="header-cell header-left">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->logo_url): ?>
                <div style="margin-bottom: 6px;">
                    <img src="<?php echo e($company->logo_url); ?>" alt="<?php echo e($companyName); ?>" style="max-height: 48px; max-width: 180px; object-fit: contain;">
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div class="company-name"><?php echo e($companyName); ?></div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->legal_name && $company->legal_name !== $companyName): ?>
                <div style="color: #475569; font-size: 10px;"><?php echo e($company->legal_name); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->gstin): ?>
                <div><strong>GSTIN:</strong> <?php echo e($company->gstin); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->pan): ?>
                <div><strong>PAN:</strong> <?php echo e($company->pan); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->cin): ?>
                <div><strong>CIN:</strong> <?php echo e($company->cin); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->address): ?>
                <div><?php echo e($company->address); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->state): ?>
                <div><?php echo e($company->state); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->pincode): ?>- <?php echo e($company->pincode); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->phone || $company?->email): ?>
                <div style="color: #475569;">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->phone): ?> Tel: <?php echo e($company->phone); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->email): ?> | Email: <?php echo e($company->email); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="header-cell header-right">
            <div style="display: flex; justify-content: flex-end; align-items: flex-start; gap: 12px; margin-bottom: 4px;">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($invoiceQrDataUri)): ?>
                    <div style="text-align: center; border: 1px solid #cbd5e1; padding: 3px; border-radius: 4px; background: #fff;">
                        <img src="<?php echo e($invoiceQrDataUri); ?>" alt="Signed QR Code" width="80" height="80" style="display: block;">
                        <div style="font-size: 7.5px; font-weight: 700; color: #475569; margin-top: 2px; text-transform: uppercase;">
                            <?php echo e($invoice->eInvoice?->irn ? 'Signed e-Invoice QR' : 'Verify QR'); ?>

                        </div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div>
                    <div class="doc-title">TAX INVOICE</div>
                    <div style="font-size: 8.5px; color: #64748b; font-weight: 600;">(Under Section 31 of CGST Act)</div>
                </div>
            </div>
            <table class="meta-table">
                <tr>
                    <td class="font-bold text-right" style="width: 50%;">Invoice No:</td>
                    <td class="text-right font-mono" style="width: 50%; font-weight: 700;"><?php echo e($invoice->invoice_no); ?></td>
                </tr>
                <tr>
                    <td class="font-bold text-right">Invoice Date:</td>
                    <td class="text-right"><?php echo e($invoice->invoice_date?->format('d/m/Y')); ?></td>
                </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->due_date): ?>
                <tr>
                    <td class="font-bold text-right">Due Date:</td>
                    <td class="text-right"><?php echo e($invoice->due_date?->format('d/m/Y')); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->credit_days): ?>(<?php echo e($invoice->credit_days); ?> days)<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->reference_no || $invoice->other_references): ?>
                <tr>
                    <td class="font-bold text-right">Reference No:</td>
                    <td class="text-right"><?php echo e($invoice->other_references ?: $invoice->reference_no); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->order_no || $invoice->order?->order_no): ?>
                <tr>
                    <td class="font-bold text-right">Order No:</td>
                    <td class="text-right"><?php echo e($invoice->order_no ?: $invoice->order?->order_no); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->delivery_note_na): ?>
                <tr>
                    <td class="font-bold text-right">Delivery Note:</td>
                    <td class="text-right">Not Applicable</td>
                </tr>
                <?php elseif($invoice->delivery_note_no): ?>
                <tr>
                    <td class="font-bold text-right">Delivery Note:</td>
                    <td class="text-right"><?php echo e($invoice->delivery_note_no); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->dispatch_doc_no): ?>
                <tr>
                    <td class="font-bold text-right">Dispatch Doc No:</td>
                    <td class="text-right"><?php echo e($invoice->dispatch_doc_no); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->transport_mode && $invoice->transport_mode !== 'Not Applicable'): ?>
                <tr>
                    <td class="font-bold text-right">Transport Mode:</td>
                    <td class="text-right"><?php echo e($invoice->transport_mode); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->dispatched_through): ?>
                <tr>
                    <td class="font-bold text-right">Dispatched Through:</td>
                    <td class="text-right"><?php echo e($invoice->dispatched_through); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->destination): ?>
                <tr>
                    <td class="font-bold text-right">Destination:</td>
                    <td class="text-right"><?php echo e($invoice->destination); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->carrier_name): ?>
                <tr>
                    <td class="font-bold text-right">Carrier Name:</td>
                    <td class="text-right"><?php echo e($invoice->carrier_name); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->lr_rr_no): ?>
                <tr>
                    <td class="font-bold text-right">LR / RR No:</td>
                    <td class="text-right"><?php echo e($invoice->lr_rr_no); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->lr_rr_date): ?>(<?php echo e($invoice->lr_rr_date->format('d/m/Y')); ?>)<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->vehicle_no): ?>
                <tr>
                    <td class="font-bold text-right">Vehicle No:</td>
                    <td class="text-right"><?php echo e($invoice->vehicle_no); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->payment_terms): ?>
                <tr>
                    <td class="font-bold text-right">Payment Terms:</td>
                    <td class="text-right"><?php echo e($invoice->payment_terms); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->terms_of_delivery): ?>
                <tr>
                    <td class="font-bold text-right" style="vertical-align: top;">Terms of Delivery:</td>
                    <td class="text-right" style="white-space: pre-line; word-break: break-word; text-align: right; line-height: 1.35;"><?php echo e($invoice->terms_of_delivery); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eInvoice?->irn): ?>
                <tr>
                    <td class="font-bold text-right" style="vertical-align: top;">IRN:</td>
                    <td class="text-right font-mono" style="font-size: 8px; word-break: break-all; max-width: 200px;" title="<?php echo e($invoice->eInvoice->irn); ?>"><?php echo e($invoice->eInvoice->irn); ?></td>
                </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eInvoice->ack_no): ?>
                <tr>
                    <td class="font-bold text-right">Ack No:</td>
                    <td class="text-right font-mono"><?php echo e($invoice->eInvoice->ack_no); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eInvoice->ack_date): ?><span style="font-size: 9px; color: #64748b;">(<?php echo e($invoice->eInvoice->ack_date->format('d/m/Y')); ?>)</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eway_bill_no || $invoice->eWayBill?->eway_bill_no): ?>
                <tr>
                    <td class="font-bold text-right">E-Way Bill:</td>
                    <td class="text-right font-mono font-bold"><?php echo e($invoice->eway_bill_no ?: $invoice->eWayBill?->eway_bill_no); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eway_bill_date || $invoice->eWayBill?->ewb_date): ?><span style="font-size: 9px; font-weight: normal; color: #64748b;">(<?php echo e(($invoice->eway_bill_date ?: $invoice->eWayBill?->ewb_date)?->format('d/m/Y')); ?>)</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->transporter_name): ?>
                <tr>
                    <td class="font-bold text-right">Transporter:</td>
                    <td class="text-right"><?php echo e($invoice->transporter_name); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->transporter_id): ?><span style="font-size: 9px; color: #64748b;">(<?php echo e($invoice->transporter_id); ?>)</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Parties Grid -->
    <div class="parties-grid">
        <div class="party-col">
            <div class="box-title">Billed To (Customer Details)</div>
            <div class="party-name"><?php echo e($customer?->name); ?></div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer?->code): ?>
                <div style="font-size: 9.5px; color: #64748b; margin-bottom: 2px;">Code: <?php echo e($customer->code); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer?->gstin): ?>
                <div><strong>GSTIN:</strong> <?php echo e($customer->gstin); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer?->pan): ?>
                <div><strong>PAN:</strong> <?php echo e($customer->pan); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer?->phone): ?>
                <div>Phone: <?php echo e($customer->phone); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer?->email): ?>
                <div>Email: <?php echo e($customer->email); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div style="margin-top: 6px;">
                <div class="box-title" style="margin-bottom: 2px;">Billing Address:</div>
                <div style="white-space: pre-line;"><?php echo e($billingAddr); ?></div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->bill_to_place || $customer?->city): ?>
                    <div style="margin-top: 2px; font-size: 9px; color: #475569;"><strong>Place of Buyer:</strong> <?php echo e($invoice->bill_to_place ?: ($customer?->city ?: '—')); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->place_of_supply_state ?: $customer?->state): ?>
                    <div style="margin-top: 2px; font-size: 9px; color: #475569;"><strong>State:</strong> <?php echo e($invoice->place_of_supply_state ?: $customer?->state); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <div class="party-col">
            <div class="box-title">Shipped / Delivered To (Consignee)</div>
            <div class="party-name"><?php echo e($invoice->consignee_name ?: $customer?->name); ?></div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->consignee_gstin || $customer?->gstin): ?>
                <div><strong>GSTIN:</strong> <?php echo e($invoice->consignee_gstin ?: $customer->gstin); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div style="margin-top: 4px;">
                <div class="box-title" style="margin-bottom: 2px;">Delivery Address:</div>
                <div style="white-space: pre-line;"><?php echo e($deliveryAddr); ?></div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->ship_to_place || $invoice->destination || $customer?->city): ?>
                    <div style="margin-top: 2px; font-size: 9px; color: #475569;"><strong>Place of Delivery:</strong> <?php echo e($invoice->ship_to_place ?: ($invoice->destination ?: ($customer?->city ?: '—'))); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->delivery_state): ?>
                <div style="margin-top: 4px;"><strong>Place of Supply (State):</strong> <?php echo e($invoice->delivery_state); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->salesperson): ?>
                <div style="margin-top: 6px; font-size: 9.5px; color: #64748b;">
                    Salesperson: <?php echo e($invoice->salesperson->name); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 28px;" class="text-center">#</th>
                <th>Product Description</th>
                <th style="width: 65px;" class="text-center">HSN/Sac</th>
                <th style="width: 85px;">Batch No</th>
                <th style="width: 55px;" class="text-right">Qty</th>
                <th style="width: 45px;" class="text-center">UOM</th>
                <th style="width: 75px;" class="text-right">Rate (₹)</th>
                <th style="width: 65px;" class="text-right">Disc (₹)</th>
                <th style="width: 65px;" class="text-right">Tax (₹)</th>
                <th style="width: 90px;" class="text-right">Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $invoice->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <tr>
                    <td class="text-center"><?php echo e($idx + 1); ?></td>
                    <td>
                        <div class="font-bold"><?php echo e($item->product?->name); ?></div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product?->code): ?>
                            <div style="font-size: 9px; color: #64748b;">Code: <?php echo e($item->product->code); ?></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="text-center font-mono"><?php echo e($item->hsn_code ?? $item->product?->hsn_code ?? '—'); ?></td>
                    <td class="font-mono"><?php echo e($item->batch_no ?? '—'); ?></td>
                    <td class="text-right"><?php echo e(number_format($item->quantity, 2)); ?></td>
                    <td class="text-center"><?php echo e($item->uom?->code ?? 'PCS'); ?></td>
                    <td class="text-right"><?php echo e(number_format($item->unit_price, 2)); ?></td>
                    <td class="text-right"><?php echo e(number_format($item->discount_amount, 2)); ?></td>
                    <td class="text-right"><?php echo e(number_format($item->tax_amount, 2)); ?></td>
                    <td class="text-right font-bold"><?php echo e(number_format($item->line_total, 2)); ?></td>
                </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <tr>
                    <td colspan="10" class="text-center" style="padding: 16px;">No line items found.</td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>

    <!-- Bottom Layout (Summary, Notes, Words, QR) -->
    <div class="bottom-layout avoid-break">
        <div class="bottom-left">
            <div class="info-card">
                <div class="box-title">Amount in Words</div>
                <div class="font-bold" style="color: #1e293b; font-size: 10.5px;"><?php echo e($amountWords); ?></div>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->notes): ?>
                <div class="info-card">
                    <div class="box-title">Notes / Remarks</div>
                    <div style="white-space: pre-line;"><?php echo e($invoice->notes); ?></div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->selling_terms_and_conditions || $invoice->terms_and_conditions): ?>
                <div class="info-card">
                    <div class="box-title">Terms & Conditions</div>
                    <div style="white-space: pre-line; font-size: 9.5px; color: #475569;">
                        <?php echo e($invoice->terms_and_conditions ?: $company?->selling_terms_and_conditions); ?>

                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->terms_of_delivery): ?>
                <div class="info-card">
                    <div class="box-title">Terms of Delivery</div>
                    <div style="white-space: pre-line; font-size: 9.5px; color: #475569;">
                        <?php echo e($invoice->terms_of_delivery); ?>

                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->bank_name || $company?->upi_id): ?>
                <div class="info-card">
                    <div class="box-title">Bank & Settlement Details</div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->bank_name): ?>
                        <div><strong>Bank:</strong> <?php echo e($company->bank_name); ?> &nbsp;|&nbsp; <strong>A/C:</strong> <?php echo e($company->bank_account_no); ?> &nbsp;|&nbsp; <strong>IFSC:</strong> <?php echo e($company->bank_ifsc); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->upi_id): ?>
                        <div><strong>UPI ID:</strong> <?php echo e($company->upi_id); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eInvoice?->irn || $invoice->eWayBill?->eway_bill_no || $invoice->eway_bill_no): ?>
                <div class="info-card" style="border-left: 3px solid #4f46e5; background: #f8fafc;">
                    <div class="box-title" style="color: #4f46e5;">Government GST Portal Details</div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eInvoice?->irn): ?>
                        <div style="font-size: 9px;"><strong>IRN:</strong> <span class="font-mono" style="font-size: 8px; word-break: break-all;"><?php echo e($invoice->eInvoice->irn); ?></span></div>
                        <div style="font-size: 9px; margin-top: 1px;"><strong>Ack No:</strong> <?php echo e($invoice->eInvoice->ack_no); ?> &nbsp;|&nbsp; <strong>Ack Date:</strong> <?php echo e($invoice->eInvoice->ack_date?->format('d/m/Y H:i') ?? '—'); ?> &nbsp;|&nbsp; <strong style="color: #059669;">Status: Active / Generated</strong></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eWayBill?->eway_bill_no || $invoice->eway_bill_no): ?>
                        <div style="font-size: 9px; margin-top: 3px;">
                            <strong>e-Way Bill No:</strong> <span class="font-mono font-bold"><?php echo e($invoice->eWayBill?->eway_bill_no ?: $invoice->eway_bill_no); ?></span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eWayBill?->ewb_date || $invoice->eway_bill_date): ?>
                                &nbsp;|&nbsp; <strong>Gen Date:</strong> <?php echo e(($invoice->eWayBill?->ewb_date ?: $invoice->eway_bill_date)?->format('d/m/Y')); ?>

                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eWayBill?->valid_upto): ?>
                                &nbsp;|&nbsp; <strong>Valid Upto:</strong> <?php echo e($invoice->eWayBill->valid_upto->format('d/m/Y')); ?>

                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            &nbsp;|&nbsp; <strong style="color: #059669;">Status: Active / Generated</strong>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- QR Codes (Invoice QR & UPI QR) -->
            <div class="qr-section">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($invoiceQrDataUri)): ?>
                    <div class="qr-card">
                        <img src="<?php echo e($invoiceQrDataUri); ?>" alt="Invoice Verification" width="80" height="80" style="display:block; margin:0 auto;">
                        <div style="font-size: 8px; color: #64748b; margin-top: 3px;">Verify Document</div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($upiQrDataUri)): ?>
                    <div class="qr-card">
                        <img src="<?php echo e($upiQrDataUri); ?>" alt="Scan & Pay UPI" width="80" height="80" style="display:block; margin:0 auto;">
                        <div style="font-size: 8px; color: #64748b; margin-top: 3px;">Scan &amp; Pay UPI</div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <div class="bottom-right">
            <table class="summary-table">
                <tr>
                    <td style="width: 55%; color: #475569;">Subtotal (Taxable Amount):</td>
                    <td class="text-right font-mono" style="width: 45%;">₹<?php echo e(number_format($invoice->taxable_amount ?? $invoice->subtotal, 2)); ?></td>
                </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) $invoice->discount_amount > 0): ?>
                <tr>
                    <td style="color: #475569;">Total Discount:</td>
                    <td class="text-right font-mono text-danger">-₹<?php echo e(number_format($invoice->discount_amount, 2)); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php
                    $isIntra = ($invoice->tax_type === 'intra') || (!$invoice->tax_type && ($invoice->cgst_amount > 0 || $invoice->sgst_amount > 0));
                    $isInter = ($invoice->tax_type === 'inter') || ($invoice->igst_amount > 0);
                ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isIntra): ?>
                <tr>
                    <td style="color: #475569;">Central Tax (CGST):</td>
                    <td class="text-right font-mono">₹<?php echo e(number_format($invoice->cgst_amount, 2)); ?></td>
                </tr>
                <tr>
                    <td style="color: #475569;">State Tax (SGST):</td>
                    <td class="text-right font-mono">₹<?php echo e(number_format($invoice->sgst_amount, 2)); ?></td>
                </tr>
                <?php elseif($isInter): ?>
                <tr>
                    <td style="color: #475569;">Integrated Tax (IGST):</td>
                    <td class="text-right font-mono">₹<?php echo e(number_format($invoice->igst_amount, 2)); ?></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td style="color: #475569;">Total Tax (GST):</td>
                    <td class="text-right font-mono">₹<?php echo e(number_format($invoice->tax_amount, 2)); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->round_off !== null && (float)$invoice->round_off != 0): ?>
                <tr>
                    <td style="color: #475569;">Round Off:</td>
                    <td class="text-right font-mono"><?php echo e((float)$invoice->round_off > 0 ? '+' : ''); ?>₹<?php echo e(number_format($invoice->round_off, 2)); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <tr class="grand-total-row">
                    <td>Grand Total:</td>
                    <td class="text-right font-mono">₹<?php echo e(number_format($invoice->grand_total, 2)); ?></td>
                </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->scheme && (float) $invoice->scheme_benefit_amount > 0): ?>
                <tr>
                    <td style="color: #4f46e5;">Scheme Rebate (<?php echo e($invoice->scheme->name); ?>):</td>
                    <td class="text-right font-mono" style="color: #059669; font-weight: 600;">₹<?php echo e(number_format($invoice->scheme_benefit_amount, 2)); ?> <span style="font-size: 9px; color: #64748b; font-weight: normal;">(via credit note)</span></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) $invoice->paid_amount > 0): ?>
                <tr>
                    <td style="color: #475569;">Paid Amount:</td>
                    <td class="text-right font-mono">₹<?php echo e(number_format($invoice->paid_amount, 2)); ?></td>
                </tr>
                <tr>
                    <td class="font-bold" style="color: #0f172a;">Balance Due:</td>
                    <td class="text-right font-mono font-bold">₹<?php echo e(number_format($invoice->grand_total - $invoice->paid_amount, 2)); ?></td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </table>

            <div class="sign-area">
                <div class="sign-box">
                    <span class="sign-line">Customer's Signature / Stamp</span>
                </div>
                <div class="sign-box">
                    <span class="sign-line">For <?php echo e($companyName); ?><br>Authorized Signatory</span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

<script>
function openConfigureModal() {
    var m = document.getElementById('configureModal');
    if (m) m.style.display = 'flex';
}
function closeConfigureModal() {
    var m = document.getElementById('configureModal');
    if (m) m.style.display = 'none';
}

<?php if(!empty($autoPrint)): ?>
window.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        window.print();
    }, 400);
});
<?php endif; ?>
</script>
</body>
</html>

<?php /**PATH D:\xampp\htdocs\DMS\resources\views/sales/invoices/preview.blade.php ENDPATH**/ ?>