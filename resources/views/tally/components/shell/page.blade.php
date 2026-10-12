@props(['title', 'section' => null, 'description' => null])
<div class="mb-4 sm:mb-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="sr-only">{{ $title }}</h1>
            @if ($section)
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $section }}</p>
            @endif
            @if ($description)
                <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2 shrink-0">{{ $actions }}</div>
        @endisset
    </div>
</div>
<div class="page-body space-y-4 sm:space-y-5">
    {{ $slot }}
</div>
