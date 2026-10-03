@extends('layouts.dms')
@section('title', 'Stock Levels')
@section('content')
@php
    $productsJson = $products->map(fn($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'sku' => $p->sku,
    ])->values()->toArray();
@endphp

<x-ui.page-header title="Stock Levels" description="Current inventory and recent movements." />

<x-ui.card>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end mb-6">
        {{-- Searchable Product Dropdown --}}
        <div x-data="searchableProductSelect(@json($productsJson), '{{ request('product_id', '') }}')"
             x-init="init()"
             @click.outside="closeDropdown()"
             class="relative space-y-1">
            <label class="block text-sm font-medium text-gray-700">Product</label>

            <div class="relative">
                <input
                    x-ref="searchInput"
                    type="text"
                    autocomplete="off"
                    placeholder="Search product..."
                    x-model="search"
                    @focus="openDropdown()"
                    @input="onInput()"
                    @keydown.arrow-down.prevent="onArrowDown()"
                    @keydown.arrow-up.prevent="onArrowUp()"
                    @keydown.enter="onEnter($event)"
                    @keydown.escape.prevent="closeDropdown()"
                    class="block w-full rounded-lg border-gray-300 shadow-sm text-sm pr-14 focus:border-indigo-500 focus:ring-indigo-500"
                >

                {{-- Clear (X) & Dropdown Arrow --}}
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1 text-gray-400">
                    <button
                        type="button"
                        x-show="search || selectedId"
                        x-cloak
                        @click.stop="clearSelection()"
                        title="Clear product"
                        class="p-1 hover:text-gray-600 focus:outline-none cursor-pointer"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        @click.stop="toggleDropdown()"
                        tabindex="-1"
                        title="Toggle dropdown"
                        class="p-1 hover:text-gray-600 focus:outline-none cursor-pointer"
                    >
                        <svg
                            class="h-4 w-4 transition-transform duration-200"
                            :class="isOpen ? 'rotate-180' : ''"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                {{-- Hidden input for form submission --}}
                <input type="hidden" name="product_id" :value="selectedId">

                {{-- Dropdown list --}}
                <div
                    x-show="isOpen"
                    x-cloak
                    x-ref="dropdownList"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute left-0 top-full z-50 mt-1 max-h-60 w-full min-w-[240px] overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg ring-1 ring-black/5"
                >
                    <template x-for="(product, idx) in filteredProducts" :key="product.id">
                        <button
                            type="button"
                            :data-index="idx"
                            @click="selectProduct(product)"
                            @mouseenter="highlightedIndex = idx"
                            :class="{
                                'bg-indigo-50 text-indigo-900 font-medium': idx === highlightedIndex || String(product.id) === String(selectedId),
                                'text-gray-900': idx !== highlightedIndex && String(product.id) !== String(selectedId)
                            }"
                            class="block w-full px-3 py-2 text-left text-sm transition hover:bg-indigo-50 hover:text-indigo-900 cursor-pointer"
                        >
                            <div class="truncate" x-text="product.name"></div>
                            <div x-show="product.sku" class="text-xs text-gray-400 font-mono" x-text="'SKU: ' + product.sku"></div>
                        </button>
                    </template>

                    <div x-show="filteredProducts.length === 0" class="px-3 py-2 text-xs text-gray-500 italic">
                        No products found
                    </div>
                </div>
            </div>
        </div>

        <x-ui.select name="brand_id" label="Brand" placeholder="All">
            <option value=""></option>
            @foreach($brands as $b)
                <option value="{{ $b->id }}" @selected(request('brand_id')==$b->id)>{{ $b->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="category_id" label="Category" placeholder="All">
            <option value=""></option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="warehouse_id" label="Warehouse" placeholder="All">
            <option value=""></option>
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}" @selected(request('warehouse_id')==$w->id)>{{ $w->name }}</option>
            @endforeach
        </x-ui.select>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Color / Variant</label>
            <input type="text" name="color_variant" value="{{ request('color_variant') }}" list="stock-colors" class="block w-full rounded-lg border-gray-300 text-sm">
            <datalist id="stock-colors">
                @foreach($colors as $c)<option value="{{ $c }}"></option>@endforeach
            </datalist>
        </div>
        <div class="flex items-center gap-2">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="low_stock" value="1" @checked(request()->boolean('low_stock')) class="rounded border-gray-300 text-indigo-600">
                Low stock only
            </label>
            <x-ui.button type="submit" variant="secondary" class="whitespace-nowrap">Filter</x-ui.button>
            @if(request()->hasAny(['product_id','brand_id','category_id','warehouse_id','color_variant','low_stock']))
                <a href="{{ route('inventory.stock.index') }}" class="text-xs text-slate-500 hover:text-slate-700">Reset</a>
            @endif
        </div>
    </form>

    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Serial No.</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Brand</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Variant</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Warehouse</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Unit</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Quantity</th>
            </tr>
        </x-slot>
        @forelse($stockLevels as $level)
            <tr>
                <td class="px-6 py-4 text-sm font-mono text-xs">{{ $level->product->serial_no }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->product->name }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->product->brand?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->product->color_variant ?? '—' }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->warehouse?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->uom->code }}</td>
                <td class="px-6 py-4 text-sm text-right">
                    <x-ui.badge :variant="$level->quantity < 10 ? 'danger' : 'success'">{{ $level->quantity }}</x-ui.badge>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-6 py-8"><x-ui.empty-state title="No stock records" /></td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-4">{{ $stockLevels->links() }}</div>
</x-ui.card>

<x-ui.card class="mt-6" title="Recent Movements">
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Type</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Qty</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Balance</th>
            </tr>
        </x-slot>
        @foreach($movements as $m)
            <tr>
                <td class="px-6 py-4 text-sm">{{ $m->product->name }}</td>
                <td class="px-6 py-4 text-sm">{{ $m->type }}</td>
                <td class="px-6 py-4 text-sm text-right">{{ $m->quantity }}</td>
                <td class="px-6 py-4 text-sm text-right">{{ $m->balance_after }}</td>
            </tr>
        @endforeach
    </x-ui.table>
</x-ui.card>

@push('scripts')
<script>
function searchableProductSelect(products, initialSelectedId) {
    return {
        products: products || [],
        selectedId: initialSelectedId ? String(initialSelectedId) : '',
        selectedName: '',
        search: '',
        isOpen: false,
        highlightedIndex: 0,

        init() {
            if (this.selectedId) {
                const found = this.products.find(p => String(p.id) === String(this.selectedId));
                if (found) {
                    this.selectedName = found.name;
                    this.search = found.name;
                }
            }
        },

        get filteredProducts() {
            const term = (this.search || '').trim().toLowerCase();
            if (!term) {
                return this.products.slice(0, 100);
            }
            return this.products
                .filter(p => {
                    const nameMatch = p.name && p.name.toLowerCase().includes(term);
                    const skuMatch = p.sku && p.sku.toLowerCase().includes(term);
                    return nameMatch || skuMatch;
                })
                .slice(0, 100);
        },

        openDropdown() {
            this.isOpen = true;
            this.highlightedIndex = 0;
        },

        closeDropdown() {
            this.isOpen = false;
            if (this.selectedId) {
                this.search = this.selectedName;
            } else {
                this.search = '';
            }
        },

        toggleDropdown() {
            if (this.isOpen) {
                this.closeDropdown();
            } else {
                this.openDropdown();
            }
        },

        onInput() {
            this.isOpen = true;
            this.highlightedIndex = 0;
            if (this.selectedId && this.search !== this.selectedName) {
                this.selectedId = '';
                this.selectedName = '';
            }
        },

        selectProduct(product) {
            this.selectedId = String(product.id);
            this.selectedName = product.name;
            this.search = product.name;
            this.isOpen = false;
            this.highlightedIndex = 0;
        },

        clearSelection() {
            this.selectedId = '';
            this.selectedName = '';
            this.search = '';
            this.isOpen = false;
            this.highlightedIndex = 0;
            this.$nextTick(() => {
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus();
                }
            });
        },

        onArrowDown() {
            if (!this.isOpen) {
                this.openDropdown();
                return;
            }
            const count = this.filteredProducts.length;
            if (count === 0) return;
            this.highlightedIndex = (this.highlightedIndex + 1) % count;
            this.scrollToHighlighted();
        },

        onArrowUp() {
            if (!this.isOpen) {
                this.openDropdown();
                return;
            }
            const count = this.filteredProducts.length;
            if (count === 0) return;
            this.highlightedIndex = (this.highlightedIndex - 1 + count) % count;
            this.scrollToHighlighted();
        },

        onEnter(event) {
            if (this.isOpen && this.filteredProducts.length > 0 && this.highlightedIndex >= 0 && this.highlightedIndex < this.filteredProducts.length) {
                event.preventDefault();
                this.selectProduct(this.filteredProducts[this.highlightedIndex]);
            }
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                const el = this.$refs.dropdownList?.querySelector(`[data-index="${this.highlightedIndex}"]`);
                if (el) {
                    el.scrollIntoView({ block: 'nearest' });
                }
            });
        }
    };
}
</script>
@endpush
@endsection