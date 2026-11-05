<?php $__env->startSection('title', $invoice->invoice_no); ?>
<?php $__env->startSection('content'); ?>
<div x-data="{
    cancelEInvoiceModal: false,
    cancelEwayModal: false,
    generateEwayModal: false,
    updateVehicleModal: false,
    printSettingsModal: false,
    printCopyType: 'all',
    printCopies: 3,
    cancelReason: '1',
    cancelRemarks: '',
    ewayCancelReason: '1',
    ewayCancelRemarks: '',
    vehicleNo: <?php echo \Illuminate\Support\Js::from($invoice->vehicle_no ?? '')->toHtml() ?>,
    vehicleType: <?php echo \Illuminate\Support\Js::from($invoice->vehicle_type ?? 'R')->toHtml() ?>,
    vehicleReason: 'Breakdown / Transshipment',
    distanceKm: <?php echo \Illuminate\Support\Js::from($invoice->distance_km ?? 50)->toHtml() ?>,
    transportMode: <?php echo \Illuminate\Support\Js::from($invoice->transport_mode ?? '1')->toHtml() ?>,
    transporterId: <?php echo \Illuminate\Support\Js::from($invoice->transporter_id ?? '')->toHtml() ?>,
    transporterName: <?php echo \Illuminate\Support\Js::from($invoice->transporter_name ?? '')->toHtml() ?>,
}">

<?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => $invoice->invoice_no,'description' => $invoice->customer->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($invoice->invoice_no),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($invoice->customer->name)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

     <?php $__env->slot('actions', null, []); ?> 
        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('invoices.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('invoices.index'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Back <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('invoices.edit', $invoice)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('invoices.edit', $invoice))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Edit <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
        <button type="button" @click="printSettingsModal = true" class="inline-flex items-center px-3.5 py-2 border border-slate-300 shadow-sm text-sm font-semibold rounded-lg text-slate-700 bg-white hover:bg-slate-50 transition">
            <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        <div class="relative inline-block text-left" x-data="{ exportOpen: false }">
            <button type="button" @click="exportOpen = !exportOpen" @click.away="exportOpen = false" class="inline-flex items-center px-3.5 py-2 border border-slate-300 shadow-sm text-sm font-semibold rounded-lg text-slate-700 bg-white hover:bg-slate-50 transition">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
                <svg class="w-3 h-3 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="exportOpen" class="origin-top-right absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 py-1" style="display: none;">
                <a href="<?php echo e(route('invoices.export', ['invoice' => $invoice, 'format' => 'pdf'])); ?>" class="flex items-center px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-100 font-medium">
                    <svg class="w-4 h-4 mr-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF Document
                </a>
                <a href="<?php echo e(route('invoices.export', ['invoice' => $invoice, 'format' => 'xlsx'])); ?>" class="flex items-center px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-100 font-medium">
                    <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel (.xlsx)
                </a>
                <a href="<?php echo e(route('invoices.export', ['invoice' => $invoice, 'format' => 'csv'])); ?>" class="flex items-center px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-100 font-medium">
                    <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    CSV File
                </a>
            </div>
        </div>
        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('invoices.preview', $invoice)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('invoices.preview', $invoice))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Preview <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('invoices.e-invoice.document', $invoice)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('invoices.e-invoice.document', $invoice))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
E-Invoice Doc <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('invoices.eway.document', $invoice)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('invoices.eway.document', $invoice))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
E-Way Bill Doc <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
        <form method="POST" action="<?php echo e(route('payment-links.create', $invoice)); ?>" class="inline"><?php echo csrf_field(); ?><?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','variant' => 'secondary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'secondary']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Payment Link <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?></form>
        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'primary','href' => route('payments.create', ['invoice_id' => $invoice->id])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('payments.create', ['invoice_id' => $invoice->id]))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Record Payment <?php echo $__env->renderComponent(); ?>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['class' => 'lg:col-span-2','title' => 'Items']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'lg:col-span-2','title' => 'Items']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <?php if (isset($component)) { $__componentOriginal793d2b22631f88b8a3d00569a12acf88 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal793d2b22631f88b8a3d00569a12acf88 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.table','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

             <?php $__env->slot('head', null, []); ?> 
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Product</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Price</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Total</th>
                </tr>
             <?php $__env->endSlot(); ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $invoice->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <tr>
                    <td class="px-6 py-4 text-sm"><?php echo e($item->product?->name ?? $item->item_name); ?></td>
                    <td class="px-6 py-4 text-sm text-right font-mono"><?php echo e($item->quantity); ?> <?php echo e($item->uom?->code); ?></td>
                    <td class="px-6 py-4 text-sm text-right font-mono">₹<?php echo e(number_format($item->unit_price, 2)); ?></td>
                    <td class="px-6 py-4 text-sm text-right font-mono font-bold">₹<?php echo e(number_format($item->line_total, 2)); ?></td>
                </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal793d2b22631f88b8a3d00569a12acf88)): ?>
<?php $attributes = $__attributesOriginal793d2b22631f88b8a3d00569a12acf88; ?>
<?php unset($__attributesOriginal793d2b22631f88b8a3d00569a12acf88); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal793d2b22631f88b8a3d00569a12acf88)): ?>
<?php $component = $__componentOriginal793d2b22631f88b8a3d00569a12acf88; ?>
<?php unset($__componentOriginal793d2b22631f88b8a3d00569a12acf88); ?>
<?php endif; ?>

        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            <h4 class="text-xs font-bold tracking-wider text-slate-500 mb-2 uppercase">Addresses</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="p-3 bg-white rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase text-slate-500">Billing Address</span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->bill_to_place): ?>
                            <span class="text-[11px] font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded">Place: <?php echo e($invoice->bill_to_place); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="mt-1 font-medium text-slate-800 whitespace-pre-line text-xs"><?php echo e($invoice->billing_address ?: ($invoice->customer->address ?: '—')); ?></div>
                </div>
                <div class="p-3 bg-white rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase text-slate-500">Delivery Address</span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->ship_to_place): ?>
                            <span class="text-[11px] font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded">Place: <?php echo e($invoice->ship_to_place); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="mt-1 font-medium text-slate-800 whitespace-pre-line text-xs"><?php echo e($invoice->shipping_address ?: ($invoice->billing_address ?: ($invoice->customer->shipping_address ?: ($invoice->customer->address ?: '—')))); ?></div>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-slate-100 bg-white">
            <h4 class="text-xs font-bold tracking-wider text-slate-700 uppercase mb-3">Delivery, Dispatch &amp; Order Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                    <div class="font-bold text-slate-700 border-b border-slate-200 pb-1">Delivery Note</div>
                    <div><span class="text-slate-500">Delivery Note No:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->delivery_note_na ? 'Not Applicable' : ($invoice->delivery_note_no ?: '—')); ?></span></div>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                    <div class="font-bold text-slate-700 border-b border-slate-200 pb-1">Dispatch Details</div>
                    <div><span class="text-slate-500">Doc No:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->dispatch_doc_no ?: '—'); ?></span></div>
                    <div><span class="text-slate-500">Dispatched Through:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->dispatched_through ?: ($invoice->transport_mode ?: '—')); ?></span></div>
                    <div><span class="text-slate-500">Destination:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->destination ?: '—'); ?></span></div>
                    <div><span class="text-slate-500">Carrier / Agent:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->carrier_name ?: '—'); ?></span></div>
                    <div><span class="text-slate-500">Bill of Lading / LR:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->lr_rr_no ?: '—'); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->lr_rr_date): ?>(<?php echo e($invoice->lr_rr_date->format('d/m/Y')); ?>)<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></span></div>
                    <div><span class="text-slate-500">Motor Vehicle:</span> <span class="font-mono font-medium text-slate-800"><?php echo e($invoice->vehicle_no ?: '—'); ?></span></div>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                    <div class="font-bold text-slate-700 border-b border-slate-200 pb-1">Order Details</div>
                    <div><span class="text-slate-500">Order No(s):</span> <span class="font-medium text-slate-800"><?php echo e($invoice->order_no ?: ($invoice->order?->order_no ?: '—')); ?></span></div>
                    <div><span class="text-slate-500">Payment Terms:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->payment_terms ?: '—'); ?></span></div>
                    <div><span class="text-slate-500">Other References:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->other_references ?: ($invoice->reference_no ?: '—')); ?></span></div>
                    <div class="pt-1">
                        <span class="text-slate-500 block mb-0.5">Terms of Delivery:</span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->terms_of_delivery): ?>
                            <div class="font-medium text-slate-800 whitespace-pre-line bg-white p-2 rounded border border-slate-200 text-xs leading-relaxed"><?php echo e($invoice->terms_of_delivery); ?></div>
                        <?php else: ?>
                            <span class="font-medium text-slate-800">—</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->consignor_name || $invoice->consignee_name || $invoice->eway_bill_no || $invoice->transporter_name): ?>
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            <h4 class="text-xs font-bold tracking-wider text-slate-700 uppercase mb-3">Consignor, Consignee &amp; Transport Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                
                <div class="p-3 bg-white rounded-lg border border-slate-200 space-y-1">
                    <div class="font-bold text-slate-800 border-b border-slate-100 pb-1 flex items-center justify-between">
                        <span>Consignor (From - Seller)</span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->consignor_gstin): ?>
                            <span class="font-mono text-[10px] text-indigo-700"><?php echo e($invoice->consignor_gstin); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="font-medium text-slate-900"><?php echo e($invoice->consignor_name ?: ($invoice->company?->name ?? '—')); ?></div>
                    <div class="text-slate-600">
                        <?php echo e($invoice->consignor_address_line_1); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->consignor_address_line_2): ?>, <?php echo e($invoice->consignor_address_line_2); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="text-slate-600">
                        <?php echo e($invoice->consignor_city); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->consignor_city && $invoice->consignor_state): ?>, <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?><?php echo e($invoice->consignor_state); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->consignor_pincode): ?> - <?php echo e($invoice->consignor_pincode); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                
                <div class="p-3 bg-white rounded-lg border border-slate-200 space-y-1">
                    <div class="font-bold text-slate-800 border-b border-slate-100 pb-1 flex items-center justify-between">
                        <span>Consignee (To - Delivery)</span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->consignee_gstin): ?>
                            <span class="font-mono text-[10px] text-emerald-700"><?php echo e($invoice->consignee_gstin); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="font-medium text-slate-900"><?php echo e($invoice->consignee_name ?: ($invoice->customer->name ?? '—')); ?></div>
                    <div class="text-slate-600">
                        <?php echo e($invoice->consignee_address_line_1 ?: ($invoice->shipping_address ?: '—')); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->consignee_address_line_2): ?>, <?php echo e($invoice->consignee_address_line_2); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="text-slate-600">
                        <?php echo e($invoice->consignee_city); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->consignee_city && $invoice->consignee_state): ?>, <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?><?php echo e($invoice->consignee_state); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->consignee_pincode): ?> - <?php echo e($invoice->consignee_pincode); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                
                <div class="p-3 bg-white rounded-lg border border-slate-200 space-y-1">
                    <div class="font-bold text-slate-800 border-b border-slate-100 pb-1 flex items-center justify-between">
                        <span>Transport &amp; e-Way Bill</span>
                        <span class="font-semibold text-[10px] text-slate-500 uppercase"><?php echo e($invoice->eway_supply_type ?: 'Outward'); ?></span>
                    </div>
                    <div><span class="text-slate-500">e-Way Bill No:</span> <span class="font-mono font-bold text-indigo-700"><?php echo e($invoice->eway_bill_no ?: ($invoice->eWayBill?->eway_bill_no ?: '—')); ?></span></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eway_bill_date || $invoice->eWayBill?->ewb_date): ?>
                        <div><span class="text-slate-500">Date:</span> <span class="font-medium text-slate-800"><?php echo e(($invoice->eway_bill_date ?: $invoice->eWayBill?->ewb_date)?->format('d/m/Y')); ?></span></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div><span class="text-slate-500">Transporter:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->transporter_name ?: '—'); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->transporter_id): ?>(<?php echo e($invoice->transporter_id); ?>)<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></span></div>
                    <div><span class="text-slate-500">Mode:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->transport_mode ?: '—'); ?></span></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->lr_rr_no): ?>
                        <div><span class="text-slate-500">Doc / LR-RR:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->lr_rr_no); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->lr_rr_date): ?>(<?php echo e($invoice->lr_rr_date->format('d/m/Y')); ?>)<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></span></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div><span class="text-slate-500">Distance:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->distance_km ? $invoice->distance_km . ' KM' : '—'); ?></span></div>
                    <div><span class="text-slate-500">Vehicle Type:</span> <span class="font-medium text-slate-800"><?php echo e($invoice->vehicle_type === 'O' ? 'Over Dimensional Cargo (ODC)' : 'Regular Cargo (R)'); ?></span></div>
                </div>
            </div>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $attributes = $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $component = $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>

    
    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Summary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Summary']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt>Status</dt><dd><?php if (isset($component)) { $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.badge','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e(ucfirst($invoice->status)); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $attributes = $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $component = $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?></dd></div>
            <div class="flex justify-between"><dt>Date</dt><dd><?php echo e($invoice->invoice_date->format('d M Y')); ?></dd></div>
            <div class="flex justify-between"><dt>Salesperson</dt><dd class="font-medium text-slate-800"><?php echo e($invoice->salesperson?->name ?? '—'); ?></dd></div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->price_type): ?>
                <div class="flex justify-between"><dt>Pricing</dt><dd class="font-medium text-slate-700 capitalize"><?php echo e($invoice->price_type); ?> Tax</dd></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->scheme): ?>
                <div class="flex justify-between border-t pt-2"><dt class="text-slate-500">Scheme</dt><dd class="font-medium text-indigo-700"><?php echo e($invoice->scheme->name); ?> (<?php echo e($invoice->scheme->code); ?>)</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Scheme Rebate</dt><dd class="font-semibold text-emerald-600">₹<?php echo e(number_format($invoice->scheme_benefit_amount, 2)); ?></dd></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div class="flex justify-between border-t pt-2"><dt class="text-slate-500">Subtotal</dt><dd class="font-mono font-medium">₹<?php echo e(number_format($invoice->subtotal, 2)); ?></dd></div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->taxable_amount !== null && (float)$invoice->taxable_amount > 0): ?>
                <div class="flex justify-between"><dt class="text-slate-500">Taxable Value</dt><dd class="font-mono font-medium">₹<?php echo e(number_format($invoice->taxable_amount, 2)); ?></dd></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($invoice->tax_type ?? '') === 'intra' || (!$invoice->tax_type && ($invoice->cgst_amount > 0 || $invoice->sgst_amount > 0))): ?>
                <div class="flex justify-between"><dt class="text-slate-500">CGST</dt><dd class="font-mono font-medium">₹<?php echo e(number_format($invoice->cgst_amount, 2)); ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">SGST</dt><dd class="font-mono font-medium">₹<?php echo e(number_format($invoice->sgst_amount, 2)); ?></dd></div>
            <?php elseif(($invoice->tax_type ?? '') === 'inter' || $invoice->igst_amount > 0): ?>
                <div class="flex justify-between"><dt class="text-slate-500">IGST</dt><dd class="font-mono font-medium">₹<?php echo e(number_format($invoice->igst_amount, 2)); ?></dd></div>
            <?php else: ?>
                <div class="flex justify-between"><dt class="text-slate-500">Tax</dt><dd class="font-mono font-medium">₹<?php echo e(number_format($invoice->tax_amount, 2)); ?></dd></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($invoice->round_off) && (float)$invoice->round_off != 0): ?>
                <div class="flex justify-between"><dt class="text-slate-500">Round Off</dt><dd class="font-mono font-medium">₹<?php echo e(number_format($invoice->round_off, 2)); ?></dd></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div class="flex justify-between border-t pt-2"><dt class="font-bold text-slate-800">Grand Total</dt><dd class="font-bold font-mono text-indigo-700">₹<?php echo e(number_format($invoice->grand_total, 2)); ?></dd></div>
            <div class="flex justify-between"><dt>Paid</dt><dd class="font-mono">₹<?php echo e(number_format($invoice->paid_amount, 2)); ?></dd></div>
            <div class="flex justify-between"><dt>Outstanding</dt><dd class="font-mono font-semibold text-rose-600">₹<?php echo e(number_format($invoice->grand_total - $invoice->paid_amount, 2)); ?></dd></div>
        </dl>
        <div class="mt-4 space-y-2">
            <form method="POST" action="<?php echo e(route('communications.send-invoice', $invoice)); ?>"><?php echo csrf_field(); ?><?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','variant' => 'ghost','class' => 'w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'ghost','class' => 'w-full']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Send WhatsApp <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?></form>
            <form method="POST" action="<?php echo e(route('communications.send-reminder', $invoice)); ?>"><?php echo csrf_field(); ?><?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','variant' => 'ghost','class' => 'w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'ghost','class' => 'w-full']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Send Reminder <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?></form>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $attributes = $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $component = $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
</div>


<div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">

    
    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-sm font-bold text-slate-800">GST E-Invoice (IRN)</h3>
            </div>
            <?php
                $eiStatus = $invoice->eInvoice?->status ?? $invoice->einvoice_status ?? 'pending';
                if ($invoice->eInvoice && $invoice->eInvoice->status === 'cancelled') $eiStatus = 'cancelled';
                elseif ($invoice->eInvoice && in_array($invoice->eInvoice->status, ['generated', 'active'])) $eiStatus = 'generated';
                elseif (!($invoice->eInvoice) && ($eInvoiceEligibility['status'] ?? '') === 'not_applicable') $eiStatus = 'not_applicable';
            ?>
            <div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($eiStatus === 'generated'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                        ● Generated (Active)
                    </span>
                <?php elseif($eiStatus === 'cancelled'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                        ✕ Cancelled
                    </span>
                <?php elseif($eiStatus === 'failed'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                        ⚠ Generation Failed
                    </span>
                <?php elseif($eiStatus === 'not_applicable'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                        Not Applicable
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                        Pending Generation
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <div class="mt-4 space-y-3 text-xs">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($eiStatus === 'generated' && $invoice->eInvoice): ?>
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2">
                    <div>
                        <span class="text-slate-500 block text-[11px] font-medium">Invoice Reference Number (IRN):</span>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <span class="font-mono font-bold text-slate-900 break-all text-xs"><?php echo e($invoice->eInvoice->irn); ?></span>
                            <button type="button" onclick="navigator.clipboard.writeText('<?php echo e($invoice->eInvoice->irn); ?>'); alert('IRN copied to clipboard!');"
                                    class="text-indigo-600 hover:text-indigo-800 font-semibold shrink-0 text-[11px]">Copy</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200">
                        <div>
                            <span class="text-slate-500 block text-[11px]">Ack No:</span>
                            <span class="font-mono font-semibold text-slate-800"><?php echo e($invoice->eInvoice->ack_no ?: '—'); ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Ack Date:</span>
                            <span class="font-semibold text-slate-800"><?php echo e($invoice->eInvoice->ack_date?->format('d M Y, h:i A') ?? '—'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                    <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('invoices.e-invoice.document', $invoice),'class' => 'text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('invoices.e-invoice.document', $invoice)),'class' => 'text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        View / Print E-Invoice
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                    <button type="button" @click="cancelEInvoiceModal = true"
                            class="px-3 py-1.5 rounded-lg border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-semibold transition">
                        Cancel IRN
                    </button>
                </div>
            <?php elseif($eiStatus === 'cancelled' && $invoice->eInvoice): ?>
                <div class="p-3 bg-rose-50 rounded-lg border border-rose-200 text-rose-800 space-y-1">
                    <div class="font-bold">E-Invoice Cancelled</div>
                    <div class="text-[11px]">Cancelled On: <?php echo e($invoice->eInvoice->cancelled_at?->format('d M Y, h:i A') ?? '—'); ?></div>
                    <div class="text-[11px]">Reason: <span class="font-semibold"><?php echo e($invoice->eInvoice->cancel_reason ?? 'Cancelled'); ?></span></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eInvoice->cancel_remarks): ?>
                        <div class="text-[11px]">Remarks: <?php echo e($invoice->eInvoice->cancel_remarks); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php elseif($eiStatus === 'failed'): ?>
                <div class="p-3 bg-red-50 rounded-lg border border-red-200 text-red-800 space-y-1">
                    <div class="font-bold">IRN Generation Failed</div>
                    <div class="text-[11px]"><?php echo e($invoice->eInvoice?->last_error ?: 'An error occurred with the IRP portal.'); ?></div>
                </div>
                <div class="pt-2">
                    <form method="POST" action="<?php echo e(route('invoices.e-invoice', $invoice)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','variant' => 'primary','class' => 'w-full text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary','class' => 'w-full text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Retry E-Invoice Generation
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                    </form>
                </div>
            <?php elseif($eiStatus === 'not_applicable'): ?>
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-slate-600 space-y-1">
                    <div class="font-semibold text-slate-800">E-Invoice Not Required</div>
                    <div class="text-[11px] leading-relaxed"><?php echo e($eInvoiceEligibility['reason'] ?? 'B2C / Unregistered Customer — E-Invoicing applies only to B2B supplies under Rule 48(4).'); ?></div>
                </div>
            <?php else: ?>
                <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-amber-800 space-y-1">
                    <div class="font-semibold text-amber-900">E-Invoice Pending Generation</div>
                    <div class="text-[11px] leading-relaxed"><?php echo e($eInvoiceEligibility['reason'] ?? 'Eligible B2B supply. Ready for IRP registration.'); ?></div>
                </div>
                <div class="pt-2">
                    <form method="POST" action="<?php echo e(route('invoices.e-invoice', $invoice)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','variant' => 'primary','class' => 'w-full text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary','class' => 'w-full text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Generate E-Invoice (IRN)
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                    </form>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $attributes = $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $component = $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>

    
    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <h3 class="text-sm font-bold text-slate-800">GST E-Way Bill</h3>
            </div>
            <?php
                $ewbStatus = $invoice->eWayBill?->status ?? $invoice->eway_bill_status ?? 'pending';
                if ($invoice->eWayBill && $invoice->eWayBill->status === 'cancelled') $ewbStatus = 'cancelled';
                elseif ($invoice->eWayBill && in_array($invoice->eWayBill->status, ['generated', 'active'])) $ewbStatus = 'generated';
                elseif (!($invoice->eWayBill) && ($eWayBillEligibility['status'] ?? '') === 'not_applicable') $ewbStatus = 'not_applicable';
            ?>
            <div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ewbStatus === 'generated'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                        ● Generated (Active)
                    </span>
                <?php elseif($ewbStatus === 'cancelled'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                        ✕ Cancelled
                    </span>
                <?php elseif($ewbStatus === 'failed'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                        ⚠ Generation Failed
                    </span>
                <?php elseif($ewbStatus === 'not_applicable'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                        Not Applicable
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                        Pending Generation
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <div class="mt-4 space-y-3 text-xs">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ewbStatus === 'generated' && $invoice->eWayBill): ?>
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2">
                    <div>
                        <span class="text-slate-500 block text-[11px] font-medium">E-Way Bill Number (EBN):</span>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <span class="font-mono font-bold text-slate-900 text-sm"><?php echo e($invoice->eWayBill->eway_bill_no); ?></span>
                            <button type="button" onclick="navigator.clipboard.writeText('<?php echo e($invoice->eWayBill->eway_bill_no); ?>'); alert('E-Way Bill number copied!');"
                                    class="text-indigo-600 hover:text-indigo-800 font-semibold text-[11px]">Copy</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200">
                        <div>
                            <span class="text-slate-500 block text-[11px]">Generated Date:</span>
                            <span class="font-semibold text-slate-800"><?php echo e($invoice->eWayBill->ewb_date?->format('d M Y, h:i A') ?? '—'); ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Valid Until:</span>
                            <span class="font-semibold text-emerald-700"><?php echo e($invoice->eWayBill->valid_upto?->format('d M Y, h:i A') ?? '—'); ?></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200">
                        <div>
                            <span class="text-slate-500 block text-[11px]">Vehicle No:</span>
                            <span class="font-mono font-semibold text-slate-800"><?php echo e($invoice->eWayBill->vehicle_no ?: ($invoice->vehicle_no ?: '—')); ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Distance:</span>
                            <span class="font-semibold text-slate-800"><?php echo e($invoice->eWayBill->distance_km ?: ($invoice->distance_km ?: '—')); ?> KM</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                    <div class="flex items-center gap-2">
                        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('invoices.eway.document', $invoice),'class' => 'text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('invoices.eway.document', $invoice)),'class' => 'text-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            View / Print E-Way Bill
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                        <button type="button" @click="updateVehicleModal = true"
                                class="px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                            Update Vehicle
                        </button>
                    </div>
                    <button type="button" @click="cancelEwayModal = true"
                            class="px-3 py-1.5 rounded-lg border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-semibold transition">
                        Cancel E-Way Bill
                    </button>
                </div>
            <?php elseif($ewbStatus === 'cancelled' && $invoice->eWayBill): ?>
                <div class="p-3 bg-rose-50 rounded-lg border border-rose-200 text-rose-800 space-y-1">
                    <div class="font-bold">E-Way Bill Cancelled</div>
                    <div class="text-[11px]">Cancelled On: <?php echo e($invoice->eWayBill->cancelled_at?->format('d M Y, h:i A') ?? '—'); ?></div>
                    <div class="text-[11px]">Reason: <span class="font-semibold"><?php echo e($invoice->eWayBill->cancel_reason ?? 'Cancelled'); ?></span></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->eWayBill->cancel_remarks): ?>
                        <div class="text-[11px]">Remarks: <?php echo e($invoice->eWayBill->cancel_remarks); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php elseif($ewbStatus === 'failed'): ?>
                <div class="p-3 bg-red-50 rounded-lg border border-red-200 text-red-800 space-y-1">
                    <div class="font-bold">E-Way Bill Generation Failed</div>
                    <div class="text-[11px]"><?php echo e($invoice->eWayBill?->last_error ?: 'An error occurred with the E-Way Bill portal.'); ?></div>
                </div>
                <div class="pt-2">
                    <button type="button" @click="generateEwayModal = true"
                            class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 transition">
                        Retry E-Way Bill Generation
                    </button>
                </div>
            <?php elseif($ewbStatus === 'not_applicable'): ?>
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-slate-600 space-y-1">
                    <div class="font-semibold text-slate-800">E-Way Bill Not Required</div>
                    <div class="text-[11px] leading-relaxed"><?php echo e($eWayBillEligibility['reason'] ?? 'Consignment value is below the ₹50,000 threshold under GST Rule 138.'); ?></div>
                </div>
                <div class="pt-2">
                    <button type="button" @click="generateEwayModal = true"
                            class="w-full inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 transition">
                        + Generate Voluntarily
                    </button>
                </div>
            <?php else: ?>
                <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-amber-800 space-y-1">
                    <div class="font-semibold text-amber-900">E-Way Bill Pending Generation</div>
                    <div class="text-[11px] leading-relaxed"><?php echo e($eWayBillEligibility['reason'] ?? 'Mandatory under Rule 138 (consignment value exceeds ₹50,000).'); ?></div>
                </div>
                <div class="pt-2">
                    <button type="button" @click="generateEwayModal = true"
                            class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 transition">
                        Generate E-Way Bill
                    </button>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $attributes = $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $component = $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>

    
    <div x-show="generateEwayModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60" @click="generateEwayModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form method="POST" action="<?php echo e(route('invoices.eway', $invoice)); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-slate-800">Generate GST E-Way Bill</h4>
                        <button type="button" @click="generateEwayModal = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                    </div>
                    <div class="p-5 space-y-3.5 text-xs">
                        <p class="text-slate-600 text-[11px]">Confirm or update movement details for Tax Invoice <strong class="text-slate-800"><?php echo e($invoice->invoice_no); ?></strong> (₹<?php echo e(number_format($invoice->grand_total, 2)); ?>).</p>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Transport Mode *</label>
                            <select name="transport_mode" x-model="transportMode" class="w-full rounded-lg border-gray-300 text-xs">
                                <option value="1">1 - Road</option>
                                <option value="2">2 - Rail</option>
                                <option value="3">3 - Air</option>
                                <option value="4">4 - Ship</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Vehicle No. (For Road)</label>
                                <input type="text" name="vehicle_no" x-model="vehicleNo" placeholder="E.g. MH-12-AB-1234" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Vehicle Type</label>
                                <select name="vehicle_type" x-model="vehicleType" class="w-full rounded-lg border-gray-300 text-xs">
                                    <option value="R">Regular (R)</option>
                                    <option value="O">Over Dimensional Cargo (O)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Distance (KM) *</label>
                                <input type="number" min="1" max="9999" name="distance_km" x-model.number="distanceKm" required class="w-full rounded-lg border-gray-300 text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Transporter ID / GSTIN</label>
                                <input type="text" name="transporter_id" x-model="transporterId" placeholder="15-digit GSTIN" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Transporter Name</label>
                            <input type="text" name="transporter_name" x-model="transporterName" placeholder="E.g. SafeX Logistics" class="w-full rounded-lg border-gray-300 text-xs">
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'button','variant' => 'secondary','@click' => 'generateEwayModal = false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','variant' => 'secondary','@click' => 'generateEwayModal = false']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Cancel <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','variant' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Generate E-Way Bill <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div x-show="cancelEInvoiceModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60" @click="cancelEInvoiceModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form method="POST" action="<?php echo e(route('invoices.e-invoice.cancel', $invoice)); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-rose-700">Cancel E-Invoice (IRN)</h4>
                        <button type="button" @click="cancelEInvoiceModal = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                    </div>
                    <div class="p-5 space-y-3 text-xs">
                        <p class="text-slate-600 text-[11px]">Under GST rules, an IRN can be cancelled within 24 hours of generation. Once cancelled, it cannot be regenerated with the same invoice number.</p>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Cancellation Reason *</label>
                            <select name="cancel_reason" x-model="cancelReason" required class="w-full rounded-lg border-gray-300 text-xs">
                                <option value="1">1 - Duplicate</option>
                                <option value="2">2 - Data entry mistake</option>
                                <option value="3">3 - Order cancelled</option>
                                <option value="4">4 - Others</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Remarks (Min 3 chars) *</label>
                            <textarea name="cancel_remarks" x-model="cancelRemarks" rows="3" required placeholder="Enter reason for cancellation..." class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'button','variant' => 'secondary','@click' => 'cancelEInvoiceModal = false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','variant' => 'secondary','@click' => 'cancelEInvoiceModal = false']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Close <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 text-white font-semibold text-xs hover:bg-rose-700 transition">
                            Confirm Cancellation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div x-show="cancelEwayModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60" @click="cancelEwayModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form method="POST" action="<?php echo e(route('invoices.eway.cancel', $invoice)); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-rose-700">Cancel E-Way Bill</h4>
                        <button type="button" @click="cancelEwayModal = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                    </div>
                    <div class="p-5 space-y-3 text-xs">
                        <p class="text-slate-600 text-[11px]">E-Way Bill can be cancelled within 24 hours of generation provided goods have not been intercepted in transit.</p>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Cancellation Reason *</label>
                            <select name="cancel_reason" x-model="ewayCancelReason" required class="w-full rounded-lg border-gray-300 text-xs">
                                <option value="1">1 - Duplicate</option>
                                <option value="2">2 - Order Cancelled</option>
                                <option value="3">3 - Data Entry Mistake</option>
                                <option value="4">4 - Others</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Remarks *</label>
                            <textarea name="cancel_remarks" x-model="ewayCancelRemarks" rows="3" required placeholder="Enter reason for cancellation..." class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'button','variant' => 'secondary','@click' => 'cancelEwayModal = false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','variant' => 'secondary','@click' => 'cancelEwayModal = false']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Close <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 text-white font-semibold text-xs hover:bg-rose-700 transition">
                            Confirm Cancellation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div x-show="updateVehicleModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60" @click="updateVehicleModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form method="POST" action="<?php echo e(route('invoices.eway.update-vehicle', $invoice)); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-slate-800">Update Vehicle Details</h4>
                        <button type="button" @click="updateVehicleModal = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                    </div>
                    <div class="p-5 space-y-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">New Vehicle Number *</label>
                            <input type="text" name="vehicle_no" x-model="vehicleNo" required placeholder="E.g. MH-14-XY-9876" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Vehicle Type</label>
                            <select name="vehicle_type" x-model="vehicleType" class="w-full rounded-lg border-gray-300 text-xs">
                                <option value="R">Regular (R)</option>
                                <option value="O">Over Dimensional Cargo (ODC)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Reason for Update *</label>
                            <select name="reason" x-model="vehicleReason" required class="w-full rounded-lg border-gray-300 text-xs">
                                <option value="Breakdown / Transshipment">Breakdown / Transshipment</option>
                                <option value="Due to Transshipment">Due to Transshipment</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'button','variant' => 'secondary','@click' => 'updateVehicleModal = false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','variant' => 'secondary','@click' => 'updateVehicleModal = false']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Cancel <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','variant' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Update Vehicle <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div x-show="printSettingsModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60" @click="printSettingsModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form method="GET" action="<?php echo e(route('invoices.preview', $invoice)); ?>" target="_blank">
                    <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 bg-indigo-100 text-indigo-700 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Print Tax Invoice</h3>
                                <p class="text-[11px] text-slate-500 font-mono"><?php echo e($invoice->invoice_no); ?></p>
                            </div>
                        </div>
                        <button type="button" @click="printSettingsModal = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                    </div>

                    <div class="p-5 space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Type of Copy *</label>
                            <select name="copy_type" x-model="printCopyType" @change="if(printCopyType === 'all') { printCopies = 3; } else { printCopies = 1; }" class="w-full rounded-lg border-slate-300 text-xs focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="all">All Copies (Original + Duplicate + Triplicate)</option>
                                <option value="original">Original for Recipient</option>
                                <option value="duplicate">Duplicate for Transporter</option>
                                <option value="triplicate">Triplicate for Supplier</option>
                                <option value="extra">Extra Copy</option>
                                <option value="na">Not Applicable (No copy label)</option>
                            </select>
                            <p class="mt-1 text-[11px] text-slate-500">Specifies the statutory copy label to render on the invoice heading.</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Number of Copies *</label>
                            <input type="number" name="copies" x-model.number="printCopies" min="1" max="10" class="w-full rounded-lg border-slate-300 text-xs focus:ring-indigo-500 focus:border-indigo-500" required>
                            <p class="mt-1 text-[11px] text-slate-500">When printing multiple copies, each copy begins on a separate page with QR and statutory details.</p>
                        </div>

                        <div class="p-3 bg-indigo-50/70 rounded-lg border border-indigo-100 flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div class="text-[11px] text-slate-600 leading-relaxed">
                                <span class="font-semibold text-slate-800">GST Statutory Rules:</span><br>
                                • <strong>All Copies:</strong> Generates the 3 statutory copies on separate pages.<br>
                                • <strong>QR Verification:</strong> Every copy contains the scannable IRP/invoice verification QR code.
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        <button type="button" @click="printSettingsModal = false" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">Cancel</button>
                        <div class="flex items-center gap-2">
                            <button type="submit" class="px-3.5 py-2 rounded-lg border border-indigo-300 bg-white text-indigo-700 font-semibold text-xs hover:bg-indigo-50 transition">
                                Preview
                            </button>
                            <button type="submit" name="auto_print" value="1" class="px-3.5 py-2 rounded-lg bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print Now
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/sales/invoices/show.blade.php ENDPATH**/ ?>