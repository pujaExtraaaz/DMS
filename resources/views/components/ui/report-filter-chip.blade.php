@props([
    'name',
    'label' => 'Filter',
    'placeholder' => 'All',
    'searchPlaceholder' => 'Search...',
    'options' => [],
    'value' => null,
    'required' => false,
])

@php
    $options = collect($options)->map(function ($item) {
        if (is_array($item)) {
            return [
                'id' => (string) ($item['id'] ?? $item['value'] ?? ''),
                'name' => (string) ($item['name'] ?? $item['label'] ?? ''),
                'code' => isset($item['code']) ? (string) $item['code'] : null,
            ];
        }

        return [
            'id' => (string) ($item->id ?? ''),
            'name' => (string) ($item->name ?? ''),
            'code' => isset($item->code) ? (string) $item->code : null,
        ];
    })->values()->all();

    $current = (string) ($value ?? request($name, ''));
@endphp

<div
    class="relative"
    x-data="dmsReportFilterChip({
        options: @js($options),
        value: @js($current),
        label: @js($label),
        placeholder: @js($placeholder)
    })"
    @click.outside="close()"
>
    <input type="hidden" name="{{ $name }}" :value="selectedId" @if($required) required @endif>

    <button
        type="button"
        @click="toggle()"
        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm whitespace-nowrap transition"
        :class="selectedId
            ? 'border-indigo-300 bg-indigo-50 text-indigo-800'
            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
    >
        <span class="font-medium" x-text="buttonLabel"></span>
        <svg class="h-3.5 w-3.5 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute left-0 top-full z-50 mt-1.5 w-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
    >
        <div class="border-b border-slate-100 p-2">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                </span>
                <input
                    type="text"
                    x-model="query"
                    x-ref="chipSearch"
                    @keydown.enter.prevent="pickFirst()"
                    @keydown.escape="close()"
                    placeholder="{{ $searchPlaceholder }}"
                    autocomplete="off"
                    class="w-full rounded-lg border-slate-200 py-1.5 pl-8 pr-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
        </div>

        <div class="max-h-64 overflow-y-auto py-1">
            <button type="button" @click="clear()" class="flex w-full items-center px-3 py-2 text-left text-sm text-slate-500 hover:bg-slate-50">
                {{ $placeholder }}
            </button>
            <template x-for="opt in filtered" :key="opt.id">
                <button
                    type="button"
                    @click="select(opt)"
                    class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-indigo-50"
                    :class="String(selectedId) === String(opt.id) ? 'bg-indigo-50 text-indigo-900 font-medium' : 'text-slate-700'"
                >
                    <span x-text="opt.name"></span>
                    <span x-show="opt.code" class="font-mono text-[11px] text-slate-400" x-text="opt.code"></span>
                </button>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-4 text-center text-xs text-slate-400">No matches</div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function dmsReportFilterChip(config) {
    return {
        options: config.options || [],
        selectedId: config.value || '',
        selectedName: '',
        label: config.label || 'Filter',
        placeholder: config.placeholder || 'All',
        query: '',
        open: false,
        init() {
            if (this.selectedId) {
                const found = this.options.find(o => String(o.id) === String(this.selectedId));
                if (found) this.selectedName = found.name;
            }
        },
        get buttonLabel() {
            return this.selectedName || this.label;
        },
        get filtered() {
            const q = (this.query || '').trim().toLowerCase();
            if (!q) return this.options.slice(0, 80);
            return this.options.filter(o => {
                const name = (o.name || '').toLowerCase();
                const code = (o.code || '').toLowerCase();
                if (name.startsWith(q) || code.startsWith(q) || name.includes(q) || code.includes(q)) return true;
                const words = name.split(/[^a-z0-9]+/).filter(Boolean);
                const initials = words.map(w => w.charAt(0)).join('');
                return initials.startsWith(q) || words.some(w => w.startsWith(q));
            }).slice(0, 80);
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.query = '';
                this.$nextTick(() => this.$refs.chipSearch?.focus());
            }
        },
        close() { this.open = false; this.query = ''; },
        select(opt) {
            this.selectedId = opt.id;
            this.selectedName = opt.name;
            this.close();
            this.$el.closest('form')?.requestSubmit();
        },
        clear() {
            this.selectedId = '';
            this.selectedName = '';
            this.close();
            this.$el.closest('form')?.requestSubmit();
        },
        pickFirst() {
            if (this.filtered.length) this.select(this.filtered[0]);
        }
    };
}
</script>
@endpush
@endonce
