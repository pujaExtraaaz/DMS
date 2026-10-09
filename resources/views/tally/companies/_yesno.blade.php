@php
    $current = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    $index = array_search($current, $values, true);
    $index = $index === false ? 0 : $index;
@endphp
<div class="yn" tabindex="0" data-yn data-index="{{ $index }}" data-options='@json($options)' data-values='@json($values)' @if (! empty($autofocus)) autofocus @endif>
    <span>{{ $label }}</span>
    <strong data-yn-label>{{ $options[$index] }}</strong>
    <input type="hidden" name="{{ $name }}" value="{{ $values[$index] }}">
</div>
