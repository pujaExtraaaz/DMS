<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>e-Invoice - <?php echo e($invoice->invoice_no); ?></title>
    <style>
        @page { size: A4; margin: 8mm; }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
            background: #f3f4f6;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #111;
            line-height: 1.5;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px 16px;
            box-sizing: border-box;
        }
        .invoice-box {
            width: 100%;
            max-width: 1000px;
            min-height: 100%;
            border: 1px solid #111;
            padding: 12px 14px 14px;
            box-sizing: border-box;
            background: #fff;
        }
        .header-table { width: 100%; border-bottom: 2px solid #000; padding-bottom: 14px; margin-bottom: 14px; }
        .header-title { font-size: 22px; font-weight: bold; text-align: center; text-transform: uppercase; margin: 10px 0; }
        .sub-header { font-size: 9px; text-align: right; color: #374151; margin-bottom: 8px; }
        .recipient-box { border: 1px solid #000; padding: 4px 8px; font-weight: bold; font-size: 10px; display: inline-block; float: right; }
        .clear { clear: both; }
        .grid-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9px; }
        .grid-table td, .grid-table th { border: 1px solid #111; padding: 6px 7px; vertical-align: top; }
        .bg-gray { background-color: #f3f4f6; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        @media print {
            html, body {
                background: #fff;
                padding: 0;
            }
            body {
                display: block;
                padding: 0;
            }
            .invoice-box {
                max-width: none;
                width: 100%;
                border: none;
            }
        }
    </style>
</head>
<body>
    <?php
        $company = $company ?? \App\Domains\Organization\Models\Company::query()->first();
        $companyName = $company?->name ?? config('app.name');
        $companyGstin = $company?->gstin ?? 'Not configured';
        $companyAddress = $company?->address ?? 'Address not configured';
        $companyState = $company?->state ?? 'NA';
        $companyPincode = $company?->pincode ?? '-';
        $customer = $invoice->customer;
    ?>
    <div class="invoice-box">
        <div class="sub-header">
            <span class="recipient-box">Original For Recipient</span>
            Schema version: 1.0 &nbsp;|&nbsp; Tax scheme: GST
        </div>
        <div class="clear"></div>

        <table class="header-table">
            <tr>
                <td style="width: 20%;">
                   <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($invoiceQrDataUri)): ?>
                        <img
                            src="<?php echo e($invoiceQrDataUri); ?>"
                            alt="Scan to view invoice"
                            width="90"
                            height="90"
                            style="border:1px solid #ccc;"
                        >
                        <div style="font-size:8px; color:#6b7280; text-align:center; margin-top:2px;width:90px;">
                            Scan to view invoice
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </td>
                <td style="width: 60%; text-align: center;">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->logo_url): ?>
                        <div style="margin-bottom: 4px;">
                            <img src="<?php echo e($company->logo_url); ?>" alt="<?php echo e($companyName); ?>" style="max-height: 44px; max-width: 160px; object-fit: contain;">
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div style="font-size: 9px; color: #4b5563;">e-Invoice System</div>
                    <div class="header-title">e-Invoice</div>
                    <div style="font-size: 10px; font-weight: bold;">TAX INVOICE</div>
                </td>
                <td style="width: 25%; text-align: right; vertical-align: bottom; font-size: 8.5px;">
                    <div>Date: <?php echo e($invoice->invoice_date->format('d/m/Y')); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->salesperson): ?><div>Salesperson: <?php echo e($invoice->salesperson->name); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->order_no || $invoice->order?->order_no): ?><div>Order No: <?php echo e($invoice->order_no ?: $invoice->order?->order_no); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->reference_no || $invoice->other_references): ?><div>Ref: <?php echo e($invoice->other_references ?: $invoice->reference_no); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->delivery_note_na): ?><div>Delivery Note: N/A</div>
                    <?php elseif($invoice->delivery_note_no): ?><div>Delivery Note: <?php echo e($invoice->delivery_note_no); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->dispatch_doc_no): ?><div>Dispatch Doc: <?php echo e($invoice->dispatch_doc_no); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->transport_mode && $invoice->transport_mode !== 'Not Applicable'): ?><div>Mode: <?php echo e($invoice->transport_mode); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->dispatched_through): ?><div>Dispatched Via: <?php echo e($invoice->dispatched_through); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->transporter_name): ?><div>Transporter: <?php echo e($invoice->transporter_name); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->transporter_id): ?>(<?php echo e($invoice->transporter_id); ?>)<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->destination): ?><div>Destination: <?php echo e($invoice->destination); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->carrier_name): ?><div>Carrier: <?php echo e($invoice->carrier_name); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->lr_rr_no): ?><div>LR/RR No: <?php echo e($invoice->lr_rr_no); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->lr_rr_date): ?>(<?php echo e($invoice->lr_rr_date->format('d/m/Y')); ?>)<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->vehicle_no): ?><div>Vehicle: <?php echo e($invoice->vehicle_no); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->payment_terms): ?><div>Payment Terms: <?php echo e($invoice->payment_terms); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->terms_of_delivery): ?><div style="margin-top: 2px; line-height: 1.25;"><span class="bold">Terms of Delivery:</span><br><?php echo nl2br(e($invoice->terms_of_delivery)); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </td>
            </tr>
        </table>

        <table class="grid-table">
            <tr>
                <td style="width: 50%;">
                    <div><span class="bold">1. GSTIN:</span> <?php echo e($companyGstin); ?></div>
                    <div><span class="bold">2. Name:</span> <?php echo e($companyName); ?></div>
                    <div><span class="bold">3. Address:</span> <?php echo e($companyAddress); ?></div>
                    <div><span class="bold">4. Serial No. of Invoice:</span> <?php echo e($invoice->invoice_no); ?></div>
                    <div><span class="bold">5. Date of Invoice:</span> <?php echo e($invoice->invoice_date->format('d/m/Y')); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eInvoice?->irn): ?>
                        <div><span class="bold">6. IRN No.:</span> <span style="font-size: 7.5px; font-family: monospace; word-break: break-all;"><?php echo e($invoice->eInvoice->irn); ?></span></div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eInvoice->ack_no): ?>
                            <div><span class="bold">Ack No. & Date:</span> <?php echo e($invoice->eInvoice->ack_no); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eInvoice->ack_date): ?>(<?php echo e($invoice->eInvoice->ack_date->format('d/m/Y')); ?>)<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php else: ?>
                        <div><span class="bold">6. IRN No.:</span> <?php echo e($invoice->einvoice_status === 'not_applicable' ? 'N/A (B2C Supply)' : 'Not Generated'); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eway_bill_no || $invoice->eWayBill?->eway_bill_no): ?>
                        <div><span class="bold">E-Way Bill No.:</span> <span style="font-family: monospace;"><?php echo e($invoice->eway_bill_no ?: $invoice->eWayBill->eway_bill_no); ?></span> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eway_bill_date || $invoice->eWayBill?->ewb_date): ?><span style="font-size: 8px;">(<?php echo e(($invoice->eway_bill_date ?: $invoice->eWayBill?->ewb_date)->format('d/m/Y')); ?>)</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </td>
                <td style="width: 50%;">
                    <div><span class="bold">Dispatch from:</span> <?php echo e($invoice->consignor_name ?: $companyName); ?> (<?php echo e($invoice->consignor_gstin ?: $companyGstin); ?>)</div>
                    <div><span class="bold">Address:</span> <?php echo e($invoice->consignor_address_line_1 ?: $companyAddress); ?></div>
                    <div><span class="bold">State:</span> <?php echo e($invoice->consignor_state ?: $companyState); ?> &nbsp;&nbsp; <span class="bold">Pincode:</span> <?php echo e($invoice->consignor_pincode ?: $companyPincode); ?></div>
                </td>
            </tr>
        </table>

        <table class="grid-table">
            <tr class="bg-gray">
                <th style="width: 50%;">Details Of Receiver (Billed To)</th>
                <th style="width: 50%;">Details Of Consignee (Shipped To)</th>
            </tr>
            <tr>
                <td>
                    <div><span class="bold">Name:</span> <?php echo e($invoice->billingAddress?->contact_person ?: $customer->name); ?></div>
                    <div><span class="bold">Address:</span> <?php echo nl2br(e($invoice->billing_address ?: ($customer->address ?: '-'))); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->bill_to_place): ?><div><span class="bold">Place:</span> <?php echo e($invoice->bill_to_place); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div><span class="bold">State:</span> <?php echo e($invoice->billingAddress?->state ?: ($customer->state ?: '-')); ?> &nbsp;&nbsp; <span class="bold">Pin Code:</span> <?php echo e($invoice->billingAddress?->pincode ?: ($customer->pincode ?: '-')); ?></div>
                    <div><span class="bold">GSTIN/Unique ID:</span> <?php echo e($invoice->billingAddress?->gstin ?: ($customer->gstin ?: '-')); ?></div>
                </td>
                <td>
                    <div><span class="bold">Name:</span> <?php echo e($invoice->consignee_name ?: ($invoice->shippingAddress?->contact_person ?: ($customer->shipping_name ?: $customer->name))); ?></div>
                    <div><span class="bold">Address:</span> <?php echo nl2br(e($invoice->shipping_address ?: ($invoice->billing_address ?: ($customer->shipping_address ?: ($customer->address ?: '-'))))); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->ship_to_place): ?><div><span class="bold">Place:</span> <?php echo e($invoice->ship_to_place); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div><span class="bold">State:</span> <?php echo e($invoice->consignee_state ?: ($invoice->shippingAddress?->state ?: ($invoice->delivery_state ?: ($customer->shipping_state ?: ($customer->state ?: '-'))))); ?> &nbsp;&nbsp; <span class="bold">Pin Code:</span> <?php echo e($invoice->consignee_pincode ?: ($invoice->shippingAddress?->pincode ?: ($customer->shipping_pincode ?: ($customer->pincode ?: '-')))); ?></div>
                    <div><span class="bold">GSTIN/Unique ID:</span> <?php echo e($invoice->consignee_gstin ?: ($invoice->shippingAddress?->gstin ?: ($customer->shipping_gstin ?: ($customer->gstin ?: '-')))); ?></div>
                </td>
            </tr>
        </table>

        <table class="grid-table">
            <tr class="bg-gray">
                <th style="width: 4%;">#</th>
                <th style="width: 32%;">Item Description</th>
                <th style="width: 10%;">HSN</th>
                <th style="width: 8%;" class="text-right">Qty</th>
                <th style="width: 10%;" class="text-right">Unit Price</th>
                <th style="width: 8%;" class="text-right">Discount</th>
                <th style="width: 8%;" class="text-right">Taxable</th>
                <th style="width: 10%;" class="text-right">GST Rate</th>
                <th style="width: 10%;" class="text-right">Total</th>
            </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $invoice->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <tr>
                    <td class="text-center"><?php echo e($i + 1); ?></td>
                    <td>
                        <div class="bold"><?php echo e($item->item_name); ?></div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->batch_no): ?><div style="color: #4b5563;">Batch: <?php echo e($item->batch_no); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php $printedSerials = is_array($item->serial_numbers ?? null) ? $item->serial_numbers : []; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($printedSerials !== []): ?><div style="color: #4b5563;">S/N: <?php echo e(implode(', ', $printedSerials)); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td><?php echo e($item->hsn_code ?: '-'); ?></td>
                    <td class="text-right"><?php echo e(number_format($item->quantity, 0)); ?></td>
                    <td class="text-right"><?php echo e(number_format($item->unit_price, 2)); ?></td>
                    <td class="text-right"><?php echo e(number_format($item->discount_amount, 2)); ?></td>
                    <td class="text-right"><?php echo e(number_format($item->taxable_amount, 2)); ?></td>
                    <td class="text-right"><?php echo e(number_format($item->gst_rate, 2)); ?>%</td>
                    <td class="text-right bold"><?php echo e(number_format($item->total_amount, 2)); ?></td>
                </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </table>

        <table class="grid-table">
            <tr class="bg-gray">
                <th class="text-right">Taxable Amount</th>
                <th class="text-right">Cgst</th>
                <th class="text-right">Sgst</th>
                <th class="text-right">Igst</th>
                <th class="text-right">Round Off</th>
                <th class="text-right">Invoice Total</th>
            </tr>
            <tr>
                <td class="text-right"><?php echo e(number_format($invoice->taxable_amount ?? $invoice->subtotal ?? 0, 2)); ?></td>
                <td class="text-right"><?php echo e(number_format($invoice->cgst_amount ?? 0, 2)); ?></td>
                <td class="text-right"><?php echo e(number_format($invoice->sgst_amount ?? 0, 2)); ?></td>
                <td class="text-right"><?php echo e(number_format($invoice->igst_amount ?? 0, 2)); ?></td>
                <td class="text-right"><?php echo e(number_format($invoice->round_off ?? 0, 2)); ?></td>
                <td class="text-right bold" style="font-size: 11px;">INR <?php echo e(number_format($invoice->grand_total ?? $invoice->total_amount ?? 0, 2)); ?></td>
            </tr>
        </table>

        <div style="font-size: 9px; margin-bottom: 12px;">
            <span class="bold">Amount in Words:</span>
            <?php echo e(\App\Support\DocumentExporter::numberToIndianWords($invoice->grand_total ?? $invoice->total_amount ?? 0)); ?>

        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->scheme && (float) $invoice->scheme_benefit_amount > 0): ?>
        <div style="font-size: 9px; margin-bottom: 8px; color: #4338ca;">
            <span class="bold">Scheme:</span> <?php echo e($invoice->scheme->name); ?> (<?php echo e($invoice->scheme->code); ?>) &bull;
            <span class="bold">Rebate Benefit:</span> INR <?php echo e(number_format($invoice->scheme_benefit_amount, 2)); ?>

            <span style="color: #6b7280;">(Settled via Credit Note)</span>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <table class="grid-table" style="margin-bottom: 0;">
            <tr>
                <td style="width: 50%;">
                    <div class="bold">Bank Details:</div>
                    <div>Bank: <?php echo e($company?->bank_name ?: 'HDFC Bank'); ?></div>
                    <div>A/C: <?php echo e($company?->bank_account_no ?: '-'); ?></div>
                    <div>IFSC: <?php echo e($company?->bank_ifsc ?: '-'); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company?->upi_id): ?><div>UPI: <?php echo e($company->upi_id); ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div style="margin-top: 6px;" class="bold">Terms &amp; Conditions:</div>
                    <div style="color: #4b5563;"><?php echo e($company?->selling_terms_and_conditions ?: '1. Subject to local jurisdiction. 2. Goods once sold will not be taken back.'); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->terms_of_delivery): ?>
                        <div style="margin-top: 4px; font-size: 8px; line-height: 1.35; color: #374151;">
                            <span class="bold">Terms of Delivery:</span><br>
                            <?php echo nl2br(e($invoice->terms_of_delivery)); ?>

                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </td>
                <td style="width: 50%; text-align: right; vertical-align: bottom;">
                    <div style="margin-bottom: 40px;">For <span class="bold"><?php echo e($companyName); ?></span></div>
                    <div class="bold">Authorized Signatory</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html><?php /**PATH D:\xampp\htdocs\DMS\resources\views/sales/invoices/pdf.blade.php ENDPATH**/ ?>