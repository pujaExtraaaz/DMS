<x-tally::layouts.app title="Voucher Types">
    <x-tally::shell.page title="Voucher Types" section="Transactions" description="Choose a voucher type.">
        <nav class="tally-type-list" data-key-menu aria-label="Voucher types">
            @foreach ($types as $type)
                <a href="{{ $type['url'] }}">
                    @if ($type['key'] !== '')
                        <kbd>{{ $type['key'] }}</kbd>
                    @endif
                    {{ $type['label'] }}
                </a>
            @endforeach
        </nav>
    </x-shell.page>
</x-layouts.app>
