@props([
    'title',
    'description' => null,
])

{{--
  App top-bar already shows the page title via @yield('title').
  Keep an accessible heading, then put actions on their own clear row
  so search/buttons never collide with a shrinking h1.
--}}
<div {{ $attributes->merge(['class' => 'mb-4 sm:mb-6']) }}>
    <h1 class="sr-only">{{ \App\Support\TitleCase::format($title) }}</h1>

    @if ($description)
        <p class="mb-3 text-sm text-gray-500">{{ \App\Support\TitleCase::format($description) }}</p>
    @endif

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
