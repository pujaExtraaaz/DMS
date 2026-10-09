@extends('layouts.dms')
@section('title', 'New Stock Transfer')

@section('content')
<x-ui.page-header title="New Stock Transfer">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('inventory.transfers.index', ['tab' => $tab ?? 'location'])">Back</x-ui.button>
    </x-slot>
</x-ui.page-header>

<div x-data="{ activeTab: '{{ old('operation_type', $tab ?? 'location') }}' }" class="space-y-4">
    <!-- Operation Tabs -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-1.5 flex gap-2">
        <button type="button"
                @click="activeTab = 'location'"
                :class="activeTab === 'location' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                class="flex-1 py-2.5 px-4 rounded-lg font-semibold text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
            </svg>
            1. Location Transfer (Warehouse &rarr; Warehouse)
        </button>

        <button type="button"
                @click="activeTab = 'name'"
                :class="activeTab === 'name' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                class="flex-1 py-2.5 px-4 rounded-lg font-semibold text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
            </svg>
            2. Item / Stock Name Transfer (Stock Reclassification)
        </button>
    </div>

    <!-- TAB 1: LOCATION TRANSFER (Existing Functionality Preserved 100%) -->
    <div x-show="activeTab === 'location'" x-cloak>
        <x-ui.card>
            <div class="mb-4 pb-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Location Transfer</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Transfer physical stock between warehouses (From Warehouse &rarr; To Warehouse).</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">Location Movement</span>
            </div>

            <form method="POST" action="{{ route('inventory.transfers.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="operation_type" value="location">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-ui.input name="transfer_date" label="Date" type="date" :value="old('transfer_date', now()->toDateString())" required />
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">From Warehouse <span class="text-rose-500">*</span></label>
                        <select name="from_warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">Select source warehouse...</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}" @selected(old('from_warehouse_id') == $w->id)>{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">To Warehouse <span class="text-rose-500">*</span></label>
                        <select name="to_warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">Select destination warehouse...</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}" @selected(old('to_warehouse_id') == $w->id)>{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                    <table class="min-w-full text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase border-b">
                            <tr>
                                <th class="px-3 py-2 text-left">Product</th>
                                <th class="px-3 py-2 text-left w-36">UOM</th>
                                <th class="px-3 py-2 text-left w-36">Quantity</th>
                            </tr>
                        </thead>
                        <tbody id="tr-lines" class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-3 py-2">
                                    <select name="items[0][product_id]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="">Select product...</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <select name="items[0][uom_id]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        @foreach($uoms as $u)
                                            <option value="{{ $u->id }}">{{ $u->code }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.0001" min="0.0001" name="items[0][quantity]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" value="1" required>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                        <input type="checkbox" name="dispatch_now" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
                        Dispatch now (deduct stock from source immediately)
                    </label>

                    <div class="flex gap-2">
                        <x-ui.button type="button" variant="secondary" onclick="addTrRow()">+ Add Line</x-ui.button>
                        <x-ui.button type="submit" variant="primary">Create Transfer</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>
    </div>

    <!-- TAB 2: ITEM / STOCK NAME TRANSFER (NEW REQUIREMENT) -->
    <div x-show="activeTab === 'name'" x-cloak
         x-data="itemTransferComponent(@js($products), @js($warehouses), @js($uoms))">
        <x-ui.card>
            <div class="mb-5 pb-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Item / Stock Name Transfer (Stock Reclassification)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Change the product identity of on-hand inventory within the same warehouse without modifying product master catalogs or historical documents.</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Identity Reclassification</span>
            </div>

            <form method="POST" action="{{ route('inventory.transfers.name-transfer') }}" class="space-y-5" @submit="handleSubmit($event)">
                @csrf
                <input type="hidden" name="operation_type" value="name">
                <input type="hidden" name="from_product_id" :value="sourceProductId">
                <input type="hidden" name="to_product_id" :value="destProductId">
                <input type="hidden" name="uom_id" :value="uomId">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.input name="transfer_date" label="Transfer Date" type="date" :value="old('transfer_date', now()->toDateString())" required />

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Warehouse / Location <span class="text-rose-500">*</span>
                        </label>
                        <select name="warehouse_id"
                                x-model="warehouseId"
                                @change="onWarehouseChange()"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                            <option value="">Select Warehouse / Location...</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1">Physical stock remains in this warehouse; only item identity changes.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-2">
                    <!-- Source Product Card -->
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-4">
                        <div class="flex items-center justify-between border-b pb-2">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Current Item (Source)</span>
                            <span class="text-[11px] font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">Stock will be deducted</span>
                        </div>

                        <!-- Searchable Source Product -->
                        <div class="relative">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Select Current Item / Product <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="text"
                                       x-model="sourceSearch"
                                       @focus="sourceOpen = true"
                                       @input="sourceOpen = true"
                                       @click.outside="sourceOpen = false"
                                       placeholder="Type product name or SKU..."
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8">
                                <button type="button" x-show="sourceProductId" @click="clearSource()" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-bold">&times;</button>
                            </div>

                            <div x-show="sourceOpen && filteredSourceProducts.length > 0"
                                 x-transition
                                 class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100">
                                <template x-for="p in filteredSourceProducts" :key="p.id">
                                    <div @click="selectSource(p)"
                                         class="p-2.5 hover:bg-indigo-50 cursor-pointer text-xs flex items-center justify-between">
                                        <div>
                                            <span class="font-medium text-slate-800" x-text="p.name"></span>
                                            <span class="text-slate-400 text-[11px] block" x-text="'SKU: ' + (p.sku || '—')"></span>
                                        </div>
                                        <span class="text-[11px] text-slate-500 font-mono" x-text="p.base_uom ? p.base_uom.code : ''"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Read-only Info Grid -->
                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase text-slate-500 mb-1">Current Product Name</label>
                                <input type="text" readonly :value="sourceProductName || '—'"
                                       class="block w-full rounded-lg border-slate-200 bg-slate-100 text-xs text-slate-800 font-semibold cursor-not-allowed">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold uppercase text-slate-500 mb-1">Unit of Measure (UOM)</label>
                                <input type="text" readonly :value="uomCode || '—'"
                                       class="block w-full rounded-lg border-slate-200 bg-slate-100 text-xs text-slate-800 font-mono cursor-not-allowed">
                            </div>

                            <div class="col-span-2">
                                <label class="block text-[11px] font-semibold uppercase text-slate-500 mb-1">Current Stock Quantity (In Selected Location)</label>
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <input type="text" readonly :value="isLoadingStock ? 'Checking available stock...' : (sourceProductId ? (availableStock.toFixed(2) + ' ' + uomCode) : 'Select product & warehouse')"
                                               :class="availableStock > 0 ? 'text-emerald-700 bg-emerald-50/60 border-emerald-300' : (sourceProductId ? 'text-rose-700 bg-rose-50/60 border-rose-300' : 'text-slate-500 bg-slate-100 border-slate-200')"
                                               class="block w-full rounded-lg text-xs font-mono font-bold cursor-not-allowed py-2">
                                    </div>
                                </div>
                                <template x-if="warehousesWithStock.length > 1 && !warehouseId">
                                    <p class="text-[11px] text-indigo-600 mt-1 font-medium">
                                        Stock found in multiple warehouses: please choose warehouse above.
                                    </p>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Destination Product Card -->
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-4">
                        <div class="flex items-center justify-between border-b pb-2">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">New Item (Destination)</span>
                            <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Stock will be credited</span>
                        </div>

                        <!-- Searchable Destination Product -->
                        <div class="relative">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Select New Item / Product Name <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="text"
                                       x-model="destSearch"
                                       @focus="destOpen = true"
                                       @input="destOpen = true"
                                       @click.outside="destOpen = false"
                                       placeholder="Type destination product name or SKU..."
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8">
                                <button type="button" x-show="destProductId" @click="clearDest()" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-bold">&times;</button>
                            </div>

                            <div x-show="destOpen && filteredDestProducts.length > 0"
                                 x-transition
                                 class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100">
                                <template x-for="p in filteredDestProducts" :key="p.id">
                                    <div @click="selectDest(p)"
                                         class="p-2.5 hover:bg-indigo-50 cursor-pointer text-xs flex items-center justify-between">
                                        <div>
                                            <span class="font-medium text-slate-800" x-text="p.name"></span>
                                            <span class="text-slate-400 text-[11px] block" x-text="'SKU: ' + (p.sku || '—')"></span>
                                        </div>
                                        <span class="text-[11px] text-slate-500 font-mono" x-text="p.base_uom ? p.base_uom.code : ''"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Quantity to Rename / Transfer <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                       step="0.0001"
                                       min="0.0001"
                                       name="quantity"
                                       x-model="quantity"
                                       :max="availableStock > 0 ? availableStock : ''"
                                       placeholder="Enter quantity to transfer..."
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono font-bold"
                                       required>
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono text-slate-500" x-text="uomCode"></span>
                            </div>
                            
                            <template x-if="isOverStock">
                                <p class="text-xs text-rose-600 font-semibold mt-1 flex items-center gap-1">
                                    <span>&times;</span> Cannot transfer more than available stock (<span x-text="availableStock.toFixed(2)"></span> <span x-text="uomCode"></span>).
                                </p>
                            </template>
                            <template x-if="isSameProduct">
                                <p class="text-xs text-rose-600 font-semibold mt-1">
                                    Source and destination cannot be the same product.
                                </p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Reason / Remarks (Optional)</label>
                            <input type="text"
                                   name="notes"
                                   x-model="notes"
                                   placeholder="e.g. Corrected SKU naming, grade reclassification, repackaged..."
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Mathematical Summary & Confirmation Card -->
                <div x-show="sourceProductId && destProductId && quantity > 0 && !isOverStock && !isSameProduct"
                     x-transition
                     class="p-4 rounded-xl bg-indigo-50/70 border border-indigo-200">
                    <h4 class="text-xs font-bold uppercase text-indigo-900 tracking-wider mb-2">Reclassification Accounting Preview</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        <div class="bg-white p-2.5 rounded-lg border border-indigo-100">
                            <span class="text-slate-500 block">Source Balance After:</span>
                            <span class="font-bold text-slate-800" x-text="sourceProductName"></span>:
                            <span class="font-mono font-bold text-rose-600" x-text="(availableStock - parseFloat(quantity || 0)).toFixed(2) + ' ' + uomCode"></span>
                        </div>
                        <div class="bg-white p-2.5 rounded-lg border border-indigo-100">
                            <span class="text-slate-500 block">Transferred Quantity:</span>
                            <span class="font-mono font-bold text-indigo-700" x-text="parseFloat(quantity || 0).toFixed(2) + ' ' + uomCode"></span>
                        </div>
                        <div class="bg-white p-2.5 rounded-lg border border-indigo-100">
                            <span class="text-slate-500 block">Destination Increase:</span>
                            <span class="font-bold text-slate-800" x-text="destProductName"></span>:
                            <span class="font-mono font-bold text-emerald-600" x-text="'+' + parseFloat(quantity || 0).toFixed(2) + ' ' + uomCode"></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <x-ui.button type="button" variant="secondary" :href="route('inventory.transfers.index', ['tab' => 'name'])">Cancel</x-ui.button>
                    <x-ui.button type="submit" variant="primary" x-bind:disabled="!canSubmit">Confirm Name Transfer</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>

@push('scripts')
<script>
// Row addition for Location Transfer
let tri = 1;
function addTrRow() {
    const t = document.getElementById('tr-lines');
    t.insertAdjacentHTML('beforeend', t.rows[0].outerHTML.replace(/items\[0\]/g, 'items[' + tri + ']'));
    tri++;
}

// Alpine logic for Item/Stock Name Transfer
function itemTransferComponent(products, warehouses, uoms) {
    return {
        products: products || [],
        warehouses: warehouses || [],
        uoms: uoms || [],
        warehouseId: '{{ old('warehouse_id', $warehouses->first()->id ?? '') }}',
        sourceProductId: '{{ old('from_product_id', '') }}',
        sourceSearch: '',
        sourceOpen: false,
        destProductId: '{{ old('to_product_id', '') }}',
        destSearch: '',
        destOpen: false,
        quantity: '{{ old('quantity', '') }}',
        availableStock: 0,
        uomId: '{{ old('uom_id', '') }}',
        uomCode: '',
        sourceProductName: '',
        destProductName: '',
        notes: '{{ old('notes', '') }}',
        isLoadingStock: false,
        warehousesWithStock: [],

        init() {
            if (this.sourceProductId) {
                const found = this.products.find(p => String(p.id) === String(this.sourceProductId));
                if (found) {
                    this.sourceSearch = found.name;
                    this.sourceProductName = found.name;
                }
                this.fetchStock();
            }
            if (this.destProductId) {
                const found = this.products.find(p => String(p.id) === String(this.destProductId));
                if (found) {
                    this.destSearch = found.name;
                    this.destProductName = found.name;
                }
            }
        },

        get filteredSourceProducts() {
            const q = (this.sourceSearch || '').toLowerCase().trim();
            if (!q) return this.products.slice(0, 50);
            return this.products.filter(p => {
                return (p.name && p.name.toLowerCase().includes(q)) ||
                       (p.sku && p.sku.toLowerCase().includes(q));
            }).slice(0, 50);
        },

        get filteredDestProducts() {
            const q = (this.destSearch || '').toLowerCase().trim();
            return this.products
                .filter(p => String(p.id) !== String(this.sourceProductId))
                .filter(p => {
                    if (!q) return true;
                    return (p.name && p.name.toLowerCase().includes(q)) ||
                           (p.sku && p.sku.toLowerCase().includes(q));
                }).slice(0, 50);
        },

        selectSource(product) {
            this.sourceProductId = String(product.id);
            this.sourceSearch = product.name;
            this.sourceProductName = product.name;
            this.sourceOpen = false;
            this.uomId = product.base_uom_id || '';
            this.uomCode = product.base_uom ? product.base_uom.code : 'PCS';
            if (this.destProductId === this.sourceProductId) {
                this.clearDest();
            }
            this.fetchStock();
        },

        clearSource() {
            this.sourceProductId = '';
            this.sourceSearch = '';
            this.sourceProductName = '';
            this.availableStock = 0;
            this.uomId = '';
            this.uomCode = '';
            this.sourceOpen = false;
        },

        selectDest(product) {
            this.destProductId = String(product.id);
            this.destSearch = product.name;
            this.destProductName = product.name;
            this.destOpen = false;
        },

        clearDest() {
            this.destProductId = '';
            this.destSearch = '';
            this.destProductName = '';
            this.destOpen = false;
        },

        onWarehouseChange() {
            if (this.sourceProductId) {
                this.fetchStock();
            }
        },

        fetchStock() {
            if (!this.sourceProductId) return;
            this.isLoadingStock = true;
            const url = `{{ route('inventory.transfers.stock-availability') }}?product_id=${this.sourceProductId}&warehouse_id=${this.warehouseId || ''}`;
            
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    this.isLoadingStock = false;
                    this.availableStock = parseFloat(data.available_quantity || 0);
                    if (data.uom_id) this.uomId = data.uom_id;
                    if (data.uom_code) this.uomCode = data.uom_code;
                    this.warehousesWithStock = data.warehouses_with_stock || [];
                    
                    // If no warehouse was selected and stock is in a single warehouse, auto-select it
                    if (!this.warehouseId && data.warehouse_id) {
                        this.warehouseId = String(data.warehouse_id);
                    }
                })
                .catch(err => {
                    this.isLoadingStock = false;
                    console.error('Error fetching stock:', err);
                });
        },

        get isOverStock() {
            const q = parseFloat(this.quantity);
            return !isNaN(q) && q > 0 && q > this.availableStock;
        },

        get isSameProduct() {
            return this.sourceProductId && this.destProductId && String(this.sourceProductId) === String(this.destProductId);
        },

        get canSubmit() {
            const q = parseFloat(this.quantity);
            return this.sourceProductId &&
                   this.destProductId &&
                   !this.isSameProduct &&
                   this.warehouseId &&
                   !isNaN(q) &&
                   q > 0 &&
                   q <= this.availableStock;
        },

        handleSubmit(event) {
            if (!this.canSubmit) {
                event.preventDefault();
                alert('Please ensure all required fields are valid and quantity does not exceed available stock.');
                return false;
            }
            if (!confirm(`Are you sure you want to reclassify ${this.quantity} ${this.uomCode} from "${this.sourceProductName}" to "${this.destProductName}"?`)) {
                event.preventDefault();
                return false;
            }
        }
    };
}
</script>
@endpush
@endsection
