<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'active' => 'requests',
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
    'active' => 'requests',
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
        'requests' => [
            'label' => 'Leave Requests',
            'route' => route('hrms.leave-requests.index'),
            'match' => request()->routeIs('hrms.leave-requests.*'),
        ],
        'balances' => [
            'label' => 'Leave Balances',
            'route' => route('hrms.leave-balances.index'),
            'match' => request()->routeIs('hrms.leave-balances.*'),
        ],
        'types' => [
            'label' => 'Leave Types',
            'route' => route('hrms.leave-types.index'),
            'match' => request()->routeIs('hrms.leave-types.*'),
        ],
        'policies' => [
            'label' => 'Policies',
            'route' => route('hrms.leave-policies.index'),
            'match' => request()->routeIs('hrms.leave-policies.*'),
        ],
        'encashments' => [
            'label' => 'Encashment',
            'route' => route('hrms.leave-encashments.index'),
            'match' => request()->routeIs('hrms.leave-encashments.*'),
        ],
    ];

    $creates = [
        'requests' => ['label' => 'New Request', 'href' => route('hrms.leave-requests.create'), 'title' => 'Create leave request'],
        'balances' => ['label' => 'Allocate', 'href' => route('hrms.leave-balances.create'), 'title' => 'Allocate / adjust balance'],
        'types' => ['label' => 'Add Type', 'href' => route('hrms.leave-types.create'), 'title' => 'Create leave type'],
        'policies' => ['label' => 'Add Policy', 'href' => route('hrms.leave-policies.create'), 'title' => 'Create leave policy'],
        'encashments' => ['label' => 'New Encashment', 'href' => route('hrms.leave-encashments.create'), 'title' => 'Request encashment'],
    ];

    $current = collect($tabs)->search(fn ($tab, $key) => $active === $key || ($tab['match'] ?? false));
    if ($current === false) {
        $current = 'requests';
    }
    $createBtn = $creates[$current] ?? $creates['requests'];
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

    <nav class="flex flex-wrap gap-1 px-2 py-2 sm:px-3" aria-label="Leave management">
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
<?php /**PATH D:\xampp\htdocs\dms\resources\views/components/hrms/leave-nav.blade.php ENDPATH**/ ?>