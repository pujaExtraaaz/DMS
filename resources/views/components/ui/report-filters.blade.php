@props([
    'dateFrom' => null,
    'dateTo' => null,
    'asOf' => null,
    'dateFromLabel' => 'From',
    'dateToLabel' => 'To',
    'showDates' => true,
    'showAsOf' => false,
    'searchPlaceholder' => 'Search',
    'searchValue' => null,
    'resetUrl' => null,
    'submitLabel' => 'Apply',
    'showParty' => false,
    'showSupplier' => false,
    'showSalesperson' => false,
    'showUnassignedSalesperson' => false,
    'showStatus' => false,
    'statuses' => [],
    'showBranch' => false,
    'showBrand' => false,
    'showCategory' => false,
    'showProduct' => false,
    'showWarehouse' => false,
    'showFulfilment' => false,
    'showBackOrder' => false,
    'showLowStock' => false,
    'partyRequired' => false,
    'filtersOpen' => null,
    'customers' => null,
    'suppliers' => null,
    'salespeople' => null,
    'branches' => null,
    'brands' => null,
    'categories' => null,
    'products' => null,
    'warehouses' => null,
])

@php
    $searchValue = (string) ($searchValue ?? request('search', ''));
    $resetUrl = $resetUrl ?: url()->current();
    $customers = collect($customers ?? []);
    $suppliers = collect($suppliers ?? []);
    $salespeople = collect($salespeople ?? []);
    $branches = collect($branches ?? []);
    $brands = collect($brands ?? []);
    $categories = collect($categories ?? []);
    $products = collect($products ?? []);
    $warehouses = collect($warehouses ?? []);

    $toOpts = fn ($items) => $items->map(function ($item) {
        if (is_array($item)) {
            return [
                'id' => (string) ($item['id'] ?? ''),
                'name' => (string) ($item['name'] ?? ''),
                'code' => isset($item['code']) ? (string) $item['code'] : null,
            ];
        }

        return [
            'id' => (string) ($item->id ?? ''),
            'name' => (string) ($item->name ?? ''),
            'code' => isset($item->code) ? (string) $item->code : (isset($item->sku) ? (string) $item->sku : null),
        ];
    })->values()->all();

    $salespersonOptions = $toOpts($salespeople);
    if ($showUnassignedSalesperson) {
        array_unshift($salespersonOptions, ['id' => 'unassigned', 'name' => 'Unassigned', 'code' => null]);
    }

    $statusOptions = collect($statuses)->map(fn ($st) => [
        'id' => (string) $st,
        'name' => ucfirst(str_replace('_', ' ', $st)),
        'code' => null,
    ])->values()->all();

    $productOptions = $products->map(fn ($p) => [
        'id' => (string) $p->id,
        'name' => (string) $p->name,
        'code' => $p->sku ?? $p->barcode ?? null,
    ])->values()->all();

    $dateLabel = '';
    if ($showAsOf && $asOf) {
        $dateLabel = \Illuminate\Support\Carbon::parse($asOf)->format('d M Y');
    } elseif ($showDates && $dateFrom && $dateTo) {
        $dateLabel = \Illuminate\Support\Carbon::parse($dateFrom)->format('d M').' – '.\Illuminate\Support\Carbon::parse($dateTo)->format('d M Y');
    } else {
        $dateLabel = 'Date range';
    }

    $chipClass = 'inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 whitespace-nowrap';
@endphp

<div
    {{ $attributes->merge(['class' => 'report-filters mb-5']) }}
    x-data="dmsReportFilters({ search: @js($searchValue) })"
>
    <form method="GET" class="flex flex-wrap items-center gap-2">
        {{-- Date range (left) --}}
        @if($showAsOf)
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open" class="{{ $chipClass }}">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                    <span>{{ $dateLabel }}</span>
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open" x-cloak x-transition class="absolute left-0 top-full z-40 mt-1.5 w-56 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">As of</label>
                    <input id="as_of" type="date" name="as_of" value="{{ $asOf }}" class="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" @change="$el.form.requestSubmit()" />
                </div>
            </div>
        @elseif($showDates)
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open" class="{{ $chipClass }}">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                    <span>{{ $dateLabel }}</span>
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open" x-cloak x-transition class="absolute left-0 top-full z-40 mt-1.5 w-72 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $dateFromLabel }}</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $dateToLabel }}</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <button type="button" class="mt-3 w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                            @click="$el.closest('form')?.requestSubmit()">Apply dates</button>
                </div>
            </div>
        @endif

        {{-- Filter chips (center) --}}
        @if($showParty)
            <x-ui.report-filter-chip name="customer_id" label="Party" placeholder="All parties" search-placeholder="Type party or initials..." :options="$toOpts($customers)" :value="request('customer_id')" :required="$partyRequired" />
        @endif
        @if($showSupplier)
            <x-ui.report-filter-chip name="supplier_id" label="Supplier" placeholder="All suppliers" search-placeholder="Type supplier..." :options="$toOpts($suppliers)" :value="request('supplier_id')" />
        @endif
        @if($showSalesperson)
            <x-ui.report-filter-chip name="salesperson_id" label="Salesperson" placeholder="All salespersons" search-placeholder="Type name..." :options="$salespersonOptions" :value="request('salesperson_id')" />
        @endif
        @if($showStatus)
            <x-ui.report-filter-chip name="status" label="Status" placeholder="All statuses" search-placeholder="Type status..." :options="$statusOptions" :value="request('status')" />
        @endif
        @if($showBranch)
            <x-ui.report-filter-chip name="branch_id" label="Branch" placeholder="All branches" search-placeholder="Type branch..." :options="$toOpts($branches)" :value="request('branch_id')" />
        @endif
        @if($showBrand)
            <x-ui.report-filter-chip name="brand_id" label="Brand" placeholder="All brands" search-placeholder="Type brand..." :options="$toOpts($brands)" :value="request('brand_id')" />
        @endif
        @if($showCategory)
            <x-ui.report-filter-chip name="category_id" label="Category" placeholder="All categories" search-placeholder="Type category..." :options="$toOpts($categories)" :value="request('category_id')" />
        @endif
        @if($showProduct)
            <x-ui.report-filter-chip name="product_id" label="Item" placeholder="All items" search-placeholder="Type item / SKU..." :options="$productOptions" :value="request('product_id')" />
        @endif
        @if($showWarehouse)
            <x-ui.report-filter-chip name="warehouse_id" label="Warehouse" placeholder="All warehouses" search-placeholder="Type warehouse..." :options="$toOpts($warehouses)" :value="request('warehouse_id')" />
        @endif
        @if($showFulfilment)
            <x-ui.report-filter-chip
                name="fulfilment_mode"
                label="Fulfilment"
                placeholder="All modes"
                search-placeholder="Type mode..."
                :options="[['id' => 'warehouse', 'name' => 'Warehouse', 'code' => null], ['id' => 'van', 'name' => 'Van', 'code' => null]]"
                :value="request('fulfilment_mode')"
            />
        @endif

        @if($showBackOrder)
            <label class="{{ $chipClass }} cursor-pointer {{ request()->boolean('back_order_only') ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : '' }}">
                <input type="checkbox" name="back_order_only" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(request()->boolean('back_order_only')) onchange="this.form.requestSubmit()">
                Back-order
            </label>
        @endif
        @if($showLowStock)
            <label class="{{ $chipClass }} cursor-pointer {{ request()->boolean('low_only') ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : '' }}">
                <input type="checkbox" name="low_only" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(request()->boolean('low_only')) onchange="this.form.requestSubmit()">
                Low stock
            </label>
        @endif

        {{ $slot }}

        {{-- Search (right) --}}
        <div class="ml-auto flex items-center gap-2 min-w-[200px] flex-1 sm:flex-none sm:w-64">
            <div class="relative w-full">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                </span>
                <input
                    type="search"
                    name="search"
                    x-model="search"
                    @input="liveFilter()"
                    @keydown.enter="$el.form.requestSubmit()"
                    placeholder="{{ \App\Support\TitleCase::format($searchPlaceholder) }}"
                    autocomplete="off"
                    class="block w-full rounded-lg border-slate-200 bg-white py-2 pl-9 pr-8 text-sm shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500"
                />
                <button type="button" x-show="search.length" x-cloak @click="clearSearch()" class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600" title="Clear">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <a href="{{ $resetUrl }}" class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 hover:bg-slate-50" title="Reset">Reset</a>
        </div>
    </form>
</div>

@once
@push('scripts')
<script>
function dmsReportFilters(config) {
    return {
        search: config.search || '',
        init() { this.$nextTick(() => this.liveFilter()); },
        clearSearch() { this.search = ''; this.liveFilter(); },
        liveFilter() {
            const q = (this.search || '').trim().toLowerCase();
            const tokens = q.split(/\s+/).filter(Boolean);
            document.querySelectorAll('[data-report-table] tbody tr').forEach((row) => {
                if (row.querySelector('[data-report-empty]') || row.hasAttribute('data-report-empty')) return;
                const hay = (row.getAttribute('data-search') || row.textContent || '').toLowerCase().replace(/\s+/g, ' ');
                const show = tokens.length === 0 || tokens.every((t) => hay.includes(t) || this.initialsMatch(hay, t));
                row.style.display = show ? '' : 'none';
            });
        },
        initialsMatch(hay, token) {
            const words = hay.split(/[^a-z0-9]+/).filter(Boolean);
            const initials = words.map((w) => w.charAt(0)).join('');
            if (initials.startsWith(token)) return true;
            for (let i = 0; i < words.length; i++) {
                let built = '';
                for (let j = i; j < words.length && built.length < token.length; j++) built += words[j].charAt(0);
                if (built.startsWith(token)) return true;
            }
            return words.some((w) => w.startsWith(token));
        }
    };
}
</script>
@endpush
@endonce
