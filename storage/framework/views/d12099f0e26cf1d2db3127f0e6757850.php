<?php
    $selectedSupplier = (string) ($selected ?? '');
?>
<select name="supplier_id" id="supplier_id" onchange="handleSupplierChange(this.value)" required
        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    <option value="">Select supplier</option>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
        <?php
            $sPhone = $s->phone ?? ($s->mobile ?? '');
        ?>
        <option value="<?php echo e($s->id); ?>"
                data-credit-days="<?php echo e($s->credit_days ?? 0); ?>"
                <?php if(!empty($sPhone)): ?> data-code="<?php echo e($sPhone); ?>" data-subtext="<?php echo e($sPhone); ?>" <?php elseif(!empty($s->code)): ?> data-code="<?php echo e($s->code); ?>" <?php endif; ?>
                <?php if($selectedSupplier === (string) $s->id): echo 'selected'; endif; ?>>
            <?php echo e($s->name); ?>

        </option>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
</select>
<?php /**PATH D:\xampp\htdocs\DMS\resources\views/purchasing/orders/partials/supplier-search.blade.php ENDPATH**/ ?>