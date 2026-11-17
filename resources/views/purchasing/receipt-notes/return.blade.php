@extends('layouts.dms')
@section('title', "Return Material - {$receiptNote->receipt_note_no}")
@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <x-ui.page-header title="Return Material (Delivery Challan)" subtitle="Issue a Delivery Challan to return physically received goods back to the supplier.">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('purchasing.receipt-notes.show', $receiptNote)">
                &larr; Back to Receipt Note
            </x-ui.button>
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

    <form method="POST" action="{{ route('purchasing.receipt-notes.return.store', $receiptNote) }}" class="space-y-6">
        @csrf

        {{-- Header Summary & Return Fields --}}
        <x-ui.card>
            <div class="p-6 space-y-6">
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 font-medium block">Receipt Note:</span>
                        <span class="font-bold text-slate-800 text-sm">{{ $receiptNote->receipt_note_no }}</span>
                        <div class="text-slate-500">Dated {{ $receiptNote->receipt_date->format('d M Y') }}</div>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium block">Supplier:</span>
                        <span class="font-bold text-slate-800 text-sm">{{ $receiptNote->supplier?->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium block">Source Warehouse:</span>
                        <span class="font-bold text-slate-800 text-sm">{{ $receiptNote->warehouse?->name }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Return Date <span class="text-rose-500">*</span></label>
                        <input
                            type="date"
                            name="return_date"
                            value="{{ old('return_date', now()->toDateString()) }}"
                            required
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Delivery Challan / Return No (Optional)</label>
                        <input
                            type="text"
                            name="return_no"
                            value="{{ old('return_no') }}"
                            placeholder="Auto-generated if left blank"
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Return</label>
                        <select
                            name="reason"
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="Damaged / Defective Goods">Damaged / Defective Goods</option>
                            <option value="Excess Quantity Received">Excess Quantity Received</option>
                            <option value="Incorrect Specifications / Wrong Material">Incorrect Specifications / Wrong Material</option>
                            <option value="Failed Quality Inspection">Failed Quality Inspection</option>
                            <option value="Expired / Near Expiry Goods">Expired / Near Expiry Goods</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks / Return Description</label>
                        <textarea
                            name="remarks"
                            rows="2"
                            placeholder="Details of defects, transporter details for returning goods..."
                            class="w-full rounded-lg border-slate-300 py-2 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >{{ old('remarks') }}</textarea>
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- Return Items Table --}}
        <x-ui.card>
            <div class="p-6 space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Select Quantities to Return</h3>
                    <p class="text-xs text-slate-500">Specifying return quantities will automatically decrease warehouse stock.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold uppercase text-[11px]">
                            <tr>
                                <th class="px-3 py-2.5">Product</th>
                                <th class="px-3 py-2.5 text-center">UOM</th>
                                <th class="px-3 py-2.5 text-right">Received Qty</th>
                                <th class="px-3 py-2.5 text-right">Already Returned</th>
                                <th class="px-3 py-2.5 text-right font-semibold text-slate-900">Returnable Qty</th>
                                <th class="px-3 py-2.5 text-right font-bold text-orange-700 w-36">Return Qty</th>
                                <th class="px-3 py-2.5 w-48">Item Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($receiptNote->items as $item)
                                @php
                                    $returnable = $item->pending_return_qty;
                                @endphp
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-3 py-3">
                                        <div class="font-bold text-slate-900">{{ $item->product?->name }}</div>
                                        <div class="text-[11px] text-slate-400">SKU: {{ $item->product?->sku ?? '—' }}</div>
                                        @if($item->batch_no)
                                            <div class="text-[11px] text-indigo-700">Batch: {{ $item->batch_no }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center font-medium text-slate-700">
                                        {{ $item->uom?->short_name ?? $item->product?->unit ?? 'PCS' }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-medium text-slate-700">
                                        {{ number_format((float) $item->received_qty, 2) }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-medium text-slate-400">
                                        {{ number_format((float) $item->returned_qty, 2) }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-bold text-slate-900">
                                        {{ number_format($returnable, 2) }}
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <input
                                            type="number"
                                            step="0.0001"
                                            name="items[{{ $item->id }}][quantity]"
                                            value="{{ old("items.{$item->id}.quantity", 0) }}"
                                            min="0"
                                            max="{{ $returnable }}"
                                            class="w-28 text-right rounded-md border-orange-300 py-1 px-2 text-xs font-bold text-orange-800 bg-orange-50/40 focus:border-orange-500 focus:ring-orange-500"
                                            placeholder="0.00"
                                            {{ $returnable <= 0.0001 ? 'disabled' : '' }}
                                        >
                                    </td>
                                    <td class="px-3 py-3">
                                        <input
                                            type="text"
                                            name="items[{{ $item->id }}][remarks]"
                                            value="{{ old("items.{$item->id}.remarks") }}"
                                            placeholder="Damage notes..."
                                            class="w-full rounded-md border-slate-300 py-1 px-2 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button variant="secondary" :href="route('purchasing.receipt-notes.show', $receiptNote)">Cancel</x-ui.button>
            <button
                type="submit"
                class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-semibold shadow-sm transition"
            >
                Confirm Return & Decrement Stock
            </button>
        </div>
    </form>
</div>
@endsection
