@extends('layouts.dms')
@section('title', 'New Purchase Invoice')
@section('content')
@php
    $productsJson = $products->map(fn($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'sku' => $p->sku,
        'base_uom_id' => $p->base_uom_id,
        'tax_rate' => (float) $p->tax_rate,
        'purchase_price' => (float) $p->purchase_price,
        'selling_price' => (float) $p->selling_price,
        'mrp' => (float) $p->calculation_mrp,
        'color_variant' => $p->color_variant,
        'is_serial_tracked' => $p->isSerialTracked(),
        'is_batch_tracked' => $p->isBatchTracked(),
    ])->values()->toArray();

    $uomsJson = $uoms->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'code' => $u->code])->values()->toArray();

    $vendorRatesJson = $vendorRates
        ->map(function ($rates) {
            return $rates->map(fn($rate) => [
                'supplier_id' => (int) $rate->supplier_id,
                'product_id' => (int) $rate->product_id,
                'uom_id' => (int) $rate->uom_id,
                'unit_cost' => (float) $rate->unit_cost,
                'effective_from' => optional($rate->effective_from)->format('Y-m-d'),
            ])->values()->toArray();
        })
        ->toArray();

    $suppliersJson = $suppliers->map(fn($s) => [
        'id' => $s->id,
        'name' => $s->name,
        'credit_days' => $s->credit_days !== null ? (int) $s->credit_days : null,
    ])->values()->toArray();
@endphp

<x-ui.page-header title="New Purchase Invoice" description="Book supplier invoice — with or without a preceding Purchase Order. Unit Cost, Freight/Landed Cost, Batch Selling Price, and Batch MRP are cleanly maintained.">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('purchasing.invoices.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="POST" action="{{ route('purchasing.invoices.store') }}"
          x-data='pInvoiceForm(@json($productsJson), @json($uomsJson), @json($vendorRatesJson), @json($suppliersJson))'
          x-init="init()"
          x-on:submit="return validateBeforeSubmit($event)"
          x-on:product-quick-added.window="onProductAdded($event.detail)"
          class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Supplier *</label>
                <select name="supplier_id" 
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" 
                        required 
                        x-model="selectedSupplierId"
                        x-on:change="onSupplierChange()">
                    <option value="">Select Supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Linked PO (optional — leave blank for direct PI)</label>
                <select name="purchase_order_id" 
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    x-on:change="loadPurchaseOrder($event.target.value)">
                    <option value="">— Direct purchase invoice —</option>
                    @foreach($orders as $o)<option value="{{ $o->id }}" @selected($selectedOrderId==$o->id)>{{ $o->po_no }}</option>@endforeach
                </select>
            </div>
            <x-ui.input name="supplier_invoice_no" label="Supplier Invoice No" :value="old('supplier_invoice_no')" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Invoice Date *</label>
                <input 
                    type="date" 
                    name="invoice_date" 
                    x-model="invoiceDate" 
                    x-on:change="onInvoiceDateChange()" 
                    required 
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Credit Days</label>
                <select 
                    x-model="creditDaysOption" 
                    x-on:change="onCreditDaysOptionChange()" 
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="0">0 Days</option>
                    <option value="7">7 Days</option>
                    <option value="15">15 Days</option>
                    <option value="30">30 Days</option>
                    <option value="45">45 Days</option>
                    <option value="50">50 Days</option>
                    <option value="60">60 Days</option>
                    <option value="90">90 Days</option>
                    <option value="custom">Custom</option>
                </select>
            </div>

            <div x-show="creditDaysOption === 'custom'" x-cloak>
                <label class="block text-sm font-medium text-slate-700 mb-1">Custom Credit Days</label>
                <input 
                    type="number" 
                    min="0" 
                    step="1" 
                    placeholder="e.g. 73"
                    x-model="customCreditDays" 
                    x-on:input="onCustomCreditDaysInput()" 
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
            </div>

            <div :class="creditDaysOption === 'custom' ? 'col-span-1' : ''">
                <label class="block text-sm font-medium text-slate-700 mb-1">Due Date</label>
                <input 
                    type="date" 
                    name="due_date" 
                    x-model="dueDate" 
                    x-on:input="isDueDateManual = true"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
            </div>

            <div :class="creditDaysOption === 'custom' ? 'col-span-1 md:col-span-4' : ''">
                <label class="block text-sm font-medium text-slate-700 mb-1">Warehouse</label>
                <select name="warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    <option value="">Default</option>
                    @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                </select>
            </div>

            <input type="hidden" name="credit_days" :value="creditDaysValue">
        </div>

        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700">Line items</h3>
            <div class="flex gap-2">
                <button type="button" x-on:click="$dispatch('open-quick-add-product')"
                        class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                    + Quick add product
                </button>
                <button type="button" x-on:click="addLine()"
                        class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                    + Add line
                </button>
            </div>
        </div>

        <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-left">Unit</th>
                        <th class="px-3 py-2 text-left">Qty</th>
                        <th class="px-3 py-2 text-left">Unit Cost (₹)</th>
                        <th class="px-3 py-2 text-left">CGST %</th>
                        <th class="px-3 py-2 text-left">SGST %</th>
                        <th class="px-3 py-2 text-left">Total Tax %</th>
                        <th class="px-3 py-2 text-left">Serial No.</th>
                        <th class="px-3 py-2 text-left">Batch</th>
                        <th class="px-3 py-2 text-left">Batch Selling ₹</th>
                        <th class="px-3 py-2 text-left">Batch MRP ₹</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, idx) in lines" :key="row._key">
                        <tr class="border-t align-top">
                            <td class="px-3 py-2">
                                <select :name="`items[${idx}][product_id]`" x-model="row.product_id" x-on:change="onProductChange(idx)" required class="block w-52 rounded-lg border-gray-300 text-sm">
                                    <option value="">Select</option>
                                    <template x-for="p in products" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <select :name="`items[${idx}][uom_id]`" x-model="row.uom_id" required class="block w-20 rounded-lg border-gray-300 text-sm">
                                    <template x-for="u in uoms" :key="u.id"><option :value="u.id" x-text="u.code"></option></template>
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="1" min="1" :name="`items[${idx}][quantity]`" x-model.number="row.quantity" x-on:input="onQtyChange(idx)" required class="block w-16 rounded-lg border-gray-300 text-sm">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.0001" :name="`items[${idx}][unit_cost]`" x-model.number="row.unit_cost" required class="block w-24 rounded-lg border-gray-300 text-sm">
                                <template x-if="freightAmount > 0">
                                    <div class="mt-1 text-[11px] text-indigo-600 font-semibold whitespace-nowrap">
                                        Landed: ₹<span x-text="getLandedUnitCost(idx).toFixed(2)"></span>
                                    </div>
                                </template>
                            </td>

                            <td class="px-3 py-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    :name="`items[${idx}][cgst_percent]`"
                                    x-model.number="row.cgst_percent"
                                    x-on:input="syncCgst(idx)"
                                    class="block w-16 rounded-lg border-gray-300 text-sm"
                                >
                            </td>

                            <td class="px-3 py-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    :name="`items[${idx}][sgst_percent]`"
                                    x-model.number="row.sgst_percent"
                                    x-on:input="syncSgst(idx)"
                                    class="block w-16 rounded-lg border-gray-300 text-sm"
                                >
                            </td>

                            <td class="px-3 py-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    :name="`items[${idx}][tax_percent]`"
                                    x-model.number="row.tax_percent"
                                    x-on:input="syncTax(idx)"
                                    class="block w-16 rounded-lg border-gray-300 text-sm"
                                >
                            </td>

                            <!-- Serial Number Column -->
                            <td class="px-3 py-2">
                                <template x-if="row.is_serial_tracked">
                                    <div>
                                        <button type="button"
                                                x-on:click="openSerialModal(idx)"
                                                :class="isSerialComplete(row) ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-300 hover:bg-amber-100'"
                                                class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-medium transition shadow-sm whitespace-nowrap">
                                            <span x-text="isSerialComplete(row) ? `✓ ${getValidSerialsCount(row)} Serials` : `+ Add (${getValidSerialsCount(row)}/${getRequiredSerials(row)})`"></span>
                                        </button>
                                        <!-- Hidden Inputs to submit serials -->
                                        <template x-for="(sn, snIdx) in row.serials" :key="snIdx">
                                            <input type="hidden" :name="`items[${idx}][serials][${snIdx}]`" :value="sn">
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!row.is_serial_tracked">
                                    <span class="text-xs text-slate-400 italic">Not required</span>
                                </template>
                            </td>

                            <td class="px-3 py-2"><input type="text" :name="`items[${idx}][batch_no]`" x-model="row.batch_no" class="block w-28 rounded-lg border-gray-300 text-sm" placeholder="Optional"></td>
                            <td class="px-3 py-2"><input type="number" step="0.01" :name="`items[${idx}][batch_selling_price]`" x-model="row.batch_selling_price" class="block w-28 rounded-lg border-gray-300 text-sm" placeholder="Selling ₹"></td>
                            <td class="px-3 py-2"><input type="number" step="0.01" :name="`items[${idx}][batch_mrp]`" x-model="row.batch_mrp" class="block w-28 rounded-lg border-gray-300 text-sm" placeholder="MRP ₹"></td>
                            <td class="px-3 py-2 text-right"><button type="button" x-show="lines.length > 1" x-on:click="removeLine(idx)" class="text-xs text-red-600 hover:text-red-800">Remove</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Freight & Cost Allocation Section -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Freight / Additional Landed Cost (₹)</label>
                <input 
                    type="number" 
                    step="0.01" 
                    min="0"
                    name="freight_amount" 
                    x-model.number="freightAmount" 
                    placeholder="0.00"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                <p class="text-[11px] text-slate-500 mt-1">Allocated to inventory landed cost (does not modify supplier payable rate).</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Freight Allocation Basis</label>
                <select 
                    name="freight_allocation_method" 
                    x-model="freightAllocationMethod"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="value">By Purchase Value (Default)</option>
                    <option value="qty">By Quantity</option>
                    <option value="equal">Equal per Line Item</option>
                </select>
                <p class="text-[11px] text-slate-500 mt-1">Formula used to distribute freight across items.</p>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-3 text-right">
                <div class="text-xs text-slate-500">Base Subtotal: ₹<span class="font-medium text-slate-800" x-text="getSubtotal().toFixed(2)"></span></div>
                <div class="text-xs text-slate-500 mt-0.5">Total Taxes: ₹<span class="font-medium text-slate-800" x-text="getTotalTax().toFixed(2)"></span></div>
                <div class="text-sm font-bold text-indigo-700 mt-1">Grand Total: ₹<span x-text="getGrandTotal().toFixed(2)"></span></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-ui.input name="rate_override_reason" label="Rate override reason (only if any unit cost > previous vendor rate)" :value="old('rate_override_reason')" />
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Terms &amp; Conditions</label>
                <textarea
                    name="terms_and_conditions"
                    rows="5"
                    class="block w-full rounded-lg border-gray-300 text-sm"
                >{{ old('terms_and_conditions', $purchaseTermsAndConditions ?? '') }}</textarea>
            </div>
        </div>

        <div class="flex gap-3"><x-ui.button type="submit" variant="primary">Post Invoice</x-ui.button></div>

        <!-- Serial Numbers Entry Modal -->
        <div x-show="showSerialModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showSerialModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" x-on:click="closeSerialModal()"></div>

                <div x-show="showSerialModal" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                    <div class="bg-indigo-600 px-6 py-4 text-white flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold" x-text="activeRow ? `Serial Numbers: ${activeRow.product_name}` : 'Serial Numbers'"></h3>
                            <p class="text-xs text-indigo-100 mt-0.5" x-text="activeRow ? `Required: ${getRequiredSerials(activeRow)} Serial Numbers (Qty: ${activeRow.quantity})` : ''"></p>
                        </div>
                        <button type="button" x-on:click="closeSerialModal()" class="text-indigo-200 hover:text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 max-h-[65vh] overflow-y-auto">
                        <!-- Quick Paste helper -->
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Quick Paste (Paste multiple serials separated by line or comma)</label>
                            <div class="flex gap-2">
                                <input type="text" x-model="bulkSerialInput" placeholder="e.g. SN001, SN002, SN003" class="block flex-1 rounded-lg border-gray-300 text-xs shadow-sm">
                                <button type="button" x-on:click="applyBulkSerials()" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 border border-indigo-200">
                                    Fill
                                </button>
                            </div>
                        </div>

                        <!-- Numbered Serial Inputs -->
                        <div class="space-y-2">
                            <template x-for="(val, snIdx) in modalSerials" :key="snIdx">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 text-xs font-bold text-slate-400 text-right" x-text="`${snIdx + 1}.`"></span>
                                    <input type="text"
                                           x-model="modalSerials[snIdx]"
                                           :placeholder="`Serial Number #${snIdx + 1}`"
                                           class="block flex-1 rounded-lg border-gray-300 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                                    <button type="button" x-show="modalSerials[snIdx]" x-on:click="modalSerials[snIdx] = ''" class="text-xs text-slate-400 hover:text-red-600">✕</button>
                                </div>
                            </template>
                        </div>

                        <div x-show="serialModalError" class="text-xs font-semibold text-red-600 bg-red-50 border border-red-200 rounded-lg p-2.5" x-text="serialModalError"></div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3.5 flex items-center justify-between border-t border-slate-100">
                        <div class="text-xs text-slate-500 font-medium">
                            Entered: <span class="font-bold text-slate-800" x-text="modalSerials.filter(s => s && s.trim()).length"></span> / <span x-text="activeRow ? getRequiredSerials(activeRow) : 0"></span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" x-on:click="closeSerialModal()" class="rounded-lg border border-gray-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                            <button type="button" x-on:click="saveModalSerials()" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm">Done</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-ui.card>

<x-quick-add-product />

@push('scripts')
<script>
function pInvoiceForm(products, uoms, vendorRates, suppliers) {
    return {
        products,
        uoms,
        vendorRates,
        suppliers,
        selectedSupplierId: '',
        invoiceDate: '{{ old('invoice_date', now()->toDateString()) }}',
        dueDate: '{{ old('due_date', now()->toDateString()) }}',
        creditDaysOption: '0',
        customCreditDays: '',
        creditDaysValue: 0,
        isDueDateManual: false,
        freightAmount: 0,
        freightAllocationMethod: 'value',
        _seq: 0,
        lines: [],
        loadingOrder: false,

        // Modal state
        showSerialModal: false,
        activeModalIdx: null,
        activeRow: null,
        modalSerials: [],
        bulkSerialInput: '',
        serialModalError: '',

        init() {
            this.addLine();
            this.calculateDueDate();
        },

        addLine() {
            this.lines.push({
                _key: ++this._seq,
                product_id: '',
                product_name: '',
                is_serial_tracked: false,
                is_batch_tracked: false,
                uom_id: '',
                quantity: 1,
                unit_cost: 0,
                tax_percent: 0,
                cgst_percent: 0,
                sgst_percent: 0,
                cgst_amount: 0,
                sgst_amount: 0,
                batch_no: '',
                batch_selling_price: '',
                batch_mrp: '',
                serials: [],
            });
        },

        removeLine(idx) {
            this.lines.splice(idx, 1);
        },

        getSubtotal() {
            return this.lines.reduce((sum, r) => sum + ((Number(r.quantity) || 0) * (Number(r.unit_cost) || 0)), 0);
        },

        getTotalTax() {
            return this.lines.reduce((sum, r) => {
                const line = (Number(r.quantity) || 0) * (Number(r.unit_cost) || 0);
                const tax = Number(r.tax_percent) || 0;
                return sum + (line * (tax / 100));
            }, 0);
        },

        getGrandTotal() {
            return this.getSubtotal() + this.getTotalTax();
        },

        getLandedUnitCost(idx) {
            const row = this.lines[idx];
            if (!row) return 0;
            const unitCost = Number(row.unit_cost) || 0;
            const qty = Number(row.quantity) || 1;
            const freight = Number(this.freightAmount) || 0;
            if (freight <= 0 || qty <= 0) return unitCost;

            if (this.freightAllocationMethod === 'qty') {
                const totalQty = this.lines.reduce((s, r) => s + (Number(r.quantity) || 0), 0);
                const share = totalQty > 0 ? (freight * (qty / totalQty)) : 0;
                return unitCost + (share / qty);
            } else if (this.freightAllocationMethod === 'equal') {
                const count = this.lines.length || 1;
                const share = freight / count;
                return unitCost + (share / qty);
            } else {
                // By value
                const totalValue = this.getSubtotal();
                const lineValue = qty * unitCost;
                const share = totalValue > 0 ? (freight * (lineValue / totalValue)) : (freight / (this.lines.length || 1));
                return unitCost + (share / qty);
            }
        },

        getRequiredSerials(row) {
            return Math.max(1, Math.floor(Number(row.quantity) || 1));
        },

        getValidSerialsCount(row) {
            return (row.serials || []).filter(s => s && String(s).trim() !== '').length;
        },

        isSerialComplete(row) {
            return this.getValidSerialsCount(row) === this.getRequiredSerials(row);
        },

        onQtyChange(idx) {
            const row = this.lines[idx];
            if (!row || !row.is_serial_tracked) return;

            const required = this.getRequiredSerials(row);
            const current = row.serials || [];

            if (current.length < required) {
                while (row.serials.length < required) {
                    row.serials.push('');
                }
            } else if (current.length > required) {
                row.serials = row.serials.slice(0, required);
            }
        },

        openSerialModal(idx) {
            this.activeModalIdx = idx;
            this.activeRow = this.lines[idx];
            this.serialModalError = '';
            this.bulkSerialInput = '';

            const required = this.getRequiredSerials(this.activeRow);
            const current = [...(this.activeRow.serials || [])];

            while (current.length < required) {
                current.push('');
            }
            this.modalSerials = current.slice(0, required);
            this.showSerialModal = true;
        },

        closeSerialModal() {
            this.showSerialModal = false;
            this.activeModalIdx = null;
            this.activeRow = null;
            this.modalSerials = [];
            this.serialModalError = '';
            this.bulkSerialInput = '';
        },

        applyBulkSerials() {
            if (!this.bulkSerialInput || !this.bulkSerialInput.trim()) return;

            const parsed = this.bulkSerialInput
                .split(/[\r\n,;]+/)
                .map(s => s.trim())
                .filter(s => s !== '');

            const required = this.getRequiredSerials(this.activeRow);

            for (let i = 0; i < required && i < parsed.length; i++) {
                this.modalSerials[i] = parsed[i];
            }
            this.bulkSerialInput = '';
        },

        saveModalSerials() {
            const required = this.getRequiredSerials(this.activeRow);
            const cleaned = this.modalSerials.map(s => (s ? String(s).trim() : ''));

            // Check duplicate serials within this row
            const nonEmpties = cleaned.filter(s => s !== '');
            const duplicates = nonEmpties.filter((item, index) => nonEmpties.indexOf(item) !== index);
            if (duplicates.length > 0) {
                this.serialModalError = `Duplicate serial numbers found: ${[...new Set(duplicates)].join(', ')}`;
                return;
            }

            this.lines[this.activeModalIdx].serials = cleaned;
            this.closeSerialModal();
        },

        validateBeforeSubmit(e) {
            for (let i = 0; i < this.lines.length; i++) {
                const row = this.lines[i];
                if (row.is_serial_tracked && row.product_id) {
                    const required = this.getRequiredSerials(row);
                    const validCount = this.getValidSerialsCount(row);
                    if (validCount !== required) {
                        e.preventDefault();
                        alert(`Product "${row.product_name}" is serial-tracked and requires ${required} serial numbers (${validCount} entered). Please click on "+ Add" to complete serial entry.`);
                        this.openSerialModal(i);
                        return false;
                    }
                }
            }
            return true;
        },

        onSupplierChange() {
            const supplier = this.suppliers.find(s => String(s.id) === String(this.selectedSupplierId));
            if (!supplier) return;

            const defaultDays = supplier.credit_days !== null && supplier.credit_days !== undefined
                ? Number(supplier.credit_days)
                : 0;

            const standardOptions = ['0', '7', '15', '30', '45', '50', '60', '90'];
            if (standardOptions.includes(String(defaultDays))) {
                this.creditDaysOption = String(defaultDays);
                this.customCreditDays = '';
            } else {
                this.creditDaysOption = 'custom';
                this.customCreditDays = String(defaultDays);
            }

            this.isDueDateManual = false;
            this.calculateDueDate();
        },

        onInvoiceDateChange() {
            this.calculateDueDate();
        },

        onCreditDaysOptionChange() {
            this.isDueDateManual = false;
            if (this.creditDaysOption !== 'custom') {
                this.customCreditDays = '';
            }
            this.calculateDueDate();
        },

        onCustomCreditDaysInput() {
            this.isDueDateManual = false;
            this.calculateDueDate();
        },

        calculateDueDate() {
            let days = 0;
            if (this.creditDaysOption === 'custom') {
                const parsed = parseInt(this.customCreditDays, 10);
                days = (!isNaN(parsed) && parsed >= 0) ? parsed : 0;
            } else {
                days = parseInt(this.creditDaysOption, 10) || 0;
            }

            this.creditDaysValue = days;

            if (this.isDueDateManual) {
                return;
            }

            if (!this.invoiceDate) {
                return;
            }

            const parts = this.invoiceDate.split('-');
            if (parts.length === 3) {
                const year = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1;
                const day = parseInt(parts[2], 10);

                const d = new Date(year, month, day);
                d.setDate(d.getDate() + days);

                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');

                this.dueDate = `${yyyy}-${mm}-${dd}`;
            }
        },

        onProductChange(idx) {
            const row = this.lines[idx];
            const p = this.products.find(x => String(x.id) === String(row.product_id));
            if (!p) return;

            row.product_name = p.name;
            row.is_serial_tracked = Boolean(p.is_serial_tracked);
            row.is_batch_tracked = Boolean(p.is_batch_tracked);
            row.uom_id = p.base_uom_id;
            row.batch_selling_price = p.selling_price ? Number(p.selling_price) : '';
            row.batch_mrp = p.mrp ? Number(p.mrp) : '';

            if (row.is_serial_tracked) {
                const required = this.getRequiredSerials(row);
                row.serials = Array(required).fill('');
            } else {
                row.serials = [];
            }

            const supplierId = this.selectedSupplierId;
            const key = `${supplierId}:${p.id}:${p.base_uom_id}`;
            const rates = this.vendorRates[key] ?? [];

            if (rates.length > 0) {
                const invoiceDate = this.invoiceDate || '';
                const applicableRates = rates.filter(rate => {
                    if (!invoiceDate) return true;
                    return !rate.effective_from || String(rate.effective_from) <= String(invoiceDate);
                });

                if (applicableRates.length > 0) {
                    const latestRate = [...applicableRates]
                        .sort((a, b) => String(b.effective_from ?? '').localeCompare(String(a.effective_from ?? '')))[0];
                    row.unit_cost = Number(latestRate.unit_cost ?? 0);
                } else {
                    row.unit_cost = Number(p.purchase_price ?? 0);
                }
            } else {
                row.unit_cost = Number(p.purchase_price ?? 0);
            }

            row.tax_percent = Number(p.tax_rate ?? 0);
            row.cgst_percent = row.tax_percent / 2;
            row.sgst_percent = row.tax_percent / 2;
        },

        syncTax(idx) {
            const row = this.lines[idx];
            const totalTax = Number(row.tax_percent || 0);
            row.cgst_percent = totalTax / 2;
            row.sgst_percent = totalTax / 2;
        },

        syncCgst(idx) {
            const row = this.lines[idx];
            row.tax_percent = Number(row.cgst_percent || 0) + Number(row.sgst_percent || 0);
        },

        syncSgst(idx) {
            const row = this.lines[idx];
            row.tax_percent = Number(row.cgst_percent || 0) + Number(row.sgst_percent || 0);
        },

        async loadPurchaseOrder(orderId) {
            if (!orderId) {
                this.lines = [];
                this.addLine();
                return;
            }

            this.loadingOrder = true;

            try {
                const response = await fetch(
                    `{{ url('/purchasing/invoices/purchase-order') }}/${orderId}/data`
                );

                if (!response.ok) {
                    throw new Error('Unable to load purchase order.');
                }

                const order = await response.json();

                this.selectedSupplierId = String(order.supplier_id ?? '');
                this.onSupplierChange();

                const warehouseSelect = document.querySelector('[name="warehouse_id"]');
                if (warehouseSelect && order.warehouse_id) {
                    warehouseSelect.value = order.warehouse_id;
                }

                this.lines = order.items.map(item => {
                    const p = this.products.find(x => String(x.id) === String(item.product_id));
                    const isSerial = p ? Boolean(p.is_serial_tracked) : false;
                    const isBatch = p ? Boolean(p.is_batch_tracked) : false;
                    const qty = Number(item.quantity ?? 1);
                    const requiredSerials = Math.max(1, Math.floor(qty));

                    return {
                        _key: ++this._seq,
                        product_id: item.product_id ?? '',
                        product_name: p ? p.name : '',
                        is_serial_tracked: isSerial,
                        is_batch_tracked: isBatch,
                        uom_id: item.uom_id ?? '',
                        quantity: qty,
                        unit_cost: Number(item.unit_cost ?? 0),
                        tax_percent: Number(item.tax_percent ?? 0),
                        cgst_percent: Number(item.cgst_percent ?? 0),
                        sgst_percent: Number(item.sgst_percent ?? 0),
                        cgst_amount: Number(item.cgst_amount ?? 0),
                        sgst_amount: Number(item.sgst_amount ?? 0),
                        batch_no: item.batch_name ?? '',
                        batch_selling_price: p?.selling_price ? Number(p.selling_price) : '',
                        batch_mrp: p?.mrp ? Number(p.mrp) : '',
                        serials: isSerial ? Array(requiredSerials).fill('') : [],
                    };
                });

                if (this.lines.length === 0) {
                    this.addLine();
                }

            } catch (error) {
                console.error(error);
                alert('Unable to load the selected Purchase Order.');
            } finally {
                this.loadingOrder = false;
            }
        },

        onProductAdded(p) {
            this.products.push({
                id: p.id,
                name: p.name,
                sku: p.sku,
                base_uom_id: p.base_uom_id,
                tax_rate: p.tax_rate,
                purchase_price: p.purchase_price,
                selling_price: p.selling_price,
                mrp: p.calculation_mrp,
                color_variant: p.color_variant,
                is_serial_tracked: p.tracking_type && (p.tracking_type.includes('serial') || p.tracking_type === 'serial'),
                is_batch_tracked: p.tracking_type && (p.tracking_type.includes('batch') || p.tracking_type === 'batch'),
            });
            const empty = this.lines.find(r => ! r.product_id) || this.lines[this.lines.length - 1];
            if (empty) {
                empty.product_id = p.id;
                this.onProductChange(this.lines.indexOf(empty));
            }
        },
    };
}
</script>
@endpush
@endsection