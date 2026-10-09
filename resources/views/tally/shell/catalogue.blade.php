<x-tally::layouts.app :title="$title">
    <div class="gateway">
        <nav class="gateway-menu" data-key-menu aria-label="{{ $title }}">
            <h2>{{ $title }}</h2>
            @if (! empty($items))
                @foreach ($items as $item)
                    <a @if ($loop->first) class="is-current" @endif href="{{ tally_route($item['route'], $item['params'] ?? []) }}">{{ $item['label'] }}</a>
                @endforeach
            @else
                @foreach ($groups as $heading => $rows)
                    <p class="gateway-group">{{ $heading }}</p>
                    @foreach ($rows as $item)
                        @if (! empty($item['route']) && tally_route_has($item['route']))
                            <a @if ($loop->parent->first && $loop->first) class="is-current" @endif href="{{ tally_route($item['route'], $item['params'] ?? []) }}">{{ $item['label'] }}</a>
                        @endif
                    @endforeach
                @endforeach
            @endif
            <a data-esc href="{{ $back }}">Quit</a>
        </nav>
    </div>
</x-layouts.app>
