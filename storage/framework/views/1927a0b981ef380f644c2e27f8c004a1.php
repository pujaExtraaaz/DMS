<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'error' => null,
    'help' => null,
    'required' => false,
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
    'label' => null,
    'name' => null,
    'type' => 'text',
    'error' => null,
    'help' => null,
    'required' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $fieldName = $name ?? $attributes->get('name');
    $hasError = $error || ($fieldName && $errors->has($fieldName));
    $errorMessage = $error ?? ($fieldName ? $errors->first($fieldName) : null);

    $isNameField = ($fieldName && \App\Support\NameCase::isNameField($fieldName))
        || $attributes->has('data-is-name')
        || (isset($label) && str_contains(strtolower($label), 'name') && !str_contains(strtolower($label), 'user') && !str_contains(strtolower($label), 'file'));

    $inputClasses = 'block w-full rounded-lg border-gray-300 shadow-sm text-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500';
    if ($hasError) {
        $inputClasses .= ' border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500';
    }

    if ($isNameField && ! $attributes->has('data-is-name')) {
        $attributes = $attributes->merge(['data-is-name' => 'true']);
    }

    if ($attributes->has('placeholder')) {
        $attributes = $attributes->merge(['placeholder' => \App\Support\TitleCase::format($attributes->get('placeholder'))]);
    }
?>

<div <?php echo e($attributes->only('class')->merge(['class' => 'space-y-1'])); ?>>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($label): ?>
        <label <?php if($fieldName): ?> for="<?php echo e($fieldName); ?>" <?php endif; ?> class="block text-sm font-medium text-gray-700">
            <?php echo e(\App\Support\TitleCase::format($label)); ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($required): ?>
                <span class="text-red-500">*</span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </label>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <input
        <?php if($fieldName): ?> id="<?php echo e($fieldName); ?>" name="<?php echo e($fieldName); ?>" <?php endif; ?>
        type="<?php echo e($type); ?>"
        <?php echo e($attributes->except('class')->merge(['class' => $inputClasses])); ?>

    />

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($help && ! $hasError): ?>
        <p class="text-xs text-gray-500"><?php echo e(\App\Support\TitleCase::format($help)); ?></p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasError): ?>
        <p class="text-xs text-red-600"><?php echo e($errorMessage); ?></p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH D:\xampp\htdocs\dms\resources\views/components/ui/input.blade.php ENDPATH**/ ?>