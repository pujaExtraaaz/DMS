@props(['title', 'section' => null, 'description' => null])
<header class="page-head">
    <div>
        @if ($section)
            <p class="kicker">{{ $section }}</p>
        @endif
        <h1>{{ $title }}</h1>
        @if ($description)
            <p class="page-desc">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="page-actions">{{ $actions }}</div>
    @endisset
</header>
<div class="page-body">
    {{ $slot }}
</div>
