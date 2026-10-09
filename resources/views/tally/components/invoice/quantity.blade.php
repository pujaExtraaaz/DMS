@props(['name', 'value' => ''])
<input {{ $attributes->merge(['class' => 'input', 'inputmode' => 'decimal', 'data-qty' => true]) }} name="{{ $name }}" value="{{ $value }}">
