<?php
    $idx = $idx ?? 0;
    $selectedBrandId = $selectedBrandId ?? null;
    $selectedProductId = $selectedProductId ?? null;
    $selectedProductName = $selectedProductName ?? '';
    $batchNo = $batchNo ?? '';
    $uomId = $uomId ?? null;
    $quantity = $quantity ?? 1;
    $unitCost = $unitCost ?? 0;
    $taxPercent = $taxPercent ?? 0;
    $cgstPercent = $cgstPercent ?? 0;
    $sgstPercent = $sgstPercent ?? 0;
    $showRemove = $showRemove ?? false;
    $isBrandSelected = !empty($selectedBrandId);
?>
<tr class="po-line-row">
    <td class="px-3 py-2">
        <select name="items[<?php echo e($idx); ?>][brand_id]" class="po-brand block w-44 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white" required>
            <option value="">Select brand</option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <option value="<?php echo e($b->id); ?>" <?php if((string) $selectedBrandId === (string) $b->id): echo 'selected'; endif; ?>><?php echo e($b->name); ?></option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </select>
    </td>
    <td class="px-3 py-2">
        <div class="relative po-product-picker-container w-64">
            <select name="items[<?php echo e($idx); ?>][product_id]" class="po-product hidden">
                <option value="">Select product</option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <option value="<?php echo e($p->id); ?>" data-brand="<?php echo e($p->brand_id); ?>" data-tax="<?php echo e($p->tax_rate ?? 0); ?>" data-cost="<?php echo e($p->purchase_price ?? 0); ?>" data-uom="<?php echo e($p->base_uom_id); ?>" <?php if((string) $selectedProductId === (string) $p->id): echo 'selected'; endif; ?>><?php echo e($p->name); ?></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </select>
            <div class="relative">
                <input type="text"
                       class="po-product-search block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-14 <?php echo e($isBrandSelected ? 'bg-white' : 'bg-slate-100 text-slate-400 cursor-not-allowed'); ?>"
                       placeholder="<?php echo e($isBrandSelected ? 'Select product...' : 'Select brand first'); ?>"
                       autocomplete="off"
                       value="<?php echo e($selectedProductName); ?>"
                       <?php echo e($isBrandSelected ? '' : 'disabled'); ?>

                       onfocus="openPoProductDropdown(this)"
                       onclick="openPoProductDropdown(this)"
                       oninput="handlePoProductSearchInput(this)"
                       onkeydown="handlePoProductSearchKeydown(event, this)">
                <div class="absolute inset-y-0 right-0 flex items-center pr-1.5 gap-0.5">
                    <button type="button"
                            class="po-clear-product p-1 text-gray-400 hover:text-gray-600 transition"
                            title="Clear product"
                            style="<?php echo e($selectedProductName !== '' ? '' : 'display: none;'); ?>"
                            onclick="clearPoProductSelection(this)">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <button type="button"
                            class="po-dropdown-toggle p-1 text-gray-400 hover:text-gray-600 transition <?php echo e($isBrandSelected ? '' : 'opacity-40 pointer-events-none'); ?>"
                            tabindex="-1"
                            <?php echo e($isBrandSelected ? '' : 'disabled'); ?>

                            onclick="togglePoProductDropdown(this)">
                        <svg class="h-4 w-4 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </td>
    <td class="px-3 py-2">
        <input type="text" name="items[<?php echo e($idx); ?>][batch_no]" list="po-batch-list-<?php echo e($idx); ?>" class="po-batch-input block w-28 rounded-lg border-gray-300 text-sm" placeholder="Optional" value="<?php echo e($batchNo); ?>">
        <datalist id="po-batch-list-<?php echo e($idx); ?>"></datalist>
    </td>
    <td class="px-3 py-2">
        <select name="items[<?php echo e($idx); ?>][uom_id]" class="po-uom block w-24 rounded-lg border-gray-300 text-sm" required>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $uoms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <option value="<?php echo e($u->id); ?>" <?php if((string) $uomId === (string) $u->id): echo 'selected'; endif; ?>><?php echo e($u->code); ?></option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </select>
    </td>
    <td class="px-3 py-2"><input type="number" step="0.0001" name="items[<?php echo e($idx); ?>][quantity]" class="block w-20 rounded-lg border-gray-300 text-sm" value="<?php echo e($quantity); ?>" required></td>
    <td class="px-3 py-2"><input type="number" step="0.0001" name="items[<?php echo e($idx); ?>][unit_cost]" class="po-cost block w-28 rounded-lg border-gray-300 text-sm" value="<?php echo e($unitCost); ?>" required></td>
    <td class="px-3 py-2">
        <input type="number" step="0.01" min="0" max="100" name="items[<?php echo e($idx); ?>][tax_percent]" class="po-tax block w-20 rounded-lg border-gray-300 text-sm" value="<?php echo e($taxPercent); ?>">
    </td>
    <td class="px-3 py-2">
        <input type="number" step="0.01" min="0" max="100" name="items[<?php echo e($idx); ?>][cgst_percent]" class="po-cgst block w-20 rounded-lg border-gray-300 text-sm" value="<?php echo e($cgstPercent); ?>">
    </td>
    <td class="px-3 py-2">
        <input type="number" step="0.01" min="0" max="100" name="items[<?php echo e($idx); ?>][sgst_percent]" class="po-sgst block w-20 rounded-lg border-gray-300 text-sm" value="<?php echo e($sgstPercent); ?>">
    </td>
    <td class="px-3 py-2 text-right">
        <button type="button" onclick="removePoRow(this)" class="text-xs text-red-600 hover:text-red-800 po-remove-btn" <?php if(! $showRemove): ?> style="display:none;" <?php endif; ?>>Remove</button>
    </td>
</tr>
<?php /**PATH D:\xampp\htdocs\DMS\resources\views/purchasing/orders/partials/line-row.blade.php ENDPATH**/ ?>