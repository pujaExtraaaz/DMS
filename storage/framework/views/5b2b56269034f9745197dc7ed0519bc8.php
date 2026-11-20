<?php $__env->startSection('title', 'Party Statement'); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Party Statement','description' => 'Detailed double-entry ledger statement and transaction history for any customer or supplier.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Party Statement','description' => 'Detailed double-entry ledger statement and transaction history for any customer or supplier.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer): ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(request()->fullUrlWithQuery(['export' => 'csv'])); ?>" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
            <a href="<?php echo e(request()->fullUrlWithQuery(['export' => 'pdf'])); ?>" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
         <?php $__env->endSlot(); ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2">
        <select name="customer_id" class="rounded-lg border-gray-300 text-sm" required>
            <option value="">Select party</option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <option value="<?php echo e($c->id); ?>" <?php if(request('customer_id')==$c->id): echo 'selected'; endif; ?>><?php echo e($c->name); ?> (<?php echo e($c->party_type_label); ?>)</option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </select>
        <input type="date" name="date_from" value="<?php echo e($dateFrom); ?>" class="rounded-lg border-gray-300 text-sm">
        <input type="date" name="date_to" value="<?php echo e($dateTo); ?>" class="rounded-lg border-gray-300 text-sm">
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
Run Statement <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
    </form>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer): ?>
        <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900"><?php echo e($customer->name); ?></h2>
                    <div class="text-xs text-slate-500 mt-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-200 text-slate-700 mr-2"><?php echo e($customer->party_type_label); ?></span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->gstin): ?><span class="mr-2">GSTIN: <span class="font-mono"><?php echo e($customer->gstin); ?></span></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->pan): ?><span class="mr-2">PAN: <span class="font-mono"><?php echo e($customer->pan); ?></span></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <span>Credit status: <strong class="capitalize"><?php echo e($customer->credit_status); ?></strong></span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->cheque_bounce_count > 0): ?>
                            <span class="ml-2 text-rose-600">· Bounce count: <?php echo e($customer->cheque_bounce_count); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <div class="text-xs text-slate-500 md:text-right">
                    Statement Period: <span class="font-semibold text-slate-700"><?php echo e(\Carbon\Carbon::parse($dateFrom)->format('d M Y')); ?></span> to <span class="font-semibold text-slate-700"><?php echo e(\Carbon\Carbon::parse($dateTo)->format('d M Y')); ?></span>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Opening Balance</span>
                    <div class="mt-1 text-lg font-bold text-slate-800">₹<?php echo e(number_format($opening, 2)); ?></div>
                </div>
                <div class="rounded-lg border border-rose-100 bg-white p-3 shadow-sm">
                    <span class="text-xs font-medium text-rose-600 uppercase tracking-wider">Total Debit</span>
                    <div class="mt-1 text-lg font-bold text-rose-600">₹<?php echo e(number_format($totalDebit, 2)); ?></div>
                </div>
                <div class="rounded-lg border border-emerald-100 bg-white p-3 shadow-sm">
                    <span class="text-xs font-medium text-emerald-600 uppercase tracking-wider">Total Credit</span>
                    <div class="mt-1 text-lg font-bold text-emerald-600">₹<?php echo e(number_format($totalCredit, 2)); ?></div>
                </div>
                <div class="rounded-lg border border-indigo-100 bg-white p-3 shadow-sm">
                    <span class="text-xs font-medium text-indigo-600 uppercase tracking-wider">Closing Balance</span>
                    <div class="mt-1 text-lg font-bold text-indigo-700">₹<?php echo e(number_format($closing, 2)); ?></div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200 shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Voucher Number</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Particulars</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Voucher Type</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Debit</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Credit</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <tr class="bg-slate-50/50 font-medium">
                        <td class="px-3 py-2 text-slate-400">—</td>
                        <td class="px-3 py-2 text-slate-400">—</td>
                        <td class="px-3 py-2 font-semibold text-slate-700">Opening Balance</td>
                        <td class="px-3 py-2"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-200 text-slate-700">Opening</span></td>
                        <td class="px-3 py-2 text-right text-slate-400">—</td>
                        <td class="px-3 py-2 text-right text-slate-400">—</td>
                        <td class="px-3 py-2 text-right font-bold text-slate-800">₹<?php echo e(number_format($opening, 2)); ?></td>
                    </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-3 py-2.5 whitespace-nowrap text-slate-700">
                                <?php echo e($entry->date instanceof \DateTimeInterface ? $entry->date->format('d M Y') : \Carbon\Carbon::parse($entry->date)->format('d M Y')); ?>

                            </td>
                            <td class="px-3 py-2.5 font-mono text-xs font-semibold text-indigo-600">
                                <?php echo e($entry->voucher_number); ?>

                            </td>
                            <td class="px-3 py-2.5 text-slate-700">
                                <?php echo e($entry->particulars); ?>

                            </td>
                            <td class="px-3 py-2.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo e(str_contains(strtolower($entry->voucher_type), 'purchase') ? 'bg-amber-100 text-amber-800' : (str_contains(strtolower($entry->voucher_type), 'sales') ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-800')); ?>">
                                    <?php echo e($entry->voucher_type); ?>

                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-rose-600">
                                <?php echo e($entry->debit > 0 ? '₹'.number_format($entry->debit, 2) : '—'); ?>

                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-emerald-600">
                                <?php echo e($entry->credit > 0 ? '₹'.number_format($entry->credit, 2) : '—'); ?>

                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-bold text-slate-800">
                                ₹<?php echo e(number_format($entry->balance, 2)); ?>

                            </td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-slate-500">
                                No ledger entries in this period.
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
                <tfoot class="bg-slate-100/80 font-semibold border-t-2 border-slate-300">
                    <tr>
                        <td colspan="4" class="px-3 py-2.5 text-right text-slate-700 uppercase tracking-wider text-xs">Period Totals</td>
                        <td class="px-3 py-2.5 text-right font-mono text-rose-600">₹<?php echo e(number_format($totalDebit, 2)); ?></td>
                        <td class="px-3 py-2.5 text-right font-mono text-emerald-600">₹<?php echo e(number_format($totalCredit, 2)); ?></td>
                        <td class="px-3 py-2.5 text-right font-mono font-bold text-indigo-700">₹<?php echo e(number_format($closing, 2)); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php else: ?>
        <p class="text-slate-500 text-sm">Select a party to view the statement.</p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/reporting/party-statement.blade.php ENDPATH**/ ?>