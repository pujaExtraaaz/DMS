@php
    $buttons = \Tally\Support\Shell\TallyPanel::buttons();
@endphp
<aside class="books-keys" aria-label="Function keys">
    @foreach ($buttons as $button)
        @if ($button['href'])
            <a @class(['is-current' => $button['current']]) href="{{ $button['href'] }}">
                <kbd>{{ $button['key'] }}</kbd>
                <span>{{ $button['label'] }}</span>
            </a>
        @else
            <button type="button" data-function-{{ $button['action'] }}>
                <kbd>{{ $button['key'] }}</kbd>
                <span>{{ $button['label'] }}</span>
            </button>
        @endif
    @endforeach
</aside>
