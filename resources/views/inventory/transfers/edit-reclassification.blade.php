@extends('layouts.dms')
@section('title', 'Edit Stock Name Transfer - ' . $reclassification->reclassification_no)

@section('content')
<div class="space-y-4">
    <x-ui.page-header :title="'Edit Stock Name Transfer: ' . $reclassification->reclassification_no">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('inventory.transfers.reclassifications.show', $reclassification)">View Details</x-ui.button>
            <x-ui.button variant="secondary" :href="route('inventory.transfers.index', ['tab' => 'name'])">Back to List</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
            <span class="font-bold">Inventory Reclassification Safeguard:</span> Modifying this transfer will reverse the prior stock movement (returning credited stock from the current destination item and restoring source inventory) and apply the updated reclassification. The system verifies that the current destination product has enough available stock so inventory will not become negative.
        </div>
    </div>

    <div x-data="itemTransferEditComponent(@js($products), @js($warehouses), @js($uoms), @js($reclassification))">
        <x-ui.card>
            <div class="mb-5 pb-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-800 tracking-wider">Modify Stock Name Transfer</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Reference No: <span class="font-mono font-semibold text-indigo-700">{{ $reclassification->reclassification_no }}</span></p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Identity Reclassification</span>
            </div>

            <form method="POST" action="{{ route('inventory.transfers.reclassifications.update', $reclassification) }}" class="space-y-5" @submit="handleSubmit($event)">
                @csrf
                @method('PUT')
                <input type="hidden" name="from_product_id" :value="sourceProductId">
                <input type="hidden" name="to_product_id" :value="destProductId">
                <input type="hidden" name="uom_id" :value="uomId">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.input
                        name="transfer_date"
                        label="Transfer Date"
                        type="date"
                        :value="old('transfer_date', $reclassification->reclassification_date?->format('Y-m-d'))"
                        required
                    />

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                            Warehouse / Location <span class="text-rose-500">*</span>
                        </label>
                        <select name="warehouse_id"
                                x-model="warehouseId"
                                @change="onWarehouseChange()"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                            <option value="">Select Warehouse / Location...</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}" @selected(old('warehouse_id', $reclassification->warehouse_id) == $w->id)>{{ $w->name }}</option>
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
                                       placeholder="Type Product Name Or SKU..."
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
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Current Product Name</label>
                                <input type="text" readonly :value="sourceProductName || '—'"
                                       class="block w-full rounded-lg border-slate-200 bg-slate-100 text-xs text-slate-800 font-semibold cursor-not-allowed">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Unit of Measure (UOM)</label>
                                <input type="text" readonly :value="uomCode || '—'"
                                       class="block w-full rounded-lg border-slate-200 bg-slate-100 text-xs text-slate-800 font-mono cursor-not-allowed">
                            </div>

                            <div class="col-span-2">
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Current Available Stock (In Selected Location)</label>
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <input type="text" readonly :value="isLoadingStock ? 'Checking available stock...' : (sourceProductId ? (effectiveStock.toFixed(2) + ' ' + uomCode) : 'Select product & warehouse')"
                                               :class="effectiveStock > 0 ? 'text-emerald-700 bg-emerald-50/60 border-emerald-300' : (sourceProductId ? 'text-rose-700 bg-rose-50/60 border-rose-300' : 'text-slate-500 bg-slate-100 border-slate-200')"
                                               class="block w-full rounded-lg text-xs font-mono font-bold cursor-not-allowed py-2">
                                    </div>
                                </div>
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
                                       placeholder="Type Destination Product Name Or SKU..."
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
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Quantity to Rename / Transfer <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                       step="0.0001"
                                       min="0.0001"
                                       name="quantity"
                                       x-model="quantity"
                                       :max="effectiveStock > 0 ? effectiveStock : ''"
                                       placeholder="Enter Quantity To Transfer..."
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono font-bold"
                                       required>
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono text-slate-500" x-text="uomCode"></span>
                            </div>

                            <template x-if="isOverStock">
                                <p class="text-xs text-rose-600 font-semibold mt-1 flex items-center gap-1">
                                    <span>&times;</span> Cannot transfer more than available stock (<span x-text="effectiveStock.toFixed(2)"></span> <span x-text="uomCode"></span>).
                                </p>
                            </template>
                            <template x-if="isSameProduct">
                                <p class="text-xs text-rose-600 font-semibold mt-1">
                                    Source and destination cannot be the same product.
                                </p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Reason / Remarks (Optional)</label>
                            <input type="text"
                                   name="notes"
                                   x-model="notes"
                                   placeholder="E.G. Corrected SKU Naming, Grade Reclassification, Repackaged..."
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <x-ui.button type="button" variant="secondary" :href="route('inventory.transfers.index', ['tab' => 'name'])">Cancel</x-ui.button>
                    <x-ui.button type="submit" variant="primary" x-bind:disabled="!canSubmit">Update Name Transfer</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>

@push('scripts')
<script>
function itemTransferEditComponent(products, warehouses, uoms, initial) {
    return {
        products: products || [],
        warehouses: warehouses || [],
        uoms: uoms || [],
        initial: initial || {},
        warehouseId: String(initial.warehouse_id || ''),
        sourceProductId: String(initial.from_product_id || ''),
        sourceSearch: '',
        sourceOpen: false,
        destProductId: String(initial.to_product_id || ''),
        destSearch: '',
        destOpen: false,
        quantity: String(initial.quantity || ''),
        availableStock: 0,
        uomId: String(initial.uom_id || ''),
        uomCode: initial.uom ? initial.uom.code : '',
        sourceProductName: '',
        destProductName: '',
        notes: initial.notes || '',
        isLoadingStock: false,

        init() {
            if (this.sourceProductId) {
                const found = this.products.find(p => String(p.id) === String(this.sourceProductId));
                if (found) {
                    this.sourceSearch = found.name;
                    this.sourceProductName = found.name;
                    if (!this.uomCode && found.base_uom) {
                        this.uomCode = found.base_uom.code;
                    }
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

        get effectiveStock() {
            // When editing, if the source product and warehouse match the original, the original transfer quantity is returned to available stock
            let stock = this.availableStock;
            if (String(this.sourceProductId) === String(this.initial.from_product_id) &&
                String(this.warehouseId) === String(this.initial.warehouse_id)) {
                stock += parseFloat(this.initial.quantity || 0);
            }
            return stock;
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
                })
                .catch(err => {
                    this.isLoadingStock = false;
                    console.error('Error fetching stock:', err);
                });
        },

        get isOverStock() {
            const q = parseFloat(this.quantity);
            return !isNaN(q) && q > 0 && q > this.effectiveStock;
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
                   q <= this.effectiveStock;
        },

        handleSubmit(event) {
            if (!this.canSubmit) {
                event.preventDefault();
                alert('Please ensure all required fields are valid and quantity does not exceed available stock.');
                return false;
            }
            if (!confirm(`Are you sure you want to update this reclassification to ${this.quantity} ${this.uomCode} from "${this.sourceProductName}" to "${this.destProductName}"?`)) {
                event.preventDefault();
                return false;
            }
        }
    };
}
</script>
@endpush
@endsection

