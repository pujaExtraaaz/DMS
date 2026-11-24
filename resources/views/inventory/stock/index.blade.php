@extends('layouts.dms')
@section('title', 'Current Stock')

@section('content')
<div id="listing-container" data-dynamic-container class="space-y-2" x-data="currentStockPage()">
<x-ui.page-header title="Current Stock">
    <x-slot name="actions">
        <x-ui.export-menu />
        <x-ui.button variant="secondary" :href="route('inventory.stock-register.index')">Stock Register</x-ui.button>
        <x-ui.button variant="secondary" :href="route('reports.stock-ledger')">View Stock Ledger</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card class="!p-3">
    <form method="GET" action="{{ route('inventory.stock.index') }}"
          class="flex flex-nowrap items-center gap-2 overflow-x-auto"
          x-data="searchableProductSelect(@js($products), '{{ $filters['product_id'] ?? '' }}')">

        <div class="relative min-w-[220px] flex-1">
            <input type="hidden" name="product_id" :value="selectedId" data-dynamic-filter>
            <div class="relative">
                <input type="text"
                       x-model="searchQuery"
                       data-dynamic-search
                       name="search"
                       @focus="openDropdown()"
                       @input="openDropdown()"
                       @click.outside="closeDropdown()"
                       placeholder="Product / Part No..."
                       class="block w-full rounded-lg border-gray-300 py-1.5 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8">
                <button type="button" x-show="selectedId" @click="clearSelection()"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-bold">&times;</button>
            </div>
            <div x-show="isOpen && filteredProducts.length > 0"
                 x-transition
                 class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100"
                 style="display: none;">
                <template x-for="prod in filteredProducts" :key="prod.id">
                    <div @click="selectProduct(prod)"
                         class="p-2 hover:bg-indigo-50 cursor-pointer text-xs flex items-center justify-between">
                        <span class="font-medium text-slate-800" x-text="prod.name"></span>
                        <span class="font-mono text-[11px] text-slate-500" x-text="prod.sku"></span>
                    </div>
                </template>
            </div>
        </div>

        <select name="warehouse_id" data-dynamic-filter title="Warehouse"
                class="rounded-lg border-gray-300 py-1.5 text-sm focus:border-indigo-500 focus:ring-indigo-500 shrink-0">
            <option value="">Warehouse: All</option>
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}" @selected(($filters['warehouse_id'] ?? '') == $w->id)>{{ $w->name }}</option>
            @endforeach
        </select>

        <select name="category_id" data-dynamic-filter title="Category"
                class="rounded-lg border-gray-300 py-1.5 text-sm focus:border-indigo-500 focus:ring-indigo-500 shrink-0">
            <option value="">Category: All</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected(($filters['category_id'] ?? '') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>

        <select name="brand_id" data-dynamic-filter title="Brand"
                class="rounded-lg border-gray-300 py-1.5 text-sm focus:border-indigo-500 focus:ring-indigo-500 shrink-0">
            <option value="">Brand: All</option>
            @foreach($brands as $b)
                <option value="{{ $b->id }}" @selected(($filters['brand_id'] ?? '') == $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>

        <input type="text" name="color_variant" title="Color / Variant"
               value="{{ $filters['color_variant'] ?? request('color_variant') }}"
               list="stock-colors" data-dynamic-filter placeholder="Variant..."
               class="w-28 rounded-lg border-gray-300 py-1.5 text-sm focus:border-indigo-500 focus:ring-indigo-500 shrink-0">
        <datalist id="stock-colors">
            @foreach($colors as $c)<option value="{{ $c }}"></option>@endforeach
        </datalist>

        <select name="stock_status" data-dynamic-filter title="Stock Status"
                class="rounded-lg border-gray-300 py-1.5 text-sm focus:border-indigo-500 focus:ring-indigo-500 shrink-0">
            <option value="">Stock: All</option>
            <option value="available" @selected(($filters['stock_status'] ?? '') == 'available')>Available</option>
            <option value="in_transit" @selected(($filters['stock_status'] ?? '') == 'in_transit')>In-Transit</option>
            <option value="both" @selected(($filters['stock_status'] ?? '') == 'both')>Both</option>
            <option value="low_stock" @selected(($filters['stock_status'] ?? '') == 'low_stock')>Low Stock</option>
        </select>

        <input type="date" name="expected_delivery_date" title="Expected Delivery"
               value="{{ $filters['expected_delivery_date'] ?? '' }}" data-dynamic-filter
               class="rounded-lg border-gray-300 py-1.5 text-sm focus:border-indigo-500 focus:ring-indigo-500 shrink-0">

        <label class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700 cursor-pointer whitespace-nowrap shrink-0">
            <input type="checkbox" name="low_stock" value="1" data-dynamic-filter
                   @checked(!empty($filters['low_stock']) || request()->boolean('low_stock'))
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            Low only
        </label>

        <x-ui.button type="submit" variant="primary" class="shrink-0 !py-1.5">Filter</x-ui.button>
        <a href="{{ route('inventory.stock.index') }}" data-reset-filters
           class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 shrink-0">Reset</a>
    </form>
</x-ui.card>

<x-ui.card padding="false">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-600 tracking-wider border-b">
                <tr>
                    <x-ui.sortable-th column="product" label="Product" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')" />
                    <x-ui.sortable-th column="sku" label="Part Number / Serial No." :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')" />
                    <x-ui.sortable-th column="brand" label="Brand" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')" />
                    <x-ui.sortable-th column="category" label="Category" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')" />
                    <th class="px-3 py-2 text-xs font-semibold">Color / Variant</th>
                    <x-ui.sortable-th column="warehouse" label="Warehouse" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')" />
                    <th class="px-3 py-2 text-xs font-semibold">Unit</th>
                    <x-ui.sortable-th
                        column="quantity"
                        label="Available Stock"
                        tooltip="Available Stock — Physically received and ready for dispatch"
                        :current-sort="$sort ?? request('sort', 'product')"
                        :current-direction="$direction ?? request('direction', 'asc')"
                        align="right"
                    />
                    <x-ui.sortable-th
                        column="in_transit"
                        label="In-Transit Stock"
                        tooltip="In-Transit Stock — Purchased from suppliers, not yet received"
                        :current-sort="$sort ?? request('sort', 'product')"
                        :current-direction="$direction ?? request('direction', 'asc')"
                        align="right"
                    />
                    <x-ui.sortable-th
                        column="total_expected"
                        label="Total Expected Stock"
                        tooltip="Total Expected Stock — Available + In-Transit"
                        :current-sort="$sort ?? request('sort', 'product')"
                        :current-direction="$direction ?? request('direction', 'asc')"
                        align="right"
                    />
                    <x-ui.sortable-th column="expected_delivery_date" label="Expected Delivery" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')" />
                    <th class="px-3 py-2 text-xs font-semibold text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($stockLevels as $stk)
                    @php
                        $qty = (float) $stk->quantity;
                        $inTransit = (float) ($stk->in_transit_quantity ?? 0);
                        $totalExpected = (float) ($stk->total_expected_quantity ?? ($qty + $inTransit));
                        $reorder = (float) ($stk->product?->reorder_level ?? 0);
                        $isLow = $qty < 10 || ($reorder > 0 && $qty <= $reorder);
                        $deliveryDateRaw = $stk->expected_delivery_date;
                        $deliveryDate = $deliveryDateRaw ? \Carbon\Carbon::parse($deliveryDateRaw) : null;
                        $isOverdue = $deliveryDate && $deliveryDate->isPast() && $inTransit > 0;
                        $invoiceCount = (int) ($stk->incoming_invoices_count ?? 0);
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-3 py-2 font-semibold text-slate-800">
                            <a href="{{ route('masters.products.show', $stk->product_id) }}" class="hover:text-indigo-600 hover:underline">
                                {{ $stk->product?->name ?? '—' }}
                            </a>
                        </td>
                        <td class="px-3 py-2 font-mono text-slate-600">
                            <div>{{ $stk->product?->sku ?? '—' }}</div>
                            @if($stk->product?->serial_no)
                                <div class="text-[11px] text-slate-400">SN: {{ $stk->product->serial_no }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-slate-600">{{ $stk->product?->brand?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $stk->product?->category?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $stk->product?->color_variant ?? '—' }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $stk->warehouse?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $stk->uom?->code ?? $stk->uom?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-slate-800">
                            @if($qty > 0)
                                <span class="text-emerald-700 font-bold">{{ number_format($qty, 2) }}</span>
                            @else
                                <span class="text-slate-400">0.00</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right font-mono">
                            @if($inTransit > 0)
                                <button type="button"
                                        @click="openInTransitModal({{ $stk->product_id }}, {{ $stk->warehouse_id }}, '{{ addslashes($stk->product?->name ?? '') }}', '{{ addslashes($stk->warehouse?->name ?? '') }}')"
                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold hover:bg-indigo-100 hover:border-indigo-300 transition text-xs group"
                                        title="Click to view {{ $invoiceCount }} incoming Purchase Invoice(s)">
                                    <span>{{ number_format($inTransit, 2) }}</span>
                                    <svg class="w-3 h-3 text-indigo-400 group-hover:text-indigo-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                            @else
                                <span class="text-slate-300">0.00</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-indigo-900">
                            {{ number_format($totalExpected, 2) }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            @if($inTransit > 0 && $deliveryDate)
                                <div class="flex items-center gap-1">
                                    <span class="font-medium text-slate-800 {{ $isOverdue ? 'text-rose-600 font-semibold' : '' }}">
                                        {{ $deliveryDate->format('d M Y') }}
                                    </span>
                                    @if($isOverdue)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-100 text-rose-700 uppercase">Overdue</span>
                                    @endif
                                </div>
                            @elseif($inTransit > 0)
                                <span class="text-slate-400 italic text-[11px]">Date not set</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            @if($isLow && $qty > 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">Low Stock</span>
                            @elseif($qty > 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">In Stock</span>
                            @elseif($inTransit > 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">In Transit Only</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">Out of Stock</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="p-8 text-center text-slate-400">No stock records found matching criteria.</td>
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
            <thead class="bg-slate-50 text-slate-600 tracking-wider border-b">
                <tr>
                    <th class="px-3 py-2">Date / Time</th>
                    <th class="px-3 py-2">Product</th>
                    <th class="px-3 py-2">Warehouse</th>
                    <th class="px-3 py-2">Type</th>
                    <th class="px-3 py-2 text-right">Quantity</th>
                    <th class="px-3 py-2 text-right">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($movements as $m)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-3 py-2 font-mono text-slate-500">{{ $m->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                        <td class="px-3 py-2 font-semibold text-slate-800">{{ $m->product?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $m->warehouse?->name ?? '—' }}</td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-slate-100 text-slate-700 border border-slate-200">
                                {{ str_replace('_', ' ', $m->type) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-right font-mono font-semibold {{ $m->quantity > 0 ? 'text-emerald-600' : 'text-slate-800' }}">
                            {{ $m->quantity > 0 ? '+'.number_format($m->quantity, 2) : number_format($m->quantity, 2) }}
                        </td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-slate-900">{{ number_format($m->balance_after, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.card>
@endif

{{-- Modal: In-Transit Incoming Purchase Invoices Breakdown --}}
<div x-show="modalOpen"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     role="dialog"
     aria-modal="true"
     @keydown.escape.window="closeModal()">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
             @click="closeModal()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-2xl shadow-xl sm:my-8"
             @click.stop>

            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <span class="p-1 rounded bg-indigo-50 text-indigo-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        </span>
                        <span>Incoming In-Transit Deliveries</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">
                        <strong class="text-slate-700" x-text="currentProductName"></strong> — 
                        <span x-text="currentWarehouseName"></span>
                    </p>
                </div>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div x-show="loading" class="py-12 text-center text-slate-400">
                <svg class="w-6 h-6 animate-spin mx-auto text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span>Loading in-transit details...</span>
            </div>

            <div x-show="!loading" class="mt-4">
                <template x-if="invoices.length > 0">
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-600 border-b">
                                <tr>
                                    <th class="p-2.5">Invoice #</th>
                                    <th class="p-2.5">Supplier</th>
                                    <th class="p-2.5 text-right">Ordered</th>
                                    <th class="p-2.5 text-right">Received</th>
                                    <th class="p-2.5 text-right font-bold text-indigo-700">In Transit</th>
                                    <th class="p-2.5">Expected Delivery</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="inv in invoices" :key="inv.item_id">
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="p-2.5 font-mono">
                                            <a :href="'{{ url('purchasing/invoices') }}/' + inv.invoice_id"
                                               target="_blank"
                                               class="text-indigo-600 font-semibold hover:underline flex items-center gap-1">
                                                <span x-text="inv.invoice_no"></span>
                                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            </a>
                                        </td>
                                        <td class="p-2.5 text-slate-700 font-medium" x-text="inv.supplier_name"></td>
                                        <td class="p-2.5 text-right font-mono" x-text="Number(inv.ordered_qty).toFixed(2)"></td>
                                        <td class="p-2.5 text-right font-mono text-emerald-700" x-text="Number(inv.received_qty).toFixed(2)"></td>
                                        <td class="p-2.5 text-right font-mono font-bold text-indigo-700 bg-indigo-50/30" x-text="Number(inv.in_transit_qty).toFixed(2)"></td>
                                        <td class="p-2.5 whitespace-nowrap">
                                            <div class="flex items-center gap-1">
                                                <span :class="inv.is_overdue ? 'text-rose-600 font-semibold' : 'text-slate-800'" x-text="inv.expected_delivery_date"></span>
                                                <span x-show="inv.is_overdue" class="px-1 py-0.2 rounded text-[9px] font-bold bg-rose-100 text-rose-700 uppercase" x-cloak>Overdue</span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-slate-50 border-t border-slate-200 font-semibold text-slate-800">
                                <tr>
                                    <td colspan="4" class="p-2.5 text-right">Total In-Transit:</td>
                                    <td class="p-2.5 text-right font-mono font-bold text-indigo-700" x-text="totalInTransit.toFixed(2)"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </template>

                <template x-if="invoices.length === 0">
                    <div class="py-8 text-center text-slate-400 italic">
                        No pending in-transit purchase invoices found for this item.
                    </div>
                </template>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="button" @click="closeModal()" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<script>
function currentStockPage() {
    return {
        modalOpen: false,
        loading: false,
        currentProductName: '',
        currentWarehouseName: '',
        invoices: [],
        totalInTransit: 0,

        async openInTransitModal(productId, warehouseId, productName, warehouseName) {
            this.modalOpen = true;
            this.loading = true;
            this.currentProductName = productName;
            this.currentWarehouseName = warehouseName;
            this.invoices = [];
            this.totalInTransit = 0;

            try {
                const url = `{{ url('inventory/stock') }}/${productId}/${warehouseId}/in-transit-details`;
                const res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.invoices = data.invoices || [];
                    this.totalInTransit = data.total_in_transit || 0;
                }
            } catch (e) {
                console.error('Failed to load in-transit details', e);
            } finally {
                this.loading = false;
            }
        },

        closeModal() {
            this.modalOpen = false;
        }
    };
}

function searchableProductSelect(products, initialId) {
    return {
        products: products || [],
        selectedId: initialId || '',
        searchQuery: '',
        isOpen: false,
        init() {
            if (this.selectedId) {
                const p = this.products.find(x => String(x.id) === String(this.selectedId));
                if (p) this.searchQuery = p.name;
            }
        },
        get filteredProducts() {
            const q = (this.searchQuery || '').trim().toLowerCase();
            if (!q) return this.products.slice(0, 30);
            return this.products.filter(p => p.name.toLowerCase().startsWith(q) || (p.sku && p.sku.toLowerCase().startsWith(q))).slice(0, 30);
        },
        openDropdown() { this.isOpen = true; },
        closeDropdown() { setTimeout(() => { this.isOpen = false; }, 200); },
        selectProduct(prod) {
            this.selectedId = prod.id;
            this.searchQuery = prod.name;
            this.isOpen = false;
        },
        clearSelection() {
            this.selectedId = '';
            this.searchQuery = '';
        }
    };
}
</script>
@endsection
