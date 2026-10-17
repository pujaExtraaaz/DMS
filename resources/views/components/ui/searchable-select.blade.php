@props([
    'label' => null,
    'name' => null,
    'options' => [],
    'value' => null,
    'required' => false,
    'placeholder' => 'Select an option',
    'searchPlaceholder' => 'Search...',
    'error' => null,
    'help' => null,
    'allowClear' => true,
    'emptyMessage' => 'No results found',
])

@php
    $errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
    $fieldName = $name ?? $attributes->get('name');
    $hasError = $error || ($fieldName && $errors->has($fieldName));
    $errorMessage = $error ?? ($fieldName ? $errors->first($fieldName) : null);

    // Normalize options to array of ['id' => ..., 'name' => ..., (optional 'code' => ..., 'subtext' => ...)]
    $normalizedOptions = collect($options)->map(function ($item, $key) {
        if (is_object($item)) {
            $name = $item->name ?? $item->label ?? $item->title ?? (string) $item;
            if (isset($item->code) && filled($item->code)) {
                $code = $item->code;
            } else {
                $code = null;
            }
            return [
                'id' => (string) ($item->id ?? $item->value ?? $key),
                'name' => (string) $name,
                'code' => $code ? (string) $code : null,
            ];
        } elseif (is_array($item)) {
            return [
                'id' => (string) ($item['id'] ?? $item['value'] ?? $key),
                'name' => (string) ($item['name'] ?? $item['label'] ?? $item['title'] ?? ''),
                'code' => isset($item['code']) ? (string) $item['code'] : null,
            ];
        } else {
            return [
                'id' => (string) $key,
                'name' => (string) $item,
                'code' => null,
            ];
        }
    })->values()->all();

    $initialVal = (string) old($fieldName, $value ?? '');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1 relative']) }}
     x-data="dmsSearchableSelect({
         options: @js($normalizedOptions),
         value: '{{ $initialVal }}',
         placeholder: '{{ \App\Support\TitleCase::format($placeholder) }}'
     })">
    @if ($label)
        <label @if($fieldName) for="{{ $fieldName }}_search_input" @endif class="block text-sm font-medium text-gray-700">
            {{ \App\Support\TitleCase::format($label) }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <!-- Hidden native input submitting the selected ID -->
    @if($fieldName)
        <input type="hidden"
               id="{{ $fieldName }}"
               name="{{ $fieldName }}"
               :value="selectedId"
               @if($required) required @endif>
    @endif

    <div class="relative" @click.outside="closeDropdown()">
        <div class="relative">
            <input type="text"
                   @if($fieldName) id="{{ $fieldName }}_search_input" @endif
                   x-model="searchQuery"
                   @focus="openDropdown()"
                   @input="openDropdown()"
                   @keydown.escape="closeDropdown()"
                   @keydown.enter.prevent="selectHighlightedOrFirst()"
                   @keydown.arrow-down.prevent="navigateOptions(1)"
                   @keydown.arrow-up.prevent="navigateOptions(-1)"
                   placeholder="{{ \App\Support\TitleCase::format($placeholder) }}"
                   autocomplete="off"
                   class="block w-full rounded-lg border-gray-300 shadow-sm text-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 pr-16 {{ $hasError ? 'border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500' : '' }}">

            <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                @if($allowClear)
                    <button type="button"
                            x-show="selectedId"
                            @click="clearSelection()"
                            title="Clear selection"
                            class="p-1 text-slate-400 hover:text-slate-600 rounded">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                @endif
                <button type="button"
                        @click="toggleDropdown()"
                        tabindex="-1"
                        class="p-1 text-slate-400 hover:text-slate-600 rounded">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
            </div>
        </div>

        <!-- Dropdown list -->
        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100"
             style="display: none;">
            <template x-for="(opt, index) in filteredOptions" :key="opt.id">
                <div @click="selectOption(opt)"
                     @mouseenter="highlightedIndex = index"
                     :class="{
                         'bg-indigo-50 text-indigo-900 font-semibold': String(selectedId) === String(opt.id),
                         'bg-slate-50': highlightedIndex === index && String(selectedId) !== String(opt.id)
                     }"
                     class="p-2.5 hover:bg-indigo-50 hover:text-indigo-900 cursor-pointer text-xs flex items-center justify-between transition-colors">
                    <span class="font-medium" x-text="opt.name"></span>
                    <span x-show="opt.code" class="font-mono text-[11px] text-slate-400" x-text="opt.code"></span>
                </div>
            </template>
            <div x-show="filteredOptions.length === 0" class="p-3 text-center text-xs text-slate-500 italic">
                {{ $emptyMessage }}
            </div>
        </div>
    </div>

    @if ($help && ! $hasError)
        <p class="text-xs text-gray-500">{{ \App\Support\TitleCase::format($help) }}</p>
    @endif

    @if ($hasError)
        <p class="text-xs text-red-600">{{ $errorMessage }}</p>
    @endif
</div>

@once
@push('scripts')
<script>
function dmsSearchableSelect(config) {
    return {
        options: config.options || [],
        selectedId: config.value || '',
        selectedName: '',
        searchQuery: '',
        isOpen: false,
        highlightedIndex: -1,

        init() {
            if (this.selectedId) {
                const found = this.options.find(o => String(o.id) === String(this.selectedId));
                if (found) {
                    this.selectedName = found.name;
                    this.searchQuery = found.name;
                }
            }
        },

        get filteredOptions() {
            if (!this.searchQuery || !this.searchQuery.trim()) {
                return this.options;
            }
            const q = this.searchQuery.toLowerCase().trim();
            return this.options.filter(o => {
                const nameMatch = o.name && o.name.toLowerCase().includes(q);
                const codeMatch = o.code && o.code.toLowerCase().includes(q);
                return nameMatch || codeMatch;
            });
        },

        openDropdown() {
            this.isOpen = true;
            this.highlightedIndex = -1;
        },

        closeDropdown() {
            this.isOpen = false;
            this.highlightedIndex = -1;
            if (this.selectedId && this.selectedName) {
                this.searchQuery = this.selectedName;
            } else {
                this.searchQuery = '';
                this.selectedId = '';
                this.selectedName = '';
            }
        },

        toggleDropdown() {
            if (this.isOpen) {
                this.closeDropdown();
            } else {
                this.openDropdown();
            }
        },

        selectOption(opt) {
            this.selectedId = opt ? opt.id : '';
            this.selectedName = opt ? opt.name : '';
            this.searchQuery = opt ? opt.name : '';
            this.isOpen = false;
            this.$dispatch('input', this.selectedId);
            this.$dispatch('change', this.selectedId);
        },

        clearSelection() {
            this.selectedId = '';
            this.selectedName = '';
            this.searchQuery = '';
            this.isOpen = false;
            this.$dispatch('input', '');
            this.$dispatch('change', '');
        },

        selectHighlightedOrFirst() {
            if (!this.isOpen) {
                this.openDropdown();
                return;
            }
            const list = this.filteredOptions;
            if (list.length === 0) return;

            if (this.highlightedIndex >= 0 && this.highlightedIndex < list.length) {
                this.selectOption(list[this.highlightedIndex]);
            } else {
                this.selectOption(list[0]);
            }
        },

        navigateOptions(step) {
            if (!this.isOpen) {
                this.openDropdown();
                return;
            }
            const list = this.filteredOptions;
            if (list.length === 0) return;

            let nextIndex = this.highlightedIndex + step;
            if (nextIndex < 0) nextIndex = list.length - 1;
            if (nextIndex >= list.length) nextIndex = 0;
            this.highlightedIndex = nextIndex;
        }
    };
}
</script>
@endpush
@endonce
