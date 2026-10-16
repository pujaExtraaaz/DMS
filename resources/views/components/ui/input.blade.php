@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'error' => null,
    'help' => null,
    'required' => false,
])

@php
    $fieldName = $name ?? $attributes->get('name');
    $hasError = $error || ($fieldName && $errors->has($fieldName));
    $errorMessage = $error ?? ($fieldName ? $errors->first($fieldName) : null);

    $isNameField = ($fieldName && \App\Support\NameCase::isNameField($fieldName))
        || $attributes->has('data-is-name')
        || (isset($label) && str_contains(strtolower($label), 'name') && !str_contains(strtolower($label), 'user') && !str_contains(strtolower($label), 'file'));

    $inputClasses = 'block w-full rounded-lg border-gray-300 shadow-sm text-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500';
    if ($isNameField) {
        $inputClasses .= ' uppercase';
    }
    if ($hasError) {
        $inputClasses .= ' border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500';
    }

    if ($isNameField && ! $attributes->has('data-is-name')) {
        $attributes = $attributes->merge(['data-is-name' => 'true']);
    }

    if ($attributes->has('placeholder')) {
        $attributes = $attributes->merge(['placeholder' => \App\Support\TitleCase::format($attributes->get('placeholder'))]);
    }
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1']) }}>
    @if ($label)
        <label @if($fieldName) for="{{ $fieldName }}" @endif class="block text-sm font-medium text-gray-700">
            {{ \App\Support\TitleCase::format($label) }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <input
        @if($fieldName) id="{{ $fieldName }}" name="{{ $fieldName }}" @endif
        type="{{ $type }}"
        {{ $attributes->except('class')->merge(['class' => $inputClasses]) }}
    />

    @if ($help && ! $hasError)
        <p class="text-xs text-gray-500">{{ \App\Support\TitleCase::format($help) }}</p>
    @endif

    @if ($hasError)
        <p class="text-xs text-red-600">{{ $errorMessage }}</p>
    @endif
</div>
