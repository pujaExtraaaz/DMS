<?php $__env->startSection('title', 'Attendance Regularisation'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-4 flex justify-between"><h1 class="text-xl font-bold">Attendance Regularisation</h1><a href="<?php echo e(route('hrms.regularisations.create')); ?>" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">+ Request</a></div>
<div class="overflow-hidden rounded-2xl border bg-white">
<table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Requested</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3"></th></tr></thead>
<tbody class="divide-y"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><tr>
<td class="px-4 py-3"><?php echo e($item->employee?->name); ?></td>
<td class="px-4 py-3"><?php echo e($item->attendance_date?->format('d M Y')); ?></td>
<td class="px-4 py-3"><?php echo e($item->requested_check_in); ?> – <?php echo e($item->requested_check_out); ?></td>
<td class="px-4 py-3"><?php echo e($item->status); ?></td>
<td class="px-4 py-3 text-right space-x-2"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->status==='pending'): ?>
<form class="inline" method="POST" action="<?php echo e(route('hrms.regularisations.approve', $item)); ?>"><?php echo csrf_field(); ?><button class="text-emerald-600">Approve</button></form>
<form class="inline" method="POST" action="<?php echo e(route('hrms.regularisations.reject', $item)); ?>"><?php echo csrf_field(); ?><button class="text-rose-600">Reject</button></form>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td>
</tr><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?><tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No requests.</td></tr><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></tbody></table>
</div><?php echo e($items->links()); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\dms\resources\views/hrms/regularisations/index.blade.php ENDPATH**/ ?>