<?php $__env->startSection('title', 'Holiday Calendars'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-4 flex items-center justify-between gap-3">
    <h1 class="text-xl font-bold text-slate-900">Holiday Calendars</h1>
    <a href="<?php echo e(route('hrms.holidays.create')); ?>" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">+ New Calendar</a>
</div>
<form method="GET" class="mb-4 flex gap-2">
    <input type="number" name="year" value="<?php echo e($filters['year'] ?? ''); ?>" placeholder="Year" class="rounded-lg border-slate-300 text-sm">
    <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Filter</button>
</form>
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr><th class="px-4 py-3">Year</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Branch</th><th class="px-4 py-3">Holidays</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <tr>
                    <td class="px-4 py-3"><?php echo e($item->year); ?></td>
                    <td class="px-4 py-3 font-medium"><?php echo e($item->name); ?></td>
                    <td class="px-4 py-3"><?php echo e($item->branch?->name ?? 'All branches'); ?></td>
                    <td class="px-4 py-3"><?php echo e($item->holidays_count); ?></td>
                    <td class="px-4 py-3"><?php echo e($item->is_published ? 'Published' : 'Draft'); ?></td>
                    <td class="px-4 py-3 text-right"><a href="<?php echo e(route('hrms.holidays.show', $item)); ?>" class="text-indigo-600 hover:underline">Open</a></td>
                </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No holiday calendars yet.</td></tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-4"><?php echo e($items->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\dms\resources\views/hrms/holidays/index.blade.php ENDPATH**/ ?>