<?php $__env->startSection('title', 'E-Way Bill - '.$invoice->invoice_no); ?>

<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'E-Way Bill','description' => $invoice->invoice_no]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'E-Way Bill','description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($invoice->invoice_no)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

     <?php $__env->slot('actions', null, []); ?> 
        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('invoices.show', $invoice)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('invoices.show', $invoice))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Back to Invoice <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'button','variant' => 'primary','onclick' => 'window.print()']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','variant' => 'primary','onclick' => 'window.print()']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Print E-Way Bill <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
     <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal91a231a9270579fa1ae9246bd51fb785)): ?>
<?php $attributes = $__attributesOriginal91a231a9270579fa1ae9246bd51fb785; ?>
<?php unset($__attributesOriginal91a231a9270579fa1ae9246bd51fb785); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal91a231a9270579fa1ae9246bd51fb785)): ?>
<?php $component = $__componentOriginal91a231a9270579fa1ae9246bd51fb785; ?>
<?php unset($__componentOriginal91a231a9270579fa1ae9246bd51fb785); ?>
<?php endif; ?>

<div class="mx-auto max-w-4xl">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($eWayBill->status === 'cancelled'): ?>
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 print:border-rose-400">
            <div class="flex items-center gap-2 font-semibold text-rose-900">
                <svg class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                This E-Way Bill was CANCELLED
            </div>
            <div class="mt-2 text-xs text-rose-700 grid grid-cols-1 md:grid-cols-3 gap-2">
                <div><span class="font-medium text-rose-900">Cancelled At:</span> <?php echo e(optional($eWayBill->cancelled_at)->format('d M Y, h:i A') ?? '—'); ?></div>
                <div><span class="font-medium text-rose-900">Reason:</span> <?php echo e($eWayBill->cancel_reason ?? '—'); ?></div>
                <div><span class="font-medium text-rose-900">Remarks:</span> <?php echo e($eWayBill->cancel_remarks ?? '—'); ?></div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div id="eway-document" class="rounded-xl border border-slate-300 bg-white p-8 text-sm text-slate-900 shadow-sm print:border-none print:p-0 print:shadow-none">
        
        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-5">
            <div class="flex items-start gap-4">
                <?php
                    $qrContent = $eWayBill->qr_token
                        ? route('eway-bill.qr', ['token' => $eWayBill->qr_token])
                        : 'EWB:'.$eWayBill->eway_bill_no.'|DOC:'.$invoice->invoice_no.'|VAL:'.$invoice->grand_total;
                    $qrUri = \App\Support\QrCodeRenderer::dataUri($qrContent, 160);
                ?>
                <div class="flex flex-col items-center">
                    <img
                        src="<?php echo e($qrUri); ?>"
                        alt="E-Way Bill QR Code"
                        width="88"
                        height="88"
                        class="border border-slate-300 rounded p-1"
                    />
                    <span class="text-[9px] text-slate-500 mt-1 uppercase tracking-wider font-semibold">Verify EWB</span>
                </div>
                <div>
                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-slate-900 text-white rounded">
                        Electronic Way Bill System
                    </span>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight mt-1">E-WAY BILL</h1>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Generated against Tax Invoice <span class="font-mono font-bold text-slate-800"><?php echo e($invoice->invoice_no); ?></span>
                    </p>
                    <div class="mt-2 text-xs">
                        <span class="text-slate-500">GSTIN / Supplier:</span>
                        <span class="font-semibold text-slate-800"><?php echo e($company?->gstin ?? config('app.company_gstin', '—')); ?></span>
                    </div>
                </div>
            </div>

            <div class="text-right">
                <div class="inline-block px-2.5 py-1 rounded text-xs font-bold <?php echo e($eWayBill->status === 'cancelled' ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300'); ?>">
                    <?php echo e($eWayBill->status === 'cancelled' ? 'CANCELLED' : 'ACTIVE'); ?>

                </div>
                <div class="mt-2">
                    <div class="text-xs text-slate-500 font-medium">E-Way Bill No.</div>
                    <div class="text-lg font-mono font-extrabold text-slate-900 tracking-wider select-all"><?php echo e($eWayBill->eway_bill_no); ?></div>
                </div>
                <div class="mt-1 text-xs text-slate-600">
                    <div><span class="text-slate-500">Generated:</span> <?php echo e(optional($eWayBill->created_at)->format('d/m/Y h:i A')); ?></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($eWayBill->valid_until): ?>
                        <div class="font-semibold text-indigo-700"><span class="text-slate-500 font-normal">Valid Until:</span> <?php echo e($eWayBill->valid_until->format('d/m/Y h:i A')); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>

        
        <div class="mt-5 border border-slate-300 rounded-lg overflow-hidden">
            <div class="bg-slate-100 px-4 py-2 border-b border-slate-300 font-bold text-xs uppercase tracking-wider text-slate-700 flex justify-between">
                <span>PART - A (Consignment & Party Details)</span>
                <span class="font-normal text-slate-500">Tax Invoice / Supply</span>
            </div>
            <table class="w-full text-xs text-left divide-y divide-slate-200">
                <tbody class="divide-y divide-slate-200">
                    <tr class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200">
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">1. GSTIN of Supplier:</span>
                            <span class="font-mono font-bold text-slate-800"><?php echo e($company?->gstin ?? config('app.company_gstin', '—')); ?></span>
                            <div class="text-slate-700 mt-0.5"><?php echo e($company?->name ?? config('app.company_name', 'DMS ERP')); ?></div>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">2. Place of Dispatch:</span>
                            <div class="text-slate-800"><?php echo e($eWayBill->from_state ?? ($company?->state ?? '—')); ?> - Pincode: <?php echo e($eWayBill->from_pincode ?? ($company?->pincode ?? '—')); ?></div>
                            <div class="text-slate-600 text-[11px] mt-0.5"><?php echo e($company?->address); ?></div>
                        </td>
                    </tr>
                    <tr class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200">
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">3. GSTIN of Recipient:</span>
                            <span class="font-mono font-bold text-slate-800"><?php echo e($invoice->customer?->shipping_gstin ?: ($invoice->customer?->gstin ?: 'URP')); ?></span>
                            <div class="text-slate-700 mt-0.5"><?php echo e($invoice->customer?->shipping_name ?: $invoice->customer?->name); ?></div>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">4. Place of Delivery:</span>
                            <div class="text-slate-800"><?php echo e($eWayBill->to_state ?? ($invoice->customer?->shipping_state ?: ($invoice->customer?->state ?: '—'))); ?> - Pincode: <?php echo e($eWayBill->to_pincode ?? ($invoice->customer?->shipping_pincode ?: ($invoice->customer?->pincode ?: '—'))); ?></div>
                            <div class="text-slate-600 text-[11px] mt-0.5"><?php echo e($invoice->customer?->shipping_address ?: ($invoice->customer?->address ?: '—')); ?></div>
                        </td>
                    </tr>
                    <tr class="grid grid-cols-2 md:grid-cols-4 divide-x divide-slate-200 bg-slate-50/50">
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">5. Document No.:</span>
                            <span class="font-mono font-bold text-slate-800"><?php echo e($invoice->invoice_no); ?></span>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">6. Document Date:</span>
                            <span class="text-slate-800"><?php echo e($invoice->invoice_date?->format('d/m/Y')); ?></span>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">7. Consignment Value:</span>
                            <span class="font-bold text-indigo-700">₹<?php echo e(number_format($invoice->grand_total, 2)); ?></span>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">8. Reason for Transport:</span>
                            <span class="text-slate-800">Supply / Sale</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        
        <div class="mt-5 border border-slate-300 rounded-lg overflow-hidden">
            <div class="bg-slate-100 px-4 py-2 border-b border-slate-300 font-bold text-xs uppercase tracking-wider text-slate-700 flex justify-between">
                <span>PART - B (Vehicle & Transportation Details)</span>
                <span class="font-normal text-slate-500">Mode: <?php echo e(ucfirst($invoice->transport_mode ?? 'Road')); ?></span>
            </div>
            <table class="w-full text-xs text-left divide-y divide-slate-200">
                <tbody class="divide-y divide-slate-200">
                    <tr class="grid grid-cols-2 md:grid-cols-4 divide-x divide-slate-200">
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">Transport Mode:</span>
                            <span class="font-bold text-slate-800"><?php echo e(ucfirst($invoice->transport_mode ?? 'Road')); ?></span>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">Vehicle Number:</span>
                            <span class="font-mono font-bold text-slate-800 text-sm"><?php echo e($invoice->vehicle_no ?: ($eWayBill->vehicle_no ?: '—')); ?></span>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">Vehicle Type:</span>
                            <span class="text-slate-800"><?php echo e($invoice->vehicle_type === 'O' || ($eWayBill->vehicle_type ?? '') === 'O' ? 'Over Dimensional Cargo (ODC)' : 'Regular (R)'); ?></span>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">Approx Distance:</span>
                            <span class="font-bold text-slate-800"><?php echo e($invoice->distance_km ?? ($eWayBill->distance_km ?? 0)); ?> KM</span>
                        </td>
                    </tr>
                    <tr class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-200 bg-slate-50/50">
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">Transporter Name:</span>
                            <span class="text-slate-800"><?php echo e($invoice->transporter_name ?: ($invoice->carrier_name ?: '—')); ?></span>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">Transporter ID / GSTIN:</span>
                            <span class="font-mono text-slate-800"><?php echo e($invoice->transporter_id ?: '—'); ?></span>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold text-slate-500 block mb-0.5">Doc / LR / RR No. & Date:</span>
                            <span class="text-slate-800 font-mono"><?php echo e($invoice->lr_rr_no ?: ($invoice->dispatch_doc_no ?: ($eWayBill->doc_no ?: '—'))); ?></span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->lr_rr_date): ?>
                                <span class="text-slate-500 text-[11px]">(<?php echo e($invoice->lr_rr_date->format('d/m/Y')); ?>)</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        
        <div class="mt-5 border border-slate-300 rounded-lg overflow-hidden">
            <div class="bg-slate-100 px-4 py-2 border-b border-slate-300 font-bold text-xs uppercase tracking-wider text-slate-700">
                Item Details
            </div>
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                    <tr>
                        <th class="px-3 py-2 text-center w-10">#</th>
                        <th class="px-3 py-2">Product Description</th>
                        <th class="px-3 py-2 text-center">HSN Code</th>
                        <th class="px-3 py-2 text-right">Quantity</th>
                        <th class="px-3 py-2 text-right">Taxable Value (₹)</th>
                        <th class="px-3 py-2 text-right">Tax Rate</th>
                        <th class="px-3 py-2 text-right">Total (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $invoice->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <tr>
                            <td class="px-3 py-2 text-center text-slate-400 font-mono"><?php echo e($idx + 1); ?></td>
                            <td class="px-3 py-2 font-medium text-slate-800">
                                <?php echo e($item->product?->name); ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->product?->code): ?>
                                    <span class="text-slate-400 text-[10px]">(<?php echo e($item->product->code); ?>)</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="px-3 py-2 text-center font-mono text-slate-600"><?php echo e($item->product?->hsn_code ?: '—'); ?></td>
                            <td class="px-3 py-2 text-right font-mono text-slate-700"><?php echo e(number_format($item->quantity, 2)); ?> <?php echo e($item->uom?->code); ?></td>
                            <td class="px-3 py-2 text-right font-mono text-slate-700">₹<?php echo e(number_format($item->taxable_amount ?? ($item->quantity * $item->unit_price), 2)); ?></td>
                            <td class="px-3 py-2 text-right font-mono text-slate-600"><?php echo e(number_format($item->gst_rate ?? 18, 0)); ?>%</td>
                            <td class="px-3 py-2 text-right font-mono font-semibold text-slate-900">₹<?php echo e(number_format($item->total_amount ?? ($item->quantity * $item->unit_price), 2)); ?></td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </tbody>
                <tfoot class="bg-slate-50 border-t border-slate-300 font-semibold text-slate-800">
                    <tr>
                        <td colspan="4" class="px-3 py-2 text-right">Total Consignment Value:</td>
                        <td class="px-3 py-2 text-right font-mono">₹<?php echo e(number_format($invoice->subtotal, 2)); ?></td>
                        <td class="px-3 py-2 text-right font-mono">Tax: ₹<?php echo e(number_format($invoice->tax_amount, 2)); ?></td>
                        <td class="px-3 py-2 text-right font-mono text-indigo-700 text-sm">₹<?php echo e(number_format($invoice->grand_total, 2)); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        
        <div class="mt-6 pt-4 border-t border-slate-200 flex items-center justify-between text-[11px] text-slate-500">
            <div>This is a computer-generated document and authentic proof of E-Way Bill generation under Rule 138 of CGST Rules, 2017.</div>
            <div class="font-mono">Page 1 of 1</div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/sales/invoices/e-way-bill.blade.php ENDPATH**/ ?>