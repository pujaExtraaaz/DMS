@props(['name', 'label', 'required' => false])
<div {{ $attributes->class(['field', 'tally-co-row']) }}>
    <label for="{{ $name }}">
        {{ $label }}
        @if ($required)
            <abbr title="Required">*</abbr>
        @endif
    </label>
    <span>:</span>
    <div>
        {{ $slot }}
        @error($name)
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>
</div>
