@props([
    'title' => null,
    'description' => null,
    'padding' => true,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 shadow-sm']) }}>
    @if ($title || $description)
        <div class="px-6 py-4 border-b border-gray-100">
            @if ($title)
                <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
            @endif
            @if ($description)
                <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
            @endif
        </div>
    @endif

    <div @class(['px-6 py-5' => $padding])>
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
            {{ $footer }}
        </div>
    @endif
</div>
