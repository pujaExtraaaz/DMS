@extends('layouts.dms')
@section('title', 'New Purchase Invoice')

@section('content')
<x-ui.page-header title="New Purchase Invoice">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.index')">Back</x-ui.button>
    </x-slot>
</x-ui.page-header>

<form method="POST" action="{{ route('purchasing.invoices.store') }}"
      x-data="purchaseInvoiceForm(@js($products), @js($order ?? null))"
      class="space-y-6">
    @csrf

    <x-ui.card>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Supplier *</label>
                    <button type="button" @click="$dispatch('open-quick-add-supplier')"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1">
                        + Add New
                    </button>
                </div>
                <select name="supplier_id" x-model="supplierId" required
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(old('supplier_id', $order?->supplier_id)==$s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Warehouse *</label>
                <select name="warehouse_id" x-model="warehouseId" required
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select warehouse</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected(old('warehouse_id', $order?->warehouse_id)==$w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Invoice Date *</label>
                <input type="date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" required
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Supplier Inv No.</label>
                <input type="text" name="supplier_invoice_number" value="{{ old('supplier_invoice_number') }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Credit Days</label>
                <input type="number" name="credit_days" min="0" placeholder="0" value="{{ old('credit_days', 0) }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        @if($order)
            <input type="hidden" name="purchase_order_id" value="{{ $order->id }}">
            <p class="mt-3 text-xs text-slate-500">Linked to PO: <span class="font-semibold text-slate-700">{{ $order->order_number }}</span></p>
        @endif
    </x-ui.card>

    <x-ui.card padding="false">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700">Line Items</h3>
            <button type="button" @click="addItem()"
                    class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                + Add Item
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b">
                    <tr>
                        <th class="p-3">Product</th>
                        <th class="p-3 w-16">Unit</th>
                        <th class="p-3 w-24">Qty</th>
                        <th class="p-3 w-28">Unit Cost</th>
                        <th class="p-3 w-20">CGST %</th>
                        <th class="p-3 w-20">SGST %</th>
                        <th class="p-3 w-24">Total Tax %</th>
                        <th class="p-3 w-28">Batch</th>
                        <th class="p-3 w-28">Batch MRP</th>
                        <th class="p-3 w-28">Batch Selling Price</th>
                        <th class="p-3 w-32 text-right">Line Total</th>
                        <th class="p-3 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(row, i) in items" :key="i">
                        <tr class="align-top hover:bg-slate-50/50">
                            <td class="p-3">
                                <select :name="`items[${i}][product_id]`" x-model="row.product_id" @change="onProductChange(i)" required
                                        class="block w-full rounded border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select product</option>
                                    <template x-for="p in products" :key="p.id">
                                        <option :value="p.id" x-text="p.name"></option>
                                    </template>
                                </select>

                                <template x-if="row.tracking_type === 'serial' || row.tracking_type === 'all'">
                                    <div class="mt-2 p-2 bg-slate-50 rounded border border-slate-200">
                                        <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-1">Serial Numbers</label>
                                        <template x-for="(sNo, sIdx) in row.serial_numbers" :key="sIdx">
                                            <div class="flex gap-1 mb-1">
                                                <input type="text" :name="`items[${i}][serial_numbers][${sIdx}]`" x-model="row.serial_numbers[sIdx]" placeholder="Serial No."
                                                       class="block w-full rounded border-gray-300 text-xs font-mono focus:border-indigo-500">
                                                <button type="button" @click="removeSerial(i, sIdx)" class="text-rose-500 hover:text-rose-700 px-1">&times;</button>
                                            </div>
                                        </template>
                                        <button type="button" @click="addSerial(i)" class="text-[10px] text-indigo-600 font-semibold hover:underline">+ Add Serial</button>
                                    </div>
                                </template>
                            </td>
                            <td class="p-3 text-slate-500" x-text="row.unit || '-'"></td>
                            <td class="p-3">
                                <input type="number" step="0.0001" min="0.0001" :name="`items[${i}][quantity]`" x-model.number="row.quantity" @input="recalc()" required
                                       class="block w-full rounded border-gray-300 text-xs focus:border-indigo-500">
                            </td>
                            <td class="p-3">
                                <input type="number" step="0.01" min="0" :name="`items[${i}][unit_cost]`" x-model.number="row.unit_cost" @input="recalc()" required
                                       class="block w-full rounded border-gray-300 text-xs focus:border-indigo-500">
                            </td>
                            <td class="p-3">
                                <input type="number" step="0.01" min="0" max="100" :name="`items[${i}][cgst_percent]`" x-model.number="row.cgst_percent" @input="recalc()"
                                       class="block w-full rounded border-gray-300 text-xs focus:border-indigo-500">
                            </td>
                            <td class="p-3">
                                <input type="number" step="0.01" min="0" max="100" :name="`items[${i}][sgst_percent]`" x-model.number="row.sgst_percent" @input="recalc()"
                                       class="block w-full rounded border-gray-300 text-xs focus:border-indigo-500">
                            </td>
                            <td class="p-3 font-semibold text-slate-700" x-text="((row.cgst_percent || 0) + (row.sgst_percent || 0)).toFixed(2) + '%'"></td>
                            <td class="p-3">
                                <input type="text" :name="`items[${i}][batch_number]`" x-model="row.batch_number" placeholder="Batch No"
                                       class="block w-full rounded border-gray-300 text-xs focus:border-indigo-500">
                            </td>
                            <td class="p-3">
                                <input type="number" step="0.01" min="0" :name="`items[${i}][batch_mrp]`" x-model.number="row.batch_mrp" placeholder="MRP"
                                       class="block w-full rounded border-gray-300 text-xs focus:border-indigo-500">
                            </td>
                            <td class="p-3">
                                <input type="number" step="0.01" min="0" :name="`items[${i}][selling_price]`" x-model.number="row.selling_price" placeholder="Selling Price"
                                       class="block w-full rounded border-gray-300 text-xs focus:border-indigo-500">
                            </td>
                            <td class="p-3 text-right font-semibold text-slate-800" x-text="formatCurrency(row.line_total)"></td>
                            <td class="p-3 text-center">
                                <button type="button" @click="removeItem(i)" x-show="items.length > 1" class="text-rose-500 hover:text-rose-700 font-bold">&times;</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-slate-50/50 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Notes / Remarks</label>
                <textarea name="notes" rows="3" placeholder="Additional delivery or invoice remarks..."
                          class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
            </div>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Taxable Subtotal:</span>
                    <span class="font-mono font-medium" x-text="formatCurrency(subtotal)"></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Total Tax (CGST + SGST):</span>
                    <span class="font-mono font-medium" x-text="formatCurrency(taxTotal)"></span>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span>Freight / Landed Charge (₹):</span>
                    <input type="number" step="0.01" min="0" name="freight_charge" x-model.number="freightCharge" @input="recalc()"
                           class="w-28 rounded border-gray-300 text-xs text-right focus:border-indigo-500">
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span>Other Charges (₹):</span>
                    <input type="number" step="0.01" min="0" name="other_charges" x-model.number="otherCharges" @input="recalc()"
                           class="w-28 rounded border-gray-300 text-xs text-right focus:border-indigo-500">
                </div>
                <div class="flex justify-between text-base font-bold text-slate-900 pt-2 border-t border-slate-200">
                    <span>Grand Total:</span>
                    <span class="font-mono text-indigo-600" x-text="formatCurrency(grandTotal)"></span>
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="flex items-center justify-end gap-3">
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.index')">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary">Create Invoice (Draft)</x-ui.button>
    </div>
</form>

<x-quick-add-supplier target-select="supplier_id" />

@push('scripts')
<script>
function purchaseInvoiceForm(products, initialOrder) {
    return {
        products: products || [],
        supplierId: initialOrder ? initialOrder.supplier_id : '',
        warehouseId: initialOrder ? initialOrder.warehouse_id : '',
        freightCharge: 0,
        otherCharges: 0,
        subtotal: 0,
        taxTotal: 0,
        grandTotal: 0,
        items: [],

        init() {
            if (initialOrder && initialOrder.items && initialOrder.items.length) {
                this.items = initialOrder.items.map(it => {
                    const p = this.products.find(prod => prod.id === it.product_id);
                    return {
                        product_id: it.product_id,
                        unit: p ? (p.unit || 'PCS') : 'PCS',
                        tracking_type: p ? (p.tracking_type || 'none') : 'none',
                        quantity: Number(it.quantity) || 1,
                        unit_cost: Number(it.unit_cost) || 0,
                        cgst_percent: Number(it.cgst_percent) || 0,
                        sgst_percent: Number(it.sgst_percent) || 0,
                        batch_number: '',
                        batch_mrp: p ? (Number(p.mrp) || 0) : 0,
                        selling_price: p ? (Number(p.selling_price) || 0) : 0,
                        serial_numbers: [],
                        line_total: 0
                    };
                });
            } else {
                this.addItem();
            }
            this.recalc();
        },

        addItem() {
            this.items.push({
                product_id: '',
                unit: '-',
                tracking_type: 'none',
                quantity: 1,
                unit_cost: 0,
                cgst_percent: 0,
                sgst_percent: 0,
                batch_number: '',
                batch_mrp: 0,
                selling_price: 0,
                serial_numbers: [],
                line_total: 0
            });
            this.recalc();
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
                this.recalc();
            }
        },

        onProductChange(index) {
            const row = this.items[index];
            const p = this.products.find(item => item.id == row.product_id);
            if (p) {
                row.unit = p.unit || 'PCS';
                row.tracking_type = p.tracking_type || 'none';
                row.unit_cost = Number(p.purchase_price) || 0;
                row.cgst_percent = Number(p.tax_rate ? p.tax_rate / 2 : 0);
                row.sgst_percent = Number(p.tax_rate ? p.tax_rate / 2 : 0);
                row.batch_mrp = Number(p.mrp) || 0;
                row.selling_price = Number(p.selling_price) || 0;
            }
            this.recalc();
        },

        addSerial(itemIndex) {
            this.items[itemIndex].serial_numbers.push('');
        },

        removeSerial(itemIndex, serialIndex) {
            this.items[itemIndex].serial_numbers.splice(serialIndex, 1);
        },

        recalc() {
            let sub = 0;
            let tax = 0;

            this.items.forEach(row => {
                const qty = Number(row.quantity) || 0;
                const cost = Number(row.unit_cost) || 0;
                const lineBase = qty * cost;
                const taxPercent = (Number(row.cgst_percent) || 0) + (Number(row.sgst_percent) || 0);
                const lineTax = (lineBase * taxPercent) / 100;
                row.line_total = lineBase + lineTax;

                sub += lineBase;
                tax += lineTax;
            });

            this.subtotal = sub;
            this.taxTotal = tax;
            this.grandTotal = sub + tax + (Number(this.freightCharge) || 0) + (Number(this.otherCharges) || 0);
        },

        formatCurrency(val) {
            return '₹' + Number(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };
}
</script>
@endpush
@endsection