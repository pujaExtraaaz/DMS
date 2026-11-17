@extends('layouts.dms')
@section('title', "Receipt Note {$receiptNote->receipt_note_no}")
@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold text-slate-900">{{ $receiptNote->receipt_note_no }}</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $receiptNote->status_badge_class }}">
                    {{ $receiptNote->status_label }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Created by {{ $receiptNote->creator?->name ?? 'System' }} on {{ $receiptNote->created_at->format('d M Y, h:i A') }}
                @if($receiptNote->confirmed_at)
                    · Confirmed on {{ $receiptNote->confirmed_at->format('d M Y, h:i A') }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-ui.button variant="secondary" :href="route('purchasing.receipt-notes.index')">
                &larr; Back to List
            </x-ui.button>

            <a
                href="{{ route('purchasing.receipt-notes.preview', $receiptNote) }}"
                target="_blank"
                class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium shadow-sm transition inline-flex items-center gap-1.5"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / View Document
            </a>

            <a
                href="{{ route('purchasing.receipt-notes.export', [$receiptNote, 'pdf']) }}"
                class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium shadow-sm transition inline-flex items-center gap-1.5"
            >
                <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export PDF
            </a>

            @if($receiptNote->isDraft())
                <form method="POST" action="{{ route('purchasing.receipt-notes.confirm', $receiptNote) }}" onsubmit="return confirm('Confirm receipt and add physical material to warehouse stock?');">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm transition inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Confirm Receipt (Add Stock)
                    </button>
                </form>
                <x-ui.button variant="secondary" :href="route('purchasing.receipt-notes.edit', $receiptNote)">
                    Edit
                </x-ui.button>
            @endif

            @if($receiptNote->canBeReturned())
                <a
                    href="{{ route('purchasing.receipt-notes.return', $receiptNote) }}"
                    class="px-3 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-semibold shadow-sm transition inline-flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    Return Material (Delivery Challan)
                </a>
            @endif

            @if($receiptNote->canBeInvoiced())
                <a
                    href="{{ route('purchasing.invoices.create', ['supplier_id' => $receiptNote->supplier_id, 'receipt_note_id' => $receiptNote->id, 'warehouse_id' => $receiptNote->warehouse_id]) }}"
                    class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition inline-flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Create Purchase Invoice
                </a>
            @endif

            @if($receiptNote->canBeCancelled() || ($receiptNote->isReceived() && $receiptNote->total_invoiced_qty == 0 && $receiptNote->total_returned_qty == 0))
                <form method="POST" action="{{ route('purchasing.receipt-notes.cancel', $receiptNote) }}" onsubmit="return confirm('Are you sure you want to cancel this Receipt Note? This will revert any physical stock posted.');">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold shadow-sm transition">
                        Cancel Note
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Details Card --}}
    <x-ui.card>
        <div class="p-6 space-y-6">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider border-b border-slate-100 pb-2">
                Document Summary
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-xs">
                <div>
                    <span class="block text-slate-400 font-medium">Receipt Date:</span>
                    <span class="font-semibold text-slate-800 text-sm">{{ $receiptNote->receipt_date->format('d M Y') }}</span>
                </div>
                <div>
                    <span class="block text-slate-400 font-medium">Supplier:</span>
                    <span class="font-semibold text-slate-800 text-sm">{{ $receiptNote->supplier?->name }}</span>
                    @if($receiptNote->supplier?->gst_number)
                        <div class="text-[11px] text-slate-500 font-mono">GSTIN: {{ $receiptNote->supplier->gst_number }}</div>
                    @endif
                </div>
                <div>
                    <span class="block text-slate-400 font-medium">Destination Warehouse:</span>
                    <span class="font-semibold text-slate-800 text-sm">{{ $receiptNote->warehouse?->name }}</span>
                    <div class="text-[11px] text-slate-500">{{ $receiptNote->warehouse?->location ?? 'Main' }}</div>
                </div>
                <div>
                    <span class="block text-slate-400 font-medium">Linked Purchase Order:</span>
                    @if($receiptNote->purchaseOrder)
                        <a href="{{ route('purchasing.orders.show', $receiptNote->purchaseOrder) }}" class="font-semibold text-indigo-600 hover:underline text-sm">
                            {{ $receiptNote->purchaseOrder->po_no }}
                        </a>
                        <div class="text-[11px] text-slate-500">{{ $receiptNote->purchaseOrder->po_date->format('d M Y') }}</div>
                    @else
                        <span class="text-slate-400 text-sm">— (Direct Receipt)</span>
                    @endif
                </div>

                <div>
                    <span class="block text-slate-400 font-medium">Supplier Challan No:</span>
                    <span class="font-semibold text-slate-800 text-sm">{{ $receiptNote->supplier_challan_no ?: '—' }}</span>
                </div>
                <div>
                    <span class="block text-slate-400 font-medium">Supplier Challan Date:</span>
                    <span class="font-semibold text-slate-800 text-sm">{{ $receiptNote->supplier_challan_date ? $receiptNote->supplier_challan_date->format('d M Y') : '—' }}</span>
                </div>
                <div>
                    <span class="block text-slate-400 font-medium">Received By:</span>
                    <span class="font-semibold text-slate-800 text-sm">{{ $receiptNote->received_by ?: '—' }}</span>
                </div>
                <div>
                    <span class="block text-slate-400 font-medium">Vehicle / Ref No:</span>
                    <span class="font-semibold text-slate-800 text-sm">{{ $receiptNote->reference_no ?: '—' }}</span>
                </div>
            </div>

            @if($receiptNote->remarks)
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs">
                    <span class="font-bold text-slate-700 block mb-0.5">Remarks / Inspection Notes:</span>
                    <p class="text-slate-600 whitespace-pre-line">{{ $receiptNote->remarks }}</p>
                </div>
            @endif

            @if($receiptNote->attachment_path)
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-slate-400 font-medium">Attached Delivery Document:</span>
                    <a href="{{ asset('storage/' . $receiptNote->attachment_path) }}" target="_blank" class="text-indigo-600 hover:underline font-semibold inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        View Attachment
                    </a>
                </div>
            @endif
        </div>
    </x-ui.card>

    {{-- Items Card --}}
    <x-ui.card>
        <div class="p-6 space-y-4">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider border-b border-slate-100 pb-2">
                Physical Material Receipt Lines
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold uppercase text-[11px]">
                        <tr>
                            <th class="px-3 py-2.5 w-10 text-center">#</th>
                            <th class="px-3 py-2.5">Product & Details</th>
                            <th class="px-3 py-2.5 text-center">UOM</th>
                            @if($receiptNote->purchase_order_id)
                                <th class="px-3 py-2.5 text-right">PO Qty</th>
                            @endif
                            <th class="px-3 py-2.5 text-right">Challan Qty</th>
                            <th class="px-3 py-2.5 text-right font-bold text-emerald-700">Received Qty</th>
                            <th class="px-3 py-2.5 text-right text-sky-700">Invoiced Qty</th>
                            <th class="px-3 py-2.5 text-right text-orange-700">Returned Qty</th>
                            <th class="px-3 py-2.5 text-right font-bold text-slate-900">Pending Invoice</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($receiptNote->items as $idx => $item)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-3 py-3 text-center text-slate-400 font-medium">{{ $idx + 1 }}</td>
                                <td class="px-3 py-3">
                                    <div class="font-bold text-slate-900">{{ $item->product?->name }}</div>
                                    <div class="text-[11px] text-slate-400">SKU: {{ $item->product?->sku ?? '—' }}</div>
                                    @if($item->batch_no)
                                        <div class="text-[11px] text-indigo-700 mt-0.5">
                                            Batch: <span class="font-mono font-semibold">{{ $item->batch_no }}</span>
                                            @if($item->expiry_date)
                                                (Exp: {{ $item->expiry_date->format('d/m/Y') }})
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Serial Numbers --}}
                                    @if($item->serials->isNotEmpty())
                                        <div class="mt-1.5 flex flex-wrap gap-1 items-center">
                                            <span class="text-[10px] uppercase font-bold text-slate-400 mr-1">Serials:</span>
                                            @foreach($item->serials as $serial)
                                                <span class="inline-block px-1.5 py-0.5 bg-slate-100 text-slate-700 font-mono text-[10px] rounded border border-slate-200">
                                                    {{ $serial->serial_number }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center font-medium text-slate-700">
                                    {{ $item->uom?->short_name ?? $item->product?->unit ?? 'PCS' }}
                                </td>
                                @if($receiptNote->purchase_order_id)
                                    <td class="px-3 py-3 text-right text-slate-500 font-medium">
                                        {{ number_format((float) $item->ordered_qty, 2) }}
                                    </td>
                                @endif
                                <td class="px-3 py-3 text-right text-slate-600">
                                    {{ number_format((float) $item->challan_qty, 2) }}
                                </td>
                                <td class="px-3 py-3 text-right font-bold text-emerald-700 text-sm">
                                    {{ number_format((float) $item->received_qty, 2) }}
                                </td>
                                <td class="px-3 py-3 text-right font-medium text-sky-700">
                                    {{ number_format((float) $item->invoiced_qty, 2) }}
                                </td>
                                <td class="px-3 py-3 text-right font-medium text-orange-700">
                                    {{ number_format((float) $item->returned_qty, 2) }}
                                </td>
                                <td class="px-3 py-3 text-right font-bold text-slate-900">
                                    {{ number_format($item->pending_invoice_qty, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200 font-semibold text-slate-700">
                        <tr>
                            <td colspan="{{ $receiptNote->purchase_order_id ? 3 : 2 }}" class="px-3 py-3 text-right uppercase text-[11px]">
                                Total Quantities:
                            </td>
                            @if($receiptNote->purchase_order_id)
                                <td class="px-3 py-3 text-right text-slate-500">{{ number_format($receiptNote->items->sum('ordered_qty'), 2) }}</td>
                            @endif
                            <td class="px-3 py-3 text-right">{{ number_format($receiptNote->items->sum('challan_qty'), 2) }}</td>
                            <td class="px-3 py-3 text-right font-bold text-emerald-700">{{ number_format($receiptNote->total_received_qty, 2) }}</td>
                            <td class="px-3 py-3 text-right font-medium text-sky-700">{{ number_format($receiptNote->total_invoiced_qty, 2) }}</td>
                            <td class="px-3 py-3 text-right font-medium text-orange-700">{{ number_format($receiptNote->total_returned_qty, 2) }}</td>
                            <td class="px-3 py-3 text-right font-bold text-slate-900">{{ number_format($receiptNote->pending_invoice_qty, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </x-ui.card>

    {{-- Returns Section (Delivery Challan) if any --}}
    @if($receiptNote->returns->isNotEmpty())
        <x-ui.card>
            <div class="p-6 space-y-4">
                <h3 class="text-xs font-bold text-orange-800 uppercase tracking-wider border-b border-orange-100 pb-2">
                    Material Returns History (Delivery Challans Issued)
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-orange-50/50 border-b border-orange-200 text-orange-900 font-semibold uppercase text-[11px]">
                            <tr>
                                <th class="px-3 py-2">Return No</th>
                                <th class="px-3 py-2">Return Date</th>
                                <th class="px-3 py-2">Reason</th>
                                <th class="px-3 py-2">Items Returned</th>
                                <th class="px-3 py-2">Processed By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($receiptNote->returns as $ret)
                                <tr>
                                    <td class="px-3 py-2.5 font-bold text-orange-700 font-mono">{{ $ret->return_no }}</td>
                                    <td class="px-3 py-2.5">{{ $ret->return_date->format('d M Y') }}</td>
                                    <td class="px-3 py-2.5 text-slate-700">{{ $ret->reason ?: '—' }}</td>
                                    <td class="px-3 py-2.5">
                                        @foreach($ret->items as $ri)
                                            <div>{{ $ri->product?->name }}: <span class="font-bold text-slate-900">{{ number_format((float) $ri->quantity, 2) }}</span> {{ $ri->uom?->short_name ?? 'PCS' }}</div>
                                        @endforeach
                                    </td>
                                    <td class="px-3 py-2.5 text-slate-500">{{ $ret->creator?->name ?? 'System' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </x-ui.card>
    @endif

    {{-- Linked Purchase Invoices if any --}}
    @if($receiptNote->invoices->isNotEmpty())
        <x-ui.card>
            <div class="p-6 space-y-4">
                <h3 class="text-xs font-bold text-indigo-800 uppercase tracking-wider border-b border-indigo-100 pb-2">
                    Linked Purchase Invoices (Billed Against This Receipt Note)
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-indigo-50/50 border-b border-indigo-200 text-indigo-900 font-semibold uppercase text-[11px]">
                            <tr>
                                <th class="px-3 py-2">Invoice No</th>
                                <th class="px-3 py-2">Invoice Date</th>
                                <th class="px-3 py-2">Supplier Invoice No</th>
                                <th class="px-3 py-2 text-right">Grand Total</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($receiptNote->invoices as $inv)
                                <tr>
                                    <td class="px-3 py-2.5 font-bold text-indigo-600">
                                        <a href="{{ route('purchasing.invoices.show', $inv) }}" class="hover:underline">
                                            {{ $inv->invoice_no }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2.5">{{ $inv->invoice_date }}</td>
                                    <td class="px-3 py-2.5">{{ $inv->supplier_invoice_no ?: '—' }}</td>
                                    <td class="px-3 py-2.5 text-right font-bold text-slate-900">₹{{ number_format((float)$inv->grand_total, 2) }}</td>
                                    <td class="px-3 py-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                            {{ ucfirst($inv->status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-right">
                                        <a href="{{ route('purchasing.invoices.show', $inv) }}" class="text-xs text-indigo-600 hover:underline font-semibold">
                                            View Invoice
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </x-ui.card>
    @endif
</div>
@endsection
