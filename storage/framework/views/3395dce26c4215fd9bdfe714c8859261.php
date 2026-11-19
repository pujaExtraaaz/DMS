<?php
    $company = $workspace->company();
    $branch = $workspace->branch();
    $year = $workspace->financialYear();
?>
<div
    class="books-context"
    aria-label="Books context"
    x-data="{ open: null }"
    @keydown.escape.window="open = null"
    @click.outside="open = null"
>
    <div class="menu" :class="{ 'is-open': open === 'company' }" data-menu="company">
        <button
            type="button"
            class="menu-trigger"
            @click="open = open === 'company' ? null : 'company'"
            :aria-expanded="open === 'company'"
        >
            <span class="menu-meta">
                <span class="menu-kicker">Company</span>
                <span class="menu-value"><?php echo e($company->name ?? 'Select company'); ?></span>
            </span>
            <svg class="menu-caret" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/></svg>
        </button>
        <div class="menu-panel" x-show="open === 'company'" x-cloak @click.stop>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $workspace->companies(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <form method="POST" action="<?php echo e(tally_route('books.tally.context.company')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="company_id" value="<?php echo e($option->id); ?>">
                    <button type="submit" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-current' => $company && $company->is($option)]); ?>">
                        <strong><?php echo e($option->name); ?></strong>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($option->city): ?>
                            <small><?php echo e($option->city); ?></small>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </button>
                </form>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <p class="menu-empty">No active company.</p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <a class="menu-link" href="<?php echo e(tally_route('books.tally.companies.create')); ?>">+ New company</a>
        </div>
    </div>

    <div class="menu" :class="{ 'is-open': open === 'branch' }" data-menu="branch">
        <button
            type="button"
            class="menu-trigger"
            @click="open = open === 'branch' ? null : 'branch'"
            :aria-expanded="open === 'branch'"
        >
            <span class="menu-meta">
                <span class="menu-kicker">Branch</span>
                <span class="menu-value"><?php echo e($branch->name ?? 'Select branch'); ?></span>
            </span>
            <svg class="menu-caret" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/></svg>
        </button>
        <div class="menu-panel" x-show="open === 'branch'" x-cloak @click.stop>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $company): ?>
                <p class="menu-empty">Select a company first.</p>
            <?php else: ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $workspace->branches(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <form method="POST" action="<?php echo e(tally_route('books.tally.context.branch')); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="branch_id" value="<?php echo e($option->id); ?>">
                        <button type="submit" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-current' => $branch && $branch->is($option)]); ?>">
                            <strong><?php echo e($option->name); ?></strong>
                            <small><?php echo e($option->code); ?></small>
                        </button>
                    </form>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <p class="menu-empty">This company has no active branch.</p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <a class="menu-link" href="<?php echo e(tally_route('books.tally.companies.branches.create', $company)); ?>">+ New branch</a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <div class="menu" :class="{ 'is-open': open === 'year' }" data-menu="year">
        <button
            type="button"
            class="menu-trigger"
            @click="open = open === 'year' ? null : 'year'"
            :aria-expanded="open === 'year'"
        >
            <span class="menu-meta">
                <span class="menu-kicker">Financial year</span>
                <span class="menu-value"><?php echo e($year->name ?? 'Select year'); ?></span>
            </span>
            <svg class="menu-caret" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/></svg>
        </button>
        <div class="menu-panel" x-show="open === 'year'" x-cloak @click.stop>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $company): ?>
                <p class="menu-empty">Select a company first.</p>
            <?php else: ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $workspace->financialYears(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <form method="POST" action="<?php echo e(tally_route('books.tally.context.financial-year')); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="financial_year_id" value="<?php echo e($option->id); ?>">
                        <button type="submit" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-current' => $year && $year->is($option)]); ?>">
                            <strong><?php echo e($option->name); ?></strong>
                            <small><?php echo e($option->rangeLabel()); ?></small>
                        </button>
                    </form>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <p class="menu-empty">This company has no active financial year.</p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <a class="menu-link" href="<?php echo e(tally_route('books.tally.companies.financial-years.create', $company)); ?>">+ New financial year</a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <button
        class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-500 hover:bg-slate-50 hover:text-slate-700"
        type="button"
        data-open-search
        title="Search (Alt+F)"
        aria-label="Search"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        </svg>
    </button>
</div>
<?php /**PATH D:\xampp\htdocs\dms\resources\views/tally/components/shell/topbar.blade.php ENDPATH**/ ?>