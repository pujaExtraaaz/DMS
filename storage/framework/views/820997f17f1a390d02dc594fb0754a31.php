<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'active' => 'runs',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'active' => 'runs',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $tabs = [
        'runs' => [
            'label' => 'Payroll Runs',
            'route' => route('hrms.payroll-runs.index'),
            'match' => request()->routeIs('hrms.payroll-runs.*'),
        ],
        'structures' => [
            'label' => 'Salary Structures',
            'route' => route('hrms.salary-structures.index'),
            'match' => request()->routeIs('hrms.salary-structures.*'),
        ],
        'heads' => [
            'label' => 'Pay Heads',
            'route' => route('hrms.pay-heads.index'),
            'match' => request()->routeIs('hrms.pay-heads.*'),
        ],
        'compoff' => [
            'label' => 'Comp-off',
            'route' => route('hrms.comp-off.index'),
            'match' => request()->routeIs('hrms.comp-off.*'),
        ],
    ];

    $creates = [
        'runs' => ['label' => 'New Run', 'href' => route('hrms.payroll-runs.create'), 'title' => 'Create payroll run'],
        'structures' => ['label' => 'New Structure', 'href' => route('hrms.salary-structures.create'), 'title' => 'Create salary structure'],
        'heads' => ['label' => 'Add Pay Head', 'href' => route('hrms.pay-heads.create'), 'title' => 'Create pay head'],
        'compoff' => ['label' => 'Credit Comp-off', 'href' => route('hrms.comp-off.index'), 'title' => 'Credit compensatory off'],
    ];

    $current = collect($tabs)->search(fn ($tab, $key) => $active === $key || ($tab['match'] ?? false));
    if ($current === false) {
        $current = 'runs';
    }
    $createBtn = $creates[$current] ?? $creates['runs'];
?>

<div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex w-full items-center justify-end border-b border-slate-100 bg-slate-50/80 px-4 py-3">
        <a href="<?php echo e($createBtn['href']); ?>"
           title="<?php echo e($createBtn['title']); ?>"
           class="inline-flex items-center gap-1.5 rounded-full border-2 border-indigo-600 bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-500">
            <span class="flex h-5 w-5 items-center justify-center rounded-full border border-white/80 text-[11px] leading-none">+</span>
            <?php echo e($createBtn['label']); ?>

        </a>
    </div>

    <nav class="flex flex-wrap gap-1 px-2 py-2 sm:px-3" aria-label="Payroll">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php $isActive = $key === $current; ?>
            <a href="<?php echo e($tab['route']); ?>"
               class="rounded-full border px-4 py-2 text-sm font-medium transition
                      <?php echo e($isActive
                          ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm'
                          : 'border-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-900'); ?>">
                <?php echo e($tab['label']); ?>

            </a>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </nav>
</div>
<?php /**PATH D:\xampp\htdocs\dms\resources\views/components/hrms/payroll-nav.blade.php ENDPATH**/ ?>