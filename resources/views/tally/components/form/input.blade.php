@props(['name', 'type' => 'text', 'value' => '', 'disabled' => false])
<input
    id="{{ $name }}"
    name="{{ $name }}"
    type="{{ $type }}"
    value="{{ $value }}"
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'input']) }}
>
