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
        'tracking_types' => $p->tracking_types ?? [],
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
@endphp

<x-ui.page-header title="New Purchase Invoice" description="Book supplier invoice — with or without a preceding Purchase Order. Batch selling-price and serials captured here flow to inventory.">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('purchasing.invoices.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="POST" action="{{ route('purchasing.invoices.store') }}"
          x-data='pInvoiceForm(@json($productsJson),@json($uomsJson),@json($vendorRatesJson))'
          x-init="init()"
          x-on:product-quick-added.window="onProductAdded($event.detail)"
          class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-slate-700">Supplier *</label>
                    <button type="button" @click="$dispatch('open-quick-add-supplier')"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        + Add Supplier
                    </button>
                </div>
                <select name="supplier_id" class="block w-full rounded-lg border-gray-300 text-sm" required @change="supplierChanged()">
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Linked PO (optional)</label>
                <select name="purchase_order_id" 
                    class="block w-full rounded-lg border-gray-300 text-sm"
                    @change="loadPurchaseOrder($event.target.value)">
                    <option value="">— Direct purchase invoice —</option>
                    @foreach($orders as $o)<option value="{{ $o->id }}" @selected($selectedOrderId==$o->id)>{{ $o->po_no }}</option>@endforeach
                </select>
            </div>
            <x-ui.input name="supplier_invoice_no" label="Supplier Invoice No" :value="old('supplier_invoice_no')" />
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Invoice Date *</label>
                <input type="date" name="invoice_date" x-model="invoice_date" @change="calcDueDate()" required
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Credit Days</label>
                <input type="number" min="0" name="credit_days" x-model="credit_days" @input="calcDueDate()" placeholder="e.g. 30"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Due Date</label>
                <input type="date" name="due_date" x-model="due_date"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Warehouse</label>
                <select name="warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm">
                    <option value="">Default</option>
                    @foreach($warehouses as $w)<option value="{{ $w->id }}" @selected(old('warehouse_id')==$w->id)>{{ $w->name }}</option>@endforeach
                </select>
            </div>
        </div>

        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
            If a unit cost is higher than another vendor's last rate, provide an override reason below.
        </p>

        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700">Line items</h3>
            <div class="flex gap-2">
                <button type="button" @click="$dispatch('open-quick-add-product')"
                        class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                    + Quick add product
                </button>
                <button type="button" @click="addLine()"
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
                        <th class="px-3 py-2 text-left">Unit Cost</th>
                        <th class="px-3 py-2 text-left">CGST %</th>
                        <th class="px-3 py-2 text-left">SGST %</th>
                        <th class="px-3 py-2 text-left">Total Tax %</th>
                        <th class="px-3 py-2 text-left">Batch</th>
                        <th class="px-3 py-2 text-left">Batch Selling ₹</th>
                        <th class="px-3 py-2 text-left">Batch MRP ₹</th>
                        <th class="px-3 py-2 text-left">Serials (comma-sep)</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, idx) in lines" :key="row._key">
                        <tr class="border-t align-top">
                            <td class="px-3 py-2">
                                <select :name="`items[${idx}][product_id]`" x-model="row.product_id" @change="onProductChange(idx)" required class="block w-52 rounded-lg border-gray-300 text-sm">
                                    <option value="">Select product</option>
                                    <template x-for="p in products" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <select :name="`items[${idx}][uom_id]`" x-model="row.uom_id" required class="block w-24 rounded-lg border-gray-300 text-sm">
                                    <template x-for="u in uoms" :key="u.id"><option :value="u.id" x-text="u.code"></option></template>
                                </select>
                            </td>
                            <td class="px-3 py-2"><input type="number" step="0.0001" :name="`items[${idx}][quantity]`" x-model="row.quantity" required class="block w-20 rounded-lg border-gray-300 text-sm"></td>
                            <td class="px-3 py-2"><input type="number" step="0.0001" :name="`items[${idx}][unit_cost]`" x-model="row.unit_cost" required class="block w-28 rounded-lg border-gray-300 text-sm"></td>

                            <td class="px-3 py-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    :name="`items[${idx}][cgst_percent]`"
                                    x-model.number="row.cgst_percent"
                                    @input="syncCgst(idx)"
                                    class="block w-20 rounded-lg border-gray-300 text-sm"
                                >
                            </td>

                            <td class="px-3 py-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    :name="`items[${idx}][sgst_percent]`"
                                    x-model.number="row.sgst_percent"
                                    @input="syncSgst(idx)"
                                    class="block w-20 rounded-lg border-gray-300 text-sm"
                                >
                            </td>

                            <td class="px-3 py-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    :name="`items[${idx}][tax_percent]`"
                                    x-model.number="row.tax_percent"
                                    @input="syncTax(idx)"
                                    class="block w-20 rounded-lg border-gray-300 text-sm"
                                >
                            </td>

                            <td class="px-3 py-2"><input type="text" :name="`items[${idx}][batch_no]`" x-model="row.batch_no" class="block w-28 rounded-lg border-gray-300 text-sm" placeholder="Optional"></td>
                            <td class="px-3 py-2"><input type="number" step="0.01" :name="`items[${idx}][batch_selling_price]`" x-model="row.batch_selling_price" class="block w-28 rounded-lg border-gray-300 text-sm" placeholder="Selling ₹"></td>
                            <td class="px-3 py-2"><input type="number" step="0.01" :name="`items[${idx}][batch_mrp]`" x-model="row.batch_mrp" class="block w-28 rounded-lg border-gray-300 text-sm" placeholder="MRP ₹"></td>
                            <td class="px-3 py-2"><input type="text" :name="`items[${idx}][serials]`" x-model="row.serials" class="block w-40 rounded-lg border-gray-300 text-sm" placeholder="SN1, SN2"></td>
                            <td class="px-3 py-2 text-right"><button type="button" x-show="lines.length > 1" @click="removeLine(idx)" class="text-xs text-red-600 hover:text-red-800">Remove</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
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
    </form>
</x-ui.card>

<x-quick-add-product />
<x-quick-add-supplier targetSelect="supplier_id" />

@push('scripts')
<script>
function pInvoiceForm(products, uoms, vendorRates) {
    return {
        products, uoms, vendorRates,
        _seq: 0,
        lines: [],
        loadingOrder: false,
        invoice_date: '{{ old("invoice_date", now()->toDateString()) }}',
        credit_days: '{{ old("credit_days", "") }}',
        due_date: '{{ old("due_date", "") }}',
        init() {
            this.addLine();
            this.calcDueDate();
        },
        addLine() {
            this.lines.push({
                _key: ++this._seq,
                product_id: '', uom_id: '',
                quantity: 1, unit_cost: 0, tax_percent: 0, cgst_percent: 0, sgst_percent: 0, cgst_amount: 0, sgst_amount: 0,
                batch_no: '', batch_selling_price: '', batch_mrp: '', serials: '',
            });
        },
        removeLine(idx) { this.lines.splice(idx, 1); },

        calcDueDate() {
            if (!this.invoice_date) return;
            const days = parseInt(this.credit_days);
            if (isNaN(days) || days < 0) {
                return;
            }
            const date = new Date(this.invoice_date);
            if (isNaN(date.getTime())) return;
            date.setDate(date.getDate() + days);
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            this.due_date = `${yyyy}-${mm}-${dd}`;
        },

        supplierChanged() {},

        onProductChange(idx) {
            const row = this.lines[idx];
            const p = this.products.find(x => String(x.id) === String(row.product_id));
            if (!p) return;

            row.uom_id = p.base_uom_id;

            const supplierId = document.querySelector('[name="supplier_id"]')?.value;
            const key = `${supplierId}:${p.id}:${p.base_uom_id}`;
            const rates = this.vendorRates[key] ?? [];

            if (rates.length > 0) {
                const invoiceDate = this.invoice_date || '';
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
            row.cgst_percent = Number((row.tax_percent / 2).toFixed(2));
            row.sgst_percent = Number((row.tax_percent / 2).toFixed(2));

            if (p.selling_price) {
                row.batch_selling_price = p.selling_price;
            }
            if (p.mrp) {
                row.batch_mrp = p.mrp;
            }
        },

        syncTax(idx) {
            const row = this.lines[idx];
            const totalTax = Number(row.tax_percent || 0);
            row.cgst_percent = Number((totalTax / 2).toFixed(2));
            row.sgst_percent = Number((totalTax / 2).toFixed(2));
        },

        syncCgst(idx) {
            const row = this.lines[idx];
            const cgst = Number(row.cgst_percent || 0);
            const sgst = Number(row.sgst_percent || 0);
            row.tax_percent = Number((cgst + sgst).toFixed(2));
        },

        syncSgst(idx) {
            const row = this.lines[idx];
            const cgst = Number(row.cgst_percent || 0);
            const sgst = Number(row.sgst_percent || 0);
            row.tax_percent = Number((cgst + sgst).toFixed(2));
        },

        async loadPurchaseOrder(orderId) {
            if (!orderId) {
                this.lines = [];
                this.addLine();
                return;
            }

            this.loadingOrder = true;

            try {
                const response = await fetch(`{{ url('/purchasing/invoices/purchase-order') }}/${orderId}/data`);
                if (!response.ok) {
                    throw new Error('Unable to load purchase order.');
                }

                const order = await response.json();

                const supplierSelect = document.querySelector('[name="supplier_id"]');
                if (supplierSelect) {
                    supplierSelect.value = order.supplier_id ?? '';
                    supplierSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                const warehouseSelect = document.querySelector('[name="warehouse_id"]');
                if (warehouseSelect) {
                    warehouseSelect.value = order.warehouse_id ?? '';
                    warehouseSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                this.lines = order.items.map(item => ({
                    _key: ++this._seq,
                    product_id: item.product_id ?? '',
                    uom_id: item.uom_id ?? '',
                    quantity: Number(item.quantity ?? 0),
                    unit_cost: Number(item.unit_cost ?? 0),
                    tax_percent: Number(item.tax_percent ?? 0),
                    cgst_percent: Number(item.cgst_percent ?? 0),
                    sgst_percent: Number(item.sgst_percent ?? 0),
                    cgst_amount: Number(item.cgst_amount ?? 0),
                    sgst_amount: Number(item.sgst_amount ?? 0),
                    batch_no: item.batch_no ?? '',
                    batch_selling_price: '',
                    batch_mrp: '',
                    serials: '',
                }));

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
            this.products.push({ id: p.id, name: p.name, sku: p.sku, base_uom_id: p.base_uom_id, tax_rate: p.tax_rate, purchase_price: p.purchase_price, selling_price: p.selling_price, mrp: p.calculation_mrp, color_variant: p.color_variant, tracking_types: p.tracking_types ?? [] });
            const empty = this.lines.find(r => ! r.product_id) || this.lines[this.lines.length - 1];
            if (empty) { empty.product_id = p.id; this.onProductChange(this.lines.indexOf(empty)); }
        },
    }
}
</script>
@endpush
@endsection