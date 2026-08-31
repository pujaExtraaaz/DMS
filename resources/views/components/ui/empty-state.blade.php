@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center']) }}>
    @isset($icon)
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
            {{ $icon }}
        </div>
    @endisset

    <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>

    @if ($description)
        <p class="mt-2 max-w-md text-sm text-gray-500">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-6">
            {{ $action }}
        </div>
    @endisset
</div>
