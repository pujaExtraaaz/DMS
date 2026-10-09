@props([
    'column',
    'currentSort' => request('sort'),
    'currentDirection' => request('direction', 'asc'),
    'defaultDirection' => 'asc',
    'align' => 'left',
])

@php
    $isActive = ($currentSort === $column);
    $nextDirection = $isActive
        ? ($currentDirection === 'asc' ? 'desc' : 'asc')
        : $defaultDirection;

    $params = request()->query();
    $params['sort'] = $column;
    $params['direction'] = $nextDirection;
    unset($params['page']);

    $url = url()->current() . '?' . http_build_query($params);

    $alignmentClass = match($align) {
        'center' => 'text-center justify-center',
        'right' => 'text-right justify-end',
        default => 'text-left justify-start',
    };
@endphp

<th scope="col" {{ $attributes->merge(['class' => 'px-4 py-3 text-xs font-semibold uppercase tracking-wider select-none text-' . $align]) }}>
    <a
        href="{{ $url }}"
        data-sort-link
        data-column="{{ $column }}"
        data-direction="{{ $nextDirection }}"
        class="group inline-flex items-center gap-1.5 {{ $alignmentClass }} {{ $isActive ? 'text-indigo-600 font-bold' : 'text-slate-600 hover:text-slate-900' }} transition-colors"
        title="Sort by {{ $column }} ({{ $nextDirection === 'asc' ? 'A-Z / 1-9' : 'Z-A / 9-1' }})"
    >
        <span>{{ $slot }}</span>

        <span class="inline-flex shrink-0 items-center">
            @if($isActive)
                @if($currentDirection === 'asc')
                    <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                    </svg>
                @else
                    <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                @endif
            @else
                <svg class="h-3.5 w-3.5 text-slate-300 opacity-60 group-hover:text-slate-500 group-hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                </svg>
            @endif
        </span>
    </a>
</th>

