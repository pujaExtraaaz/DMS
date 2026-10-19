@once
    <link rel="stylesheet" href="{{ asset('css/books-sidebar.css') }}?v=7">
@endonce

@php
    use Illuminate\Support\Facades\Route;

    $booksSidebar = config('books_sidebar', []);
    $resolveItems = function (array $items): array {
        $out = [];
        foreach ($items as $item) {
            $routeName = $item['route'] ?? null;
            if (! $routeName || ! Route::has($routeName)) {
                continue;
            }
            $activePatterns = $item['active'] ?? [$routeName];
            $out[] = [
                'label' => $item['label'],
                'url' => route($routeName),
                'route' => $routeName,
                'keywords' => $item['keywords'] ?? '',
                'isActive' => collect($activePatterns)->contains(fn ($p) => request()->routeIs($p)),
            ];
        }

        return $out;
    };

    $gatewayItems = $resolveItems($booksSidebar['gateway'] ?? []);
    $categories = [];
    $flatSearch = [];
    $activeCategoryKey = null;

    foreach ($booksSidebar['categories'] ?? [] as $category) {
        $items = $resolveItems($category['items'] ?? []);
        if ($items === []) {
            continue;
        }
        $hasActive = collect($items)->contains(fn ($i) => $i['isActive']);
        if ($hasActive) {
            $activeCategoryKey = $category['key'];
        }
        $categories[] = [
            'key' => $category['key'],
            'letter' => $category['letter'],
            'label' => $category['label'],
            'items' => $items,
            'hasActive' => $hasActive,
        ];
        foreach ($items as $item) {
            $flatSearch[] = [
                'id' => $category['key'].':'.$item['route'],
                'categoryKey' => $category['key'],
                'category' => $category['label'],
                'label' => $item['label'],
                'url' => $item['url'],
                'haystack' => mb_strtolower($category['label'].' '.$item['label'].' '.($item['keywords'] ?? '')),
            ];
        }
    }
@endphp

@once
<script>
window.booksAqNavFactory = window.booksAqNavFactory || function booksAqNavFactory(payload) {
    return {
        query: '',
        selectedIndex: 0,
        openMap: {},
        index: payload.index || [],
        _categories: payload.categories || [],
        _activeKey: payload.activeKey || null,
        init() {
            // Only expand the active category (or first) — restoring every prior open group
            // made the Books column look overcrowded / “cooked”.
            this._categories.forEach((c) => { this.openMap[c.key] = false; });
            const preferred = this._activeKey
                || (this._categories.find((c) => c.hasActive)?.key)
                || this._categories[0]?.key
                || null;
            if (preferred) {
                this.openMap[preferred] = true;
            }
            this.$watch('query', () => { this.selectedIndex = 0; });
            this._onKey = (e) => {
                if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                    const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
                    if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target?.isContentEditable) {
                        return;
                    }
                    if (!this.$el || !this.$el.offsetParent) return;
                    e.preventDefault();
                    this.$refs.booksSearch?.focus();
                }
            };
            window.addEventListener('keydown', this._onKey);
        },
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) return [];
            return this.index.filter((row) => row.haystack.includes(q)).slice(0, 40);
        },
        isOpen(key) {
            return !!this.openMap[key];
        },
        toggle(key) {
            // Accordion: one open category at a time keeps the aside scannable.
            const willOpen = !this.openMap[key];
            Object.keys(this.openMap).forEach((k) => { this.openMap[k] = false; });
            this.openMap[key] = willOpen;
        },
        move(delta) {
            const len = this.filtered.length;
            if (!len) return;
            this.selectedIndex = (this.selectedIndex + delta + len) % len;
        },
        openSelected() {
            const hit = this.filtered[this.selectedIndex];
            if (hit?.url) window.location.href = hit.url;
        },
        onEscape() {
            if (this.query) {
                this.query = '';
                this.selectedIndex = 0;
                return;
            }
            this.$refs.booksSearch?.blur();
        },
    };
};
</script>
@endonce

{{-- No x-cloak on root: Alpine delay / localStorage collapse must not blank the whole Books column --}}
<aside
    class="books-aside"
    aria-label="Accounting Books navigation"
    x-bind:class="booksAsideCollapsed && 'is-collapsed'"
>
    <div
        class="books-aside__inner"
        x-data="window.booksAqNavFactory({
            categories: @js(collect($categories)->map(fn ($c) => ['key' => $c['key'], 'hasActive' => $c['hasActive']])->values()),
            activeKey: @js($activeCategoryKey ?: 'a'),
            index: @js($flatSearch),
        })"
    >
        <div class="books-aside__header">
            <p class="books-aside__title">Accounting Books</p>
        </div>

        @if($gatewayItems !== [])
            <div class="books-aside__gateway">
                @foreach($gatewayItems as $g)
                    <a href="{{ $g['url'] }}" class="books-aside__gateway-link {{ $g['isActive'] ? 'is-active' : '' }}">{{ $g['label'] }}</a>
                @endforeach
            </div>
        @endif

        <div class="books-aside__search">
            <svg class="books-aside__search-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.6-4.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" />
            </svg>
            <input
                type="search"
                x-ref="booksSearch"
                x-model="query"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="openSelected()"
                @keydown.escape.prevent="onEscape()"
                placeholder="Search in Accounting… (Ctrl+K)"
                autocomplete="off"
                aria-label="Search Accounting Books navigation"
                aria-controls="books-aside-search-results"
            >
        </div>

        <div
            id="books-aside-search-results"
            class="books-aside__results"
            x-show="query.trim().length > 0"
            x-cloak
            role="listbox"
        >
            <template x-if="filtered.length === 0">
                <div class="books-aside__empty">No matching accounting pages</div>
            </template>
            <template x-for="(hit, idx) in filtered" :key="hit.id">
                <a
                    :href="hit.url"
                    class="books-aside__result"
                    :class="idx === selectedIndex && 'is-active'"
                    role="option"
                    :aria-selected="idx === selectedIndex"
                    @mouseenter="selectedIndex = idx"
                >
                    <span class="books-aside__result-cat" x-text="hit.category"></span>
                    <span class="books-aside__result-label" x-text="hit.label"></span>
                </a>
            </template>
        </div>

        <nav class="books-aside__nav" x-show="query.trim().length === 0" aria-label="Accounting categories">
            @foreach($categories as $category)
                <div class="books-aside__cat" data-books-cat="{{ $category['key'] }}">
                    <button
                        type="button"
                        class="books-aside__cat-btn"
                        :class="{
                            'is-open': isOpen('{{ $category['key'] }}'),
                            'is-active-parent': {{ $category['hasActive'] ? 'true' : 'false' }}
                        }"
                        @click="toggle('{{ $category['key'] }}')"
                        :aria-expanded="isOpen('{{ $category['key'] }}') ? 'true' : 'false'"
                    >
                        <span class="books-aside__cat-label">{{ $category['label'] }}</span>
                        <svg class="books-aside__chevron" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div class="books-aside__items" x-show="isOpen('{{ $category['key'] }}')" x-cloak>
                        @foreach($category['items'] as $item)
                            <a
                                href="{{ $item['url'] }}"
                                class="books-aside__item {{ $item['isActive'] ? 'is-active' : '' }}"
                            >{{ $item['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </div>
</aside>
