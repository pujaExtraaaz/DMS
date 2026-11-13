<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'search' => request('search', request('q', '')),
    'searchPlaceholder' => 'Search...',
    'searchName' => 'search',
    'resetUrl' => url()->current(),
    'hasActiveFilters' => false,
    'autocompleteUrl' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'search' => request('search', request('q', '')),
    'searchPlaceholder' => 'Search...',
    'searchName' => 'search',
    'resetUrl' => url()->current(),
    'hasActiveFilters' => false,
    'autocompleteUrl' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $searchTerm = (string) $search;
    // Check if any filter/search is active
    $queryParams = request()->except(['page']);
    $isActive = $hasActiveFilters || !empty($searchTerm) || count(array_filter($queryParams, fn($v) => $v !== null && $v !== '')) > 0;
?>

<div <?php echo e($attributes->merge(['class' => 'dms-table-toolbar mb-4 space-y-3'])); ?>>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        
        <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-0">
            
            <div
                class="relative flex-1 min-w-[200px] max-w-md"
                x-data="dmsTableToolbarSearch({
                    initialQuery: '<?php echo e(addslashes($searchTerm)); ?>',
                    autocompleteUrl: <?php echo e($autocompleteUrl ? "'" . addslashes($autocompleteUrl) . "'" : 'null'); ?>

                })"
                @click.outside="closeAutocomplete()"
                @keydown.escape="closeAutocomplete()"
            >
                <div class="relative w-full">
                    <input
                        x-ref="searchInput"
                        type="text"
                        name="<?php echo e($searchName); ?>"
                        x-model="query"
                        data-dynamic-search
                        placeholder="<?php echo e(\App\Support\TitleCase::format($searchPlaceholder)); ?>"
                        autocomplete="off"
                        @input="onInput()"
                        @focus="onFocus()"
                        @click="onClick()"
                        @keydown="onKeyDown($event)"
                        class="block w-full rounded-lg border-slate-300 pl-3 pr-9 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition"
                        role="combobox"
                        :aria-expanded="open"
                        aria-autocomplete="list"
                    />

                    
                    <div
                        x-show="loading"
                        x-cloak
                        class="absolute inset-y-0 right-7 flex items-center pr-1.5 pointer-events-none"
                    >
                        <svg class="animate-spin h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </div>

                    
                    <button
                        type="button"
                        x-show="query.length > 0"
                        x-cloak
                        @click="clear()"
                        class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 transition"
                        title="Clear Search"
                    >
                        <svg class="h-4 w-4 rounded-full p-0.5 hover:bg-slate-100" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($autocompleteUrl): ?>
                    
                    <div
                        x-show="open && (items.length > 0 || hasSearched || loading)"
                        x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 translate-y-1 scale-98"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-1 scale-98"
                        class="absolute left-0 top-full mt-1.5 w-full min-w-[320px] sm:min-w-[440px] md:min-w-[500px] max-w-xl bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden z-50 divide-y divide-slate-100 text-slate-800"
                        role="listbox"
                        x-ref="dropdownList"
                    >
                        
                        <div x-show="loading" class="px-4 py-3 flex items-center gap-2.5 text-xs text-slate-500 bg-slate-50/70">
                            <svg class="animate-spin h-4 w-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Searching matching parties...</span>
                        </div>

                        
                        <div x-show="!loading && hasSearched && items.length === 0" class="px-4 py-6 text-center text-xs text-slate-500 bg-white">
                            <div class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-slate-100 text-slate-400 mb-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </div>
                            <p class="font-medium text-slate-700">No matching parties found</p>
                            <p class="text-slate-400 mt-0.5 text-[11px]">Press Enter to search the table or try another keyword.</p>
                        </div>

                        
                        <div x-show="!loading && items.length > 0" class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                            <template x-for="(item, index) in items" :key="item.id">
                                <div
                                    class="px-3.5 py-2.5 cursor-pointer transition flex items-center justify-between gap-3 group"
                                    :class="{
                                        'bg-indigo-50/90 text-indigo-950': selectedIndex === index,
                                        'hover:bg-slate-50 text-slate-800': selectedIndex !== index
                                    }"
                                    :data-selected="selectedIndex === index"
                                    @click="openParty(item)"
                                    @mouseenter="selectedIndex = index"
                                    role="option"
                                    :aria-selected="selectedIndex === index"
                                >
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                            <span
                                                class="font-semibold text-sm truncate"
                                                :class="{
                                                    'text-indigo-900': selectedIndex === index,
                                                    'text-slate-900 group-hover:text-indigo-700': selectedIndex !== index
                                                }"
                                                x-text="item.name"
                                            ></span>

                                            <template x-if="item.party_type_label">
                                                <span
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
                                                    :class="{
                                                        'bg-amber-100 text-amber-800 border border-amber-200': item.party_type_key === 'sundry_creditors',
                                                        'bg-purple-100 text-purple-800 border border-purple-200': item.party_type_key === 'both',
                                                        'bg-blue-100 text-blue-800 border border-blue-200': item.party_type_key !== 'sundry_creditors' && item.party_type_key !== 'both'
                                                    }"
                                                    x-text="item.party_type_label"
                                                ></span>
                                            </template>

                                            <template x-if="!item.is_active">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-700 border border-rose-200">
                                                    Inactive
                                                </span>
                                            </template>
                                        </div>

                                        <div class="flex items-center gap-3 text-xs text-slate-500 flex-wrap mt-1">
                                            
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="h-3 w-3 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                                </svg>
                                                <span x-text="item.phone || '—'"></span>
                                            </span>

                                            
                                            <span class="inline-flex items-center gap-1 font-mono text-[11px] text-slate-600">
                                                <span class="text-[10px] uppercase font-bold text-slate-400">GST:</span>
                                                <span x-text="item.gstin || '—'"></span>
                                            </span>

                                            
                                            <span class="inline-flex items-center gap-1 text-[11px] text-slate-500">
                                                <svg class="h-3 w-3 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                                </svg>
                                                <span x-text="item.area_name || item.city || '—'"></span>
                                            </span>

                                            
                                            <template x-if="item.code">
                                                <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-[10px] text-slate-600" x-text="item.code"></span>
                                            </template>
                                        </div>
                                    </div>

                                    
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button
                                            type="button"
                                            @click.stop.prevent="filterByParty(item)"
                                            class="px-2.5 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-600 hover:text-white rounded-md transition border border-indigo-200 shadow-2xs"
                                            title="Filter table to this party"
                                        >
                                            Filter
                                        </button>
                                        <button
                                            type="button"
                                            @click.stop.prevent="openParty(item)"
                                            class="px-2 py-1 text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-md transition flex items-center gap-0.5"
                                            title="Open party details"
                                        >
                                            View &rarr;
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        
                        <div x-show="!loading && items.length > 0" class="bg-slate-50 px-3.5 py-1.5 flex items-center justify-between text-[11px] text-slate-400">
                            <span><strong class="text-slate-600" x-text="items.length"></strong> suggestions</span>
                            <span class="flex items-center gap-1.5">
                                <kbd class="px-1 py-0.5 rounded bg-white border border-slate-200 text-[10px] text-slate-500 font-mono">↑↓</kbd> navigate
                                <kbd class="px-1 py-0.5 rounded bg-white border border-slate-200 text-[10px] text-slate-500 font-mono">↵</kbd> view
                                <kbd class="px-1 py-0.5 rounded bg-white border border-slate-200 text-[10px] text-slate-500 font-mono">esc</kbd> close
                            </span>
                        </div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($filters)): ?>
                <div class="flex flex-wrap items-center gap-2">
                    <?php echo e($filters); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isActive): ?>
                <a
                    href="<?php echo e($resetUrl); ?>"
                    data-reset-filters
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 shadow-sm transition"
                    title="Reset All Filters And Search"
                >
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Reset</span>
                </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($actions)): ?>
            <div class="flex items-center gap-2 shrink-0">
                <?php echo e($actions); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>

<script>
if (!window.dmsTableToolbarSearch) {
    window.dmsTableToolbarSearch = function (config) {
        return {
            query: config.initialQuery || '',
            autocompleteUrl: config.autocompleteUrl || null,
            open: false,
            loading: false,
            items: [],
            selectedIndex: -1,
            hasSearched: false,
            debounceTimer: null,
            controller: null,

            init() {
                // If filter selects change while autocomplete is open, refresh suggestions
                const container = this.$el?.closest('.dms-table-toolbar') || this.$el?.closest('#listing-container') || document;
                container.querySelectorAll?.('[data-dynamic-filter]').forEach(el => {
                    el.addEventListener('change', () => {
                        if (this.open) {
                            this.fetchSuggestions(this.query.trim());
                        }
                    });
                });
            },

            clear() {
                this.query = '';
                this.items = [];
                this.open = false;
                this.loading = false;
                this.selectedIndex = -1;
                this.hasSearched = false;

                const container = this.$el?.closest('[data-dynamic-container]') ||
                                  this.$el?.closest('#listing-container') ||
                                  document.querySelector('[data-dynamic-container]') ||
                                  document.querySelector('#listing-container');

                const partyInput = container ? container.querySelector('input[name="party_id"]') : null;
                if (partyInput) {
                    partyInput.value = '';
                }

                const input = this.$refs.searchInput;
                if (input) {
                    input.value = '';
                }

                const url = new URL(window.location.href);
                url.searchParams.delete('party_id');
                url.searchParams.delete('search');
                url.searchParams.delete('q');
                url.searchParams.delete('page');

                if (window.DmsDynamicTables && typeof window.DmsDynamicTables.fetchAndSwap === 'function') {
                    window.DmsDynamicTables.fetchAndSwap(url.toString(), false, container);
                } else {
                    window.location.href = url.toString();
                }

                if (input) {
                    input.focus();
                }
            },

            closeAutocomplete() {
                this.open = false;
                this.selectedIndex = -1;
            },

            onFocus() {
                if (!this.autocompleteUrl) return;
                this.open = true;
                if (this.items.length === 0 || !this.hasSearched) {
                    this.fetchSuggestions(this.query.trim());
                }
            },

            onClick() {
                if (!this.autocompleteUrl) return;
                this.open = true;
                if (this.items.length === 0 || !this.hasSearched) {
                    this.fetchSuggestions(this.query.trim());
                }
            },

            onInput() {
                if (!this.autocompleteUrl) return;

                clearTimeout(this.debounceTimer);
                const q = this.query.trim();

                this.loading = true;
                this.debounceTimer = setTimeout(() => {
                    this.fetchSuggestions(q);
                }, 220);
            },

            async fetchSuggestions(q) {
                if (this.controller) {
                    this.controller.abort();
                }
                this.controller = new AbortController();

                this.loading = true;

                const params = new URLSearchParams();
                params.set('q', q);

                // Inherit active filters from toolbar or listing container
                const container = this.$el.closest('.dms-table-toolbar') || this.$el.closest('#listing-container') || document;
                container.querySelectorAll('[data-dynamic-filter]').forEach(el => {
                    if (el.name && el.value !== '' && el.value !== null && el.value !== undefined) {
                        params.set(el.name, el.value);
                    }
                });

                try {
                    const sep = this.autocompleteUrl.includes('?') ? '&' : '?';
                    const res = await fetch(this.autocompleteUrl + sep + params.toString(), {
                        signal: this.controller.signal,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (res.ok) {
                        const data = await res.json();
                        this.items = Array.isArray(data) ? data : [];
                        this.hasSearched = true;
                        this.open = true;
                        this.selectedIndex = -1;
                    }
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Autocomplete fetch error:', err);
                    }
                } finally {
                    this.loading = false;
                }
            },

            onKeyDown(e) {
                if (!this.autocompleteUrl) return;

                if (!this.open || this.items.length === 0) {
                    // Dropdown closed - if user presses ArrowDown when input has text, reopen
                    if (e.key === 'ArrowDown' && this.query.trim().length >= 1) {
                        this.fetchSuggestions(this.query.trim());
                    }
                    return;
                }

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.selectedIndex = (this.selectedIndex + 1) % this.items.length;
                    this.scrollToSelected();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.selectedIndex = this.selectedIndex <= 0 ? this.items.length - 1 : this.selectedIndex - 1;
                    this.scrollToSelected();
                } else if (e.key === 'Enter') {
                    if (this.selectedIndex >= 0 && this.items[this.selectedIndex]) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        this.openParty(this.items[this.selectedIndex]);
                    } else {
                        this.open = false;
                    }
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    this.closeAutocomplete();
                }
            },

            scrollToSelected() {
                this.$nextTick(() => {
                    const list = this.$refs.dropdownList;
                    if (!list) return;
                    const selected = list.querySelector('[data-selected="true"]');
                    if (selected) {
                        selected.scrollIntoView({ block: 'nearest' });
                    }
                });
            },

            openParty(item) {
                if (!item) return;
                this.query = item.name;
                const input = this.$refs.searchInput;
                if (input) {
                    input.value = item.name;
                }
                this.closeAutocomplete();
                if (item.view_url) {
                    window.location.href = item.view_url;
                }
            },

            filterByParty(item) {
                if (!item) return;

                // 1. Keep selected party's name visible in search field
                this.query = item.name;
                const searchInput = this.$refs.searchInput;
                if (searchInput) {
                    searchInput.value = item.name;
                }

                // 2. Close autocomplete dropdown
                this.closeAutocomplete();

                // 3. Locate container
                const container = this.$el?.closest('[data-dynamic-container]') ||
                                  this.$el?.closest('#listing-container') ||
                                  document.querySelector('[data-dynamic-container]') ||
                                  document.querySelector('#listing-container');

                // 4. Update or inject hidden party_id filter
                let partyInput = container ? container.querySelector('input[name="party_id"]') : null;
                if (!partyInput && container) {
                    partyInput = document.createElement('input');
                    partyInput.type = 'hidden';
                    partyInput.name = 'party_id';
                    partyInput.setAttribute('data-dynamic-filter', '');
                    const toolbar = container.querySelector('.dms-table-toolbar') || container;
                    toolbar.appendChild(partyInput);
                }
                if (partyInput) {
                    partyInput.value = item.id;
                }

                // 5. Build URL with party_id, search, and active filters
                const url = new URL(window.location.href);
                url.searchParams.set('party_id', item.id);
                url.searchParams.set('search', item.name);
                url.searchParams.delete('page');

                if (container) {
                    container.querySelectorAll('[data-dynamic-filter]').forEach(el => {
                        if (el.name && el.name !== 'party_id') {
                            const val = el.value;
                            if (val !== '' && val !== null && val !== undefined) {
                                url.searchParams.set(el.name, val);
                            } else {
                                url.searchParams.delete(el.name);
                            }
                        }
                    });
                }

                const targetUrl = url.toString();

                // 6. Apply filter immediately without full page reload
                if (window.DmsDynamicTables && typeof window.DmsDynamicTables.fetchAndSwap === 'function') {
                    window.DmsDynamicTables.fetchAndSwap(targetUrl, false, container);
                } else {
                    window.location.href = targetUrl;
                }
            }
        };
    };
}
</script>
<?php /**PATH D:\xampp\htdocs\DMS\resources\views/components/ui/table-toolbar.blade.php ENDPATH**/ ?>