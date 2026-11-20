<?php $__env->startSection('title', 'HR Dashboard'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
<h1 class="text-xl font-bold">HR Dashboard</h1>
<form method="GET" class="flex gap-2"><input type="date" name="date" value="<?php echo e($date->toDateString()); ?>" class="rounded-lg border-slate-300 text-sm"><button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">Go</button></form>
</div>
<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Active employees</div><div class="text-2xl font-bold"><?php echo e($activeEmployees); ?></div></div>
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Present / Late</div><div class="text-2xl font-bold"><?php echo e($present->count()); ?></div></div>
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Absent</div><div class="text-2xl font-bold"><?php echo e($absent->count()); ?></div></div>
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">On leave</div><div class="text-2xl font-bold"><?php echo e($onLeave->count()); ?></div></div>
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Pending leave</div><div class="text-2xl font-bold"><?php echo e($pendingLeave); ?></div><a href="<?php echo e(route('hrms.leave-requests.index', ['status' => 'pending'])); ?>" class="text-xs text-indigo-600">Review →</a></div>
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Pending regularisations</div><div class="text-2xl font-bold"><?php echo e($pendingRegularisations); ?></div><a href="<?php echo e(route('hrms.regularisations.index')); ?>" class="text-xs text-indigo-600">Review →</a></div>
</div>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($latestPayroll): ?>
<div class="mb-4 rounded-2xl border border-indigo-100 bg-indigo-50/60 px-4 py-3 text-sm flex flex-wrap items-center justify-between gap-2">
    <span>Latest payroll: <strong><?php echo e(sprintf('%02d', $latestPayroll->month)); ?>/<?php echo e($latestPayroll->year); ?></strong> · <?php echo e($latestPayroll->status); ?></span>
    <a href="<?php echo e(route('hrms.payroll-runs.show', $latestPayroll)); ?>" class="font-semibold text-indigo-700">Open run →</a>
</div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<div class="mb-4 flex flex-wrap gap-2 text-sm">
<a class="rounded-full border px-3 py-1.5" href="<?php echo e(route('hrms.reports.attendance-register')); ?>">Attendance register</a>
<a class="rounded-full border px-3 py-1.5" href="<?php echo e(route('hrms.reports.late-ot')); ?>">Late / OT</a>
<a class="rounded-full border px-3 py-1.5" href="<?php echo e(route('hrms.reports.leave-utilisation')); ?>">Leave utilisation</a>
<a class="rounded-full border px-3 py-1.5" href="<?php echo e(route('hrms.payroll-runs.index')); ?>">Payroll runs</a>
<a class="rounded-full border px-3 py-1.5" href="<?php echo e(route('hrms.punch-conflicts.index')); ?>">Punch conflicts</a>
<a class="rounded-full border px-3 py-1.5" href="<?php echo e(route('hrms.comp-off.index')); ?>">Comp-off credit</a>
<a class="rounded-full border px-3 py-1.5" href="<?php echo e(route('hrms.regularisations.index')); ?>">Regularisations</a>
</div>
<div class="grid gap-6 lg:grid-cols-2">
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = [['Present / Late',$present],['Absent',$absent],['Late',$late],['On leave',$onLeave]]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$title,$list]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
<?php $cardTitle = $title.' · '.$date->format('d M Y'); ?>
<?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => $cardTitle]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cardTitle)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<ul class="max-h-64 space-y-1 overflow-y-auto text-sm"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><li><?php echo e($row->employee?->name); ?> <span class="text-slate-400"><?php echo e($row->check_in ?? '—'); ?>–<?php echo e($row->check_out ?? '—'); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($row->leaveType)): ?> · <?php echo e($row->leaveType->code); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></span></li><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?><li class="text-slate-500">None</li><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></ul>
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
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
</div>
<?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Leave balances (sample)','class' => 'mt-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Leave balances (sample)','class' => 'mt-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<table class="min-w-full text-sm"><thead><tr class="text-left text-xs uppercase text-slate-500"><th class="py-2">Employee</th><th>Type</th><th>Closing</th></tr></thead>
<tbody><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $leaveBalances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><tr class="border-t"><td class="py-2"><?php echo e($b->employee?->name); ?></td><td><?php echo e($b->leaveType?->code); ?></td><td><?php echo e($b->closing_balance); ?></td></tr><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?></tbody></table>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\dms\resources\views/hrms/dashboard/index.blade.php ENDPATH**/ ?>