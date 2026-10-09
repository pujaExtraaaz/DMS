@props(['name', 'value' => ''])
<input {{ $attributes->merge(['class' => 'input', 'inputmode' => 'decimal', 'data-discount' => true]) }} name="{{ $name }}" value="{{ $value }}">
