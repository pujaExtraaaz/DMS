@extends('layouts.dms')
@section('title', "Edit Receipt Note {$receiptNote->receipt_note_no}")
@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="receiptNoteEditForm()">
    <x-ui.page-header title="Edit Receipt Note {{ $receiptNote->receipt_note_no }}" subtitle="Update draft receipt note details and items.">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('purchasing.receipt-notes.show', $receiptNote)">Cancel</x-ui.button>
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

    <form method="POST" action="{{ route('purchasing.receipt-notes.update', $receiptNote) }}" class="space-y-6">
        @csrf
        @method('PUT')

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
                            required
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $receiptNote->supplier_id) == $supplier->id)>
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
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected(old('warehouse_id', $receiptNote->warehouse_id) == $wh->id)>
                                    {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt Date <span class="text-rose-500">*</span></label>
                        <input
                            type="date"
                            name="receipt_date"
                            value="{{ old('receipt_date', $receiptNote->receipt_date->toDateString()) }}"
                            required
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Challan / Delivery Note No</label>
                        <input
                            type="text"
                            name="supplier_challan_no"
                            value="{{ old('supplier_challan_no', $receiptNote->supplier_challan_no) }}"
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Challan Date</label>
                        <input
                            type="date"
                            name="supplier_challan_date"
                            value="{{ old('supplier_challan_date', $receiptNote->supplier_challan_date?->toDateString()) }}"
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Received By</label>
                        <input
                            type="text"
                            name="received_by"
                            value="{{ old('received_by', $receiptNote->received_by) }}"
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Vehicle / Reference No</label>
                        <input
                            type="text"
                            name="reference_no"
                            value="{{ old('reference_no', $receiptNote->reference_no) }}"
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks</label>
                        <textarea
                            name="remarks"
                            rows="1"
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >{{ old('remarks', $receiptNote->remarks) }}</textarea>
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- Line Items --}}
        <x-ui.card>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Received Items</h3>
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
                                            required
                                            class="w-full rounded-md border-slate-300 py-1 px-2 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">Select Product</option>
                                            @foreach($products as $prod)
                                                <option value="{{ $prod->id }}">
                                                    {{ $prod->name }} ({{ $prod->sku ?? 'No SKU' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <input
                                            type="number"
                                            step="0.0001"
                                            :name="`items[${index}][challan_qty]`"
                                            x-model.number="item.challan_qty"
                                            class="w-24 text-right rounded-md border-slate-300 py-1 px-2 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
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
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button variant="secondary" :href="route('purchasing.receipt-notes.show', $receiptNote)">Cancel</x-ui.button>
            <x-ui.button variant="primary" type="submit">Update Receipt Note</x-ui.button>
        </div>
    </form>
</div>

@php
    $editItems = old('items');
    if (!$editItems) {
        $editItems = $receiptNote->items->map(fn($it) => [
            'product_id' => $it->product_id,
            'challan_qty' => (float)$it->challan_qty,
            'received_qty' => (float)$it->received_qty,
            'batch_no' => $it->batch_no ?? '',
            'expiry_date' => $it->expiry_date?->toDateString() ?? '',
        ])->values()->all();
    }
@endphp

<script>
function receiptNoteEditForm() {
    return {
        items: @json($editItems),

        addItem() {
            this.items.push({
                product_id: '',
                challan_qty: '',
                received_qty: '',
                batch_no: '',
                expiry_date: '',
            });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        }
    };
}
</script>
@endsection
