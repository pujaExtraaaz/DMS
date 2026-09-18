@extends('layouts.dms')
@section('title', 'Direct Billing')
@section('content')
@php
    $productsJson = $products->map(fn($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'base_uom_id' => $p->base_uom_id,
        'selling_price' => (float) $p->selling_price,
        'selling_discount_type' => $p->selling_discount_type ?? 'percent',
        'selling_discount_value' => (float) ($p->selling_discount_value ?? 0),
    ])->values()->toArray();
    $uomsJson = $uoms->map(fn($u) => ['id' => $u->id, 'code' => $u->code, 'name' => $u->name])->values()->toArray();
@endphp
<x-ui.page-header title="Direct Billing" description="Book a tax invoice with per-line discount, universal discount, T&amp;C and e-invoice ready fields.">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('invoices.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="POST" action="{{ route('invoices.store') }}" class="space-y-6"
          x-data='invoiceForm(@json($productsJson), @json($uomsJson))' x-init="init()"
          x-on:product-quick-added.window="onProductAdded($event.detail)">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.select name="customer_id" id="customer_id" label="Customer" required placeholder="Select">
                @foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </x-ui.select>
            <x-ui.input name="invoice_date" label="Invoice Date" type="date" :value="now()->toDateString()" required />
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
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Batch No</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Warehouse</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Expiry</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Available</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Selling ₹</th>
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
function invoiceForm(products, uoms) {
    return {
        products, uoms,
        _seq: 0,
        lines: [],
        universalType: 'flat',
        universalValue: 0,
        batchPicker: { open: false, loading: false, targetIdx: null, batches: [] },
        init() { this.addLine(); },
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
            this.products.push({ id: p.id, name: p.name, base_uom_id: p.base_uom_id, selling_price: p.selling_price, selling_discount_type: 'percent', selling_discount_value: 0 });
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
    }
}
</script>
@endpush
@endsection
