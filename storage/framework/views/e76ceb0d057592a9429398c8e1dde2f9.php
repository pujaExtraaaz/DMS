<?php $__env->startSection('title', 'Punch Conflicts'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-xl font-bold">Punch Conflicts</h1>
        <p class="text-sm text-slate-500">Days where both eSSL device and web/manual punches exist. Prefer one source to rebuild attendance.</p>
    </div>
    <a href="<?php echo e(route('hrms.attendances.index')); ?>" class="text-sm text-indigo-600">← Attendance</a>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="date" name="from" value="<?php echo e($from); ?>" class="rounded-lg border-slate-300 text-sm">
    <input type="date" name="to" value="<?php echo e($to); ?>" class="rounded-lg border-slate-300 text-sm">
    <button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Filter</button>
</form>

<div class="space-y-4">
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $conflicts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
    <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
            <div>
                <div class="font-semibold text-slate-900"><?php echo e($c['employee']?->name ?? 'Employee #'.$c['employee']?->id); ?></div>
                <div class="text-xs text-slate-500"><?php echo e($c['employee']?->employee_code); ?> · <?php echo e(\Carbon\Carbon::parse($c['date'])->format('d M Y')); ?></div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($c['attendance']): ?>
                    <div class="mt-1 text-xs text-slate-500">Current attendance: <?php echo e($c['attendance']->status); ?> · <?php echo e($c['attendance']->check_in); ?>–<?php echo e($c['attendance']->check_out); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <form method="POST" action="<?php echo e(route('hrms.punch-conflicts.resolve')); ?>" class="flex flex-wrap gap-2">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="employee_id" value="<?php echo e($c['employee']->id); ?>">
                <input type="hidden" name="date" value="<?php echo e($c['date']); ?>">
                <button name="prefer" value="essl" class="rounded-lg border border-emerald-600 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Prefer eSSL</button>
                <button name="prefer" value="web" class="rounded-lg border border-indigo-600 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Prefer Web/Manual</button>
            </form>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 text-sm">
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="mb-1 text-xs font-semibold uppercase text-slate-500">eSSL</div>
                <ul class="space-y-0.5"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $c['essl']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><li><?php echo e($p->punched_at->format('H:i:s')); ?> <span class="text-slate-400"><?php echo e($p->direction); ?></span></li><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?></ul>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="mb-1 text-xs font-semibold uppercase text-slate-500">Web / Manual</div>
                <ul class="space-y-0.5"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $c['other']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><li><?php echo e($p->punched_at->format('H:i:s')); ?> <span class="text-slate-400"><?php echo e($p->source); ?> / <?php echo e($p->direction); ?></span></li><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?></ul>
            </div>
        </div>
    </div>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    <div class="rounded-2xl border bg-white px-4 py-10 text-center text-slate-500">No punch conflicts in this range.</div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\dms\resources\views/hrms/punch-conflicts/index.blade.php ENDPATH**/ ?>