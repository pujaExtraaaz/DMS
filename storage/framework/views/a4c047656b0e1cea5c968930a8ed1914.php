<?php $__env->startSection('title', 'Payroll Runs'); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal6215b73eadd41faa041d2efbe191c532 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6215b73eadd41faa041d2efbe191c532 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.hrms.payroll-nav','data' => ['active' => 'runs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('hrms.payroll-nav'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['active' => 'runs']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6215b73eadd41faa041d2efbe191c532)): ?>
<?php $attributes = $__attributesOriginal6215b73eadd41faa041d2efbe191c532; ?>
<?php unset($__attributesOriginal6215b73eadd41faa041d2efbe191c532); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6215b73eadd41faa041d2efbe191c532)): ?>
<?php $component = $__componentOriginal6215b73eadd41faa041d2efbe191c532; ?>
<?php unset($__componentOriginal6215b73eadd41faa041d2efbe191c532); ?>
<?php endif; ?>
<div class="mb-4"><h1 class="text-lg font-bold">Payroll Runs</h1></div>
<div class="overflow-hidden rounded-2xl border bg-white">
<table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Period</th><th class="px-4 py-3 text-left">Branch</th><th class="px-4 py-3 text-left">Employees</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3"></th></tr></thead>
<tbody class="divide-y"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><tr>
<td class="px-4 py-3 font-medium"><?php echo e(sprintf('%02d', $item->month)); ?>/<?php echo e($item->year); ?></td>
<td class="px-4 py-3"><?php echo e($item->branch?->name ?? 'All'); ?></td>
<td class="px-4 py-3"><?php echo e($item->lines_count); ?></td>
<td class="px-4 py-3"><?php echo e($item->status); ?></td>
<td class="px-4 py-3 text-right"><a href="<?php echo e(route('hrms.payroll-runs.show', $item)); ?>" class="text-indigo-600">Open</a></td>
</tr><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?><tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No payroll runs.</td></tr><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></tbody></table>
</div><?php echo e($items->links()); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\dms\resources\views/hrms/payroll-runs/index.blade.php ENDPATH**/ ?>