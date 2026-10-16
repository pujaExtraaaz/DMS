@props(['disabled' => false])

@php
    $fieldName = $attributes->get('name');
    $isNameField = ($fieldName && \App\Support\NameCase::isNameField($fieldName))
        || $attributes->has('data-is-name');
    $nameClasses = $isNameField ? ' uppercase' : '';
    if ($isNameField && ! $attributes->has('data-is-name')) {
        $attributes = $attributes->merge(['data-is-name' => 'true']);
    }
@endphp

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm' . $nameClasses]) }}>

