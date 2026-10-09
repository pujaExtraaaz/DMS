@php
    $current = (string) ($current ?? '');
    $selected = $current !== '' ? ($options[$current] ?? '') : '';
    $alterBase = $alter ?? null;
    if (! $alterBase && ! empty($create) && str_ends_with((string) parse_url($create, PHP_URL_PATH), '/create')) {
        $alterBase = preg_replace('#/create$#', '/__ID__/edit', $create);
    }
@endphp
<div class="picker" data-picker @if (! empty($create)) data-create-url="{{ $create }}" @endif>
    <input class="input picker-query" data-picker-query autocomplete="off" placeholder="{{ $placeholder ?? 'Select' }}" value="{{ $selected }}">
    <select class="picker-store" name="{{ $name }}" tabindex="-1" aria-hidden="true" @required(empty($optional))>
        <option value="">{{ $placeholder ?? 'Select' }}</option>
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected($current === (string) $value) @foreach (($meta[$value] ?? []) as $key => $metaValue) data-{{ $key }}="{{ $metaValue }}" @endforeach>{{ $label }}</option>
        @endforeach
    </select>
    <div class="picker-list" hidden>
        @foreach ($options as $value => $label)
            <button type="button" data-picker-choice data-value="{{ $value }}" @foreach (($meta[$value] ?? []) as $key => $metaValue) data-{{ $key }}="{{ $metaValue }}" @endforeach>{{ $label }}</button>
        @endforeach
        @if (! empty($create))
            <a href="{{ $create }}">Create</a>
        @endif
    </div>
    @if ($alterBase)
        <a class="picker-alter" data-picker-alter data-base="{{ $alterBase }}" href="{{ $current !== '' ? str_replace('__ID__', $current, $alterBase) : '#' }}" @if ($current === '') hidden @endif>Alter</a>
    @endif
</div>
