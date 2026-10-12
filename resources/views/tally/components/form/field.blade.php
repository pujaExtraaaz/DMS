@props([
    'name',
    'label',
    'required' => false,
    'size' => null, // xs|sm|md|lg|full — omit for auto
    'hint' => null,
])
@php
    $sizeClass = match ($size) {
        'xs' => 'field--xs',
        'sm' => 'field--sm',
        'md' => 'field--md',
        'lg' => 'field--lg',
        'full' => 'field--full',
        default => null,
    };
@endphp
<div {{ $attributes->class(['field', $sizeClass]) }} @if ($size) data-size="{{ $size }}" @endif>
    <label for="{{ $name }}">
        {{ $label }}
        @if ($required)
            <abbr title="Required">*</abbr>
        @endif
    </label>
    <div class="field-control">
        {{ $slot }}
        @if ($hint)
            <p class="field-hint">{{ $hint }}</p>
        @endif
        @error($name)
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>
</div>
