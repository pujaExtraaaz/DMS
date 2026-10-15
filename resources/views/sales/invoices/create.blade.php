@extends('layouts.dms')
@section('title', 'Direct Billing')
@section('content')
@php
    $productsJson = $products->map(fn($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'brand_id' => $p->brand_id,
        'base_uom_id' => $p->base_uom_id,
        'selling_price' => (float) $p->selling_price,
        'selling_discount_type' => $p->selling_discount_type ?? 'percent',
        'selling_discount_value' => (float) ($p->selling_discount_value ?? 0),
    ])->values()->toArray();
    $uomsJson = $uoms->map(fn($u) => ['id' => $u->id, 'code' => $u->code, 'name' => $u->name])->values()->toArray();
    $schemesJson = ($schemes ?? collect())->map(fn($s) => [
        'id' => $s->id,
        'code' => $s->code,
        'name' => $s->name,
        'scheme_type' => $s->scheme_type,
        'brand_id' => $s->brand_id,
        'brand_name' => $s->brand?->name,
        'product_ids' => $s->products->pluck('product_id')->filter()->map(fn($id) => (int)$id)->values()->toArray(),
        'starts_on' => optional($s->starts_on)->toDateString(),
        'ends_on' => optional($s->ends_on)->toDateString(),
        'basis' => $s->basis,
        'net_credit_notes' => (bool) $s->net_credit_notes,
        'slabs' => $s->slabs->map(fn($sl) => [
            'from_value' => (float) $sl->from_value,
            'to_value' => $sl->to_value !== null ? (float) $sl->to_value : null,
            'benefit_percent' => (float) $sl->benefit_percent,
            'benefit_amount' => (float) $sl->benefit_amount,
        ])->values()->toArray(),
    ])->values()->toArray();
@endphp
<x-ui.page-header title="Direct Billing" description="Book a tax invoice with per-line discount, universal discount, T&amp;C and e-invoice ready fields.">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('invoices.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="POST" action="{{ route('invoices.store') }}" class="space-y-6"
          x-data='invoiceForm(@json($productsJson), @json($uomsJson), @json($schemesJson))' x-init="init()"
          x-on:product-quick-added.window="onProductAdded($event.detail)">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer *</label>
                <select name="customer_id" id="customer_id" x-model="customerId" @change="onCustomerChange($event.target.value)" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select Customer</option>
                    @foreach($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <x-ui.input name="invoice_date" id="invoice_date" label="Invoice Date" type="date" :value="old('invoice_date', now()->toDateString())" x-model="invoiceDate" required />
            <x-ui.input name="reference_no" label="Reference / Order No" :value="old('reference_no')" />
            <x-ui.input name="vehicle_no" label="Vehicle No" :value="old('vehicle_no')" />
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Transport Mode</label>
                <select name="transport_mode" class="block w-full rounded-lg border-gray-300 text-sm">
                    @foreach(['' => '—', '1' => 'Road', '2' => 'Rail', '3' => 'Air', '4' => 'Ship'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('transport_mode')===$val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-ui.input name="delivery_state" label="Delivery State" :value="old('delivery_state')" />
        </div>

        <!-- Party Addresses Selection -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-4" x-show="customerId" x-cloak>
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Billing &amp; Delivery Addresses</span>
                <span class="text-xs text-slate-500" x-text="addressesLoading ? 'Loading addresses...' : `${partyAddresses.length} address(es) available`"></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Billing Address Card -->
                <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider">Billing Address</label>
                        <span class="text-[11px] text-indigo-600 font-medium">Billed to</span>
                    </div>
                    <select name="billing_address_id" x-model="selectedBillingId" @change="onBillingAddressChange()"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Default Address</option>
                        <template x-for="addr in partyAddresses" :key="addr.id">
                            <option :value="addr.id" x-text="`${addr.label} — ${addr.full_address}`"></option>
                        </template>
                    </select>
                    <textarea name="billing_address" x-model="billingSnapshot" rows="3"
                              placeholder="Billing Address Snapshot"
                              class="block w-full rounded-lg border-gray-200 text-xs text-slate-700 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <!-- Delivery Address Card -->
                <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider">Delivery / Consignee Address</label>
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 cursor-pointer font-medium">
                            <input type="checkbox" x-model="sameAsBilling" @change="onSameAsBillingToggle()"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Same as Billing Address</span>
                        </label>
                    </div>
                    <div x-show="!sameAsBilling">
                        <select name="shipping_address_id" x-model="selectedShippingId" @change="onShippingAddressChange()"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Default Delivery Address</option>
                            <template x-for="addr in partyAddresses" :key="addr.id">
                                <option :value="addr.id" x-text="`${addr.label} — ${addr.full_address}`"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="sameAsBilling" class="text-xs text-slate-500 italic py-1">
                        Delivery address matches the selected Billing Address.
                    </div>
                    <textarea name="shipping_address" x-model="shippingSnapshot" rows="3"
                              placeholder="Delivery Address Snapshot"
                              class="block w-full rounded-lg border-gray-200 text-xs text-slate-700 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700">Line items</h3>
            <div class="flex gap-2">
                <button type="button" @click="$dispatch('open-quick-add-product')" class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">+ Quick add product</button>
                <button type="button" @click="addLine()" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">+ Add line</button>
            </div>
        </div>

        <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-left">UOM</th>
                        <th class="px-3 py-2 text-left">Qty</th>
                        <th class="px-3 py-2 text-left">Unit Price</th>
                        <th class="px-3 py-2 text-left">Disc Type</th>
                        <th class="px-3 py-2 text-left">Disc Value</th>
                        <th class="px-3 py-2 text-left">Batch</th>
                        <th class="px-3 py-2 text-right">Line Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, idx) in lines" :key="row._key">
                        <tr class="border-t align-top">
                            <td class="px-3 py-2">
                                <select :name="`items[${idx}][product_id]`" x-model="row.product_id" @change="onProductChange(idx)" required class="block w-52 rounded-lg border-gray-300 text-sm">
                                    <option value="">Select</option>
                                    <template x-for="p in products" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <select :name="`items[${idx}][uom_id]`" x-model="row.uom_id" required class="block w-24 rounded-lg border-gray-300 text-sm">
                                    <template x-for="u in uoms" :key="u.id"><option :value="u.id" x-text="u.code"></option></template>
                                </select>
                            </td>
                            <td class="px-3 py-2"><input type="number" step="0.0001" :name="`items[${idx}][quantity]`" x-model.number="row.quantity" required class="block w-20 rounded-lg border-gray-300 text-sm"></td>
                            <td class="px-3 py-2"><input type="number" step="0.01" :name="`items[${idx}][unit_price]`" x-model.number="row.unit_price" required class="block w-28 rounded-lg border-gray-300 text-sm"></td>
                            <td class="px-3 py-2">
                                <select :name="`items[${idx}][discount_type]`" x-model="row.discount_type" class="block w-20 rounded-lg border-gray-300 text-sm">
                                    <option value="percent">%</option>
                                    <option value="flat">₹</option>
                                </select>
                            </td>
                            <td class="px-3 py-2"><input type="number" step="0.01" :name="`items[${idx}][discount_value]`" x-model.number="row.discount_value" class="block w-20 rounded-lg border-gray-300 text-sm"></td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-1">
                                    <input type="text" :name="`items[${idx}][batch_no]`" x-model="row.batch_no" placeholder="Optional" class="block w-24 rounded-lg border-gray-300 text-sm">
                                    <button type="button" @click="openBatchPicker(idx)" x-show="row.product_id" title="Pick batch" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-100">Pick</button>
                                </div>
                                <template x-if="row.batch_meta">
                                    <p class="mt-1 text-[10px] text-slate-500" x-text="`Avail: ${row.batch_meta.available_qty} @ ₹${row.batch_meta.selling_price ?? row.batch_meta.unit_cost}` + (row.batch_meta.expiry_date ? ` · exp ${row.batch_meta.expiry_date}` : '')"></p>
                                </template>
                            </td>
                            <td class="px-3 py-2 text-right text-sm font-medium" x-text="fmt(lineTotal(row))"></td>
                            <td class="px-3 py-2 text-right"><button type="button" x-show="lines.length > 1" @click="removeLine(idx)" class="text-xs text-red-600 hover:text-red-800">Remove</button></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-slate-50 font-medium">
                    <tr>
                        <td colspan="7" class="px-3 py-2 text-right">Sub-total (post item discount)</td>
                        <td class="px-3 py-2 text-right" x-text="fmt(subTotal())"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Universal discount type</label>
                <select name="universal_discount_type" x-model="universalType" class="block w-full rounded-lg border-gray-300 text-sm">
                    <option value="flat">Flat ₹</option>
                    <option value="percent">Percent %</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Universal discount value</label>
                <input type="number" step="0.01" name="universal_discount_value" x-model.number="universalValue" class="block w-full rounded-lg border-gray-300 text-sm">
                <p class="text-xs text-slate-500 mt-1">Applied on the sub-total in addition to per-line discounts.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estimated grand total</label>
                <div class="text-xl font-semibold text-slate-800" x-text="fmt(grandTotal())"></div>
                <template x-if="selectedSchemeBenefit() > 0">
                    <p class="text-xs text-emerald-600 font-semibold mt-1" x-text="`Scheme Benefit: ${fmt(selectedSchemeBenefit())} (provisional rebate)`"></p>
                </template>
                <p class="text-xs text-slate-500">Server recomputes with tax before saving.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Terms &amp; Conditions</label>
                <textarea
                    name="terms_and_conditions"
                    rows="5"
                    class="block w-full rounded-lg border-gray-300 text-sm"
                >{{ old('terms_and_conditions', $sellingTermsAndConditions ?? '') }}</textarea>
            </div>
        </div>

        <!-- Commercial Scheme Selection -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div>
                    <label class="block text-sm font-bold text-slate-800">Scheme</label>
                    <p class="text-xs text-slate-500">Optionally apply an eligible commercial scheme to accrue rebate benefits for this invoice.</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-200 text-slate-700" x-text="`${eligibleSchemes().length} scheme(s) available`"></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                <div>
                    <label for="scheme_id" class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Select Scheme</label>
                    <select name="scheme_id" id="scheme_id" x-model="selectedSchemeId" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">No Scheme</option>
                        <template x-for="s in eligibleSchemes()" :key="s.id">
                            <option :value="s.id" x-text="`${s.name} (${s.code}) · ${s.basis === 'quantity' ? 'Quantity Basis' : 'Value Basis'}${s.brand_name ? ' · ' + s.brand_name : ''}`"></option>
                        </template>
                    </select>

                    <template x-if="eligibleSchemes().length === 0">
                        <p class="mt-2 text-xs text-amber-700 bg-amber-50 p-2.5 rounded-lg border border-amber-200">
                            No eligible schemes available for the current invoice date and selected items. You can proceed without selecting a scheme.
                        </p>
                    </template>
                </div>

                <div>
                    <template x-if="currentSchemeDetails()">
                        <div class="bg-white p-3.5 rounded-lg border border-indigo-100 shadow-sm space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-indigo-900" x-text="currentSchemeDetails().scheme.name"></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700" x-text="currentSchemeDetails().scheme.basis === 'quantity' ? 'Quantity Basis' : 'Value Basis'"></span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100">
                                <div>
                                    <span class="text-slate-500">Qualified:</span>
                                    <span class="font-medium text-slate-800 ml-1" x-text="currentSchemeDetails().scheme.basis === 'quantity' ? `${currentSchemeDetails().qualified} Units` : fmt(currentSchemeDetails().qualified)"></span>
                                </div>
                                <div>
                                    <span class="text-slate-500">Calculated Benefit:</span>
                                    <span class="font-bold text-emerald-600 ml-1" x-text="fmt(currentSchemeDetails().benefit)"></span>
                                </div>
                            </div>

                            <template x-if="currentSchemeDetails().slab">
                                <div class="text-[11px] text-slate-600 bg-slate-50 p-2 rounded border border-slate-100">
                                    <span class="font-medium">Applied Slab:</span>
                                    <span x-text="`From ${currentSchemeDetails().scheme.basis === 'quantity' ? currentSchemeDetails().slab.from_value + ' units' : fmt(currentSchemeDetails().slab.from_value)}` + (currentSchemeDetails().slab.to_value ? ` to ${currentSchemeDetails().scheme.basis === 'quantity' ? currentSchemeDetails().slab.to_value + ' units' : fmt(currentSchemeDetails().slab.to_value)}` : ' onwards')"></span>
                                    <span class="text-slate-500" x-text="` · Benefit: ${currentSchemeDetails().slab.benefit_amount > 0 ? fmt(currentSchemeDetails().slab.benefit_amount) : currentSchemeDetails().slab.benefit_percent + '%'}`"></span>
                                </div>
                            </template>

                            <template x-if="!currentSchemeDetails().slab && currentSchemeDetails().qualifyingCount > 0">
                                <div class="text-[11px] text-amber-700 bg-amber-50 p-2 rounded border border-amber-200">
                                    <span class="font-medium">Minimum slab not reached:</span>
                                    <span x-text="`Requires minimum ${currentSchemeDetails().scheme.basis === 'quantity' ? currentSchemeDetails().minSlab + ' units' : fmt(currentSchemeDetails().minSlab)}. Current benefit: ₹0.00`"></span>
                                </div>
                            </template>

                            <template x-if="currentSchemeDetails().qualifyingCount === 0">
                                <div class="text-[11px] text-rose-700 bg-rose-50 p-2 rounded border border-rose-200">
                                    No items in the invoice qualify for this scheme's products or brand.
                                </div>
                            </template>

                            <p class="text-[10px] text-slate-400 italic">
                                * Scheme rebate will be credited via commercial credit note / settlement as per scheme policy.
                            </p>
                        </div>
                    </template>

                    <template x-if="!currentSchemeDetails()">
                        <div class="h-full flex items-center justify-center p-3 border border-dashed border-slate-200 rounded-lg text-xs text-slate-400 text-center">
                            Select a scheme from the dropdown to view calculated rebate benefits.
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Legacy compatibility --}}
        <input type="hidden" name="discount_amount" :value="itemDiscountTotal()">

        <x-ui.button type="submit" variant="primary">Create Invoice</x-ui.button>
    </form>
</x-ui.card>

<x-quick-add-product />

{{-- Pick Batch popover (Alpine, teleported into the form scope via x-teleport wouldn't work here so we render inline) --}}
<div x-show="batchPicker.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="batchPicker.open = false">
    <div class="w-full max-w-2xl rounded-xl bg-white p-5 shadow-xl">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-800">Pick Batch</h3>
            <button type="button" @click="batchPicker.open = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <template x-if="batchPicker.loading">
            <p class="py-6 text-center text-slate-500">Loading batches…</p>
        </template>
        <template x-if="!batchPicker.loading && batchPicker.batches.length === 0">
            <p class="py-6 text-center text-slate-500">No batches available for this product.</p>
        </template>
        <template x-if="!batchPicker.loading && batchPicker.batches.length > 0">
            <div class="max-h-96 overflow-y-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="sticky top-0 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Batch No</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Warehouse</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Expiry</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Available</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Selling ₹</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="b in batchPicker.batches" :key="b.id">
                            <tr>
                                <td class="px-3 py-2 font-mono" x-text="b.batch_no || '—'"></td>
                                <td class="px-3 py-2" x-text="b.warehouse || '—'"></td>
                                <td class="px-3 py-2" x-text="b.expiry_date || '—'"></td>
                                <td class="px-3 py-2 text-right" x-text="b.available_qty"></td>
                                <td class="px-3 py-2 text-right" x-text="b.selling_price ?? b.unit_cost"></td>
                                <td class="px-3 py-2 text-right"><button type="button" @click="applyBatch(b)" class="rounded-lg bg-indigo-600 px-2 py-1 text-xs font-semibold text-white hover:bg-indigo-700">Use</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function invoiceForm(products, uoms, schemes = []) {
    return {
        products, uoms, schemes,
        _seq: 0,
        lines: [],
        universalType: 'flat',
        universalValue: 0,
        batchPicker: { open: false, loading: false, targetIdx: null, batches: [] },
        customerId: '',
        partyAddresses: [],
        addressesLoading: false,
        selectedBillingId: '',
        selectedShippingId: '',
        sameAsBilling: true,
        billingSnapshot: '',
        shippingSnapshot: '',
        selectedSchemeId: '{{ old('scheme_id', '') }}',
        invoiceDate: '{{ old('invoice_date', now()->toDateString()) }}',
        init() {
            this.addLine();
            const custSelect = document.getElementById('customer_id');
            if (custSelect && custSelect.value) {
                this.onCustomerChange(custSelect.value);
            }
            const dateInput = document.getElementById('invoice_date');
            if (dateInput && dateInput.value) {
                this.invoiceDate = dateInput.value;
            }
        },
        async onCustomerChange(id) {
            this.customerId = id;
            if (!id) {
                this.partyAddresses = [];
                this.billingSnapshot = '';
                this.shippingSnapshot = '';
                return;
            }
            this.addressesLoading = true;
            try {
                const res = await fetch(`{{ url('/masters/customers') }}/${id}/addresses`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.partyAddresses = data.addresses || [];
                    this.selectedBillingId = data.default_billing_id || (this.partyAddresses[0]?.id ?? '');
                    this.selectedShippingId = data.default_delivery_id || this.selectedBillingId;

                    this.onBillingAddressChange();
                    if (!this.sameAsBilling) {
                        this.onShippingAddressChange();
                    } else {
                        this.shippingSnapshot = this.billingSnapshot;
                    }
                }
            } catch (e) {
                console.error('Failed to load party addresses:', e);
            } finally {
                this.addressesLoading = false;
            }
        },
        onBillingAddressChange() {
            const addr = this.partyAddresses.find(a => String(a.id) === String(this.selectedBillingId));
            this.billingSnapshot = addr ? (addr.snapshot || addr.full_address) : '';
            if (this.sameAsBilling) {
                this.selectedShippingId = this.selectedBillingId;
                this.shippingSnapshot = this.billingSnapshot;
                if (addr?.state) {
                    const stateInput = document.querySelector('input[name="delivery_state"]');
                    if (stateInput) stateInput.value = addr.state;
                }
            }
        },
        onShippingAddressChange() {
            const addr = this.partyAddresses.find(a => String(a.id) === String(this.selectedShippingId));
            this.shippingSnapshot = addr ? (addr.snapshot || addr.full_address) : '';
            if (addr?.state) {
                const stateInput = document.querySelector('input[name="delivery_state"]');
                if (stateInput) stateInput.value = addr.state;
            }
        },
        onSameAsBillingToggle() {
            if (this.sameAsBilling) {
                this.selectedShippingId = this.selectedBillingId;
                this.shippingSnapshot = this.billingSnapshot;
            } else {
                this.onShippingAddressChange();
            }
        },
        addLine() {
            this.lines.push({
                _key: ++this._seq,
                product_id: '', uom_id: '',
                quantity: 1, unit_price: 0, discount_type: 'percent', discount_value: 0, batch_no: '', batch_meta: null,
            });
        },
        async openBatchPicker(idx) {
            const row = this.lines[idx];
            if (! row.product_id) return;
            this.batchPicker.open = true;
            this.batchPicker.loading = true;
            this.batchPicker.targetIdx = idx;
            this.batchPicker.batches = [];
            try {
                const params = new URLSearchParams();
                if (row.uom_id) params.set('uom_id', row.uom_id);
                const res = await fetch(`{{ url('/inventory/batches') }}/${row.product_id}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.batchPicker.batches = data.batches || [];
            } catch (e) { console.error(e); }
            finally { this.batchPicker.loading = false; }
        },
        applyBatch(b) {
            const row = this.lines[this.batchPicker.targetIdx];
            if (! row) return;
            row.batch_no = b.batch_no || '';
            row.batch_meta = b;
            if (b.uom_id) row.uom_id = b.uom_id;
            const price = b.selling_price ?? b.unit_cost;
            if (price) row.unit_price = price;
            if (b.available_qty && Number(row.quantity) > Number(b.available_qty)) row.quantity = Number(b.available_qty);
            this.batchPicker.open = false;
        },
        removeLine(idx) { this.lines.splice(idx, 1); },
        onProductChange(idx) {
            const row = this.lines[idx];
            const p = this.products.find(x => String(x.id) === String(row.product_id));
            if (! p) return;
            row.uom_id = p.base_uom_id;
            row.unit_price = p.selling_price;
            row.discount_type = p.selling_discount_type;
            row.discount_value = p.selling_discount_value;
        },
        onProductAdded(p) {
            this.products.push({ id: p.id, name: p.name, brand_id: p.brand_id, base_uom_id: p.base_uom_id, selling_price: p.selling_price, selling_discount_type: 'percent', selling_discount_value: 0 });
            const empty = this.lines.find(r => ! r.product_id) || this.lines[this.lines.length - 1];
            if (empty) { empty.product_id = p.id; this.onProductChange(this.lines.indexOf(empty)); }
        },
        lineGross(row) { return Math.max(0, (Number(row.quantity) || 0) * (Number(row.unit_price) || 0)); },
        lineDiscount(row) {
            const g = this.lineGross(row);
            const v = Number(row.discount_value) || 0;
            if (v <= 0) return 0;
            return row.discount_type === 'flat' ? Math.min(g, v * Math.max(Number(row.quantity) || 1, 1)) : g * v / 100;
        },
        lineTotal(row) { return Math.max(0, this.lineGross(row) - this.lineDiscount(row)); },
        subTotal() { return this.lines.reduce((s, r) => s + this.lineTotal(r), 0); },
        itemDiscountTotal() { return this.lines.reduce((s, r) => s + this.lineDiscount(r), 0); },
        universalDiscount() {
            const sub = this.subTotal();
            const v = Number(this.universalValue) || 0;
            if (v <= 0) return 0;
            return this.universalType === 'flat' ? Math.min(sub, v) : sub * v / 100;
        },
        grandTotal() { return Math.max(0, this.subTotal() - this.universalDiscount()); },
        fmt(n) { return '₹ ' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

        // Commercial Scheme Helpers
        eligibleSchemes() {
            const date = this.invoiceDate;
            const validLines = this.lines.filter(l => l.product_id && (Number(l.quantity) || 0) > 0);

            return this.schemes.filter(s => {
                if (s.starts_on && date < s.starts_on) return false;
                if (s.ends_on && date > s.ends_on) return false;

                if (validLines.length === 0) return true;

                if (s.product_ids && s.product_ids.length > 0) {
                    return validLines.some(l => s.product_ids.includes(Number(l.product_id)));
                }

                if (s.brand_id) {
                    return validLines.some(l => {
                        const p = this.products.find(x => String(x.id) === String(l.product_id));
                        return p && Number(p.brand_id) === Number(s.brand_id);
                    });
                }

                return true;
            });
        },

        currentSchemeDetails() {
            if (! this.selectedSchemeId) return null;
            const s = this.schemes.find(x => String(x.id) === String(this.selectedSchemeId));
            if (! s) return null;

            const validLines = this.lines.filter(l => l.product_id && (Number(l.quantity) || 0) > 0);
            let qualifying = [];

            if (s.product_ids && s.product_ids.length > 0) {
                qualifying = validLines.filter(l => s.product_ids.includes(Number(l.product_id)));
            } else if (s.brand_id) {
                qualifying = validLines.filter(l => {
                    const p = this.products.find(x => String(x.id) === String(l.product_id));
                    return p && Number(p.brand_id) === Number(s.brand_id);
                });
            } else {
                qualifying = validLines;
            }

            let qualified = 0;
            if (s.basis === 'quantity') {
                qualified = qualifying.reduce((sum, l) => sum + (Number(l.quantity) || 0), 0);
            } else {
                qualified = qualifying.reduce((sum, l) => sum + this.lineTotal(l), 0);
            }

            const sortedSlabs = (s.slabs || []).slice().sort((a, b) => Number(b.from_value) - Number(a.from_value));
            const matchedSlab = sortedSlabs.find(sl => {
                const from = Number(sl.from_value) || 0;
                const to = (sl.to_value !== null && sl.to_value !== '') ? Number(sl.to_value) : null;
                return qualified >= from && (to === null || qualified <= to);
            });

            let benefit = 0;
            if (matchedSlab) {
                if (Number(matchedSlab.benefit_amount) > 0) {
                    benefit = Number(matchedSlab.benefit_amount);
                } else {
                    benefit = qualified * (Number(matchedSlab.benefit_percent) || 0) / 100;
                }
            }

            const minSlab = sortedSlabs.length > 0 ? Math.min(...sortedSlabs.map(x => Number(x.from_value))) : 0;

            return {
                scheme: s,
                qualifyingCount: qualifying.length,
                qualified: Math.round(qualified * 100) / 100,
                slab: matchedSlab,
                benefit: Math.round(benefit * 100) / 100,
                minSlab: minSlab,
            };
        },

        selectedSchemeBenefit() {
            const det = this.currentSchemeDetails();
            return det ? det.benefit : 0;
        },
    }
}
</script>
@endpush
@endsection
