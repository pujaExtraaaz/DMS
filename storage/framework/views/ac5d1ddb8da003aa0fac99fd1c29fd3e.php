<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label',
    'value',
    'change' => null,
    'changeType' => 'neutral',
    'icon' => null,
    'accent' => 'indigo',
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
    'label',
    'value',
    'change' => null,
    'changeType' => 'neutral',
    'icon' => null,
    'accent' => 'indigo',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $changeColors = [
        'positive' => 'text-emerald-600 bg-emerald-50',
        'negative' => 'text-red-600 bg-red-50',
        'neutral' => 'text-slate-500 bg-slate-50',
        'warning' => 'text-amber-600 bg-amber-50',
    ];

    $accentStyles = [
        'indigo' => 'from-indigo-500 to-indigo-600 shadow-indigo-500/25',
        'emerald' => 'from-emerald-500 to-emerald-600 shadow-emerald-500/25',
        'amber' => 'from-amber-500 to-amber-600 shadow-amber-500/25',
        'rose' => 'from-rose-500 to-rose-600 shadow-rose-500/25',
        'violet' => 'from-violet-500 to-violet-600 shadow-violet-500/25',
        'sky' => 'from-sky-500 to-sky-600 shadow-sky-500/25',
    ];

    $iconHtml = $icon;

    if (isset($icon) && is_object($icon) && method_exists($icon, 'toHtml')) {
        $iconHtml = $icon->toHtml();
    } elseif (isset($icon) && $icon instanceof \Illuminate\View\ComponentSlot) {
        $iconHtml = $icon->toHtml();
    }
?>

<div <?php echo e($attributes->merge([
    'class' => 'relative flex w-full min-w-0 min-h-[150px] flex-col rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-shadow duration-200'
])); ?>>

    
    <div class="flex items-start justify-between gap-4">

        <p class="min-w-0 pr-2 text-sm font-semibold tracking-wider text-slate-500">
            <?php echo e(\App\Support\TitleCase::format($label)); ?>

        </p>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($iconHtml || isset($icon)): ?>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br <?php echo e($accentStyles[$accent] ?? $accentStyles['indigo']); ?> text-white shadow-lg">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($icon) && $icon instanceof \Illuminate\View\ComponentSlot): ?>
                    <?php echo e($icon); ?>

                <?php elseif(filled($iconHtml)): ?>
                    <?php echo $iconHtml; ?>

                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    </div>

    
    <div class="mt-3 min-w-0 flex-1">
        <p class="min-w-0 break-words text-xl font-bold leading-snug tracking-tight text-slate-900 tabular-nums sm:text-2xl">
            <?php echo e($value); ?>

        </p>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($change): ?>
        <div class="mt-3 min-h-[24px]">
            <span class="inline-flex max-w-full items-center rounded-full px-3 py-1 text-xs font-medium <?php echo e($changeColors[$changeType] ?? $changeColors['neutral']); ?>">
                <?php echo e($change); ?>

            </span>
        </div>
    <?php else: ?>
        <div class="mt-3 min-h-[24px]"></div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</div><?php /**PATH D:\xampp\htdocs\DMS\resources\views/components/ui/stat-card.blade.php ENDPATH**/ ?>