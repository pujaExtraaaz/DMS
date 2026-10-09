@extends('layouts.dms')
@section('title', 'Stock Levels')

@section('content')
<div id="listing-container" data-dynamic-container class="space-y-4">
<x-ui.page-header title="Stock Levels">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('reports.stock-ledger')">View Stock Ledger</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="GET" action="{{ route('inventory.stock.index') }}" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-4"
          x-data="searchableProductSelect(@js($products), '{{ $filters['product_id'] ?? '' }}')">
        
        <div class="relative">
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Product</label>
            <input type="hidden" name="product_id" :value="selectedId" data-dynamic-filter>
            <div class="relative">
                <input type="text"
                       x-model="searchQuery"
                       data-dynamic-search
                       name="search"
                       @focus="openDropdown()"
                       @input="openDropdown()"
                       @click.outside="closeDropdown()"
                       placeholder="Search product name or SKU..."
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8">
                <button type="button" x-show="selectedId" @click="clearSelection()"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-bold">&times;</button>
            </div>
            
            <div x-show="isOpen && filteredProducts.length > 0"
                 x-transition
                 class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100">
                <template x-for="prod in filteredProducts" :key="prod.id">
                    <div @click="selectProduct(prod)"
                         class="p-2.5 hover:bg-indigo-50 cursor-pointer text-xs flex items-center justify-between">
                        <span class="font-medium text-slate-800" x-text="prod.name"></span>
                        <span class="font-mono text-[11px] text-slate-500" x-text="prod.sku"></span>
                    </div>
                </template>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Warehouse</label>
            <select name="warehouse_id" data-dynamic-filter class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Warehouses</option>
                @foreach($warehouses as $w)
                    <option value="{{ $w->id }}" @selected(($filters['warehouse_id'] ?? '') == $w->id)>{{ $w->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Category</label>
            <select name="category_id" data-dynamic-filter class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Categories</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(($filters['category_id'] ?? '') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Brand</label>
            <select name="brand_id" data-dynamic-filter class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Brands</option>
                @foreach($brands as $b)
                    <option value="{{ $b->id }}" @selected(($filters['brand_id'] ?? '') == $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Color / Variant</label>
            <input type="text"
                   name="color_variant"
                   value="{{ $filters['color_variant'] ?? request('color_variant') }}"
                   list="stock-colors"
                   data-dynamic-filter
                   placeholder="Filter variant..."
                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <datalist id="stock-colors">
                @foreach($colors as $c)<option value="{{ $c }}"></option>@endforeach
            </datalist>
        </div>

        <div class="md:col-span-4 lg:col-span-5 flex items-center justify-between pt-2">
            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                <input type="checkbox" name="low_stock" value="1" data-dynamic-filter @checked(!empty($filters['low_stock']) || request()->boolean('low_stock'))
                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Show low stock items only
            </label>

            <div class="flex items-center gap-2">
                <x-ui.button type="submit" variant="primary">Filter</x-ui.button>
                <a href="{{ route('inventory.stock.index') }}" data-reset-filters class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Reset</a>
            </div>
        </div>
    </form>
</x-ui.card>

<x-ui.card padding="false">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b">
                <tr>
                    <x-ui.sortable-th column="product" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')">
                        Product
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="sku" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')">
                        SKU / Serial No.
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="brand" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')">
                        Brand
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="category" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')">
                        Category
                    </x-ui.sortable-th>
                    <th class="p-3">Color / Variant</th>
                    <x-ui.sortable-th column="warehouse" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')">
                        Warehouse
                    </x-ui.sortable-th>
                    <th class="p-3">Unit</th>
                    <x-ui.sortable-th column="quantity" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')" align="right">
                        Quantity
                    </x-ui.sortable-th>
                    <th class="p-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($stockLevels as $stk)
                    @php
                        $qty = (float) $stk->quantity;
                        $reorder = (float) ($stk->product?->reorder_level ?? 0);
                        $isLow = $qty < 10 || ($reorder > 0 && $qty <= $reorder);
                    @endphp
                    <tr class="hover:bg-slate-50/50">
                        <td class="p-3 font-semibold text-slate-800">{{ $stk->product?->name ?? '—' }}</td>
                        <td class="p-3 font-mono text-slate-600">
                            <div>{{ $stk->product?->sku ?? '—' }}</div>
                            @if($stk->product?->serial_no)
                                <div class="text-[11px] text-slate-400">SN: {{ $stk->product->serial_no }}</div>
                            @endif
                        </td>
                        <td class="p-3 text-slate-600">{{ $stk->product?->brand?->name ?? '—' }}</td>
                        <td class="p-3 text-slate-600">{{ $stk->product?->category?->name ?? '—' }}</td>
                        <td class="p-3 text-slate-600">{{ $stk->product?->color_variant ?? '—' }}</td>
                        <td class="p-3 text-slate-600">{{ $stk->warehouse?->name ?? '—' }}</td>
                        <td class="p-3 text-slate-600">{{ $stk->uom?->code ?? $stk->uom?->name ?? '—' }}</td>
                        <td class="p-3 text-right font-mono font-bold text-slate-800">{{ number_format($stk->quantity, 2) }}</td>
                        <td class="p-3 text-center">
                            @if($isLow)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">Low Stock</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">In Stock</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-8 text-center text-slate-400">No stock records found matching criteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($stockLevels->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $stockLevels->links() }}
        </div>
    @endif
</x-ui.card>

@if(isset($movements) && count($movements) > 0)
<x-ui.card class="mt-6" title="Recent Stock Movements">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b">
                <tr>
                    <th class="p-3">Date / Time</th>
                    <th class="p-3">Product</th>
                    <th class="p-3">Warehouse</th>
                    <th class="p-3">Type</th>
                    <th class="p-3 text-right">Quantity</th>
                    <th class="p-3 text-right">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($movements as $m)
                    <tr class="hover:bg-slate-50/50">
                        <td class="p-3 font-mono text-slate-500">{{ $m->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                        <td class="p-3 font-semibold text-slate-800">{{ $m->product?->name ?? '—' }}</td>
                        <td class="p-3 text-slate-600">{{ $m->warehouse?->name ?? '—' }}</td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-slate-100 text-slate-700 border border-slate-200">
                                {{ str_replace('_', ' ', $m->type) }}
                            </span>
                        </td>
                        <td class="p-3 text-right font-mono font-semibold">{{ number_format($m->quantity, 2) }}</td>
                        <td class="p-3 text-right font-mono font-bold text-slate-700">{{ number_format($m->balance_after, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.card>
@endif
</div>

@push('scripts')
<script>
function searchableProductSelect(products, initialSelectedId) {
    return {
        products: products || [],
        selectedId: initialSelectedId || '',
        searchQuery: '',
        isOpen: false,

        init() {
            if (this.selectedId) {
                const selected = this.products.find(p => p.id == this.selectedId);
                if (selected) {
                    this.searchQuery = `${selected.name} (${selected.sku})`;
                }
            }
        },

        get filteredProducts() {
            if (!this.searchQuery || this.searchQuery.trim() === '') {
                return this.products.slice(0, 30);
            }
            const q = this.searchQuery.toLowerCase().trim();
            return this.products.filter(p => {
                return (p.name && p.name.toLowerCase().includes(q)) ||
                       (p.sku && p.sku.toLowerCase().includes(q));
            }).slice(0, 30);
        },

        openDropdown() {
            this.isOpen = true;
        },

        closeDropdown() {
            this.isOpen = false;
        },

        selectProduct(product) {
            this.selectedId = product.id;
            this.searchQuery = `${product.name} (${product.sku})`;
            this.isOpen = false;
        },

        clearSelection() {
            this.selectedId = '';
            this.searchQuery = '';
            this.isOpen = false;
        }
    };
}
</script>
@endpush
@endsection