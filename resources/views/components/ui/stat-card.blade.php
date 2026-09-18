@props([
    'label',
    'value',
    'change' => null,
    'changeType' => 'neutral',
    'icon' => null,
    'accent' => 'indigo',
])

@php
    $changeColors = [
        'positive' => 'text-emerald-600 bg-emerald-50',
        'negative' => 'text-red-600 bg-red-50',
        'neutral' => 'text-slate-500 bg-slate-50',
        'warning' => 'text-amber-600 bg-amber-50',
    ];

    $accentStyles = [
        'indigo' => 'from-indigo-500 to-indigo-600 shadow-indigo-500/25',
        'emerald' => 'from-emerald-500 to-emerald-600 shadow-emerald-500/25',
        'amber' => 'from-amber-500 to-amber-600 shadow-amber-500/25',
        'rose' => 'from-rose-500 to-rose-600 shadow-rose-500/25',
        'violet' => 'from-violet-500 to-violet-600 shadow-violet-500/25',
        'sky' => 'from-sky-500 to-sky-600 shadow-sky-500/25',
    ];

    $iconHtml = $icon;

    if (isset($icon) && is_object($icon) && method_exists($icon, 'toHtml')) {
        $iconHtml = $icon->toHtml();
    } elseif (isset($icon) && $icon instanceof \Illuminate\View\ComponentSlot) {
        $iconHtml = $icon->toHtml();
    }
@endphp

<div {{ $attributes->merge([
    'class' => 'relative w-full min-w-0 h-[170px] rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-shadow duration-200'
]) }}>

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">

        <p class="min-w-0 pr-2 text-sm font-semibold uppercase tracking-wider text-slate-400">
            {{ $label }}
        </p>

        @if ($iconHtml || isset($icon))
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $accentStyles[$accent] ?? $accentStyles['indigo'] }} text-white shadow-lg">
                @if(isset($icon) && $icon instanceof \Illuminate\View\ComponentSlot)
                    {{ $icon }}
                @elseif(filled($iconHtml))
                    {!! $iconHtml !!}
                @endif
            </div>
        @endif

    </div>

    {{-- Main KPI value --}}
    <div class="mt-3 min-w-0">
        <p class="min-w-0 truncate text-3xl font-bold leading-none tracking-tight text-slate-900 sm:text-4xl">
            {{ $value }}
        </p>
    </div>

    {{-- Supporting information --}}
    @if ($change)
        <div class="mt-3 min-h-[24px]">
            <span class="inline-flex max-w-full items-center rounded-full px-3 py-1 text-xs font-medium {{ $changeColors[$changeType] ?? $changeColors['neutral'] }}">
                {{ $change }}
            </span>
        </div>
    @else
        <div class="mt-3 min-h-[24px]"></div>
    @endif

</div>