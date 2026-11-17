@extends('layouts.dms')
@section('title', 'New Receipt Note')
@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="receiptNoteForm()">
    <x-ui.page-header title="New Receipt Note" subtitle="Record physical receipt of goods against a Delivery Challan or Purchase Order.">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('purchasing.receipt-notes.index')">Cancel</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs">
            <p class="font-bold mb-1">Please fix the following errors:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('purchasing.receipt-notes.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Primary Information Card --}}
        <x-ui.card>
            <div class="p-6 space-y-6">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3">
                    Receipt Details
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier <span class="text-rose-500">*</span></label>
                        <select
                            name="supplier_id"
                            x-model="supplierId"
                            @change="onSupplierChange()"
                            required
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $preselectedPo?->supplier_id) == $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Destination Warehouse <span class="text-rose-500">*</span></label>
                        <select
                            name="warehouse_id"
                            required
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="">Select Warehouse</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected(old('warehouse_id', $preselectedPo?->warehouse_id) == $wh->id)>
                                    {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Link Purchase Order (Optional)</label>
                        <select
                            name="purchase_order_id"
                            x-model="purchaseOrderId"
                            @change="onPoChange()"
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="">None (Direct Receipt)</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->id }}" data-supplier-id="{{ $po->supplier_id }}" @selected(old('purchase_order_id', $preselectedPo?->id) == $po->id)>
                                    {{ $po->po_no }} - {{ $po->supplier?->name }} ({{ $po->po_date->format('d/m/Y') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt Date <span class="text-rose-500">*</span></label>
                        <input
                            type="date"
                            name="receipt_date"
                            value="{{ old('receipt_date', now()->toDateString()) }}"
                            required
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Challan / Delivery Note No</label>
                        <input
                            type="text"
                            name="supplier_challan_no"
                            value="{{ old('supplier_challan_no') }}"
                            placeholder="e.g. DC-98421"
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Challan Date</label>
                        <input
                            type="date"
                            name="supplier_challan_date"
                            value="{{ old('supplier_challan_date') }}"
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Received By (Person / Gatekeeper)</label>
                        <input
                            type="text"
                            name="received_by"
                            value="{{ old('received_by', auth()->user()->name) }}"
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Vehicle / Reference No</label>
                        <input
                            type="text"
                            name="reference_no"
                            value="{{ old('reference_no') }}"
                            placeholder="e.g. MH-12-AB-1234"
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Upload Delivery Challan / Proof</label>
                        <input
                            type="file"
                            name="attachment"
                            class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        >
                    </div>
                </div>

                {{-- Immediate Confirmation Flag --}}
                <div class="p-4 bg-emerald-50/60 border border-emerald-200/80 rounded-xl flex items-start gap-3">
                    <input
                        type="checkbox"
                        id="confirm_immediately"
                        name="confirm_immediately"
                        value="1"
                        checked
                        class="mt-1 h-4 w-4 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500"
                    >
                    <label for="confirm_immediately" class="text-xs text-emerald-950 font-medium cursor-pointer">
                        <span class="font-bold text-emerald-900 block">Confirm Receipt & Post Stock Movement Now</span>
                        Directly add the received physical materials into warehouse stock immediately. (Uncheck only if you want to save as an unverified draft).
                    </label>
                </div>
            </div>
        </x-ui.card>

        {{-- Line Items Card --}}
        <x-ui.card>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Received Items</h3>
                        <p class="text-xs text-slate-500">Record physically received product quantities, batches, and serial numbers.</p>
                    </div>
                    <button
                        type="button"
                        @click="addItem()"
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold transition"
                    >
                        + Add Product
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold uppercase text-[11px]">
                            <tr>
                                <th class="px-3 py-2.5 w-10 text-center">#</th>
                                <th class="px-3 py-2.5 min-w-[200px]">Product <span class="text-rose-500">*</span></th>
                                <th class="px-3 py-2.5 w-24 text-right" x-show="purchaseOrderId">PO Qty</th>
                                <th class="px-3 py-2.5 w-28 text-right">Challan Qty</th>
                                <th class="px-3 py-2.5 w-28 text-right">Received Qty <span class="text-rose-500">*</span></th>
                                <th class="px-3 py-2.5 w-28">Batch No</th>
                                <th class="px-3 py-2.5 w-32">Expiry Date</th>
                                <th class="px-3 py-2.5 w-10 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-3 py-2 text-center text-slate-400 font-medium" x-text="index + 1"></td>
                                    <td class="px-3 py-2">
                                        <select
                                            :name="`items[${index}][product_id]`"
                                            x-model="item.product_id"
                                            @change="onProductSelect(index)"
                                            required
                                            class="w-full rounded-md border-slate-300 py-1 px-2 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">Select Product</option>
                                            @foreach($products as $prod)
                                                <option value="{{ $prod->id }}" data-cost="{{ $prod->purchase_price }}" data-serial="{{ $prod->isSerialTracked() ? '1' : '0' }}">
                                                    {{ $prod->name }} ({{ $prod->sku ?? 'No SKU' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" :name="`items[${index}][purchase_order_item_id]`" :value="item.purchase_order_item_id">
                                        <input type="hidden" :name="`items[${index}][unit_cost]`" :value="item.unit_cost">

                                        {{-- Serial numbers expansion if tracked --}}
                                        <template x-if="item.is_serial_tracked">
                                            <div class="mt-2 p-2 bg-amber-50/70 border border-amber-200 rounded text-[11px] space-y-1">
                                                <div class="flex items-center justify-between text-amber-900 font-semibold">
                                                    <span>Serial Numbers (Comma or newline separated):</span>
                                                    <span class="text-slate-500" x-text="`Count: ${countSerials(item.serials)} / ${Math.round(item.received_qty || 0)}`"></span>
                                                </div>
                                                <textarea
                                                    :name="`items[${index}][serials]`"
                                                    x-model="item.serials"
                                                    placeholder="Enter serial numbers..."
                                                    rows="2"
                                                    class="w-full rounded border-amber-300 p-1.5 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                                                ></textarea>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-right text-slate-500 font-medium" x-show="purchaseOrderId">
                                        <span x-text="item.ordered_qty || '—'"></span>
                                        <input type="hidden" :name="`items[${index}][ordered_qty]`" :value="item.ordered_qty">
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <input
                                            type="number"
                                            step="0.0001"
                                            :name="`items[${index}][challan_qty]`"
                                            x-model.number="item.challan_qty"
                                            class="w-24 text-right rounded-md border-slate-300 py-1 px-2 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="0.00"
                                        >
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <input
                                            type="number"
                                            step="0.0001"
                                            :name="`items[${index}][received_qty]`"
                                            x-model.number="item.received_qty"
                                            required
                                            class="w-24 text-right rounded-md border-slate-300 py-1 px-2 text-xs font-bold text-emerald-700 bg-emerald-50/30 border-emerald-300 focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="0.00"
                                        >
                                    </td>
                                    <td class="px-3 py-2">
                                        <input
                                            type="text"
                                            :name="`items[${index}][batch_no]`"
                                            x-model="item.batch_no"
                                            placeholder="Batch #"
                                            class="w-full rounded-md border-slate-300 py-1 px-2 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </td>
                                    <td class="px-3 py-2">
                                        <input
                                            type="date"
                                            :name="`items[${index}][expiry_date]`"
                                            x-model="item.expiry_date"
                                            class="w-full rounded-md border-slate-300 py-1 px-2 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button
                                            type="button"
                                            @click="removeItem(index)"
                                            class="text-slate-400 hover:text-rose-600 transition"
                                            title="Remove row"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-50 border-t border-slate-200 font-semibold text-slate-700">
                            <tr>
                                <td colspan="2" class="px-3 py-2.5 text-right uppercase text-[11px]">Total Quantities:</td>
                                <td class="px-3 py-2.5 text-right font-medium text-slate-500" x-show="purchaseOrderId" x-text="totalOrderedQty().toFixed(2)"></td>
                                <td class="px-3 py-2.5 text-right font-medium" x-text="totalChallanQty().toFixed(2)"></td>
                                <td class="px-3 py-2.5 text-right font-bold text-emerald-700" x-text="totalReceivedQty().toFixed(2)"></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt Remarks / Inspection Notes</label>
                    <textarea
                        name="remarks"
                        rows="2"
                        placeholder="Condition of package, seals checked, damage remarks if any..."
                        class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >{{ old('remarks') }}</textarea>
                </div>
            </div>
        </x-ui.card>

        {{-- Action buttons --}}
        <div class="flex items-center justify-end gap-3">
            <x-ui.button variant="secondary" :href="route('purchasing.receipt-notes.index')">Cancel</x-ui.button>
            <x-ui.button variant="primary" type="submit">
                Save Receipt Note
            </x-ui.button>
        </div>
    </form>
</div>

@php
    $initialItems = old('items');
    if (!$initialItems) {
        if ($preselectedPo) {
            $initialItems = $preselectedPo->items->map(function($pi) {
                $rem = max(0, (float)$pi->quantity - (float)$pi->received_qty);
                return [
                    'purchase_order_item_id' => $pi->id,
                    'product_id' => $pi->product_id,
                    'ordered_qty' => (float) $pi->quantity,
                    'challan_qty' => $rem,
                    'received_qty' => $rem,
                    'unit_cost' => (float) $pi->unit_cost,
                    'batch_no' => $pi->batch_no ?? '',
                    'expiry_date' => '',
                    'serials' => '',
                    'is_serial_tracked' => $pi->product?->isSerialTracked() ?? false,
                ];
            })->values()->all();
        } else {
            $initialItems = [
                [
                    'purchase_order_item_id' => null,
                    'product_id' => '',
                    'ordered_qty' => 0,
                    'challan_qty' => '',
                    'received_qty' => '',
                    'unit_cost' => 0,
                    'batch_no' => '',
                    'expiry_date' => '',
                    'serials' => '',
                    'is_serial_tracked' => false,
                ]
            ];
        }
    }
@endphp

<script>
function receiptNoteForm() {
    return {
        supplierId: '{{ old('supplier_id', $preselectedPo?->supplier_id ?? '') }}',
        purchaseOrderId: '{{ old('purchase_order_id', $preselectedPo?->id ?? '') }}',
        items: @json($initialItems),

        addItem() {
            this.items.push({
                purchase_order_item_id: null,
                product_id: '',
                ordered_qty: 0,
                challan_qty: '',
                received_qty: '',
                unit_cost: 0,
                batch_no: '',
                expiry_date: '',
                serials: '',
                is_serial_tracked: false,
            });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },

        onSupplierChange() {
            // If supplier changes and PO was selected from a different supplier, reset PO
        },

        async onPoChange() {
            if (!this.purchaseOrderId) {
                return;
            }

            try {
                const res = await fetch(`{{ url('purchasing/receipt-notes/purchase-order') }}/${this.purchaseOrderId}/items`);
                const data = await res.json();
                if (data.items && data.items.length > 0) {
                    this.items = data.items.map(it => ({
                        purchase_order_item_id: it.purchase_order_item_id,
                        product_id: it.product_id,
                        ordered_qty: it.ordered_qty,
                        challan_qty: it.remaining_qty,
                        received_qty: it.remaining_qty,
                        unit_cost: it.unit_cost,
                        batch_no: it.batch_no || '',
                        expiry_date: '',
                        serials: '',
                        is_serial_tracked: false,
                    }));
                }
            } catch (err) {
                console.error('Failed to load PO items', err);
            }
        },

        onProductSelect(index) {
            const select = event.target;
            const option = select.options[select.selectedIndex];
            if (option) {
                this.items[index].unit_cost = parseFloat(option.dataset.cost || 0);
                this.items[index].is_serial_tracked = option.dataset.serial === '1';
            }
        },

        countSerials(text) {
            if (!text) return 0;
            return text.split(/[\r\n,]+/).map(s => s.trim()).filter(Boolean).length;
        },

        totalOrderedQty() {
            return this.items.reduce((sum, it) => sum + (parseFloat(it.ordered_qty) || 0), 0);
        },

        totalChallanQty() {
            return this.items.reduce((sum, it) => sum + (parseFloat(it.challan_qty) || 0), 0);
        },

        totalReceivedQty() {
            return this.items.reduce((sum, it) => sum + (parseFloat(it.received_qty) || 0), 0);
        }
    };
}
</script>
@endsection
