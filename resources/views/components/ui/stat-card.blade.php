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
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-shadow duration-200']) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">{{ $value }}</p>

            @if ($change)
                <span class="inline-flex items-center mt-2.5 rounded-full px-2 py-0.5 text-xs font-medium {{ $changeColors[$changeType] ?? $changeColors['neutral'] }}">
                    {{ $change }}
                </span>
            @endif
        </div>

        @if ($icon)
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $accentStyles[$accent] ?? $accentStyles['indigo'] }} text-white shadow-lg">
                {!! $icon !!}
            </div>
        @endif
    </div>
</div>
