@extends('layouts.dms')
@section('title', 'Stock Levels')

@section('content')
<x-ui.page-header title="Stock Levels">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('inventory.stock.movements')">View Movements</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="GET" action="{{ route('inventory.stock.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4"
          x-data="searchableProductSelect(@js($products), '{{ $filters['product_id'] ?? '' }}')">
        
        <div class="relative">
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Product</label>
            <input type="hidden" name="product_id" :value="selectedId">
            <div class="relative">
                <input type="text"
                       x-model="searchQuery"
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
            <select name="warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Warehouses</option>
                @foreach($warehouses as $w)
                    <option value="{{ $w->id }}" @selected(($filters['warehouse_id'] ?? '') == $w->id)>{{ $w->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Category</label>
            <select name="category_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Categories</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(($filters['category_id'] ?? '') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Brand</label>
            <select name="brand_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Brands</option>
                @foreach($brands as $b)
                    <option value="{{ $b->id }}" @selected(($filters['brand_id'] ?? '') == $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-4 flex items-center justify-between pt-2">
            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                <input type="checkbox" name="low_stock" value="1" @checked(!empty($filters['low_stock']))
                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Show low stock items only
            </label>

            <div class="flex items-center gap-2">
                <x-ui.button type="submit" variant="primary">Filter</x-ui.button>
                <x-ui.button variant="secondary" :href="route('inventory.stock.index')">Reset</x-ui.button>
            </div>
        </div>
    </form>
</x-ui.card>

<x-ui.card padding="false">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b">
                <tr>
                    <th class="p-3">Product</th>
                    <th class="p-3">SKU</th>
                    <th class="p-3">Category</th>
                    <th class="p-3">Warehouse</th>
                    <th class="p-3">Batch</th>
                    <th class="p-3 text-right">On Hand</th>
                    <th class="p-3 text-right">Allocated</th>
                    <th class="p-3 text-right">Available</th>
                    <th class="p-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($stockLevels as $stk)
                    <tr class="hover:bg-slate-50/50">
                        <td class="p-3 font-semibold text-slate-800">{{ $stk->product?->name ?? '-' }}</td>
                        <td class="p-3 font-mono text-slate-600">{{ $stk->product?->sku ?? '-' }}</td>
                        <td class="p-3 text-slate-600">{{ $stk->product?->category?->name ?? '-' }}</td>
                        <td class="p-3 text-slate-600">{{ $stk->warehouse?->name ?? '-' }}</td>
                        <td class="p-3 text-slate-600">{{ $stk->batch?->batch_number ?? '-' }}</td>
                        <td class="p-3 text-right font-mono font-semibold">{{ number_format($stk->quantity_on_hand, 2) }}</td>
                        <td class="p-3 text-right font-mono text-slate-500">{{ number_format($stk->quantity_allocated, 2) }}</td>
                        <td class="p-3 text-right font-mono font-bold text-indigo-600">{{ number_format($stk->quantity_available, 2) }}</td>
                        <td class="p-3 text-center">
                            @if($stk->quantity_available <= ($stk->product?->reorder_level ?? 0))
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