@props([
    'variant' => 'default',
    'size' => 'md',
])

@php
    $variants = [
        'default' => 'bg-gray-100 text-gray-700 ring-gray-200',
        'neutral' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'primary' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'danger' => 'bg-red-50 text-red-700 ring-red-200',
        'info' => 'bg-sky-50 text-sky-700 ring-sky-200',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-0.5 text-xs',
        'lg' => 'px-3 py-1 text-sm',
    ];

    $classes = ($variants[$variant] ?? $variants['default']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full font-medium ring-1 ring-inset '.$classes]) }}>
    {{ $slot }}
</span>
