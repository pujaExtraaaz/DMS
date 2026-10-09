@props(['name', 'value' => ''])
<input {{ $attributes->merge(['class' => 'input', 'inputmode' => 'decimal', 'data-rate' => true]) }} name="{{ $name }}" value="{{ $value }}">
