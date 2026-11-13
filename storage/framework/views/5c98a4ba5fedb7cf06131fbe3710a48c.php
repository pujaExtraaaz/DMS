<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Purchase Invoice - <?php echo e($invoice->invoice_no); ?></title>

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

        .toolbar-left, .toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toolbar a,
        .toolbar button {
            border: 1px solid #d1d5db;
            background: #fff;
            color: #111827;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .toolbar .primary {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #fff;
        }

        .toolbar .btn-success {
            background: #059669;
            border-color: #059669;
            color: #fff;
        }

        .invoice {
            max-width: 1000px;
            margin: 0 auto 30px;
            background: #fff;
            border: 1px solid #d1d5db;
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
        .header-right {
            display: table-cell;
            vertical-align: top;
        }

        .header-left {
            width: 60%;
        }

        .header-right {
            width: 40%;
            text-align: right;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #4f46e5;
            text-transform: uppercase;
        }

        .meta-table,
        .party-table,
        .items-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 3px 0;
        }

        .party-table {
            margin-top: 10px;
            border: 1px solid #e5e7eb;
        }

        .party-table td {
            width: 50%;
            padding: 10px 12px;
            vertical-align: top;
        }

        .party-box-title {
            font-size: 11px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .items-table th,
        .items-table td,
        .summary-table td,
        .summary-table th {
            border: 1px solid #e5e7eb;
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

        .notes-box {
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            min-height: 95px;
            margin-bottom: 10px;
        }

        .sign-area {
            margin-top: 36px;
            display: table;
            width: 100%;
        }

        .sign-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding-top: 40px;
            border-top: 1px dashed #9ca3af;
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

<?php
    $companyName = $company?->name ?? config('app.name');
    $supplier = $invoice->supplier;
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($isPdf)): ?>
<div class="toolbar no-print">
    <div class="toolbar-left">
        <a href="<?php echo e(route('purchasing.invoices.show', $invoice)); ?>">
            Back to Invoice
        </a>
        <a href="<?php echo e(route('purchasing.invoices.index')); ?>">
            Invoice Listing
        </a>
    </div>

    <div class="toolbar-right">
        <button class="primary" onclick="window.print()">
            Print Invoice
        </button>
        <a href="<?php echo e(route('purchasing.invoices.export', ['invoice' => $invoice, 'format' => 'pdf'])); ?>" class="primary">
            PDF
        </a>
        <a href="<?php echo e(route('purchasing.invoices.export', ['invoice' => $invoice, 'format' => 'xlsx'])); ?>" class="btn-success">
            Excel (.xlsx)
        </a>
        <a href="<?php echo e(route('purchasing.invoices.export', ['invoice' => $invoice, 'format' => 'csv'])); ?>">
            CSV
        </a>
    </div>
</div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<div class="invoice">

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->notes): ?>
        <div style="margin: 0 0 10px; padding: 8px 10px; background: #fef3c7; border: 2px solid #d97706; border-left: 6px solid #b45309; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
            <div style="font-size: 10px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #92400e;">Remarks &amp; Additional Delivery</div>
            <div style="margin-top: 3px; font-size: 13px; font-weight: 800; color: #1c1917; white-space: pre-line;"><?php echo e($invoice->notes); ?></div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="header">
        <div class="header-left">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->logo_url): ?>
                <div style="margin-bottom: 8px;">
                    <img src="<?php echo e($company->logo_url); ?>" alt="<?php echo e($companyName); ?>" style="max-height: 52px; max-width: 180px; object-fit: contain;">
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($invoiceQrDataUri)): ?>
                <div style="margin-bottom: 8px;">
                    <img
                        src="<?php echo e($invoiceQrDataUri); ?>"
                        alt="Scan to view invoice"
                        width="90"
                        height="90"
                        style="border:1px solid #ccc;"
                    >
                    <div style="font-size:8px; color:#6b7280; margin-top:2px;">
                        Scan to view invoice
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div class="company-name"><?php echo e($companyName); ?></div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->gstin): ?>
                <div>GSTIN: <?php echo e($company->gstin); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->address): ?>
                <div><?php echo e($company->address); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->state): ?>
                <div>
                    <?php echo e($company->state); ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->pincode): ?>
                        - <?php echo e($company->pincode); ?>

                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->phone): ?>
                <div>Phone: <?php echo e($company->phone); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="header-right">
            <div class="invoice-title">Purchase Invoice</div>

            <table class="meta-table" style="margin-top: 8px; width: 100%;">
                <tr>
                    <td class="bold right" style="width: 50%;">Invoice No:</td>
                    <td class="right" style="width: 50%;"><?php echo e($invoice->invoice_no); ?></td>
                </tr>

                <tr>
                    <td class="bold right">Date:</td>
                    <td class="right"><?php echo e(optional($invoice->invoice_date)->format('d/m/Y')); ?></td>
                </tr>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->purchase_receive_date): ?>
                    <tr>
                        <td class="bold right">Receive Date:</td>
                        <td class="right"><?php echo e(optional($invoice->purchase_receive_date)->format('d/m/Y')); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->payment_order_date): ?>
                    <tr>
                        <td class="bold right">Payment Order Date:</td>
                        <td class="right"><?php echo e(optional($invoice->payment_order_date)->format('d/m/Y')); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->due_date): ?>
                    <tr>
                        <td class="bold right">Due Date:</td>
                        <td class="right"><?php echo e(optional($invoice->due_date)->format('d/m/Y')); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->credit_days): ?><span style="color:#6b7280; font-size:11px;">(<?php echo e($invoice->credit_days); ?> days)</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->supplier_invoice_no): ?>
                    <tr>
                        <td class="bold right">Supplier Inv No:</td>
                        <td class="right"><?php echo e($invoice->supplier_invoice_no); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->supplier_invoice_date): ?>
                    <tr>
                        <td class="bold right">Supplier Inv Date:</td>
                        <td class="right"><?php echo e(optional($invoice->supplier_invoice_date)->format('d/m/Y')); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->purchaseOrder): ?>
                    <tr>
                        <td class="bold right">PO Ref:</td>
                        <td class="right"><?php echo e($invoice->purchaseOrder->po_number); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->grn): ?>
                    <tr>
                        <td class="bold right">GRN Ref:</td>
                        <td class="right"><?php echo e($invoice->grn->grn_number); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </table>
        </div>
    </div>

    <table class="party-table">
        <tr>
            <td>
                <div class="party-box-title">Vendor / Supplier Details</div>
                <div class="bold" style="font-size: 13px;"><?php echo e($supplier?->name ?? 'N/A'); ?></div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($supplier?->gstin): ?>
                    <div>GSTIN: <?php echo e($supplier->gstin); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($supplier?->phone): ?>
                    <div>Phone: <?php echo e($supplier->phone); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($supplier?->email): ?>
                    <div>Email: <?php echo e($supplier->email); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($invoice->billing_address)): ?>
                    <div style="margin-top: 5px;">
                        <span class="bold">Billing Address:</span><br>
                        <?php echo nl2br(e($invoice->billing_address)); ?>

                    </div>
                <?php elseif($supplier?->address): ?>
                    <div>Address: <?php echo e($supplier->address); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($supplier?->state): ?>
                        <div>
                            <?php echo e($supplier->state); ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($supplier?->pincode): ?>
                                - <?php echo e($supplier->pincode); ?>

                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($invoice->shipping_address) && $invoice->shipping_address !== $invoice->billing_address): ?>
                    <div style="margin-top: 5px;">
                        <span class="bold">Delivery / Dispatch Address:</span><br>
                        <?php echo nl2br(e($invoice->shipping_address)); ?>

                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </td>

            <td style="border-left: 1px solid #e5e7eb;">
                <div class="party-box-title">Billed To / Delivered To</div>
                <div class="bold" style="font-size: 13px;"><?php echo e($companyName); ?></div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->gstin): ?>
                    <div>GSTIN: <?php echo e($company->gstin); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->address): ?>
                    <div><?php echo e($company->address); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->state): ?>
                    <div>
                        <?php echo e($company->state); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->pincode): ?>
                            - <?php echo e($company->pincode); ?>

                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->warehouse): ?>
                    <div style="margin-top: 6px;">
                        <span class="bold">Warehouse:</span>
                        <?php echo e($invoice->warehouse->name); ?>

                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="center">#</th>
                <th style="width: 28%;">Product / Item</th>
                <th style="width: 10%;">HSN</th>
                <th style="width: 8%;" class="right">Qty</th>
                <th style="width: 10%;" class="right">Rate</th>
                <th style="width: 8%;" class="right">Disc</th>
                <th style="width: 10%;" class="right">Taxable</th>
                <th style="width: 6%;" class="right">GST%</th>
                <th style="width: 8%;" class="right">Tax</th>
                <th style="width: 8%;" class="right">Total</th>
            </tr>
        </thead>

        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $invoice->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php
                    $productName = $item->product?->name ?? $item->description ?? 'Item';
                    $lineTax = (float) $item->cgst_amount + (float) $item->sgst_amount + (float) $item->igst_amount;
                    $serialNumbers = $item->serialNumbers();
                    $batchText = $item->batch_no ?: $item->batch_number;
                ?>
                <tr>
                    <td class="center"><?php echo e($index + 1); ?></td>
                    <td>
                        <div class="bold"><?php echo e($productName); ?></div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($batchText): ?>
                            <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">Batch: <?php echo e($batchText); ?></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($serialNumbers)): ?>
                            <div style="font-size: 10px; color: #4338ca; margin-top: 2px;">
                                <span class="bold" style="color: #3730a3;">Serial No:</span> <?php echo e(implode(', ', $serialNumbers)); ?>

                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td><?php echo e($item->product?->hsn_code ?? $item->hsn_code ?? '-'); ?></td>
                    <td class="right"><?php echo e(number_format((float) $item->quantity, 2)); ?></td>
                    <td class="right"><?php echo e(number_format((float) ($item->unit_cost ?? $item->unit_price), 2)); ?></td>
                    <td class="right"><?php echo e(number_format((float) ($item->purchase_discount_percent ?? $item->discount_amount), 2)); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->purchase_discount_percent): ?>%<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td>
                    <td class="right"><?php echo e(number_format((float) $item->taxable_amount, 2)); ?></td>
                    <td class="right"><?php echo e(number_format((float) ($item->tax_percent ?? $item->gst_rate), 1)); ?>%</td>
                    <td class="right"><?php echo e(number_format($lineTax, 2)); ?></td>
                    <td class="right bold"><?php echo e(number_format((float) ($item->line_total ?? $item->total_amount), 2)); ?></td>
                </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <tr>
                    <td colspan="10" class="center">No items found on this invoice.</td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>

    <div class="bottom-grid">
        <div class="bottom-left">
            <div class="notes-box" style="min-height: auto; margin-bottom: 8px;">
                <div class="bold" style="margin-bottom: 4px;">Amount in Words</div>
                <div style="font-weight: 700; color: #111827;">
                    <?php echo e(\App\Support\DocumentExporter::numberToIndianWords($invoice->total_amount ?? $invoice->grand_total)); ?>

                </div>
            </div>

            <div class="notes-box">
                <div class="bold" style="margin-bottom: 4px;">Terms &amp; Conditions</div>
                <div style="color: #4b5563; font-size: 11px;">
                    <?php echo e($company?->purchase_terms_and_conditions ?: 'Payment is due per agreed credit terms. All disputes subject to local jurisdiction.'); ?>

                </div>

            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->bank_name || $company?->bank_account_no): ?>
                <div style="font-size: 11px; color: #4b5563;">
                    <span class="bold">Bank:</span> <?php echo e($company->bank_name ?? '-'); ?> &nbsp;|&nbsp;
                    <span class="bold">A/C:</span> <?php echo e($company->bank_account_no ?? '-'); ?> &nbsp;|&nbsp;
                    <span class="bold">IFSC:</span> <?php echo e($company->bank_ifsc ?? '-'); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="bottom-right">
            <table class="summary-table">
                <tr>
                    <td>Subtotal:</td>
                    <td class="right"><?php echo e(number_format((float) $invoice->subtotal, 2)); ?></td>
                </tr>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) $invoice->discount_amount > 0): ?>
                    <tr>
                        <td>Discount:</td>
                        <td class="right">-<?php echo e(number_format((float) $invoice->discount_amount, 2)); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <tr>
                    <td>Taxable Amount:</td>
                    <td class="right"><?php echo e(number_format((float) $invoice->taxable_amount, 2)); ?></td>
                </tr>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) $invoice->cgst_amount > 0): ?>
                    <tr>
                        <td>CGST:</td>
                        <td class="right"><?php echo e(number_format((float) $invoice->cgst_amount, 2)); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) $invoice->sgst_amount > 0): ?>
                    <tr>
                        <td>SGST:</td>
                        <td class="right"><?php echo e(number_format((float) $invoice->sgst_amount, 2)); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) $invoice->igst_amount > 0): ?>
                    <tr>
                        <td>IGST:</td>
                        <td class="right"><?php echo e(number_format((float) $invoice->igst_amount, 2)); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) $invoice->tcs_amount > 0): ?>
                    <tr>
                        <td>TCS:</td>
                        <td class="right"><?php echo e(number_format((float) $invoice->tcs_amount, 2)); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) $invoice->round_off != 0): ?>
                    <tr>
                        <td>Round Off:</td>
                        <td class="right"><?php echo e(number_format((float) $invoice->round_off, 2)); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) ($invoice->freight_charge ?? 0) > 0): ?>
                    <tr>
                        <td>Freight / Landed Charge:</td>
                        <td class="right"><?php echo e(number_format((float) $invoice->freight_charge, 2)); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) ($invoice->other_charges ?? 0) > 0): ?>
                    <tr>
                        <td>Other Charges:</td>
                        <td class="right"><?php echo e(number_format((float) $invoice->other_charges, 2)); ?></td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <tr class="section-title">
                    <td class="bold" style="font-size: 13px;">Grand Total:</td>
                    <td class="right bold" style="font-size: 13px;">
                        INR <?php echo e(number_format((float) ($invoice->grand_total ?: $invoice->total_amount), 2)); ?>

                    </td>
                </tr>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((float) $invoice->paid_amount > 0): ?>
                    <tr>
                        <td>Paid Amount:</td>
                        <td class="right"><?php echo e(number_format((float) $invoice->paid_amount, 2)); ?></td>
                    </tr>

                    <tr>
                        <td class="bold">Balance Due:</td>
                        <td class="right bold">
                            INR <?php echo e(number_format((float) $invoice->outstanding_amount, 2)); ?>

                        </td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </table>
        </div>
    </div>

    <div class="sign-area">
        <div class="sign-box" style="padding-right: 20px;">
            Prepared By
        </div>

        <div class="sign-box" style="padding-left: 20px;">
            Authorized Signatory / Received By
        </div>
    </div>

</div>

</body>
</html><?php /**PATH D:\xampp\htdocs\DMS\resources\views/purchasing/invoices/preview.blade.php ENDPATH**/ ?>