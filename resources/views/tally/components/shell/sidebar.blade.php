@props(['sections', 'shellNav'])
<aside id="sidebar" class="sidebar">
    <div class="brand">
        <span class="brand-mark" aria-hidden="true">TC</span>
        <span>
            <strong>{{ config('app.name') }}</strong>
            <small>Books</small>
        </span>
    </div>
    <nav class="nav" aria-label="Primary">
        @foreach ($sections as $section)
            @if (empty($section['children']))
                <a
                    href="{{ tally_route($section['route']) }}"
                    data-nav-item
                    data-nav-section="{{ $section['key'] }}"
                    @class(['nav-link', 'is-active' => $shellNav->isCurrent($section)])
                    @if ($shellNav->isCurrent($section)) aria-current="page" @endif
                >
                    <span title="{{ $section['label'] }}">{{ $section['label'] }}</span>
                    @if ($keys = $shellNav->shortcutKeys($section))
                        <kbd class="nav-kbd">{{ $keys }}</kbd>
                    @endif
                </a>
            @else
                <details class="nav-group" data-nav-section="{{ $section['key'] }}" @if ($shellNav->sectionIsCurrent($section)) open @endif>
                    <summary data-nav-item>
                        <span class="nav-label"><span class="nav-chevron" aria-hidden="true"></span><span title="{{ $section['label'] }}">{{ $section['label'] }}</span></span>
                        @if ($keys = $shellNav->shortcutKeys($section))
                            <kbd class="nav-kbd">{{ $keys }}</kbd>
                        @endif
                    </summary>
                    <div class="nav-children">
                        @foreach ($section['children'] as $item)
                            <a
                                href="{{ $shellNav->href($item) }}"
                                data-nav-item
                                @class(['nav-link', 'is-active' => $shellNav->isCurrent($item)])
                                @if ($shellNav->isCurrent($item)) aria-current="page" @endif
                            >
                                <span title="{{ $item['label'] }}">{{ $item['label'] }}</span>
                                @if ($keys = $shellNav->shortcutKeys($item))
                                    <kbd class="nav-kbd">{{ $keys }}</kbd>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif
        @endforeach
    </nav>
</aside>
