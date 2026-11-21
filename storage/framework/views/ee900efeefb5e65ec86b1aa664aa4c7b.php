<?php $__env->startSection('title', 'Payroll '.$run->month.'/'.$run->year); ?>
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
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
<div>
<a href="<?php echo e(route('hrms.payroll-runs.index')); ?>" class="text-sm text-indigo-600">← Runs</a>
<h1 class="text-xl font-bold">Payroll <?php echo e(sprintf('%02d', $run->month)); ?>/<?php echo e($run->year); ?></h1>
<p class="text-sm text-slate-500">Status: <?php echo e($run->status); ?> · <?php echo e($run->branch?->name ?? 'All branches'); ?></p>
</div>
<div class="flex flex-wrap gap-2">
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($run->status==='draft'): ?>
<form method="POST" action="<?php echo e(route('hrms.payroll-runs.recompute', $run)); ?>"><?php echo csrf_field(); ?><button class="rounded-lg border px-3 py-2 text-sm">Recompute</button></form>
<form method="POST" action="<?php echo e(route('hrms.payroll-runs.approve', $run)); ?>"><?php echo csrf_field(); ?><button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">Approve</button></form>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($run->status==='approved'): ?>
<form method="POST" action="<?php echo e(route('hrms.payroll-runs.lock', $run)); ?>"><?php echo csrf_field(); ?><button class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white">Lock</button></form>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<a href="<?php echo e(route('hrms.payroll-runs.bank-file', $run)); ?>" class="rounded-lg border px-3 py-2 text-sm">Bank CSV</a>
<a href="<?php echo e(route('hrms.payroll-runs.department-report', $run)); ?>" class="rounded-lg border px-3 py-2 text-sm">Dept report</a>
<a href="<?php echo e(route('hrms.payroll-runs.employee-report', $run)); ?>" class="rounded-lg border px-3 py-2 text-sm">Employee report</a>
</div>
</div>
<div class="overflow-hidden rounded-2xl border bg-white">
<table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-right">Payable</th><th class="px-4 py-3 text-right">LWP</th><th class="px-4 py-3 text-right">OT min</th><th class="px-4 py-3 text-right">Gross</th><th class="px-4 py-3 text-right">Ded.</th><th class="px-4 py-3 text-right">Net</th><th class="px-4 py-3"></th></tr></thead>
<tbody class="divide-y"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $run->lines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><tr>
<td class="px-4 py-3"><?php echo e($line->employee?->name); ?></td>
<td class="px-4 py-3 text-right"><?php echo e($line->payable_days); ?></td>
<td class="px-4 py-3 text-right"><?php echo e($line->lwp_days); ?></td>
<td class="px-4 py-3 text-right"><?php echo e($line->ot_minutes); ?></td>
<td class="px-4 py-3 text-right"><?php echo e(number_format((float)$line->gross_earnings,2)); ?></td>
<td class="px-4 py-3 text-right"><?php echo e(number_format((float)$line->total_deductions,2)); ?></td>
<td class="px-4 py-3 text-right font-semibold"><?php echo e(number_format((float)$line->net_payable,2)); ?></td>
<td class="px-4 py-3 text-right space-x-2">
<a class="text-indigo-600" href="<?php echo e(route('hrms.payroll-runs.payslip', [$run, $line])); ?>">Slip</a>
<a class="text-slate-600" href="<?php echo e(route('hrms.payroll-runs.payslip-pdf', [$run, $line])); ?>">PDF</a>
</td>
</tr><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?></tbody></table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\dms\resources\views/hrms/payroll-runs/show.blade.php ENDPATH**/ ?>