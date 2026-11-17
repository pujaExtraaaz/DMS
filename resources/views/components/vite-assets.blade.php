@props([
    'entries' => ['resources/css/app.css', 'resources/js/app.js'],
    'cssOnly' => false,
])

@php
    $entries = is_array($entries) ? $entries : [$entries];
    if ($cssOnly) {
        $entries = ['resources/css/app.css'];
    }
@endphp

@if (dms_vite_ready($entries))
    @vite($entries)
@else
    {!! dms_vite_cdn_fallback(! $cssOnly) !!}
@endif
