@php
    $buttons = \Tally\Support\Shell\TallyPanel::buttons();
@endphp
<aside class="tally-keys" aria-label="Function keys">
    @foreach ($buttons as $button)
        @if ($button['href'])
            <a @class(['is-current' => $button['current']]) href="{{ $button['href'] }}">{{ $button['key'] }}: {{ $button['label'] }}</a>
        @else
            <button type="button" data-function-{{ $button['action'] }}>{{ $button['key'] }}: {{ $button['label'] }}</button>
        @endif
    @endforeach
</aside>
