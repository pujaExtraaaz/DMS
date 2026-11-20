<?php $__env->startSection('title', 'Sales Target Reports & Export'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Sales Target Reports</h1>
            <p class="text-xs sm:text-sm text-slate-500">Comprehensive target attainment reports with live invoice reconciliation and export capabilities.</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            
            <a href="<?php echo e(request()->fullUrlWithQuery(['export' => 'xlsx'])); ?>"
               class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-800 shadow-xs hover:bg-emerald-100 transition">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel (.xlsx)
            </a>

            
            <a href="<?php echo e(request()->fullUrlWithQuery(['export' => 'csv'])); ?>"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
        </div>
    </div>

    
    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <form method="GET" action="<?php echo e(route('sales-targets.reports')); ?>" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Owner Type</label>
                <select name="owner_type" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Owners</option>
                    <option value="party" <?php if(request('owner_type') === 'party'): echo 'selected'; endif; ?>>Party / Customer</option>
                    <option value="individual" <?php if(request('owner_type') === 'individual'): echo 'selected'; endif; ?>>Individual Salesman</option>
                    <option value="team" <?php if(request('owner_type') === 'team'): echo 'selected'; endif; ?>>Sales Team</option>
                    <option value="branch" <?php if(request('owner_type') === 'branch'): echo 'selected'; endif; ?>>Branch</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Party / Customer</label>
                <select name="customer_id" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Parties</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $parties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($p->id); ?>" <?php if(request('customer_id') == $p->id): echo 'selected'; endif; ?>><?php echo e($p->name); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Salesperson</label>
                <select name="salesperson_id" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Salespeople</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $salespeople; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($sp->id); ?>" <?php if(request('salesperson_id') == $sp->id): echo 'selected'; endif; ?>><?php echo e($sp->name); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Target Type</label>
                <select name="target_type" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Types</option>
                    <option value="value" <?php if(request('target_type') === 'value'): echo 'selected'; endif; ?>>Value-Wise</option>
                    <option value="product" <?php if(request('target_type') === 'product'): echo 'selected'; endif; ?>>Product-Wise</option>
                    <option value="sku" <?php if(request('target_type') === 'sku'): echo 'selected'; endif; ?>>SKU-Wise</option>
                    <option value="brand" <?php if(request('target_type') === 'brand'): echo 'selected'; endif; ?>>Brand-Wise</option>
                    <option value="category" <?php if(request('target_type') === 'category'): echo 'selected'; endif; ?>>Category-Wise</option>
                    <option value="combined" <?php if(request('target_type') === 'combined'): echo 'selected'; endif; ?>>Combined</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Statuses</option>
                    <option value="active" <?php if(request('status') === 'active'): echo 'selected'; endif; ?>>Active</option>
                    <option value="completed" <?php if(request('status') === 'completed'): echo 'selected'; endif; ?>>Completed</option>
                    <option value="pending_approval" <?php if(request('status') === 'pending_approval'): echo 'selected'; endif; ?>>Pending Approval</option>
                    <option value="cancelled" <?php if(request('status') === 'cancelled'): echo 'selected'; endif; ?>>Cancelled</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">From Date</label>
                <input type="date" name="date_from" value="<?php echo e(request('date_from')); ?>" class="w-full rounded-lg border-gray-300 text-xs">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Filter</button>
                <a href="<?php echo e(route('sales-targets.reports')); ?>" class="rounded-lg border px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">↺</a>
            </div>
        </form>
    </div>

    
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
            <thead class="bg-slate-50 text-slate-700 font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-3.5 py-3">Ref ID</th>
                    <th class="px-3.5 py-3">Target Name</th>
                    <th class="px-3.5 py-3">Owner</th>
                    <th class="px-3.5 py-3">Period</th>
                    <th class="px-3.5 py-3 text-right">Target (₹)</th>
                    <th class="px-3.5 py-3 text-right">Achieved (₹)</th>
                    <th class="px-3.5 py-3 text-right">Pending (₹)</th>
                    <th class="px-3.5 py-3 text-center">Attainment %</th>
                    <th class="px-3.5 py-3 text-center">Target Qty</th>
                    <th class="px-3.5 py-3 text-center">Achieved Qty</th>
                    <th class="px-3.5 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                <?php
                    $sumTarget = 0;
                    $sumAchieved = 0;
                    $sumPending = 0;
                ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $targets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $ach = $t->achievement;
                        $tAmt = (float) $t->target_amount;
                        $aAmt = $ach ? (float) $ach->achieved_amount : 0.0;
                        $pAmt = $ach ? (float) $ach->pending_amount : max(0, $tAmt - $aAmt);
                        $pct = $ach ? (float) $ach->achievement_percent : 0.0;

                        $sumTarget += $tAmt;
                        $sumAchieved += $aAmt;
                        $sumPending += $pAmt;
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-3.5 py-2.5 font-mono text-[11px] text-slate-500">TGT-<?php echo e(str_pad((string)$t->id, 5, '0', STR_PAD_LEFT)); ?></td>
                        <td class="px-3.5 py-2.5 font-bold text-slate-900">
                            <a href="<?php echo e(route('sales-targets.show', $t)); ?>" class="text-indigo-600 hover:underline">
                                <?php echo e($t->name); ?>

                            </a>
                        </td>
                        <td class="px-3.5 py-2.5">
                            <span class="font-medium text-slate-800"><?php echo e($t->owner_name); ?></span>
                            <span class="text-[10px] text-slate-400 block"><?php echo e(ucfirst($t->owner_type)); ?></span>
                        </td>
                        <td class="px-3.5 py-2.5 text-slate-600">
                            <?php echo e($t->starts_on->format('d M y')); ?> &rarr; <?php echo e($t->ends_on->format('d M y')); ?>

                        </td>
                        <td class="px-3.5 py-2.5 text-right font-semibold">₹ <?php echo e(number_format($tAmt, 2)); ?></td>
                        <td class="px-3.5 py-2.5 text-right font-bold text-emerald-700">₹ <?php echo e(number_format($aAmt, 2)); ?></td>
                        <td class="px-3.5 py-2.5 text-right font-semibold text-rose-700">₹ <?php echo e(number_format($pAmt, 2)); ?></td>
                        <td class="px-3.5 py-2.5 text-center font-bold <?php echo e($pct >= 100 ? 'text-emerald-700' : 'text-indigo-600'); ?>"><?php echo e($pct); ?>%</td>
                        <td class="px-3.5 py-2.5 text-center"><?php echo e($t->target_quantity > 0 ? number_format($t->target_quantity, 0) : '—'); ?></td>
                        <td class="px-3.5 py-2.5 text-center text-emerald-700 font-semibold"><?php echo e($ach && $ach->achieved_quantity > 0 ? number_format($ach->achieved_quantity, 0) : '—'); ?></td>
                        <td class="px-3.5 py-2.5 text-center">
                            <span class="rounded px-2 py-0.5 text-[10px] font-semibold <?php echo e($t->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'); ?>">
                                <?php echo e(ucfirst($t->status)); ?>

                            </span>
                        </td>
                    </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <tr>
                        <td colspan="11" class="py-12 text-center text-xs text-slate-400">No report records found.</td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($targets->isNotEmpty()): ?>
                <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-200">
                    <tr>
                        <td colspan="4" class="px-3.5 py-2.5 text-right uppercase">Page Totals:</td>
                        <td class="px-3.5 py-2.5 text-right font-bold">₹ <?php echo e(number_format($sumTarget, 2)); ?></td>
                        <td class="px-3.5 py-2.5 text-right font-bold text-emerald-700">₹ <?php echo e(number_format($sumAchieved, 2)); ?></td>
                        <td class="px-3.5 py-2.5 text-right font-bold text-rose-700">₹ <?php echo e(number_format($sumPending, 2)); ?></td>
                        <td class="px-3.5 py-2.5 text-center font-bold text-indigo-700">
                            <?php echo e($sumTarget > 0 ? round(($sumAchieved / $sumTarget) * 100, 1) : 0); ?>%
                        </td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </table>
    </div>

    <div>
        <?php echo e($targets->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/sales-targets/reports.blade.php ENDPATH**/ ?>