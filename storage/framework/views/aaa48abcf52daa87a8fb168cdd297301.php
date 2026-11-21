
<?php
    $fyCompanyId = auth()->user()?->company_id;
    $periodYears = \App\Domains\Organization\Models\FinancialYear::query()
        ->when($fyCompanyId, fn ($q) => $q->where('company_id', $fyCompanyId))
        ->orderByDesc('starts_on')
        ->get(['id', 'name', 'starts_on', 'ends_on', 'is_current', 'is_closed']);
    $currentFy = $periodYears->firstWhere('is_current', true) ?? $periodYears->first();
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($periodYears->isNotEmpty()): ?>
    <div class="relative shrink-0" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
        <button
            type="button"
            @click.stop="open = !open"
            title="Switch period"
            class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 sm:px-3 py-1.5 text-xs font-semibold transition
                <?php echo e(($currentFy?->is_closed ?? false) ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'); ?>"
        >
            <span class="opacity-70 hidden sm:inline">Period:</span>
            <span><?php echo e($currentFy?->name ?? 'Select'); ?></span>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($currentFy?->is_closed): ?>
                <span class="rounded bg-red-600 px-1 text-[10px] text-white">CLOSED</span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <svg class="h-3.5 w-3.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition
            class="absolute right-0 z-[80] mt-1.5 w-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
            role="menu"
            aria-label="Financial years"
        >
            <div class="border-b border-slate-100 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                Switch financial year
            </div>
            <div class="max-h-72 overflow-y-auto py-1">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $periodYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <form method="POST" action="<?php echo e(route('organization.financial-years.set-current', $fy)); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="redirect" value="<?php echo e(url()->full()); ?>">
                        <button
                            type="submit"
                            role="menuitem"
                            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm transition
                                <?php echo e($fy->is_current ? 'bg-emerald-50 font-semibold text-emerald-800' : 'text-slate-700 hover:bg-slate-50'); ?>"
                        >
                            <span class="min-w-0">
                                <span class="block truncate"><?php echo e($fy->name); ?></span>
                                <span class="block text-[11px] font-normal text-slate-400">
                                    <?php echo e(optional($fy->starts_on)->format('d M Y')); ?> – <?php echo e(optional($fy->ends_on)->format('d M Y')); ?>

                                </span>
                            </span>
                            <span class="shrink-0 flex items-center gap-1">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($fy->is_current): ?>
                                    <span class="rounded-full bg-emerald-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">Current</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($fy->is_closed): ?>
                                    <span class="rounded-full bg-slate-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">Closed</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                        </button>
                    </form>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('hasanyrole', 'super-admin|owner|client-admin')): ?>
                <div class="border-t border-slate-100 px-3 py-2">
                    <a href="<?php echo e(route('organization.financial-years.index')); ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Manage financial years →</a>
                </div>
            <?php else: ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['organization.view', 'organization.manage'])): ?>
                    <div class="border-t border-slate-100 px-3 py-2">
                        <a href="<?php echo e(route('organization.financial-years.index')); ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Manage financial years →</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH resources/views/partials/period-switcher.blade.php ENDPATH**/ ?>