@props([
    'search' => request('search', request('q', '')),
    'searchPlaceholder' => 'Search...',
    'searchName' => 'search',
    'resetUrl' => url()->current(),
    'hasActiveFilters' => false,
])

@php
    $searchTerm = (string) $search;
    // Check if any filter/search is active
    $queryParams = request()->except(['page']);
    $isActive = $hasActiveFilters || !empty($searchTerm) || count(array_filter($queryParams, fn($v) => $v !== null && $v !== '')) > 0;
@endphp

<div {{ $attributes->merge(['class' => 'dms-table-toolbar mb-4 space-y-3']) }}>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        {{-- Left: Search & Filter controls --}}
        <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-0">
            {{-- Responsive Dynamic Search Bar --}}
            <div
                class="relative flex-1 min-w-[200px] max-w-md"
                x-data="{
                    query: '{{ addslashes($searchTerm) }}',
                    clear() {
                        this.query = '';
                        const input = this.$refs.searchInput;
                        input.value = '';
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }"
            >
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </div>

                <input
                    x-ref="searchInput"
                    type="text"
                    name="{{ $searchName }}"
                    x-model="query"
                    data-dynamic-search
                    placeholder="{{ $searchPlaceholder }}"
                    autocomplete="off"
                    class="block w-full rounded-lg border-slate-300 pl-9 pr-9 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition"
                />

                {{-- Clear 'X' button --}}
                <button
                    type="button"
                    x-show="query.length > 0"
                    x-cloak
                    @click="clear()"
                    class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 transition"
                    title="Clear search"
                >
                    <svg class="h-4 w-4 rounded-full p-0.5 hover:bg-slate-100" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Custom Contextual Filters Slot --}}
            @isset($filters)
                <div class="flex flex-wrap items-center gap-2">
                    {{ $filters }}
                </div>
            @endisset

            {{-- Reset All Filters / Search Button --}}
            @if($isActive)
                <a
                    href="{{ $resetUrl }}"
                    data-reset-filters
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 shadow-sm transition"
                    title="Reset all filters and search"
                >
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Reset</span>
                </a>
            @endif
        </div>

        {{-- Right: Actions Slot (Add, Export, etc.) --}}
        @isset($actions)
            <div class="flex items-center gap-2 shrink-0">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>

