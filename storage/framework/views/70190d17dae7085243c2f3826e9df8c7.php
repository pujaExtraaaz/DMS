<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'variant' => 'default',
    'size' => 'md',
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
    'variant' => 'default',
    'size' => 'md',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $variants = [
        'default' => 'bg-gray-100 text-gray-700 ring-gray-200',
        'neutral' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'primary' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'danger' => 'bg-red-50 text-red-700 ring-red-200',
        'info' => 'bg-sky-50 text-sky-700 ring-sky-200',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-0.5 text-xs',
        'lg' => 'px-3 py-1 text-sm',
    ];

    $classes = ($variants[$variant] ?? $variants['default']).' '.($sizes[$size] ?? $sizes['md']);
?>

<span <?php echo e($attributes->merge(['class' => 'inline-flex items-center rounded-full font-medium ring-1 ring-inset '.$classes])); ?>>
    <?php echo \App\Support\TitleCase::format((string) $slot); ?>

</span>
<?php /**PATH D:\xampp\htdocs\dms\resources\views/components/ui/badge.blade.php ENDPATH**/ ?>