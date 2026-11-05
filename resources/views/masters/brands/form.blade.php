@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Brand' : 'Create Brand')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Brand' : 'Create Brand'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.brands.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
    <form
        method="POST"
        action="{{ $item->exists ? route('masters.brands.update', $item) : route('masters.brands.store') }}"
        class="space-y-4 max-w-xl"
        x-data="brandAutocomplete({
            searchUrl: '{{ route('masters.brands.search') }}',
            currentBrandId: {{ $item->exists ? $item->id : 'null' }},
            isEdit: {{ $item->exists ? 'true' : 'false' }},
            initialName: @js(old('name', $item->name ?? '')),
            initialCompanyId: @js(old('company_id', $item->company_id ?? ''))
        })"
    >
        @csrf @if($item->exists) @method('PUT') @endif

        <div>
            <label for="company_id" class="block text-sm font-medium text-slate-700 mb-1">
                Company
            </label>
            <select
                id="company_id"
                name="company_id"
                x-model="companyId"
                @change="onCompanyChange()"
                class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white"
            >
                <option value="">Select Company</option>
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" @selected(old('company_id', $item->company_id)==$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            @error('company_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Autocomplete Brand Name Field --}}
        <div class="relative" @click.outside="closeDropdown()">
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">
                Brand Name <span class="text-red-500">*</span>
            </label>

            <div class="relative">
                <input
                    type="text"
                    id="name"
                    name="name"
                    x-model="query"
                    @input="onInput()"
                    @focus="onFocus()"
                    @keydown.arrow-down.prevent="onArrowDown()"
                    @keydown.arrow-up.prevent="onArrowUp()"
                    @keydown.enter="onEnter($event)"
                    @keydown.escape.prevent="closeDropdown()"
                    placeholder="e.g. Sony, Samsung..."
                    autocomplete="off"
                    required
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 pr-10 @error('name') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror"
                >

                {{-- Status Indicator (Spinner / Clear Button) --}}
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <svg x-show="isLoading" x-cloak class="h-4 w-4 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <button
                        type="button"
                        x-show="!isLoading && query.trim().length > 0"
                        x-cloak
                        @click.stop="clearQuery()"
                        class="pointer-events-auto text-slate-400 hover:text-slate-600 p-0.5 rounded"
                        title="Clear brand name"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            @error('name')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror

            {{-- Suggestions Dropdown --}}
            <div
                x-show="isOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl py-1 text-sm divide-y divide-slate-100"
            >
                <div x-show="isLoading" class="flex items-center gap-2 px-3 py-2 text-xs text-slate-500">
                    <svg class="h-3.5 w-3.5 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Searching brands...</span>
                </div>

                {{-- Suggestions List --}}
                <template x-for="(brand, index) in suggestions" :key="brand.id">
                    <div
                        @click="selectBrand(brand)"
                        @mouseenter="highlightedIndex = index"
                        :class="highlightedIndex === index ? 'bg-indigo-50 text-indigo-900' : 'text-slate-800 hover:bg-slate-50'"
                        class="flex items-center justify-between px-3 py-2 cursor-pointer transition-colors"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                            </svg>
                            <span class="font-medium truncate" x-text="brand.name"></span>
                            <span x-show="currentBrandId && String(brand.id) === String(currentBrandId)" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">Current</span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">Existing Brand</span>
                            <a
                                :href="brand.edit_url"
                                @click.stop
                                class="text-[11px] font-medium text-indigo-600 hover:text-indigo-800 underline"
                                title="Edit this brand in a separate screen"
                            >Edit &rarr;</a>
                        </div>
                    </div>
                </template>

                {{-- Empty State --}}
                <div x-show="!isLoading && hasSearched && suggestions.length === 0" class="px-3 py-2.5 text-xs text-slate-500 flex items-center gap-2">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>No matching brands found</span>
                </div>
            </div>

            {{-- Existing Brand Selection / Duplicate Alert --}}
            <div
                x-show="isDuplicate"
                x-cloak
                class="mt-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 flex items-start gap-2.5"
            >
                <svg class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="flex-1">
                    <p class="font-semibold text-amber-950">
                        Brand "<span x-text="selectedExistingBrand?.name"></span>" already exists.
                    </p>
                    <p class="text-[11px] text-amber-800 mt-0.5">
                        To prevent duplicate records, you cannot create another brand with this name. You can edit the existing brand instead.
                    </p>
                    <div class="mt-2 flex items-center gap-2">
                        <a
                            :href="selectedExistingBrand?.edit_url"
                            class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-amber-700 transition"
                        >
                            <span>Edit Existing Brand</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                        <button
                            type="button"
                            @click="clearQuery()"
                            class="rounded-lg border border-amber-300 bg-white px-2 py-1 text-[11px] font-medium text-amber-900 hover:bg-amber-100 transition"
                        >
                            Clear &amp; Type New Name
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <label for="detail" class="block text-sm font-medium text-slate-700 mb-1">
                Detail
            </label>

            <textarea
                id="detail"
                name="detail"
                rows="4"
                maxlength="2000"
                placeholder="Enter Brand Details..."
                class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >{{ old('detail', $item->detail) }}</textarea>

            @error('detail')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active
        </label>

        <div class="pt-2">
            <x-ui.button
                type="submit"
                variant="primary"
                ::disabled="isDuplicate"
                ::class="isDuplicate ? 'opacity-50 cursor-not-allowed' : ''"
            >
                Save
            </x-ui.button>
        </div>
    </form>
</x-ui.card>

<script>
function brandAutocomplete(config) {
    return {
        searchUrl: config.searchUrl,
        currentBrandId: config.currentBrandId,
        isEdit: config.isEdit,
        query: config.initialName || '',
        companyId: config.initialCompanyId || '',
        suggestions: [],
        isOpen: false,
        isLoading: false,
        hasSearched: false,
        highlightedIndex: -1,
        selectedExistingBrand: null,
        debounceTimer: null,

        get isDuplicate() {
            if (!this.selectedExistingBrand) return false;
            if (this.isEdit && this.currentBrandId && String(this.selectedExistingBrand.id) === String(this.currentBrandId)) {
                return false;
            }
            return true;
        },

        onCompanyChange() {
            if (this.query.trim().length >= 1) {
                this.fetchSuggestions();
            }
        },

        onInput() {
            const trimmed = this.query.trim();
            if (trimmed.length === 0) {
                this.suggestions = [];
                this.isOpen = false;
                this.hasSearched = false;
                this.selectedExistingBrand = null;
                return;
            }

            this.isLoading = true;
            this.isOpen = true;
            this.hasSearched = false;

            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.fetchSuggestions();
            }, 300);
        },

        onFocus() {
            if (this.query.trim().length >= 1 && this.suggestions.length > 0) {
                this.isOpen = true;
            }
        },

        async fetchSuggestions() {
            const trimmed = this.query.trim();
            if (trimmed.length < 1) {
                this.isLoading = false;
                this.suggestions = [];
                return;
            }

            try {
                const params = new URLSearchParams({
                    q: trimmed,
                    company_id: this.companyId || ''
                });
                const res = await fetch(`${this.searchUrl}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.suggestions = Array.isArray(data) ? data : [];
                    this.hasSearched = true;
                    this.highlightedIndex = -1;

                    // Check exact match (case-insensitive) for duplicate warning
                    const lower = trimmed.toLowerCase();
                    const exact = this.suggestions.find(b => b.name.trim().toLowerCase() === lower);
                    if (exact && (!this.isEdit || String(exact.id) !== String(this.currentBrandId))) {
                        this.selectedExistingBrand = exact;
                    } else if (!this.selectedExistingBrand || this.selectedExistingBrand.name.trim().toLowerCase() !== lower) {
                        this.selectedExistingBrand = null;
                    }
                }
            } catch (err) {
                console.error('Failed to search brands:', err);
            } finally {
                this.isLoading = false;
            }
        },

        selectBrand(brand) {
            this.query = brand.name;
            this.isOpen = false;
            this.highlightedIndex = -1;
            if (!this.isEdit || String(brand.id) !== String(this.currentBrandId)) {
                this.selectedExistingBrand = brand;
            } else {
                this.selectedExistingBrand = null;
            }
        },

        clearQuery() {
            this.query = '';
            this.suggestions = [];
            this.isOpen = false;
            this.hasSearched = false;
            this.selectedExistingBrand = null;
            this.highlightedIndex = -1;
            document.getElementById('name')?.focus();
        },

        closeDropdown() {
            this.isOpen = false;
            this.highlightedIndex = -1;
        },

        onArrowDown() {
            if (!this.isOpen) {
                this.isOpen = true;
                return;
            }
            if (this.suggestions.length > 0) {
                this.highlightedIndex = (this.highlightedIndex + 1) % this.suggestions.length;
            }
        },

        onArrowUp() {
            if (this.suggestions.length > 0) {
                this.highlightedIndex = (this.highlightedIndex - 1 + this.suggestions.length) % this.suggestions.length;
            }
        },

        onEnter(event) {
            if (this.isOpen && this.highlightedIndex >= 0 && this.suggestions[this.highlightedIndex]) {
                event.preventDefault();
                this.selectBrand(this.suggestions[this.highlightedIndex]);
            } else if (this.isDuplicate) {
                event.preventDefault();
            }
        }
    };
}
</script>
@endsection
