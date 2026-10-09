@props(['name', 'value' => '', 'disabled' => false])
<textarea id="{{ $name }}" name="{{ $name }}" @disabled($disabled) {{ $attributes->merge(['class' => 'input']) }}>{{ $value }}</textarea>
