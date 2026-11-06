@props([
    'name' => 'delivery_state',
    'id' => null,
    'label' => 'Delivery State',
    'model' => 'deliveryState',
    'value' => null,
    'required' => false,
    'placeholder' => 'Search or select Indian State / UT...',
    'showGstBadge' => true,
    'showLabel' => true,
    'onChange' => 'recalc()',
    'states' => null,
])

@php
    $fieldId = $id ?? $name;
    $stateOptions = $states ?? \App\Support\IndianStates::options();
    $initialValue = (string) old($name, $value ?? '');
@endphp

<div {{ $attributes->merge(['class' => 'relative space-y-1']) }}
     x-data="{
         open: false,
         search: '',
         highlightedIndex: -1,
         states: @js($stateOptions),

         get currentVal() {
             return (typeof this.{{ $model }} !== 'undefined' && this.{{ $model }} !== null)
                 ? String(this.{{ $model }})
                 : '{{ addslashes($initialValue) }}';
         },

         get filteredStates() {
             if (!this.search || !this.search.trim()) {
                 return this.states;
             }
             const q = this.search.toLowerCase().trim();
             return this.states.filter(s => {
                 return (s.name && s.name.toLowerCase().includes(q)) ||
                        (s.code && String(s.code).includes(q));
             });
         },

         get selectedOption() {
             const v = this.currentVal.trim().toLowerCase();
             if (!v) return null;
             return this.states.find(s => s.name.toLowerCase() === v) || null;
         },

         openDropdown() {
             this.open = true;
             this.search = '';
             this.highlightedIndex = -1;
             this.$nextTick(() => {
                 if (this.$refs.searchInput) {
                     this.$refs.searchInput.focus();
                 }
             });
         },

         closeDropdown() {
             this.open = false;
             this.search = '';
             this.highlightedIndex = -1;
         },

         toggleDropdown() {
             if (this.open) {
                 this.closeDropdown();
             } else {
                 this.openDropdown();
             }
         },

         selectState(opt) {
             const val = opt ? opt.name : '';
             this.{{ $model }} = val;
             this.open = false;
             this.search = '';
             this.highlightedIndex = -1;
             if (typeof this.isManualState !== 'undefined') {
                 this.isManualState = true;
             }

             // Dispatch native and Alpine events for reactive recalculation
             this.$dispatch('input', val);
             this.$dispatch('change', val);

             const hidden = document.getElementById('{{ $fieldId }}');
             if (hidden) {
                 hidden.value = val;
                 hidden.dispatchEvent(new Event('input', { bubbles: true }));
                 hidden.dispatchEvent(new Event('change', { bubbles: true }));
             }

             @if($onChange)
             if (typeof this.{{ $onChange }} === 'function') {
                 this.{{ $onChange }}();
             }
             @endif
         },

         clearState() {
             this.selectState(null);
         },

         onKeydownDown() {
             if (!this.open) {
                 this.openDropdown();
                 return;
             }
             if (this.highlightedIndex < this.filteredStates.length - 1) {
                 this.highlightedIndex++;
                 this.scrollToHighlighted();
             }
         },

         onKeydownUp() {
             if (!this.open) return;
             if (this.highlightedIndex > 0) {
                 this.highlightedIndex--;
                 this.scrollToHighlighted();
             }
         },

         onKeydownEnter() {
             if (!this.open) {
                 this.openDropdown();
                 return;
             }
             if (this.highlightedIndex >= 0 && this.highlightedIndex < this.filteredStates.length) {
                 this.selectState(this.filteredStates[this.highlightedIndex]);
             } else if (this.filteredStates.length === 1) {
                 this.selectState(this.filteredStates[0]);
             }
         },

         scrollToHighlighted() {
             this.$nextTick(() => {
                 const el = this.$refs.list?.querySelector(`[data-index='${this.highlightedIndex}']`);
                 if (el) {
                     el.scrollIntoView({ block: 'nearest' });
                 }
             });
         }
     }"
     @click.outside="closeDropdown()">

    @if($showLabel)
    <div class="flex items-center justify-between mb-1">
        <label for="{{ $fieldId }}_search_input" class="block text-xs font-semibold text-slate-700 tracking-wider">
            {{ $label }}
            @if($required)<span class="text-rose-500">*</span>@endif
        </label>
        @if($showGstBadge)
        <span class="text-[11px] font-semibold"
              x-show="typeof isIntraState === 'function'"
              :class="isIntraState() ? 'text-indigo-600' : 'text-emerald-600'"
              x-text="isIntraState() ? 'Intra-State (CGST + SGST)' : 'Inter-State (IGST)'"></span>
        @endif
    </div>
    @endif

    {{-- Hidden native input submitting the exact state name for Laravel validation and persistence --}}
    <input type="hidden"
           name="{{ $name }}"
           id="{{ $fieldId }}"
           :value="{{ $model }}"
           value="{{ $initialValue }}"
           @if($required) required @endif>

    {{-- Searchable Combobox Trigger --}}
    <div class="relative">
        <div class="relative flex items-center">
            <input type="text"
                   id="{{ $fieldId }}_search_input"
                   x-ref="searchInput"
                   :value="open ? search : (selectedOption ? `${selectedOption.name} (${selectedOption.code})` : currentVal)"
                   @focus="openDropdown()"
                   @click="openDropdown()"
                   @input="search = $event.target.value; open = true; highlightedIndex = 0"
                   @keydown.arrow-down.prevent="onKeydownDown()"
                   @keydown.arrow-up.prevent="onKeydownUp()"
                   @keydown.enter.prevent="onKeydownEnter()"
                   @keydown.escape="closeDropdown()"
                   placeholder="{{ $placeholder }}"
                   autocomplete="off"
                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-16 bg-white cursor-pointer transition-colors shadow-2xs font-medium text-slate-800 placeholder:text-slate-400 placeholder:font-normal">

            <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                {{-- Clear button --}}
                <button type="button"
                        x-show="currentVal"
                        @click.stop="clearState()"
                        class="p-1 text-slate-400 hover:text-slate-600 rounded focus:outline-none transition-colors"
                        title="Clear selected state">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                {{-- Dropdown toggle caret --}}
                <button type="button"
                        @click.stop="toggleDropdown()"
                        tabindex="-1"
                        class="p-1 text-slate-400 hover:text-slate-600 rounded focus:outline-none">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Dropdown list --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-ref="list"
             class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100"
             style="display: none;">
            <template x-for="(st, idx) in filteredStates" :key="st.code">
                <div @click="selectState(st)"
                     @mouseenter="highlightedIndex = idx"
                     :data-index="idx"
                     :class="{
                         'bg-indigo-50 text-indigo-900 font-semibold': currentVal && currentVal.toLowerCase() === st.name.toLowerCase(),
                         'bg-slate-50 text-slate-900': highlightedIndex === idx && !(currentVal && currentVal.toLowerCase() === st.name.toLowerCase())
                     }"
                     class="px-3 py-2 text-xs flex items-center justify-between cursor-pointer hover:bg-indigo-50 hover:text-indigo-900 transition-colors">
                    <div class="flex items-center gap-2">
                        <span x-text="st.name" class="font-medium text-slate-800"></span>
                        <template x-if="currentVal && currentVal.toLowerCase() === st.name.toLowerCase()">
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </template>
                    </div>
                    <span class="font-mono text-[11px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded" x-text="`GST: ${st.code}`"></span>
                </div>
            </template>
            <div x-show="filteredStates.length === 0" class="p-3 text-center text-xs text-slate-500 italic">
                No states found matching "<span x-text="search"></span>"
            </div>
        </div>
    </div>

    @error($name)
        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
    @enderror
</div>

