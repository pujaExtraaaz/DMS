@props([
    'label' => null,
    'name' => null,
    'error' => null,
    'help' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $fieldName = $name ?? $attributes->get('name');
    $hasError = $error || ($fieldName && $errors->has($fieldName));
    $errorMessage = $error ?? ($fieldName ? $errors->first($fieldName) : null);

    $selectClasses = 'block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500';
    if ($hasError) {
        $selectClasses .= ' border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500';
    }
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1']) }}>
    @if ($label)
        <label @if($fieldName) for="{{ $fieldName }}" @endif class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <select
        @if($fieldName) id="{{ $fieldName }}" name="{{ $fieldName }}" @endif
        {{ $attributes->except('class')->merge(['class' => $selectClasses]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>

    @if ($help && ! $hasError)
        <p class="text-xs text-gray-500">{{ $help }}</p>
    @endif

    @if ($hasError)
        <p class="text-xs text-red-600">{{ $errorMessage }}</p>
    @endif
</div>
