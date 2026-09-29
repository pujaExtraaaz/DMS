@extends('layouts.dms')
@section('title', 'New Purchase Order')
@section('content')
<x-ui.page-header title="New Purchase Order">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('purchasing.orders.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>

<x-ui.card>
<form method="POST" action="{{ route('purchasing.orders.store') }}" class="space-y-4">
@csrf

<div class="grid grid-cols-1 md:grid-cols-3 gap-4" x-data="{
    suppliers: {{ Js::from($suppliers->map(fn($s) => ['id' => (string) $s->id, 'name' => $s->name, 'gstin' => $s->gstin ?? ''])) }},
    selectedSupplierId: '{{ (string) old('supplier_id') }}',
    showSupplierModal: false,
    savingSupplier: false,
    fetchingGst: false,
    supplierError: null,
    supplierSuccess: null,
    gstMessage: '',
    gstStatusType: '',
    newSupplier: {
        name: '',
        party_type: 'sundry_creditor',
        gstin: '',
        phone: '',
        email: '',
        state: '',
        address: '',
        pincode: ''
    },
    openSupplierModal() {
        this.newSupplier = {
            name: '',
            party_type: 'sundry_creditor',
            gstin: '',
            phone: '',
            email: '',
            state: '',
            address: '',
            pincode: ''
        };
        this.supplierError = null;
        this.supplierSuccess = null;
        this.gstMessage = '';
        this.gstStatusType = '';
        this.showSupplierModal = true;
        this.$nextTick(() => {
            this.$refs.supplierNameInput?.focus();
        });
    },
    closeSupplierModal() {
        this.showSupplierModal = false;
        this.supplierError = null;
        this.supplierSuccess = null;
        this.gstMessage = '';
        this.gstStatusType = '';
    },
    async fetchGstDetails() {
        const cleanedGstin = (this.newSupplier.gstin || '').trim().toUpperCase();
        this.newSupplier.gstin = cleanedGstin;

        if (!cleanedGstin) {
            this.gstStatusType = 'error';
            this.gstMessage = 'Please enter a GSTIN first.';
            return;
        }

        if (!/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i.test(cleanedGstin)) {
            this.gstStatusType = 'error';
            this.gstMessage = 'Please enter a valid 15-digit GSTIN format.';
            return;
        }

        this.fetchingGst = true;
        this.gstMessage = '';
        this.gstStatusType = '';

        try {
            const response = await fetch('{{ route('masters.customers.gst-lookup') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}',
                },
                body: JSON.stringify({ gstin: cleanedGstin }),
            });

            const res = await response.json();

            if (!response.ok || !res.ok) {
                this.gstStatusType = 'error';
                this.gstMessage = res.message || 'Failed to fetch GST details.';
                return;
            }

            const d = res.data;
            if (d) {
                if (d.name) this.newSupplier.name = d.name;
                if (d.state) this.newSupplier.state = d.state;
                if (d.pincode) this.newSupplier.pincode = d.pincode;
                if (d.address) this.newSupplier.address = d.address;

                const statusLabel = d.status || 'Active';
                this.gstStatusType = statusLabel.toLowerCase() === 'active' ? 'success' : 'warning';
                this.gstMessage = `GST Verified: ${d.trade_name || d.legal_name || d.name} (Status: ${statusLabel})`;
            }
        } catch (err) {
            this.gstStatusType = 'error';
            this.gstMessage = 'Network error while fetching GST details.';
        } finally {
            this.fetchingGst = false;
        }
    },
    async saveSupplier() {
        const trimmedName = (this.newSupplier.name || '').trim();
        if (!trimmedName) {
            this.supplierError = 'Supplier name is required.';
            return;
        }

        this.savingSupplier = true;
        this.supplierError = null;
        this.supplierSuccess = null;

        try {
            const response = await fetch('{{ route('masters.parties.quick-add') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    name: trimmedName,
                    party_type: this.newSupplier.party_type || 'sundry_creditor',
                    gstin: (this.newSupplier.gstin || '').trim().toUpperCase(),
                    phone: this.newSupplier.phone || '',
                    email: this.newSupplier.email || '',
                    state: this.newSupplier.state || '',
                    address: this.newSupplier.address || '',
                    pincode: this.newSupplier.pincode || '',
                }),
            });

            const data = await response.json();

            if (!response.ok || !data.ok) {
                if (data.errors) {
                    const firstErr = Object.values(data.errors).flat()[0];
                    this.supplierError = firstErr || 'Validation failed.';
                } else if (data.message) {
                    this.supplierError = data.message;
                } else {
                    this.supplierError = 'Failed to create supplier.';
                }
                this.savingSupplier = false;
                return;
            }

            if (data.supplier) {
                const created = {
                    id: String(data.supplier.id),
                    name: data.supplier.name,
                    gstin: data.supplier.gstin || ''
                };
                this.suppliers.push(created);
                this.suppliers.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
                this.selectedSupplierId = created.id;
                this.supplierSuccess = data.message || 'Supplier created successfully.';
                setTimeout(() => {
                    this.closeSupplierModal();
                }, 500);
            }
        } catch (err) {
            this.supplierError = 'Network error while creating supplier.';
        } finally {
            this.savingSupplier = false;
        }
    }
}">
    <!-- Supplier Selection Field with Quick Add Trigger -->
    <div>
        <div class="flex items-center justify-between mb-1">
            <label for="supplier_id" class="block text-sm font-medium text-slate-700">
                Supplier <span class="text-red-500">*</span>
            </label>
            <button
                type="button"
                @click="openSupplierModal()"
                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800"
            >
                + Add Supplier
            </button>
        </div>
        <select
            id="supplier_id"
            name="supplier_id"
            x-model="selectedSupplierId"
            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
        >
            <option value="">Select supplier</option>
            <template x-for="s in suppliers" :key="s.id">
                <option :value="s.id" x-text="s.name" :selected="String(s.id) === String(selectedSupplierId)"></option>
            </template>
        </select>
        @error('supplier_id')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Quick Add Supplier Modal -->
    <template x-teleport="body">
        <div x-show="showSupplierModal" x-cloak style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-lg rounded-xl bg-white shadow-2xl overflow-hidden" @click.outside="closeSupplierModal()" @keydown.escape.window="if (showSupplierModal) closeSupplierModal()">
                <div class="flex items-center justify-between px-5 py-3 border-b bg-slate-50">
                    <h3 class="text-sm font-semibold text-slate-800">Add New Supplier</h3>
                    <button type="button" @click="closeSupplierModal()" class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
                </div>

                <div class="p-5 space-y-3.5 max-h-[80vh] overflow-y-auto">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Supplier / Party Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            x-ref="supplierNameInput"
                            x-model="newSupplier.name"
                            placeholder="e.g. DONGAS TRADERS"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Party Type</label>
                            <select x-model="newSupplier.party_type" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="sundry_creditor">Sundry Creditor</option>
                                <option value="both">Both (Sundry Creditor & Debtor)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">GSTIN</label>
                            <div class="flex gap-1.5">
                                <input
                                    type="text"
                                    x-model="newSupplier.gstin"
                                    @input="newSupplier.gstin = (newSupplier.gstin || '').toUpperCase()"
                                    maxlength="15"
                                    placeholder="27AAPFU0939F1ZV"
                                    class="block w-full rounded-lg border-gray-300 text-xs uppercase tracking-wider font-mono focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                <button
                                    type="button"
                                    @click="fetchGstDetails()"
                                    :disabled="fetchingGst || !newSupplier.gstin.trim()"
                                    class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 disabled:opacity-50 whitespace-nowrap"
                                >
                                    <span x-text="fetchingGst ? '...' : 'Fetch'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <template x-if="gstMessage">
                        <div
                            class="flex items-center gap-1.5 text-xs"
                            :class="{
                                'text-emerald-700': gstStatusType === 'success',
                                'text-amber-700': gstStatusType === 'warning',
                                'text-red-600': gstStatusType === 'error'
                            }"
                        >
                            <span
                                class="inline-block w-2 h-2 rounded-full shrink-0"
                                :class="{
                                    'bg-emerald-500': gstStatusType === 'success',
                                    'bg-amber-500': gstStatusType === 'warning',
                                    'bg-red-500': gstStatusType === 'error'
                                }"
                            ></span>
                            <span x-text="gstMessage"></span>
                        </div>
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Phone</label>
                            <input
                                type="text"
                                x-model="newSupplier.phone"
                                placeholder="e.g. 9876543210"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                            <input
                                type="email"
                                x-model="newSupplier.email"
                                placeholder="e.g. supplier@domain.com"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">State</label>
                        <select x-model="newSupplier.state" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select State</option>
                            @foreach($states as $st)
                                <option value="{{ $st }}">{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Address</label>
                        <textarea
                            x-model="newSupplier.address"
                            rows="2"
                            placeholder="Street, City, Pincode"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <template x-if="supplierError">
                        <div class="rounded-lg bg-red-50 border border-red-200 p-2.5 text-xs text-red-600" x-text="supplierError"></div>
                    </template>

                    <template x-if="supplierSuccess">
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-2.5 text-xs text-emerald-700" x-text="supplierSuccess"></div>
                    </template>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="closeSupplierModal()" class="rounded-lg border border-slate-200 px-3.5 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
                        <button
                            type="button"
                            @click="saveSupplier()"
                            :disabled="savingSupplier || !newSupplier.name.trim()"
                            class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                        >
                            <span x-show="!savingSupplier">Save Supplier</span>
                            <span x-show="savingSupplier">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Warehouse</label>
        <select name="warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm">
            <option value="">Optional</option>
            @foreach($warehouses as $w)<option value="{{ $w->id }}" @selected(old('warehouse_id')==$w->id)>{{ $w->name }}</option>@endforeach
        </select>
    </div>
    <x-ui.input name="po_date" label="PO Date" type="date" :value="old('po_date', now()->toDateString())" required />
    <x-ui.input name="expected_date" label="Expected Date" type="date" :value="old('expected_date')" />
</div>

<div class="overflow-x-auto border rounded-lg">
<table class="min-w-full text-sm">
<thead class="bg-gray-50"><tr>
<th class="px-3 py-2 text-left">Product</th>
<th class="px-3 py-2 text-left">UOM</th>
<th class="px-3 py-2 text-left">Batch Name</th>
<th class="px-3 py-2 text-left">Qty</th>
<th class="px-3 py-2 text-left">Unit Cost</th>
<th class="px-3 py-2 text-left">Tax %</th>
<th class="px-3 py-2 text-left">CGST %</th>
<th class="px-3 py-2 text-left">SGST %</th>
<th></th>
</tr></thead>
<tbody id="po-lines">
@php
    $defaultProduct = $products->first();
    $defaultTaxRate = $defaultProduct ? (float) ($defaultProduct->tax_rate ?? 0) : 0;
    $defaultCgst = round($defaultTaxRate / 2, 2);
    $defaultSgst = round($defaultTaxRate / 2, 2);

    $oldItems = old('items', [
        [
            'product_id' => $defaultProduct?->id ?? '',
            'uom_id' => $uoms->first()?->id ?? '',
            'batch_name' => '',
            'quantity' => 1,
            'unit_cost' => $defaultProduct ? (float) ($defaultProduct->purchase_price ?? 0) : '',
            'tax_percent' => $defaultTaxRate,
            'cgst_percent' => $defaultCgst,
            'sgst_percent' => $defaultSgst,
        ]
    ]);
@endphp

@foreach($oldItems as $idx => $it)
<tr>
<td class="px-3 py-2">
    <select name="items[{{ $idx }}][product_id]" class="po-product block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
        @foreach($products as $p)
            <option
                value="{{ $p->id }}"
                data-tax="{{ (float) ($p->tax_rate ?? 0) }}"
                data-cost="{{ (float) ($p->purchase_price ?? 0) }}"
                @selected(($it['product_id'] ?? '') == $p->id)
            >
                {{ $p->name }}
            </option>
        @endforeach
    </select>
</td>
<td class="px-3 py-2">
    <select name="items[{{ $idx }}][uom_id]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
        @foreach($uoms as $u)
            <option value="{{ $u->id }}" @selected(($it['uom_id'] ?? '') == $u->id)>{{ $u->code }}</option>
        @endforeach
    </select>
</td>
<td class="px-3 py-2">
    <input
        type="text"
        name="items[{{ $idx }}][batch_name]"
        value="{{ $it['batch_name'] ?? '' }}"
        placeholder="e.g. IP18-SEP-2026"
        class="po-batch-name block w-full min-w-[130px] rounded-lg border-gray-300 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
    >
    @error("items.{$idx}.batch_name")
        <p class="text-[11px] text-red-600 mt-0.5">{{ $message }}</p>
    @enderror
</td>
<td class="px-3 py-2">
    <input type="number" step="0.0001" min="0.0001" name="items[{{ $idx }}][quantity]" class="block w-full min-w-[75px] rounded-lg border-gray-300 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500" value="{{ $it['quantity'] ?? 1 }}" required>
</td>
<td class="px-3 py-2">
    <input type="number" step="0.0001" min="0" name="items[{{ $idx }}][unit_cost]" class="po-unit-cost block w-full min-w-[85px] rounded-lg border-gray-300 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500" value="{{ $it['unit_cost'] ?? '' }}" required>
</td>
<td class="px-3 py-2">
    <input
        type="number"
        step="0.01"
        min="0"
        max="100"
        name="items[{{ $idx }}][tax_percent]"
        class="po-tax block w-full min-w-[70px] rounded-lg border-gray-300 bg-slate-50 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
        value="{{ $it['tax_percent'] ?? 0 }}"
    >
</td>

<td class="px-3 py-2">
    <input
        type="number"
        step="0.01"
        min="0"
        max="100"
        name="items[{{ $idx }}][cgst_percent]"
        class="po-cgst block w-full min-w-[70px] rounded-lg border-gray-300 bg-slate-50 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
        value="{{ $it['cgst_percent'] ?? 0 }}"
    >
</td>

<td class="px-3 py-2">
    <input
        type="number"
        step="0.01"
        min="0"
        max="100"
        name="items[{{ $idx }}][sgst_percent]"
        class="po-sgst block w-full min-w-[70px] rounded-lg border-gray-300 bg-slate-50 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
        value="{{ $it['sgst_percent'] ?? 0 }}"
    >
</td>
<td class="px-2 py-2">
    <button type="button" onclick="removePoRow(this)" class="text-xs text-red-500 hover:text-red-700 font-semibold leading-none">&times;</button>
</td>
</tr>
@endforeach
</tbody></table></div>

<div class="flex flex-wrap gap-3 items-center">
<x-ui.button type="button" variant="secondary" onclick="addPoRow()">Add Line</x-ui.button>
<label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="submit_for_approval" value="1" class="rounded border-gray-300"> Submit for approval</label>
<x-ui.button type="submit" variant="primary">Save PO</x-ui.button>
</div>
</form></x-ui.card>

@push('scripts')
<script>
let poi = {{ count($oldItems) }};

function syncPoTax(row, changed) {
    const tax = row.querySelector('.po-tax');
    const cgst = row.querySelector('.po-cgst');
    const sgst = row.querySelector('.po-sgst');

    if (!tax || !cgst || !sgst) {
        return;
    }

    const taxValue = parseFloat(tax.value) || 0;

    if (changed === 'tax') {
        const half = (taxValue / 2).toFixed(2);
        cgst.value = half;
        sgst.value = half;
        return;
    }

    const cgstValue = parseFloat(cgst.value) || 0;

    if (changed === 'cgst') {
        sgst.value = Math.max(0, taxValue - cgstValue).toFixed(2);
        return;
    }

    if (changed === 'sgst') {
        cgst.value = Math.max(0, taxValue - (parseFloat(sgst.value) || 0)).toFixed(2);
    }
}

/**
 * Automatically fetch the configured Tax Rate from the selected Product's option data
 * and update Tax %, CGST %, and SGST % for this row.
 */
function updateRowProductTax(productSelect) {
    const row = productSelect.closest('tr');
    if (!row) return;

    const selectedOption = productSelect.options[productSelect.selectedIndex];
    if (!selectedOption) return;

    const taxRate = selectedOption.dataset.tax !== undefined ? (parseFloat(selectedOption.dataset.tax) || 0) : 0;
    const taxInput = row.querySelector('.po-tax');
    const cgstInput = row.querySelector('.po-cgst');
    const sgstInput = row.querySelector('.po-sgst');

    if (taxInput) {
        taxInput.value = taxRate;
    }
    if (cgstInput && sgstInput) {
        const half = (taxRate / 2).toFixed(2);
        cgstInput.value = half;
        sgstInput.value = half;
    }

    // Also auto-fill unit cost if empty
    const unitCostInput = row.querySelector('.po-unit-cost');
    if (unitCostInput && (!unitCostInput.value || parseFloat(unitCostInput.value) === 0)) {
        if (selectedOption.dataset.cost && parseFloat(selectedOption.dataset.cost) > 0) {
            unitCostInput.value = selectedOption.dataset.cost;
        }
    }
}

function addPoRow() {
    const table = document.getElementById('po-lines');

    table.insertAdjacentHTML(
        'beforeend',
        table.rows[0].outerHTML.replace(
            /items\[\d+\]/g,
            'items[' + poi + ']'
        )
    );

    const row = table.rows[table.rows.length - 1];

    row.querySelectorAll('input').forEach(input => {
        if (input.classList.contains('po-batch-name')) {
            input.value = '';
        } else if (input.name.includes('[quantity]')) {
            input.value = '1';
        } else if (input.classList.contains('po-unit-cost')) {
            input.value = '';
        }
    });

    const productSelect = row.querySelector('.po-product');
    if (productSelect) {
        updateRowProductTax(productSelect);
    }

    poi++;
}

function removePoRow(button) {
    const table = document.getElementById('po-lines');
    if (table.rows.length > 1) {
        button.closest('tr')?.remove();
    }
}

document.addEventListener('change', function (event) {
    if (event.target.classList.contains('po-product')) {
        updateRowProductTax(event.target);
    }
});

document.addEventListener('input', function (event) {
    const input = event.target;

    if (
        !input.classList.contains('po-tax') &&
        !input.classList.contains('po-cgst') &&
        !input.classList.contains('po-sgst')
    ) {
        return;
    }

    const row = input.closest('tr');

    if (!row) {
        return;
    }

    if (input.classList.contains('po-tax')) {
        syncPoTax(row, 'tax');
    } else if (input.classList.contains('po-cgst')) {
        syncPoTax(row, 'cgst');
    } else if (input.classList.contains('po-sgst')) {
        syncPoTax(row, 'sgst');
    }
});
</script>
@endpush
@endsection