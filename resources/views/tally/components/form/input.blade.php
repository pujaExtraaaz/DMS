@props([
    'name',
    'type' => 'text',
    'value' => '',
    'disabled' => false,
    'size' => null, // xs|sm|md|lg|full — only used in filter bars
])
@php
    $sizeClass = $size ? 'input--'.$size : null;
@endphp
<input
    id="{{ $name }}"
    name="{{ $name }}"
    type="{{ $type }}"
    value="{{ $value }}"
    @disabled($disabled)
    @if ($size) data-size="{{ $size }}" @endif
    {{ $attributes->merge(['class' => trim('input '.($sizeClass ?? ''))]) }}
>
