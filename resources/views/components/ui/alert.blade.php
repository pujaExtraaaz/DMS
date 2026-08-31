@props([
    'type' => 'info',
    'message' => null,
    'dismissible' => false,
])

@php
    $styles = [
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'error' => 'bg-red-50 text-red-800 border-red-200',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
        'info' => 'bg-sky-50 text-sky-800 border-sky-200',
    ];

    $classes = $styles[$type] ?? $styles['info'];
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition
    {{ $attributes->merge(['class' => 'rounded-lg border px-4 py-3 text-sm '.$classes]) }}
    role="alert"
>
    <div class="flex items-start justify-between gap-3">
        <div class="flex-1">
            @if ($message)
                {{ $message }}
            @else
                {{ $slot }}
            @endif
        </div>

        @if ($dismissible)
            <button type="button" @click="show = false" class="shrink-0 opacity-70 hover:opacity-100">
                <span class="sr-only">Dismiss</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        @endif
    </div>
</div>
