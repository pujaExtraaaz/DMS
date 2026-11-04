<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'column',
    'currentSort' => request('sort'),
    'currentDirection' => request('direction', 'asc'),
    'defaultDirection' => 'asc',
    'align' => 'left',
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
    'column',
    'currentSort' => request('sort'),
    'currentDirection' => request('direction', 'asc'),
    'defaultDirection' => 'asc',
    'align' => 'left',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $isActive = ($currentSort === $column);
    $nextDirection = $isActive
        ? ($currentDirection === 'asc' ? 'desc' : 'asc')
        : $defaultDirection;

    $params = request()->query();
    $params['sort'] = $column;
    $params['direction'] = $nextDirection;
    unset($params['page']);

    $url = url()->current() . '?' . http_build_query($params);

    $alignmentClass = match($align) {
        'center' => 'text-center justify-center',
        'right' => 'text-right justify-end',
        default => 'text-left justify-start',
    };
?>

<th scope="col" <?php echo e($attributes->merge(['class' => 'px-4 py-3 text-xs font-semibold tracking-wider select-none text-' . $align])); ?>>
    <a
        href="<?php echo e($url); ?>"
        data-sort-link
        data-column="<?php echo e($column); ?>"
        data-direction="<?php echo e($nextDirection); ?>"
        class="group inline-flex items-center gap-1.5 <?php echo e($alignmentClass); ?> <?php echo e($isActive ? 'text-indigo-600 font-bold' : 'text-slate-600 hover:text-slate-900'); ?> transition-colors"
        title="Sort by <?php echo e(\App\Support\TitleCase::format($column)); ?> (<?php echo e($nextDirection === 'asc' ? 'A-Z / 1-9' : 'Z-A / 9-1'); ?>)"
    >
        <span><?php echo e(\App\Support\TitleCase::format((string)$slot)); ?></span>

        <span class="inline-flex shrink-0 items-center">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isActive): ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($currentDirection === 'asc'): ?>
                    <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                    </svg>
                <?php else: ?>
                    <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php else: ?>
                <svg class="h-3.5 w-3.5 text-slate-300 opacity-60 group-hover:text-slate-500 group-hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                </svg>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </span>
    </a>
</th>

<?php /**PATH D:\xampp\htdocs\DMS\resources\views/components/ui/sortable-th.blade.php ENDPATH**/ ?>