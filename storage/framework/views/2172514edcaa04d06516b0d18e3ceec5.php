<?php $__env->startSection('title', 'New Purchase Order'); ?>
<?php $__env->startSection('content'); ?>
<div x-data>
    <?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'New Purchase Order']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'New Purchase Order']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('actions', null, []); ?> <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('purchasing.orders.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('purchasing.orders.index'))]); ?>
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
<?php endif; ?> <?php $__env->endSlot(); ?>
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

    <form method="POST" action="<?php echo e(route('purchasing.orders.store')); ?>" class="space-y-4"><?php echo csrf_field(); ?>
    <?php
        $poSeries = $poNumberSeries ?? null;
        $poManual = $poSeries && $poSeries->isManual();
        $poPreview = $nextPoNumber ?? ($poSeries?->preview());
    ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-slate-700">
                    Voucher No. <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($poManual): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </label>
                <a href="<?php echo e(route('organization.document-number-series.index')); ?>"
                   class="text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                   title="Configure voucher numbering">
                    Configure
                </a>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($poManual): ?>
                <input type="text"
                       name="po_no"
                       value="<?php echo e(old('po_no')); ?>"
                       required
                       maxlength="60"
                       placeholder="e.g. AVITPO-2627-001"
                       class="block w-full rounded-lg border-gray-300 text-sm uppercase font-mono focus:border-indigo-500 focus:ring-indigo-500">
                <p class="mt-1 text-[11px] text-slate-500">Manual numbering is enabled for Purchase Orders.</p>
            <?php else: ?>
                <input type="text"
                       value="<?php echo e($poPreview ?: 'Auto on save'); ?>"
                       readonly
                       class="block w-full rounded-lg border-gray-200 bg-slate-50 text-sm font-mono text-slate-700">
                <p class="mt-1 text-[11px] text-slate-500">
                    <?php echo e($poSeries ? 'Next automatic number (assigned on save).' : 'Using default PO numbering until a series is configured.'); ?>

                </p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-slate-700">Supplier *</label>
                <a href="<?php echo e(route('masters.customers.create', [
                        'party_type' => \App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_CREDITORS,
                        'return_url' => route('purchasing.orders.create'),
                    ])); ?>"
                   class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                    + Add Supplier
                </a>
            </div>
            <?php echo $__env->make('purchasing.orders.partials.supplier-search', [
                'suppliers' => $suppliers,
                'selected' => old('supplier_id', request('supplier_id')),
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Warehouse</label>
            <select name="warehouse_id" id="warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm" onchange="handleWarehouseChange(this)">
                <option value="" data-address="">Optional</option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $wAddress = $w->address ?: ($w->branch?->address ?: ($w->company?->address ?: ''));
                    ?>
                    <option value="<?php echo e($w->id); ?>" data-address="<?php echo e($wAddress); ?>" <?php if(old('warehouse_id')==$w->id): echo 'selected'; endif; ?>><?php echo e($w->name); ?></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </select>
        </div>
        <?php if (isset($component)) { $__componentOriginal65bd7e7dbd93cec773ad6501ce127e46 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.input','data' => ['name' => 'po_date','id' => 'po_date','label' => 'PO Date','type' => 'date','value' => old('po_date', now()->toDateString()),'required' => true,'onchange' => 'recalcPoDueDate(); updateExpectedQuickHighlight()','oninput' => 'recalcPoDueDate(); updateExpectedQuickHighlight()']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'po_date','id' => 'po_date','label' => 'PO Date','type' => 'date','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('po_date', now()->toDateString())),'required' => true,'onchange' => 'recalcPoDueDate(); updateExpectedQuickHighlight()','oninput' => 'recalcPoDueDate(); updateExpectedQuickHighlight()']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46)): ?>
<?php $attributes = $__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46; ?>
<?php unset($__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65bd7e7dbd93cec773ad6501ce127e46)): ?>
<?php $component = $__componentOriginal65bd7e7dbd93cec773ad6501ce127e46; ?>
<?php unset($__componentOriginal65bd7e7dbd93cec773ad6501ce127e46); ?>
<?php endif; ?>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Expected Date</label>
            <input type="date" name="expected_date" id="po_expected_date"
                   value="<?php echo e(old('expected_date')); ?>"
                   onchange="updateExpectedQuickHighlight()"
                   oninput="updateExpectedQuickHighlight()"
                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <div class="flex flex-wrap items-center gap-1.5 mt-2">
                <span class="text-[11px] font-medium text-slate-400">Quick:</span>
                <button type="button" onclick="setPoExpectedDate(0)" id="expected_quick_0"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    Today
                </button>
                <button type="button" onclick="setPoExpectedDate(7)" id="expected_quick_7"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    +7 Days
                </button>
                <button type="button" onclick="setPoExpectedDate(15)" id="expected_quick_15"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    +15 Days
                </button>
                <button type="button" onclick="setPoExpectedDate(30)" id="expected_quick_30"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    +30 Days
                </button>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-semibold text-slate-700 tracking-wider">Credit Days</label>
                <span class="text-xs text-slate-500" id="due_date_preview_badge" style="display: none;">
                    Due: <span class="font-semibold text-indigo-600" id="due_date_badge_text"></span>
                </span>
            </div>
            <input type="number" name="credit_days" id="po_credit_days" min="0" placeholder="0"
                   value="<?php echo e(old('credit_days', 0)); ?>"
                   oninput="recalcPoDueDate()"
                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input type="hidden" name="due_date" id="po_due_date_hidden" value="<?php echo e(old('due_date')); ?>">

            <div class="flex items-center gap-1.5 mt-2">
                <span class="text-[11px] font-medium text-slate-400">Quick:</span>
                <button type="button"
                        onclick="setPoCreditDays(30)"
                        id="quick_btn_30"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    30 Days
                </button>
                <button type="button"
                        onclick="setPoCreditDays(45)"
                        id="quick_btn_45"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    45 Days
                </button>
                <button type="button"
                        onclick="setPoCreditDays(50)"
                        id="quick_btn_50"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    50 Days
                </button>
            </div>
            <p class="mt-1 text-[11px] text-slate-500" id="due_date_preview_text_container" style="display: none;">
                Due Date Preview: <strong class="text-slate-700" id="due_date_preview_text"></strong>
            </p>
        </div>
    </div>

    <?php echo $__env->make('purchasing.orders.partials.party-details', [
        'initialBillingId' => old('billing_address_id'),
        'initialBillingText' => old('billing_address'),
        'initialDeliveryId' => old('company_delivery_address_id'),
        'initialShippingText' => old('shipping_address'),
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="overflow-x-auto border rounded-lg">
    <table class="min-w-full text-sm">
    <thead class="bg-gray-50"><tr>
    <th class="px-3 py-2 text-left w-44">Brand</th>
    <th class="px-3 py-2 text-left min-w-[250px]">Product</th>
    <th class="px-3 py-2 text-left">Batch Name</th>
    <th class="px-3 py-2 text-left">UOM</th>
    <th class="px-3 py-2 text-left">Qty</th>
    <th class="px-3 py-2 text-left whitespace-nowrap">MRP</th>
    <th class="px-3 py-2 text-left whitespace-nowrap">Purchase Price Incl. GST</th>
    <th class="px-3 py-2 text-left whitespace-nowrap">Purchase Disc. %</th>
    <th class="px-3 py-2 text-left">Unit Cost</th>
    <th class="px-3 py-2 text-left">Tax %</th>
    <th class="px-3 py-2 text-left">Cgst %</th>
    <th class="px-3 py-2 text-left">Sgst %</th>
    <th class="px-3 py-2"></th>
    </tr></thead>
    <tbody id="po-lines">
    <?php
        $oldItems = old('items');
    ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($oldItems) && is_array($oldItems)): ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $oldItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $oldItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php
                $selectedProduct = $products->firstWhere('id', $oldItem['product_id'] ?? null);
                $selectedBrandId = $oldItem['brand_id'] ?? ($selectedProduct ? $selectedProduct->brand_id : null);
            ?>
            <?php echo $__env->make('purchasing.orders.partials.line-row', [
                'idx' => $idx,
                'brands' => $brands,
                'products' => $products,
                'uoms' => $uoms,
                'selectedBrandId' => $selectedBrandId,
                'selectedProductId' => $oldItem['product_id'] ?? null,
                'selectedProductName' => $selectedProduct ? $selectedProduct->name : '',
                'batchNo' => $oldItem['batch_no'] ?? $oldItem['batch_name'] ?? '',
                'uomId' => $oldItem['uom_id'] ?? null,
                'quantity' => $oldItem['quantity'] ?? 1,
                'unitCost' => $oldItem['unit_cost'] ?? 0,
                'mrp' => $oldItem['mrp'] ?? 0,
                'priceInclusiveTax' => $oldItem['price_inclusive_tax'] ?? $oldItem['purchase_price_inclusive'] ?? null,
                'purchaseDiscountPercent' => $oldItem['purchase_discount_percent'] ?? null,
                'taxPercent' => $oldItem['tax_percent'] ?? 0,
                'cgstPercent' => $oldItem['cgst_percent'] ?? 0,
                'sgstPercent' => $oldItem['sgst_percent'] ?? 0,
                'showRemove' => count($oldItems) > 1,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    <?php else: ?>
        <?php echo $__env->make('purchasing.orders.partials.line-row', [
            'idx' => 0,
            'brands' => $brands,
            'products' => $products,
            'uoms' => $uoms,
            'showRemove' => false,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </tbody></table></div>

    <template id="po-line-template">
        <?php echo $__env->make('purchasing.orders.partials.line-row', [
            'idx' => '__INDEX__',
            'brands' => $brands,
            'products' => $products,
            'uoms' => $uoms,
            'showRemove' => true,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </template>

    <!-- Section 3: Add Line Button directly below line items table -->
    <div class="mt-3 flex items-center justify-between">
        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'button','variant' => 'secondary','onclick' => 'addPoRow()','class' => 'inline-flex items-center gap-1.5 shadow-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','variant' => 'secondary','onclick' => 'addPoRow()','class' => 'inline-flex items-center gap-1.5 shadow-xs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add Line</span>
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
    </div>

    <!-- Freight Charges Section -->
    <div class="mt-6 border border-slate-200 rounded-xl bg-slate-50/70 p-4 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Freight Charges</h3>
                <p class="text-xs text-slate-500">Specify whether freight / shipping costs apply to this purchase order.</p>
            </div>
            <div class="flex items-center gap-3">
                <label class="text-xs font-semibold text-slate-700 tracking-wider">Freight Applicable?</label>
                <select name="is_freight_applicable" id="po_is_freight_applicable" onchange="toggleFreightFields()" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="0" <?php if(old('is_freight_applicable', 0) == 0): echo 'selected'; endif; ?>>No</option>
                    <option value="1" <?php if(old('is_freight_applicable', 0) == 1): echo 'selected'; endif; ?>>Yes</option>
                </select>
            </div>
        </div>

        <?php echo $__env->make('purchasing.orders.partials.freight-fields', [
            'freightApplicable' => old('is_freight_applicable', 0),
            'freightMode' => old('freight_mode', 'amount'),
            'freightPercent' => old('freight_percent', '0.00'),
            'freightAmount' => old('freight_amount', '0.00'),
            'freightTaxRate' => old('freight_tax_rate', '0.00'),
            'freightTaxAmount' => old('freight_tax_amount', '0.00'),
            'freightTotal' => old('freight_total', '0.00'),
            'freightNotes' => old('freight_notes'),
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

    <!-- Notes & Order Summary Section -->
    <div class="mt-4 grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
        <div class="lg:col-span-7 space-y-2">
            <label class="block text-xs font-semibold text-slate-700 tracking-wider">Notes / Instructions</label>
            <textarea name="notes" rows="3" placeholder="Optional Notes Or Instructions For This Purchase Order..." class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"><?php echo e(old('notes')); ?></textarea>
        </div>
        <div class="lg:col-span-5 bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
            <h4 class="text-xs font-bold tracking-wider text-slate-600 border-b border-slate-100 pb-2 mb-3">Order Summary</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Items Subtotal:</span>
                    <span class="font-mono font-medium" id="summary_subtotal">₹0.00</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Items Tax (GST):</span>
                    <span class="font-mono font-medium" id="summary_tax">₹0.00</span>
                </div>
                <div class="flex justify-between text-slate-600" id="summary_freight_row" style="<?php echo e(old('is_freight_applicable', 0) ? 'display: flex;' : 'display: none;'); ?>">
                    <span>Freight Charges:</span>
                    <span class="font-mono font-medium" id="summary_freight">₹0.00</span>
                </div>
                <div class="flex justify-between text-slate-600" id="summary_freight_tax_row" style="<?php echo e(old('is_freight_applicable', 0) ? 'display: flex;' : 'display: none;'); ?>">
                    <span>Freight Tax:</span>
                    <span class="font-mono font-medium" id="summary_freight_tax">₹0.00</span>
                </div>
                <div class="pt-2 border-t border-slate-200 flex justify-between text-base font-bold text-slate-900">
                    <span>Order Total:</span>
                    <span class="font-mono text-indigo-600" id="summary_grand_total">₹0.00</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 7: Action bar with Submit for Approval and Save PO -->
    <div class="flex flex-wrap gap-3 items-center justify-end pt-4 border-t border-slate-200">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
            <input type="checkbox" name="submit_for_approval" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span>Submit for approval</span>
        </label>
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
Save PO <?php echo $__env->renderComponent(); ?>
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
    </form> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $attributes = $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $component = $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>

    <!-- Floating Product Search Dropdown Menu -->
    <div id="po-product-dropdown-menu"
         class="hidden fixed bg-white rounded-xl shadow-2xl border border-gray-200 z-[999999] overflow-hidden flex flex-col"
         style="width: 320px; max-height: 290px;">
        <!-- Sticky Search Box inside Dropdown -->
        <div class="p-2 border-b border-gray-100 bg-gray-50/90 sticky top-0 z-10">
            <div class="relative">
                <input type="text"
                       id="po-dropdown-search-box"
                       class="block w-full px-3 py-1.5 text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white placeholder-gray-400"
                       placeholder="Search Product By Name Or Part Number..."
                       autocomplete="off"
                       oninput="handleDropdownFilter(this.value)"
                       onkeydown="handleDropdownSearchKeydown(event)">
            </div>
        </div>
        <!-- Scrollable options container -->
        <div id="po-dropdown-list" class="overflow-y-auto divide-y divide-gray-100 flex-1 overscroll-contain">
        </div>
    </div>
</div>
<?php $__env->startPush('scripts'); ?>
<?php
    $poProductsData = $products->map(function($p) {
        return [
            'id' => (string) $p->id,
            'name' => $p->name,
            'sku' => $p->sku ?? '',
            'brand_id' => $p->brand_id ? (string)$p->brand_id : '',
            'tax_rate' => (float)($p->tax_rate ?? 0),
            'purchase_price' => (float)($p->purchase_price ?? 0),
            'mrp' => (float)($p->mrp ?? 0),
            'base_uom_id' => $p->base_uom_id ? (string)$p->base_uom_id : '',
        ];
    })->values();
?>
<script>
let poi = <?php echo e((!empty($oldItems) && is_array($oldItems)) ? count($oldItems) : 1); ?>;

const poProductsList = <?php echo json_encode($poProductsData); ?>;

let activeRow = null;
let highlightedIndex = -1;
let currentFilteredProducts = [];

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function setPoProductPickerEnabled(row, enabled) {
    const searchInput = row.querySelector('.po-product-search');
    const toggleBtn = row.querySelector('.po-dropdown-toggle');

    if (searchInput) {
        searchInput.disabled = !enabled;
        if (enabled) {
            searchInput.removeAttribute('disabled');
            searchInput.placeholder = 'Select product...';
            searchInput.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
            searchInput.classList.add('bg-white');
        } else {
            searchInput.setAttribute('disabled', 'disabled');
            searchInput.placeholder = 'Select brand first';
            searchInput.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
            searchInput.classList.remove('bg-white');
        }
    }

    if (toggleBtn) {
        toggleBtn.disabled = !enabled;
        if (enabled) {
            toggleBtn.removeAttribute('disabled');
            toggleBtn.classList.remove('opacity-40', 'pointer-events-none');
        } else {
            toggleBtn.setAttribute('disabled', 'disabled');
            toggleBtn.classList.add('opacity-40', 'pointer-events-none');
        }
    }
}

function handlePoBrandChange(brandSelect) {
    const row = brandSelect.closest('tr');
    if (!row) return;

    const brandId = brandSelect.value;

    try {
        clearPoProductSelection(brandSelect);
    } catch (e) {
        console.error('clearPoProductSelection failed', e);
    }

    if (brandId) {
        setPoProductPickerEnabled(row, true);
        brandSelect.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
    } else {
        setPoProductPickerEnabled(row, false);
    }
}

function openPoProductDropdown(triggerEl, initialQuery = null) {
    const row = triggerEl.closest('tr');
    if (!row) return;

    const brandSelect = row.querySelector('.po-brand');
    if (!brandSelect || !brandSelect.value) {
        if (brandSelect) {
            brandSelect.focus();
            brandSelect.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
        }
        return;
    }

    if (activeRow && activeRow !== row) {
        activeRow.querySelector('.po-dropdown-toggle svg')?.classList.remove('rotate-180');
    }

    activeRow = row;
    row.querySelector('.po-dropdown-toggle svg')?.classList.add('rotate-180');

    const menu = document.getElementById('po-product-dropdown-menu');
    const searchBox = document.getElementById('po-dropdown-search-box');
    const select = row.querySelector('.po-product');
    const selectedId = select ? select.value : '';

    if (!menu) return;
    menu.classList.remove('hidden');
    positionPoDropdown();

    const query = initialQuery !== null ? initialQuery : '';
    if (searchBox) {
        searchBox.value = query;
        if (initialQuery === null) {
            setTimeout(() => searchBox.focus(), 20);
        }
    }

    handleDropdownFilter(query, selectedId);
}

function closePoProductDropdown() {
    const menu = document.getElementById('po-product-dropdown-menu');
    if (menu) {
        menu.classList.add('hidden');
    }
    if (activeRow) {
        activeRow.querySelector('.po-dropdown-toggle svg')?.classList.remove('rotate-180');
        const select = activeRow.querySelector('.po-product');
        const searchInput = activeRow.querySelector('.po-product-search');
        if (select && searchInput) {
            if (select.value) {
                const p = poProductsList.find(x => String(x.id) === String(select.value));
                if (p) {
                    searchInput.value = p.name;
                }
            } else {
                searchInput.value = '';
            }
        }
    }
    highlightedIndex = -1;
    activeRow = null;
}

function togglePoProductDropdown(btn) {
    const row = btn.closest('tr');
    const menu = document.getElementById('po-product-dropdown-menu');
    if (activeRow === row && menu && !menu.classList.contains('hidden')) {
        closePoProductDropdown();
    } else {
        openPoProductDropdown(btn);
    }
}

function handlePoProductSearchInput(input) {
    openPoProductDropdown(input, input.value);
}

function handlePoProductSearchKeydown(event, input) {
    if (event.key === 'ArrowDown' || event.key === 'Enter') {
        event.preventDefault();
        openPoProductDropdown(input);
        const searchBox = document.getElementById('po-dropdown-search-box');
        if (searchBox) searchBox.focus();
    } else if (event.key === 'Escape') {
        closePoProductDropdown();
    }
}

function positionPoDropdown() {
    if (!activeRow) return;
    const menu = document.getElementById('po-product-dropdown-menu');
    const container = activeRow.querySelector('.po-product-picker-container');
    if (!menu || !container) return;

    const rect = container.getBoundingClientRect();
    const menuHeight = menu.offsetHeight || 280;
    const windowHeight = window.innerHeight;

    let top = rect.bottom + 4;
    if (rect.bottom + menuHeight > windowHeight && rect.top > menuHeight) {
        top = rect.top - menuHeight - 4;
    }

    menu.style.top = `${top}px`;
    menu.style.left = `${rect.left}px`;
    menu.style.width = `${Math.max(rect.width, 320)}px`;
}

function handleDropdownFilter(query, selectedId = null) {
    if (!activeRow) return;
    const brandSelect = activeRow.querySelector('.po-brand');
    const activeBrandId = brandSelect ? String(brandSelect.value) : '';

    if (selectedId === null) {
        const select = activeRow.querySelector('.po-product');
        selectedId = select ? select.value : '';
    }

    let filtered = poProductsList.filter(p => String(p.brand_id) === activeBrandId);
    if (query && query.trim() !== '') {
        const q = query.toLowerCase().trim();
        filtered = filtered.filter(p => {
            return (p.name && p.name.toLowerCase().includes(q)) ||
                   (p.sku && p.sku.toLowerCase().includes(q));
        });
    }

    currentFilteredProducts = filtered;
    renderDropdownItems(filtered.slice(0, 50), selectedId, filtered.length);
}

function renderDropdownItems(items, selectedId, totalMatches) {
    const list = document.getElementById('po-dropdown-list');
    if (!list) return;

    if (!items || items.length === 0) {
        list.innerHTML = `
            <div class="px-4 py-6 text-center text-xs text-gray-500">
                <svg class="h-6 w-6 mx-auto mb-1.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="font-medium text-slate-700">No products found for this brand</div>
                <div class="text-[11px] text-slate-400 mt-0.5">Try searching with a different name or Part Number</div>
            </div>
        `;
        highlightedIndex = -1;
        return;
    }

    let html = '';
    items.forEach((p, index) => {
        const isSelected = selectedId && String(selectedId) === String(p.id);
        const activeClass = isSelected ? 'bg-indigo-50/70 border-l-2 border-indigo-600' : '';
        html += `
            <div class="po-dropdown-item px-3 py-2 cursor-pointer text-xs flex items-center justify-between hover:bg-indigo-50 transition-colors ${activeClass}"
                 data-id="${p.id}"
                 data-index="${index}">
                <div class="min-w-0 pr-2">
                    <div class="font-medium text-slate-800 truncate">${escapeHtml(p.name)}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">${p.sku ? 'Part No: ' + escapeHtml(p.sku) : 'No Part Number'}</div>
                </div>
                <div class="shrink-0 text-right">
                    <span class="inline-block text-[10px] font-medium text-indigo-700 bg-indigo-50 border border-indigo-100 rounded px-1.5 py-0.5">
                        ${p.tax_rate > 0 ? p.tax_rate + '% GST' : '0% GST'}
                    </span>
                </div>
            </div>
        `;
    });

    if (totalMatches > items.length) {
        html += `
            <div class="px-3 py-1.5 text-center text-[11px] text-gray-400 bg-gray-50 border-t border-gray-100">
                Showing top ${items.length} of ${totalMatches} products. Type to narrow search.
            </div>
        `;
    }

    list.innerHTML = html;
    highlightedIndex = -1;
}

function selectPoProduct(productId) {
    if (!activeRow) return;

    const select = activeRow.querySelector('.po-product');
    const searchInput = activeRow.querySelector('.po-product-search');
    const clearBtn = activeRow.querySelector('.po-clear-product');

    const product = poProductsList.find(p => String(p.id) === String(productId));
    if (!product || !select) return;

    select.value = product.id;
    if (searchInput) {
        searchInput.value = product.name;
        searchInput.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
    }
    if (clearBtn) {
        clearBtn.style.display = 'inline-flex';
    }

    closePoProductDropdown();
    handlePoProductChange(select);
}

function clearPoProductSelection(btn) {
    const row = btn.closest('tr');
    if (!row) return;

    const select = row.querySelector('.po-product');
    const searchInput = row.querySelector('.po-product-search');
    const clearBtn = row.querySelector('.po-clear-product');

    if (select) {
        select.value = '';
        select.selectedIndex = 0;
    }
    if (searchInput) {
        searchInput.value = '';
    }
    if (clearBtn) {
        clearBtn.style.display = 'none';
    }

    const taxInput = row.querySelector('.po-tax');
    const cgstInput = row.querySelector('.po-cgst');
    const sgstInput = row.querySelector('.po-sgst');
    const costInput = row.querySelector('.po-cost');
    const datalist = row.querySelector('datalist');
    const batchInput = row.querySelector('.po-batch-input');

    if (taxInput) taxInput.value = '0';
    if (cgstInput) cgstInput.value = '0';
    if (sgstInput) sgstInput.value = '0';
    if (costInput) costInput.value = '0';
    if (batchInput) batchInput.value = '';
    if (datalist) datalist.innerHTML = '';

    if (activeRow === row) {
        closePoProductDropdown();
    }
    recalculatePoTotals();
}

function handleDropdownSearchKeydown(e) {
    const menu = document.getElementById('po-product-dropdown-menu');
    if (!menu || menu.classList.contains('hidden')) return;

    const items = menu.querySelectorAll('.po-dropdown-item');
    if (!items.length) {
        if (e.key === 'Escape') {
            closePoProductDropdown();
        }
        return;
    }

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedIndex = (highlightedIndex + 1) % items.length;
        updateHighlight(items);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
        updateHighlight(items);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const targetIndex = highlightedIndex >= 0 ? highlightedIndex : 0;
        if (items[targetIndex]) {
            selectPoProduct(items[targetIndex].dataset.id);
        }
    } else if (e.key === 'Escape') {
        e.preventDefault();
        closePoProductDropdown();
    }
}

function updateHighlight(items) {
    items.forEach((item, idx) => {
        if (idx === highlightedIndex) {
            item.classList.add('bg-indigo-100', 'text-indigo-900');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('bg-indigo-100', 'text-indigo-900');
        }
    });
}

async function handlePoProductChange(select) {
    const row = select.closest('tr');
    if (!row) return;

    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) return;

    const productMeta = (typeof poProductsList !== 'undefined')
        ? poProductsList.find(p => String(p.id) === String(opt.value))
        : null;
    const taxRate = parseFloat(opt.getAttribute('data-tax')) || Number(productMeta?.tax_rate) || 0;
    const cost = parseFloat(opt.getAttribute('data-cost')) || Number(productMeta?.purchase_price) || 0;
    const mrp = parseFloat(opt.getAttribute('data-mrp')) || Number(productMeta?.mrp) || 0;
    const uomId = opt.getAttribute('data-uom') || productMeta?.base_uom_id || '';

    const taxInput = row.querySelector('.po-tax');
    const cgstInput = row.querySelector('.po-cgst');
    const sgstInput = row.querySelector('.po-sgst');
    const costInput = row.querySelector('.po-cost');
    const mrpInput = row.querySelector('.po-mrp');
    const uomSelect = row.querySelector('.po-uom');

    if (taxInput) taxInput.value = taxRate;
    if (cgstInput) cgstInput.value = (taxRate / 2).toFixed(2);
    if (sgstInput) sgstInput.value = (taxRate / 2).toFixed(2);
    if (mrpInput && (!mrpInput.value || mrpInput.value === '0')) {
        mrpInput.value = mrp;
    }
    if (costInput && (!costInput.value || costInput.value === '0')) {
        costInput.value = cost;
        syncPoPriceTaxPair(row, 'cost');
    } else {
        syncPoPurchaseDiscount(row);
    }
    if (uomSelect && uomId) uomSelect.value = uomId;

    // Load existing batches for this product into datalist if available
    const datalist = row.querySelector('datalist');
    if (datalist) {
        datalist.innerHTML = '';
        try {
            const res = await fetch(`<?php echo e(url('/inventory/batches')); ?>/${encodeURIComponent(opt.value)}?include_zero=1`, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data = await res.json();
                if (data.batches && Array.isArray(data.batches)) {
                    data.batches.forEach(b => {
                        if (b.batch_no) {
                            const optEl = document.createElement('option');
                            optEl.value = b.batch_no;
                            optEl.textContent = b.batch_no + (b.available_qty ? ` (Stock: ${b.available_qty})` : '');
                            datalist.appendChild(optEl);
                        }
                    });
                }
            }
        } catch (e) {
            // silent fallback
        }
    }
    recalculatePoTotals();
}

function syncPoTax(row, changed) {
    const tax = row.querySelector('.po-tax');
    const cgst = row.querySelector('.po-cgst');
    const sgst = row.querySelector('.po-sgst');

    if (!tax || !cgst || !sgst) {
        return;
    }

    const taxValue = parseFloat(tax.value) || 0;

    if (changed === 'tax') {
        const half = (taxValue / 2).toFixed(2);
        cgst.value = half;
        sgst.value = half;
        syncPoPriceTaxPair(row, row.dataset.priceEditSource === 'inclusive' ? 'inclusive' : 'cost');
        recalculatePoTotals();
        return;
    }

    const cgstValue = parseFloat(cgst.value) || 0;

    if (changed === 'cgst') {
        sgst.value = Math.max(0, taxValue - cgstValue).toFixed(2);
        syncPoPriceTaxPair(row, row.dataset.priceEditSource === 'inclusive' ? 'inclusive' : 'cost');
        recalculatePoTotals();
        return;
    }

    if (changed === 'sgst') {
        cgst.value = Math.max(0, taxValue - (parseFloat(sgst.value) || 0)).toFixed(2);
        syncPoPriceTaxPair(row, row.dataset.priceEditSource === 'inclusive' ? 'inclusive' : 'cost');
        recalculatePoTotals();
    }
}

function poRowTaxPercent(row) {
    const tax = row.querySelector('.po-tax');
    return Math.max(0, parseFloat(tax?.value) || 0);
}

function syncPoPurchaseDiscount(row) {
    if (!row) return;
    const mrpInput = row.querySelector('.po-mrp');
    const inclusiveInput = row.querySelector('.po-price-inclusive');
    const discountInput = row.querySelector('.po-purchase-discount');
    if (!discountInput) return;

    const mrp = parseFloat(mrpInput?.value) || 0;
    const inclusive = parseFloat(inclusiveInput?.value) || 0;
    discountInput.value = mrp > 0 ? (((mrp - inclusive) / mrp) * 100).toFixed(2) : '';
}

function syncPoPriceTaxPair(row, source) {
    if (!row) return;
    const costInput = row.querySelector('.po-cost');
    const inclusiveInput = row.querySelector('.po-price-inclusive');
    if (!costInput || !inclusiveInput) return;

    const tax = poRowTaxPercent(row);
    const factor = 1 + (tax / 100);

    if (source === 'inclusive') {
        const inclusive = parseFloat(inclusiveInput.value) || 0;
        costInput.value = factor > 0 ? (inclusive / factor).toFixed(2) : inclusive.toFixed(2);
        row.dataset.priceEditSource = 'inclusive';
    } else {
        const cost = parseFloat(costInput.value) || 0;
        inclusiveInput.value = (cost * factor).toFixed(2);
        row.dataset.priceEditSource = 'cost';
    }
    syncPoPurchaseDiscount(row);
}

function toggleFreightFields() {
    const isApplicable = document.getElementById('po_is_freight_applicable')?.value === '1';
    const container = document.getElementById('freight_fields_container');
    const freightRow = document.getElementById('summary_freight_row');
    const freightTaxRow = document.getElementById('summary_freight_tax_row');
    if (container) {
        container.style.display = isApplicable ? 'block' : 'none';
    }
    if (freightRow) {
        freightRow.style.display = isApplicable ? 'flex' : 'none';
    }
    if (freightTaxRow) {
        freightTaxRow.style.display = isApplicable ? 'flex' : 'none';
    }
    recalculatePoTotals();
}

function toggleFreightMode() {
    const mode = document.getElementById('po_freight_mode')?.value || 'amount';
    const percentWrap = document.getElementById('freight_percent_wrap');
    const amountWrap = document.getElementById('freight_amount_wrap');
    if (percentWrap) percentWrap.style.display = mode === 'percent' ? '' : 'none';
    if (amountWrap) amountWrap.style.display = mode === 'amount' ? '' : 'none';
}

function recalculatePoTotals() {
    let subtotal = 0;
    let taxTotal = 0;

    const rows = document.querySelectorAll('#po-lines tr');
    rows.forEach(row => {
        const qtyInput = row.querySelector('input[name*="[quantity]"]');
        const costInput = row.querySelector('input[name*="[unit_cost]"]');
        const taxInput = row.querySelector('.po-tax');

        const qty = parseFloat(qtyInput?.value) || 0;
        const cost = parseFloat(costInput?.value) || 0;
        const taxPercent = parseFloat(taxInput?.value) || 0;

        const lineSubtotal = qty * cost;
        const lineTax = (lineSubtotal * taxPercent) / 100;

        subtotal += lineSubtotal;
        taxTotal += lineTax;
    });

    const isFreightApplicable = document.getElementById('po_is_freight_applicable')?.value === '1';
    const freightMode = document.getElementById('po_freight_mode')?.value || 'amount';
    const freightAmountInput = document.getElementById('po_freight_amount');
    const freightPercentInput = document.getElementById('po_freight_percent');
    const freightTaxRateInput = document.getElementById('po_freight_tax_rate');
    const freightTaxAmountInput = document.getElementById('po_freight_tax_amount');
    const freightTotalInput = document.getElementById('po_freight_total');
    const freightCalculated = document.getElementById('freight_calculated_amount');

    let freightAmount = 0;
    let freightTaxRate = 0;
    let freightTaxAmount = 0;
    let freightTotal = 0;

    if (isFreightApplicable) {
        freightTaxRate = parseFloat(freightTaxRateInput?.value) || 0;
        if (freightMode === 'percent') {
            const freightPercent = Math.min(100, Math.max(0, parseFloat(freightPercentInput?.value) || 0));
            freightAmount = subtotal * freightPercent / 100;
            if (freightAmountInput) {
                freightAmountInput.value = freightAmount.toFixed(2);
            }
        } else {
            freightAmount = parseFloat(freightAmountInput?.value) || 0;
        }
        freightTaxAmount = (freightAmount * freightTaxRate) / 100;
        freightTotal = freightAmount + freightTaxAmount;

        if (freightTaxAmountInput) {
            freightTaxAmountInput.value = freightTaxAmount.toFixed(2);
        }
        if (freightTotalInput) {
            freightTotalInput.value = freightTotal.toFixed(2);
        }
    } else {
        if (freightTaxAmountInput) {
            freightTaxAmountInput.value = '0.00';
        }
        if (freightTotalInput) {
            freightTotalInput.value = '0.00';
        }
    }

    const grandTotal = subtotal + taxTotal + freightTotal;

    const summarySubtotal = document.getElementById('summary_subtotal');
    const summaryTax = document.getElementById('summary_tax');
    const summaryFreight = document.getElementById('summary_freight');
    const summaryFreightTax = document.getElementById('summary_freight_tax');
    const summaryGrandTotal = document.getElementById('summary_grand_total');

    if (summarySubtotal) summarySubtotal.textContent = '₹' + subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (summaryTax) summaryTax.textContent = '₹' + taxTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (freightCalculated) freightCalculated.textContent = '₹' + freightAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (summaryFreight) summaryFreight.textContent = '₹' + freightAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (summaryFreightTax) summaryFreightTax.textContent = '₹' + freightTaxAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (summaryGrandTotal) summaryGrandTotal.textContent = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function resetPoSelect(sel) {
    if (!sel) return;
    Array.from(sel.options).forEach((opt, index) => {
        opt.selected = index === 0;
        if (index === 0) {
            opt.setAttribute('selected', 'selected');
        } else {
            opt.removeAttribute('selected');
        }
    });
    sel.selectedIndex = 0;
    sel.value = sel.options[0] ? sel.options[0].value : '';
}

function addPoRow() {
    closePoProductDropdown();

    const table = document.getElementById('po-lines');
    const tpl = document.getElementById('po-line-template');
    if (!table || !tpl) {
        alert('Could not add line. Please refresh the page and try again.');
        return;
    }

    const index = poi;
    const html = tpl.innerHTML.replaceAll('__INDEX__', String(index));
    const wrap = document.createElement('tbody');
    wrap.innerHTML = html.trim();
    const newRow = wrap.querySelector('tr');
    if (!newRow) return;

    setPoProductPickerEnabled(newRow, false);

    table.appendChild(newRow);
    poi++;
    updateRemoveButtons();
    recalculatePoTotals();

    const brandSelect = newRow.querySelector('.po-brand');
    if (brandSelect) {
        brandSelect.disabled = false;
        brandSelect.removeAttribute('disabled');
        brandSelect.focus();
    }
}

function removePoRow(btn) {
    const table = document.getElementById('po-lines');
    const row = btn.closest('tr');
    if (activeRow === row) {
        closePoProductDropdown();
    }
    if (table.rows.length > 1) {
        row.remove();
    }
    updateRemoveButtons();
    recalculatePoTotals();
}

function updateRemoveButtons() {
    const table = document.getElementById('po-lines');
    const rows = table.rows;
    for (let i = 0; i < rows.length; i++) {
        const btn = rows[i].querySelector('.po-remove-btn');
        if (btn) {
            btn.style.display = rows.length > 1 ? 'inline' : 'none';
        }
    }
}

document.addEventListener('input', function (event) {
    const input = event.target;

    if (input.name === 'po_date' || input.id === 'po_credit_days') {
        recalcPoDueDate();
    }

    if (input.classList.contains('po-price-inclusive')) {
        const row = input.closest('tr');
        if (row) {
            syncPoPriceTaxPair(row, 'inclusive');
            recalculatePoTotals();
        }
        return;
    }

    if (input.classList.contains('po-mrp')) {
        const row = input.closest('tr');
        if (row) syncPoPurchaseDiscount(row);
        return;
    }

    if (input.classList.contains('po-cost') || input.name?.includes('[unit_cost]')) {
        const row = input.closest('tr');
        if (row) {
            syncPoPriceTaxPair(row, 'cost');
        }
    }

    if (input.classList.contains('po-tax')) {
        const row = input.closest('tr');
        if (row) syncPoTax(row, 'tax');
    } else if (input.classList.contains('po-cgst')) {
        const row = input.closest('tr');
        if (row) syncPoTax(row, 'cgst');
    } else if (input.classList.contains('po-sgst')) {
        const row = input.closest('tr');
        if (row) syncPoTax(row, 'sgst');
    }

    if (
        input.name?.includes('[quantity]') ||
        input.name?.includes('[unit_cost]') ||
        input.classList.contains('po-tax') ||
        input.classList.contains('po-cost') ||
        input.classList.contains('po-price-inclusive') ||
        input.id === 'po_freight_amount' ||
        input.id === 'po_freight_tax_rate'
    ) {
        recalculatePoTotals();
    }
});

document.addEventListener('change', function (event) {
    if (event.target.name === 'po_date' || event.target.id === 'po_credit_days') {
        recalcPoDueDate();
    }
});

let supplierAddresses = [];
let companyDeliveryAddresses = [];
let companyDeliveryMeta = { name: '', legal_name: '' };
let isInitialSupplierLoad = true;
const initialCompanyDeliveryId = <?php echo json_encode(old('company_delivery_address_id'), 15, 512) ?>;
const deliveryAddressesUrl = <?php echo json_encode(route('purchasing.delivery-addresses.index'), 15, 512) ?>;
const deliveryAddressesStoreUrl = <?php echo json_encode(route('purchasing.delivery-addresses.store'), 15, 512) ?>;
const csrfToken = <?php echo json_encode(csrf_token(), 15, 512) ?>;

function setPoCreditDays(days) {
    const input = document.getElementById('po_credit_days');
    if (input) {
        input.value = parseInt(days, 10) || 0;
    }
    recalcPoDueDate();
}

function formatLocalDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function setPoExpectedDate(daysFromPo) {
    const poDateInput = document.querySelector('input[name="po_date"]');
    const expectedInput = document.getElementById('po_expected_date');
    if (!poDateInput?.value || !expectedInput) return;

    const parts = String(poDateInput.value).split('-');
    if (parts.length !== 3) return;
    const base = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    if (Number.isNaN(base.getTime())) return;

    base.setDate(base.getDate() + (parseInt(daysFromPo, 10) || 0));
    expectedInput.value = formatLocalDate(base);
    updateExpectedQuickHighlight();
}

function updateExpectedQuickHighlight() {
    const poDateInput = document.querySelector('input[name="po_date"]');
    const expectedInput = document.getElementById('po_expected_date');
    const options = [0, 7, 15, 30];
    let active = -1;

    if (poDateInput?.value && expectedInput?.value) {
        const poParts = String(poDateInput.value).split('-');
        const exParts = String(expectedInput.value).split('-');
        if (poParts.length === 3 && exParts.length === 3) {
            const po = new Date(parseInt(poParts[0], 10), parseInt(poParts[1], 10) - 1, parseInt(poParts[2], 10));
            const ex = new Date(parseInt(exParts[0], 10), parseInt(exParts[1], 10) - 1, parseInt(exParts[2], 10));
            const diff = Math.round((ex - po) / 86400000);
            if (options.includes(diff)) active = diff;
        }
    }

    options.forEach((days) => {
        const btn = document.getElementById(`expected_quick_${days}`);
        if (!btn) return;
        if (days === active) {
            btn.className = 'px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-indigo-600 text-white border-indigo-600';
        } else {
            btn.className = 'px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300';
        }
    });
}

function recalcPoDueDate() {
    const poDateInput = document.querySelector('input[name="po_date"]');
    const creditDaysInput = document.getElementById('po_credit_days');
    const hiddenDueDate = document.getElementById('po_due_date_hidden');
    const badgeContainer = document.getElementById('due_date_preview_badge');
    const badgeText = document.getElementById('due_date_badge_text');
    const textContainer = document.getElementById('due_date_preview_text_container');
    const previewText = document.getElementById('due_date_preview_text');

    const poDateVal = poDateInput?.value;
    if (!poDateVal) {
        if (badgeContainer) badgeContainer.style.display = 'none';
        if (textContainer) textContainer.style.display = 'none';
        if (hiddenDueDate) hiddenDueDate.value = '';
        updateQuickButtonsHighlight(-1);
        return;
    }

    const days = parseInt(creditDaysInput?.value, 10);
    const validDays = (!isNaN(days) && days >= 0) ? days : 0;

    const parts = String(poDateVal).split('-');
    if (parts.length !== 3) {
        if (badgeContainer) badgeContainer.style.display = 'none';
        if (textContainer) textContainer.style.display = 'none';
        if (hiddenDueDate) hiddenDueDate.value = '';
        updateQuickButtonsHighlight(-1);
        return;
    }

    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);

    const d = new Date(year, month, day);
    d.setDate(d.getDate() + validDays);

    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const formattedYmd = `${yyyy}-${mm}-${dd}`;

    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const formattedPreview = `${dd} ${monthNames[d.getMonth()]} ${yyyy}`;

    if (hiddenDueDate) hiddenDueDate.value = formattedYmd;
    if (badgeText) badgeText.textContent = formattedPreview;
    if (previewText) previewText.textContent = formattedPreview;
    if (badgeContainer) badgeContainer.style.display = 'inline';
    if (textContainer) textContainer.style.display = 'block';

    updateQuickButtonsHighlight(validDays);
}

function updateQuickButtonsHighlight(currentDays) {
    [30, 45, 50].forEach(d => {
        const btn = document.getElementById('quick_btn_' + d);
        if (btn) {
            if (currentDays === d) {
                btn.className = 'px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-indigo-600 text-white border-indigo-600 shadow-sm';
            } else {
                btn.className = 'px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300';
            }
        }
    });
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value || '—';
}

function renderBillingDetails(customer, addr) {
    const empty = document.getElementById('billing-empty-hint');
    const list = document.getElementById('billing-details-list');
    const snapshot = document.getElementById('billing_address');
    const hiddenId = document.getElementById('billing_address_id');

    if (!customer && !addr) {
        if (empty) empty.classList.remove('hidden');
        if (list) list.classList.add('hidden');
        if (snapshot) snapshot.value = '';
        if (hiddenId) hiddenId.value = '';
        return;
    }

    if (empty) empty.classList.add('hidden');
    if (list) list.classList.remove('hidden');

    const partyName = customer?.name || '';
    const mailing = addr?.contact_person || partyName;
    const lines = [addr?.address_line_1, addr?.address_line_2].filter(Boolean).join('\n') || (addr?.full_address || '');
    setText('billing_party_name', partyName);
    setText('billing_address_type', addr?.label || (addr?.type ? String(addr.type) : 'Primary'));
    setText('billing_mailing_name', mailing);
    setText('billing_address_lines', lines);
    setText('billing_state', addr?.state || customer?.state || '');
    setText('billing_pincode', addr?.pincode || '');
    setText('billing_gstin', addr?.gstin || customer?.gstin || '');

    if (hiddenId) hiddenId.value = addr?.id || '';
    if (snapshot) snapshot.value = addr ? (addr.snapshot || addr.full_address || '') : '';
}

function renderDeliveryDetails(addr) {
    const empty = document.getElementById('delivery-empty-hint');
    const list = document.getElementById('delivery-details-list');
    const snapshot = document.getElementById('shipping_address');

    if (!addr) {
        if (empty) empty.classList.remove('hidden');
        if (list) list.classList.add('hidden');
        if (snapshot) snapshot.value = '';
        return;
    }

    if (empty) empty.classList.add('hidden');
    if (list) list.classList.remove('hidden');

    setText('delivery_party_name', companyDeliveryMeta.legal_name || companyDeliveryMeta.name || addr.mailing_name || '');
    setText('delivery_address_type', addr.address_type || '');
    setText('delivery_mailing_name', addr.mailing_name || companyDeliveryMeta.legal_name || companyDeliveryMeta.name || '');
    setText('delivery_address_lines', addr.address || '');
    setText('delivery_state', addr.state || '');
    setText('delivery_country', addr.country || 'India');
    setText('delivery_pincode', addr.pincode || '');
    setText('delivery_gstin', addr.gstin || '');

    if (snapshot) snapshot.value = addr.snapshot || '';
}

function populateDeliverySelect(selectedId) {
    const select = document.getElementById('company_delivery_address_id');
    if (!select) return;
    select.innerHTML = '<option value="">Select Delivery Address</option>';
    companyDeliveryAddresses.forEach((addr) => {
        select.add(new Option(addr.display_text || addr.address_type, addr.id));
    });
    const prefer = selectedId || initialCompanyDeliveryId;
    if (prefer && companyDeliveryAddresses.some((a) => String(a.id) === String(prefer))) {
        select.value = prefer;
    } else {
        const def = companyDeliveryAddresses.find((a) => a.is_default) || companyDeliveryAddresses[0];
        if (def) select.value = def.id;
    }
    handleDeliveryChange();
}

async function loadCompanyDeliveryAddresses(preferId) {
    const statusText = document.getElementById('address-status-text');
    try {
        const res = await fetch(deliveryAddressesUrl, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error('Failed');
        companyDeliveryMeta = data.company || companyDeliveryMeta;
        companyDeliveryAddresses = data.addresses || [];
        populateDeliverySelect(preferId || data.default_id);
        if (statusText && !statusText.innerText.includes('supplier')) {
            statusText.innerText = `${companyDeliveryAddresses.length} delivery address(es) available`;
        }
        const mailing = document.getElementById('new_delivery_mailing_name');
        if (mailing && !mailing.value) {
            mailing.value = companyDeliveryMeta.legal_name || companyDeliveryMeta.name || '';
        }
        const gstin = document.getElementById('new_delivery_gstin');
        if (gstin && !gstin.value && data.company?.gstin) gstin.value = data.company.gstin;
    } catch (e) {
        console.error('Failed to load delivery addresses:', e);
        if (statusText) statusText.innerText = 'Could not load delivery addresses';
    }
}

async function handleSupplierChange(supplierId) {
    const statusText = document.getElementById('address-status-text');
    const billingSnapshot = document.getElementById('billing_address');

    if (!supplierId) {
        supplierAddresses = [];
        if (billingSnapshot) billingSnapshot.value = '';
        renderBillingDetails(null, null);
        if (!isInitialSupplierLoad) {
            setPoCreditDays(0);
        }
        return;
    }

    if (!isInitialSupplierLoad) {
        const supSelect = document.getElementById('supplier_id');
        const supOpt = supSelect ? supSelect.querySelector(`option[value="${supplierId}"]`) : null;
        if (supOpt && supOpt.dataset.creditDays !== undefined) {
            const sDays = parseInt(supOpt.dataset.creditDays, 10);
            setPoCreditDays(!isNaN(sDays) && sDays >= 0 ? sDays : 0);
        }
    }

    if (statusText) statusText.innerText = 'Loading vendor billing details...';
    try {
        const res = await fetch(`<?php echo e(url('/masters/customers')); ?>/${supplierId}/addresses`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            supplierAddresses = data.addresses || [];
            const billingId = data.default_billing_id;
            const addr = supplierAddresses.find((a) => String(a.id) === String(billingId)) || supplierAddresses[0] || null;
            renderBillingDetails(data.customer || null, addr);
            if (statusText) statusText.innerText = 'Vendor billing loaded automatically';
        }
    } catch (e) {
        console.error('Failed to load supplier addresses:', e);
        if (statusText) statusText.innerText = 'Could not load vendor billing';
    }
}

function handleDeliveryChange() {
    const select = document.getElementById('company_delivery_address_id');
    const addr = companyDeliveryAddresses.find((a) => String(a.id) === String(select?.value));
    renderDeliveryDetails(addr || null);
}

function handleWarehouseChange(selectEl) {
    // Delivery address is chosen from company delivery addresses (Tally-style consignee).
}

function openDeliveryAddressModal() {
    const modal = document.getElementById('delivery-address-modal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    const err = document.getElementById('new-delivery-error');
    if (err) { err.classList.add('hidden'); err.textContent = ''; }
}

function closeDeliveryAddressModal() {
    const modal = document.getElementById('delivery-address-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
}

async function saveDeliveryAddress() {
    const err = document.getElementById('new-delivery-error');
    const addressType = document.getElementById('new_delivery_address_type')?.value?.trim() || '';
    const address = document.getElementById('new_delivery_address')?.value?.trim() || '';
    if (!addressType || !address) {
        if (err) { err.textContent = 'Address type and address are required.'; err.classList.remove('hidden'); }
        return;
    }

    const payload = {
        address_type: addressType,
        mailing_name: document.getElementById('new_delivery_mailing_name')?.value?.trim() || '',
        address,
        state: document.getElementById('new_delivery_state')?.value?.trim() || '',
        country: document.getElementById('new_delivery_country')?.value?.trim() || 'India',
        pincode: document.getElementById('new_delivery_pincode')?.value?.trim() || '',
        gstin: document.getElementById('new_delivery_gstin')?.value?.trim() || '',
        is_default: !!document.getElementById('new_delivery_is_default')?.checked,
    };

    try {
        const res = await fetch(deliveryAddressesStoreUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Could not save address');
        }
        closeDeliveryAddressModal();
        document.getElementById('new_delivery_address_type').value = '';
        document.getElementById('new_delivery_address').value = '';
        await loadCompanyDeliveryAddresses(data.address?.id);
    } catch (e) {
        if (err) { err.textContent = e.message || 'Could not save address'; err.classList.remove('hidden'); }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const poLines = document.getElementById('po-lines');
    if (poLines) {
        poLines.addEventListener('change', function (event) {
            if (!event.target) return;
            if (event.target.classList.contains('po-brand')) {
                handlePoBrandChange(event.target);
            } else if (event.target.classList.contains('po-product')) {
                handlePoProductChange(event.target);
            }
        });
    }

    const btnNew = document.getElementById('btn-new-delivery-address');
    if (btnNew) btnNew.addEventListener('click', openDeliveryAddressModal);

    loadCompanyDeliveryAddresses(initialCompanyDeliveryId);

    const supSelect = document.getElementById('supplier_id');
    if (supSelect && supSelect.value) {
        handleSupplierChange(supSelect.value);
    }
    isInitialSupplierLoad = false;
    recalcPoDueDate();
    updateExpectedQuickHighlight();

    // Attach event delegation for options click
    const dropdownList = document.getElementById('po-dropdown-list');
    if (dropdownList) {
        dropdownList.addEventListener('click', function (e) {
            const item = e.target.closest('.po-dropdown-item');
            if (item && item.dataset.id) {
                selectPoProduct(item.dataset.id);
            }
        });
    }

    // Form validation check before submitting
    const poForm = document.querySelector('form[action="<?php echo e(route('purchasing.orders.store')); ?>"]');
    if (poForm) {
        poForm.addEventListener('submit', function (e) {
            let firstInvalid = null;
            let missingType = null;
            document.querySelectorAll('#po-lines tr').forEach(row => {
                const brandSelect = row.querySelector('.po-brand');
                const select = row.querySelector('.po-product');
                const searchInput = row.querySelector('.po-product-search');

                if (brandSelect && !brandSelect.value) {
                    if (!firstInvalid) {
                        firstInvalid = brandSelect;
                        missingType = 'brand';
                    }
                    brandSelect.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
                } else if (select && !select.value) {
                    if (!firstInvalid) {
                        firstInvalid = searchInput || select;
                        missingType = 'product';
                    }
                    if (searchInput) {
                        searchInput.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
                    }
                }
            });
            if (firstInvalid) {
                e.preventDefault();
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (missingType === 'brand') {
                    alert('Please select a brand for all line items before saving.');
                } else {
                    alert('Please select a product for all line items before saving.');
                }
            }
        });
    }

    toggleFreightFields();
    recalculatePoTotals();
});

// Close dropdown on outside click
document.addEventListener('click', function(event) {
    const menu = document.getElementById('po-product-dropdown-menu');
    if (!menu || menu.classList.contains('hidden')) return;

    if (menu.contains(event.target)) return;
    if (activeRow && activeRow.querySelector('.po-product-picker-container')?.contains(event.target)) return;

    closePoProductDropdown();
});

// Reposition or close dropdown on scroll
window.addEventListener('scroll', function() {
    const menu = document.getElementById('po-product-dropdown-menu');
    if (menu && !menu.classList.contains('hidden') && activeRow) {
        const container = activeRow.querySelector('.po-product-picker-container');
        if (container) {
            const rect = container.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > window.innerHeight) {
                closePoProductDropdown();
                return;
            }
        }
        positionPoDropdown();
    }
}, true);

// Reposition on window resize
window.addEventListener('resize', function() {
    const menu = document.getElementById('po-product-dropdown-menu');
    if (menu && !menu.classList.contains('hidden')) {
        positionPoDropdown();
    }
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/purchasing/orders/create.blade.php ENDPATH**/ ?>