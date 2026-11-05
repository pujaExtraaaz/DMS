@extends('layouts.dms')
@section('title', $invoice->invoice_no)
@section('content')
<div x-data="{
    cancelEInvoiceModal: false,
    cancelEwayModal: false,
    generateEwayModal: false,
    updateVehicleModal: false,
    printSettingsModal: false,
    printCopyType: 'all',
    printCopies: 3,
    cancelReason: '1',
    cancelRemarks: '',
    ewayCancelReason: '1',
    ewayCancelRemarks: '',
    vehicleNo: @js($invoice->vehicle_no ?? ''),
    vehicleType: @js($invoice->vehicle_type ?? 'R'),
    vehicleReason: 'Breakdown / Transshipment',
    distanceKm: @js($invoice->distance_km ?? 50),
    transportMode: @js($invoice->transport_mode ?? '1'),
    transporterId: @js($invoice->transporter_id ?? ''),
    transporterName: @js($invoice->transporter_name ?? ''),
}">

<x-ui.page-header :title="$invoice->invoice_no" :description="$invoice->customer->name">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('invoices.index')">Back</x-ui.button>
        <x-ui.button variant="secondary" :href="route('invoices.edit', $invoice)">Edit</x-ui.button>
        <button type="button" @click="printSettingsModal = true" class="inline-flex items-center px-3.5 py-2 border border-slate-300 shadow-sm text-sm font-semibold rounded-lg text-slate-700 bg-white hover:bg-slate-50 transition">
            <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        <div class="relative inline-block text-left" x-data="{ exportOpen: false }">
            <button type="button" @click="exportOpen = !exportOpen" @click.away="exportOpen = false" class="inline-flex items-center px-3.5 py-2 border border-slate-300 shadow-sm text-sm font-semibold rounded-lg text-slate-700 bg-white hover:bg-slate-50 transition">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
                <svg class="w-3 h-3 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="exportOpen" class="origin-top-right absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 py-1" style="display: none;">
                <a href="{{ route('invoices.export', ['invoice' => $invoice, 'format' => 'pdf']) }}" class="flex items-center px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-100 font-medium">
                    <svg class="w-4 h-4 mr-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF Document
                </a>
                <a href="{{ route('invoices.export', ['invoice' => $invoice, 'format' => 'xlsx']) }}" class="flex items-center px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-100 font-medium">
                    <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel (.xlsx)
                </a>
                <a href="{{ route('invoices.export', ['invoice' => $invoice, 'format' => 'csv']) }}" class="flex items-center px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-100 font-medium">
                    <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    CSV File
                </a>
            </div>
        </div>
        <x-ui.button variant="secondary" :href="route('invoices.preview', $invoice)">Preview</x-ui.button>
        <x-ui.button variant="secondary" :href="route('invoices.e-invoice.document', $invoice)">E-Invoice Doc</x-ui.button>
        <x-ui.button variant="secondary" :href="route('invoices.eway.document', $invoice)">E-Way Bill Doc</x-ui.button>
        <form method="POST" action="{{ route('payment-links.create', $invoice) }}" class="inline">@csrf<x-ui.button type="submit" variant="secondary">Payment Link</x-ui.button></form>
        <x-ui.button variant="primary" :href="route('payments.create', ['invoice_id' => $invoice->id])">Record Payment</x-ui.button>
    </x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <x-ui.card class="lg:col-span-2" title="Items">
        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Product</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Price</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Total</th>
                </tr>
            </x-slot>
            @foreach($invoice->items as $item)
                <tr>
                    <td class="px-6 py-4 text-sm">{{ $item->product?->name ?? $item->item_name }}</td>
                    <td class="px-6 py-4 text-sm text-right font-mono">{{ $item->quantity }} {{ $item->uom?->code }}</td>
                    <td class="px-6 py-4 text-sm text-right font-mono">₹{{ number_format($item->unit_price, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-right font-mono font-bold">₹{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </x-ui.table>

        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            <h4 class="text-xs font-bold tracking-wider text-slate-500 mb-2 uppercase">Addresses</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="p-3 bg-white rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase text-slate-500">Billing Address</span>
                        @if($invoice->bill_to_place)
                            <span class="text-[11px] font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded">Place: {{ $invoice->bill_to_place }}</span>
                        @endif
                    </div>
                    <div class="mt-1 font-medium text-slate-800 whitespace-pre-line text-xs">{{ $invoice->billing_address ?: ($invoice->customer->address ?: '—') }}</div>
                </div>
                <div class="p-3 bg-white rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase text-slate-500">Delivery Address</span>
                        @if($invoice->ship_to_place)
                            <span class="text-[11px] font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded">Place: {{ $invoice->ship_to_place }}</span>
                        @endif
                    </div>
                    <div class="mt-1 font-medium text-slate-800 whitespace-pre-line text-xs">{{ $invoice->shipping_address ?: ($invoice->billing_address ?: ($invoice->customer->shipping_address ?: ($invoice->customer->address ?: '—'))) }}</div>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-slate-100 bg-white">
            <h4 class="text-xs font-bold tracking-wider text-slate-700 uppercase mb-3">Delivery, Dispatch &amp; Order Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                    <div class="font-bold text-slate-700 border-b border-slate-200 pb-1">Delivery Note</div>
                    <div><span class="text-slate-500">Delivery Note No:</span> <span class="font-medium text-slate-800">{{ $invoice->delivery_note_na ? 'Not Applicable' : ($invoice->delivery_note_no ?: '—') }}</span></div>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                    <div class="font-bold text-slate-700 border-b border-slate-200 pb-1">Dispatch Details</div>
                    <div><span class="text-slate-500">Doc No:</span> <span class="font-medium text-slate-800">{{ $invoice->dispatch_doc_no ?: '—' }}</span></div>
                    <div><span class="text-slate-500">Dispatched Through:</span> <span class="font-medium text-slate-800">{{ $invoice->dispatched_through ?: ($invoice->transport_mode ?: '—') }}</span></div>
                    <div><span class="text-slate-500">Destination:</span> <span class="font-medium text-slate-800">{{ $invoice->destination ?: '—' }}</span></div>
                    <div><span class="text-slate-500">Carrier / Agent:</span> <span class="font-medium text-slate-800">{{ $invoice->carrier_name ?: '—' }}</span></div>
                    <div><span class="text-slate-500">Bill of Lading / LR:</span> <span class="font-medium text-slate-800">{{ $invoice->lr_rr_no ?: '—' }} @if($invoice->lr_rr_date)({{ $invoice->lr_rr_date->format('d/m/Y') }})@endif</span></div>
                    <div><span class="text-slate-500">Motor Vehicle:</span> <span class="font-mono font-medium text-slate-800">{{ $invoice->vehicle_no ?: '—' }}</span></div>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                    <div class="font-bold text-slate-700 border-b border-slate-200 pb-1">Order Details</div>
                    <div><span class="text-slate-500">Order No(s):</span> <span class="font-medium text-slate-800">{{ $invoice->order_no ?: ($invoice->order?->order_no ?: '—') }}</span></div>
                    <div><span class="text-slate-500">Payment Terms:</span> <span class="font-medium text-slate-800">{{ $invoice->payment_terms ?: '—' }}</span></div>
                    <div><span class="text-slate-500">Other References:</span> <span class="font-medium text-slate-800">{{ $invoice->other_references ?: ($invoice->reference_no ?: '—') }}</span></div>
                    <div class="pt-1">
                        <span class="text-slate-500 block mb-0.5">Terms of Delivery:</span>
                        @if($invoice->terms_of_delivery)
                            <div class="font-medium text-slate-800 whitespace-pre-line bg-white p-2 rounded border border-slate-200 text-xs leading-relaxed">{{ $invoice->terms_of_delivery }}</div>
                        @else
                            <span class="font-medium text-slate-800">—</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($invoice->consignor_name || $invoice->consignee_name || $invoice->eway_bill_no || $invoice->transporter_name)
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            <h4 class="text-xs font-bold tracking-wider text-slate-700 uppercase mb-3">Consignor, Consignee &amp; Transport Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                {{-- Consignor (From) --}}
                <div class="p-3 bg-white rounded-lg border border-slate-200 space-y-1">
                    <div class="font-bold text-slate-800 border-b border-slate-100 pb-1 flex items-center justify-between">
                        <span>Consignor (From - Seller)</span>
                        @if($invoice->consignor_gstin)
                            <span class="font-mono text-[10px] text-indigo-700">{{ $invoice->consignor_gstin }}</span>
                        @endif
                    </div>
                    <div class="font-medium text-slate-900">{{ $invoice->consignor_name ?: ($invoice->company?->name ?? '—') }}</div>
                    <div class="text-slate-600">
                        {{ $invoice->consignor_address_line_1 }}
                        @if($invoice->consignor_address_line_2), {{ $invoice->consignor_address_line_2 }}@endif
                    </div>
                    <div class="text-slate-600">
                        {{ $invoice->consignor_city }}@if($invoice->consignor_city && $invoice->consignor_state), @endif{{ $invoice->consignor_state }}@if($invoice->consignor_pincode) - {{ $invoice->consignor_pincode }}@endif
                    </div>
                </div>

                {{-- Consignee (To) --}}
                <div class="p-3 bg-white rounded-lg border border-slate-200 space-y-1">
                    <div class="font-bold text-slate-800 border-b border-slate-100 pb-1 flex items-center justify-between">
                        <span>Consignee (To - Delivery)</span>
                        @if($invoice->consignee_gstin)
                            <span class="font-mono text-[10px] text-emerald-700">{{ $invoice->consignee_gstin }}</span>
                        @endif
                    </div>
                    <div class="font-medium text-slate-900">{{ $invoice->consignee_name ?: ($invoice->customer->name ?? '—') }}</div>
                    <div class="text-slate-600">
                        {{ $invoice->consignee_address_line_1 ?: ($invoice->shipping_address ?: '—') }}
                        @if($invoice->consignee_address_line_2), {{ $invoice->consignee_address_line_2 }}@endif
                    </div>
                    <div class="text-slate-600">
                        {{ $invoice->consignee_city }}@if($invoice->consignee_city && $invoice->consignee_state), @endif{{ $invoice->consignee_state }}@if($invoice->consignee_pincode) - {{ $invoice->consignee_pincode }}@endif
                    </div>
                </div>

                {{-- Transport & e-Way Bill --}}
                <div class="p-3 bg-white rounded-lg border border-slate-200 space-y-1">
                    <div class="font-bold text-slate-800 border-b border-slate-100 pb-1 flex items-center justify-between">
                        <span>Transport &amp; e-Way Bill</span>
                        <span class="font-semibold text-[10px] text-slate-500 uppercase">{{ $invoice->eway_supply_type ?: 'Outward' }}</span>
                    </div>
                    <div><span class="text-slate-500">e-Way Bill No:</span> <span class="font-mono font-bold text-indigo-700">{{ $invoice->eway_bill_no ?: ($invoice->eWayBill?->eway_bill_no ?: '—') }}</span></div>
                    @if($invoice->eway_bill_date || $invoice->eWayBill?->ewb_date)
                        <div><span class="text-slate-500">Date:</span> <span class="font-medium text-slate-800">{{ ($invoice->eway_bill_date ?: $invoice->eWayBill?->ewb_date)?->format('d/m/Y') }}</span></div>
                    @endif
                    <div><span class="text-slate-500">Transporter:</span> <span class="font-medium text-slate-800">{{ $invoice->transporter_name ?: '—' }} @if($invoice->transporter_id)({{ $invoice->transporter_id }})@endif</span></div>
                    <div><span class="text-slate-500">Mode:</span> <span class="font-medium text-slate-800">{{ $invoice->transport_mode ?: '—' }}</span></div>
                    @if($invoice->lr_rr_no)
                        <div><span class="text-slate-500">Doc / LR-RR:</span> <span class="font-medium text-slate-800">{{ $invoice->lr_rr_no }} @if($invoice->lr_rr_date)({{ $invoice->lr_rr_date->format('d/m/Y') }})@endif</span></div>
                    @endif
                    <div><span class="text-slate-500">Distance:</span> <span class="font-medium text-slate-800">{{ $invoice->distance_km ? $invoice->distance_km . ' KM' : '—' }}</span></div>
                    <div><span class="text-slate-500">Vehicle Type:</span> <span class="font-medium text-slate-800">{{ $invoice->vehicle_type === 'O' ? 'Over Dimensional Cargo (ODC)' : 'Regular Cargo (R)' }}</span></div>
                </div>
            </div>
        </div>
        @endif
    </x-ui.card>

    {{-- Summary Card --}}
    <x-ui.card title="Summary">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt>Status</dt><dd><x-ui.badge>{{ ucfirst($invoice->status) }}</x-ui.badge></dd></div>
            <div class="flex justify-between"><dt>Date</dt><dd>{{ $invoice->invoice_date->format('d M Y') }}</dd></div>
            <div class="flex justify-between"><dt>Salesperson</dt><dd class="font-medium text-slate-800">{{ $invoice->salesperson?->name ?? '—' }}</dd></div>
            @if($invoice->price_type)
                <div class="flex justify-between"><dt>Pricing</dt><dd class="font-medium text-slate-700 capitalize">{{ $invoice->price_type }} Tax</dd></div>
            @endif
            @if($invoice->scheme)
                <div class="flex justify-between border-t pt-2"><dt class="text-slate-500">Scheme</dt><dd class="font-medium text-indigo-700">{{ $invoice->scheme->name }} ({{ $invoice->scheme->code }})</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Scheme Rebate</dt><dd class="font-semibold text-emerald-600">₹{{ number_format($invoice->scheme_benefit_amount, 2) }}</dd></div>
            @endif
            <div class="flex justify-between border-t pt-2"><dt class="text-slate-500">Subtotal</dt><dd class="font-mono font-medium">₹{{ number_format($invoice->subtotal, 2) }}</dd></div>
            @if($invoice->taxable_amount !== null && (float)$invoice->taxable_amount > 0)
                <div class="flex justify-between"><dt class="text-slate-500">Taxable Value</dt><dd class="font-mono font-medium">₹{{ number_format($invoice->taxable_amount, 2) }}</dd></div>
            @endif
            @if(($invoice->tax_type ?? '') === 'intra' || (!$invoice->tax_type && ($invoice->cgst_amount > 0 || $invoice->sgst_amount > 0)))
                <div class="flex justify-between"><dt class="text-slate-500">CGST</dt><dd class="font-mono font-medium">₹{{ number_format($invoice->cgst_amount, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">SGST</dt><dd class="font-mono font-medium">₹{{ number_format($invoice->sgst_amount, 2) }}</dd></div>
            @elseif(($invoice->tax_type ?? '') === 'inter' || $invoice->igst_amount > 0)
                <div class="flex justify-between"><dt class="text-slate-500">IGST</dt><dd class="font-mono font-medium">₹{{ number_format($invoice->igst_amount, 2) }}</dd></div>
            @else
                <div class="flex justify-between"><dt class="text-slate-500">Tax</dt><dd class="font-mono font-medium">₹{{ number_format($invoice->tax_amount, 2) }}</dd></div>
            @endif
            @if(!empty($invoice->round_off) && (float)$invoice->round_off != 0)
                <div class="flex justify-between"><dt class="text-slate-500">Round Off</dt><dd class="font-mono font-medium">₹{{ number_format($invoice->round_off, 2) }}</dd></div>
            @endif
            <div class="flex justify-between border-t pt-2"><dt class="font-bold text-slate-800">Grand Total</dt><dd class="font-bold font-mono text-indigo-700">₹{{ number_format($invoice->grand_total, 2) }}</dd></div>
            <div class="flex justify-between"><dt>Paid</dt><dd class="font-mono">₹{{ number_format($invoice->paid_amount, 2) }}</dd></div>
            <div class="flex justify-between"><dt>Outstanding</dt><dd class="font-mono font-semibold text-rose-600">₹{{ number_format($invoice->grand_total - $invoice->paid_amount, 2) }}</dd></div>
        </dl>
        <div class="mt-4 space-y-2">
            <form method="POST" action="{{ route('communications.send-invoice', $invoice) }}">@csrf<x-ui.button type="submit" variant="ghost" class="w-full">Send WhatsApp</x-ui.button></form>
            <form method="POST" action="{{ route('communications.send-reminder', $invoice) }}">@csrf<x-ui.button type="submit" variant="ghost" class="w-full">Send Reminder</x-ui.button></form>
        </div>
    </x-ui.card>
</div>

{{-- E-Invoice & E-Way Bill Compliance Management --}}
<div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- 1. E-Invoice (IRN) Card --}}
    <x-ui.card>
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-sm font-bold text-slate-800">GST E-Invoice (IRN)</h3>
            </div>
            @php
                $eiStatus = $invoice->eInvoice?->status ?? $invoice->einvoice_status ?? 'pending';
                if ($invoice->eInvoice && $invoice->eInvoice->status === 'cancelled') $eiStatus = 'cancelled';
                elseif ($invoice->eInvoice && in_array($invoice->eInvoice->status, ['generated', 'active'])) $eiStatus = 'generated';
                elseif (!($invoice->eInvoice) && ($eInvoiceEligibility['status'] ?? '') === 'not_applicable') $eiStatus = 'not_applicable';
            @endphp
            <div>
                @if($eiStatus === 'generated')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                        ● Generated (Active)
                    </span>
                @elseif($eiStatus === 'cancelled')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                        ✕ Cancelled
                    </span>
                @elseif($eiStatus === 'failed')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                        ⚠ Generation Failed
                    </span>
                @elseif($eiStatus === 'not_applicable')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                        Not Applicable
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                        Pending Generation
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-4 space-y-3 text-xs">
            @if($eiStatus === 'generated' && $invoice->eInvoice)
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2">
                    <div>
                        <span class="text-slate-500 block text-[11px] font-medium">Invoice Reference Number (IRN):</span>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <span class="font-mono font-bold text-slate-900 break-all text-xs">{{ $invoice->eInvoice->irn }}</span>
                            <button type="button" onclick="navigator.clipboard.writeText('{{ $invoice->eInvoice->irn }}'); alert('IRN copied to clipboard!');"
                                    class="text-indigo-600 hover:text-indigo-800 font-semibold shrink-0 text-[11px]">Copy</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200">
                        <div>
                            <span class="text-slate-500 block text-[11px]">Ack No:</span>
                            <span class="font-mono font-semibold text-slate-800">{{ $invoice->eInvoice->ack_no ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Ack Date:</span>
                            <span class="font-semibold text-slate-800">{{ $invoice->eInvoice->ack_date?->format('d M Y, h:i A') ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                    <x-ui.button variant="secondary" :href="route('invoices.e-invoice.document', $invoice)" class="text-xs">
                        View / Print E-Invoice
                    </x-ui.button>
                    <button type="button" @click="cancelEInvoiceModal = true"
                            class="px-3 py-1.5 rounded-lg border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-semibold transition">
                        Cancel IRN
                    </button>
                </div>
            @elseif($eiStatus === 'cancelled' && $invoice->eInvoice)
                <div class="p-3 bg-rose-50 rounded-lg border border-rose-200 text-rose-800 space-y-1">
                    <div class="font-bold">E-Invoice Cancelled</div>
                    <div class="text-[11px]">Cancelled On: {{ $invoice->eInvoice->cancelled_at?->format('d M Y, h:i A') ?? '—' }}</div>
                    <div class="text-[11px]">Reason: <span class="font-semibold">{{ $invoice->eInvoice->cancel_reason ?? 'Cancelled' }}</span></div>
                    @if($invoice->eInvoice->cancel_remarks)
                        <div class="text-[11px]">Remarks: {{ $invoice->eInvoice->cancel_remarks }}</div>
                    @endif
                </div>
            @elseif($eiStatus === 'failed')
                <div class="p-3 bg-red-50 rounded-lg border border-red-200 text-red-800 space-y-1">
                    <div class="font-bold">IRN Generation Failed</div>
                    <div class="text-[11px]">{{ $invoice->eInvoice?->last_error ?: 'An error occurred with the IRP portal.' }}</div>
                </div>
                <div class="pt-2">
                    <form method="POST" action="{{ route('invoices.e-invoice', $invoice) }}">
                        @csrf
                        <x-ui.button type="submit" variant="primary" class="w-full text-xs">
                            Retry E-Invoice Generation
                        </x-ui.button>
                    </form>
                </div>
            @elseif($eiStatus === 'not_applicable')
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-slate-600 space-y-1">
                    <div class="font-semibold text-slate-800">E-Invoice Not Required</div>
                    <div class="text-[11px] leading-relaxed">{{ $eInvoiceEligibility['reason'] ?? 'B2C / Unregistered Customer — E-Invoicing applies only to B2B supplies under Rule 48(4).' }}</div>
                </div>
            @else
                <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-amber-800 space-y-1">
                    <div class="font-semibold text-amber-900">E-Invoice Pending Generation</div>
                    <div class="text-[11px] leading-relaxed">{{ $eInvoiceEligibility['reason'] ?? 'Eligible B2B supply. Ready for IRP registration.' }}</div>
                </div>
                <div class="pt-2">
                    <form method="POST" action="{{ route('invoices.e-invoice', $invoice) }}">
                        @csrf
                        <x-ui.button type="submit" variant="primary" class="w-full text-xs">
                            Generate E-Invoice (IRN)
                        </x-ui.button>
                    </form>
                </div>
            @endif
        </div>
    </x-ui.card>

    {{-- 2. E-Way Bill (EBN) Card --}}
    <x-ui.card>
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <h3 class="text-sm font-bold text-slate-800">GST E-Way Bill</h3>
            </div>
            @php
                $ewbStatus = $invoice->eWayBill?->status ?? $invoice->eway_bill_status ?? 'pending';
                if ($invoice->eWayBill && $invoice->eWayBill->status === 'cancelled') $ewbStatus = 'cancelled';
                elseif ($invoice->eWayBill && in_array($invoice->eWayBill->status, ['generated', 'active'])) $ewbStatus = 'generated';
                elseif (!($invoice->eWayBill) && ($eWayBillEligibility['status'] ?? '') === 'not_applicable') $ewbStatus = 'not_applicable';
            @endphp
            <div>
                @if($ewbStatus === 'generated')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                        ● Generated (Active)
                    </span>
                @elseif($ewbStatus === 'cancelled')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                        ✕ Cancelled
                    </span>
                @elseif($ewbStatus === 'failed')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                        ⚠ Generation Failed
                    </span>
                @elseif($ewbStatus === 'not_applicable')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                        Not Applicable
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                        Pending Generation
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-4 space-y-3 text-xs">
            @if($ewbStatus === 'generated' && $invoice->eWayBill)
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2">
                    <div>
                        <span class="text-slate-500 block text-[11px] font-medium">E-Way Bill Number (EBN):</span>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <span class="font-mono font-bold text-slate-900 text-sm">{{ $invoice->eWayBill->eway_bill_no }}</span>
                            <button type="button" onclick="navigator.clipboard.writeText('{{ $invoice->eWayBill->eway_bill_no }}'); alert('E-Way Bill number copied!');"
                                    class="text-indigo-600 hover:text-indigo-800 font-semibold text-[11px]">Copy</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200">
                        <div>
                            <span class="text-slate-500 block text-[11px]">Generated Date:</span>
                            <span class="font-semibold text-slate-800">{{ $invoice->eWayBill->ewb_date?->format('d M Y, h:i A') ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Valid Until:</span>
                            <span class="font-semibold text-emerald-700">{{ $invoice->eWayBill->valid_upto?->format('d M Y, h:i A') ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200">
                        <div>
                            <span class="text-slate-500 block text-[11px]">Vehicle No:</span>
                            <span class="font-mono font-semibold text-slate-800">{{ $invoice->eWayBill->vehicle_no ?: ($invoice->vehicle_no ?: '—') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Distance:</span>
                            <span class="font-semibold text-slate-800">{{ $invoice->eWayBill->distance_km ?: ($invoice->distance_km ?: '—') }} KM</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                    <div class="flex items-center gap-2">
                        <x-ui.button variant="secondary" :href="route('invoices.eway.document', $invoice)" class="text-xs">
                            View / Print E-Way Bill
                        </x-ui.button>
                        <button type="button" @click="updateVehicleModal = true"
                                class="px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                            Update Vehicle
                        </button>
                    </div>
                    <button type="button" @click="cancelEwayModal = true"
                            class="px-3 py-1.5 rounded-lg border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-semibold transition">
                        Cancel E-Way Bill
                    </button>
                </div>
            @elseif($ewbStatus === 'cancelled' && $invoice->eWayBill)
                <div class="p-3 bg-rose-50 rounded-lg border border-rose-200 text-rose-800 space-y-1">
                    <div class="font-bold">E-Way Bill Cancelled</div>
                    <div class="text-[11px]">Cancelled On: {{ $invoice->eWayBill->cancelled_at?->format('d M Y, h:i A') ?? '—' }}</div>
                    <div class="text-[11px]">Reason: <span class="font-semibold">{{ $invoice->eWayBill->cancel_reason ?? 'Cancelled' }}</span></div>
                    @if($invoice->eWayBill->cancel_remarks)
                        <div class="text-[11px]">Remarks: {{ $invoice->eWayBill->cancel_remarks }}</div>
                    @endif
                </div>
            @elseif($ewbStatus === 'failed')
                <div class="p-3 bg-red-50 rounded-lg border border-red-200 text-red-800 space-y-1">
                    <div class="font-bold">E-Way Bill Generation Failed</div>
                    <div class="text-[11px]">{{ $invoice->eWayBill?->last_error ?: 'An error occurred with the E-Way Bill portal.' }}</div>
                </div>
                <div class="pt-2">
                    <button type="button" @click="generateEwayModal = true"
                            class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 transition">
                        Retry E-Way Bill Generation
                    </button>
                </div>
            @elseif($ewbStatus === 'not_applicable')
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-slate-600 space-y-1">
                    <div class="font-semibold text-slate-800">E-Way Bill Not Required</div>
                    <div class="text-[11px] leading-relaxed">{{ $eWayBillEligibility['reason'] ?? 'Consignment value is below the ₹50,000 threshold under GST Rule 138.' }}</div>
                </div>
                <div class="pt-2">
                    <button type="button" @click="generateEwayModal = true"
                            class="w-full inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 transition">
                        + Generate Voluntarily
                    </button>
                </div>
            @else
                <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-amber-800 space-y-1">
                    <div class="font-semibold text-amber-900">E-Way Bill Pending Generation</div>
                    <div class="text-[11px] leading-relaxed">{{ $eWayBillEligibility['reason'] ?? 'Mandatory under Rule 138 (consignment value exceeds ₹50,000).' }}</div>
                </div>
                <div class="pt-2">
                    <button type="button" @click="generateEwayModal = true"
                            class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 transition">
                        Generate E-Way Bill
                    </button>
                </div>
            @endif
        </div>
    </x-ui.card>

    {{-- Modal 1: Generate E-Way Bill with Transport Details --}}
    <div x-show="generateEwayModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60" @click="generateEwayModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form method="POST" action="{{ route('invoices.eway', $invoice) }}">
                    @csrf
                    <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-slate-800">Generate GST E-Way Bill</h4>
                        <button type="button" @click="generateEwayModal = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                    </div>
                    <div class="p-5 space-y-3.5 text-xs">
                        <p class="text-slate-600 text-[11px]">Confirm or update movement details for Tax Invoice <strong class="text-slate-800">{{ $invoice->invoice_no }}</strong> (₹{{ number_format($invoice->grand_total, 2) }}).</p>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Transport Mode *</label>
                            <select name="transport_mode" x-model="transportMode" class="w-full rounded-lg border-gray-300 text-xs">
                                <option value="1">1 - Road</option>
                                <option value="2">2 - Rail</option>
                                <option value="3">3 - Air</option>
                                <option value="4">4 - Ship</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Vehicle No. (For Road)</label>
                                <input type="text" name="vehicle_no" x-model="vehicleNo" placeholder="E.g. MH-12-AB-1234" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Vehicle Type</label>
                                <select name="vehicle_type" x-model="vehicleType" class="w-full rounded-lg border-gray-300 text-xs">
                                    <option value="R">Regular (R)</option>
                                    <option value="O">Over Dimensional Cargo (O)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Distance (KM) *</label>
                                <input type="number" min="1" max="9999" name="distance_km" x-model.number="distanceKm" required class="w-full rounded-lg border-gray-300 text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Transporter ID / GSTIN</label>
                                <input type="text" name="transporter_id" x-model="transporterId" placeholder="15-digit GSTIN" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Transporter Name</label>
                            <input type="text" name="transporter_name" x-model="transporterName" placeholder="E.g. SafeX Logistics" class="w-full rounded-lg border-gray-300 text-xs">
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" @click="generateEwayModal = false">Cancel</x-ui.button>
                        <x-ui.button type="submit" variant="primary">Generate E-Way Bill</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal 2: Cancel E-Invoice IRN --}}
    <div x-show="cancelEInvoiceModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60" @click="cancelEInvoiceModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form method="POST" action="{{ route('invoices.e-invoice.cancel', $invoice) }}">
                    @csrf
                    <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-rose-700">Cancel E-Invoice (IRN)</h4>
                        <button type="button" @click="cancelEInvoiceModal = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                    </div>
                    <div class="p-5 space-y-3 text-xs">
                        <p class="text-slate-600 text-[11px]">Under GST rules, an IRN can be cancelled within 24 hours of generation. Once cancelled, it cannot be regenerated with the same invoice number.</p>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Cancellation Reason *</label>
                            <select name="cancel_reason" x-model="cancelReason" required class="w-full rounded-lg border-gray-300 text-xs">
                                <option value="1">1 - Duplicate</option>
                                <option value="2">2 - Data entry mistake</option>
                                <option value="3">3 - Order cancelled</option>
                                <option value="4">4 - Others</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Remarks (Min 3 chars) *</label>
                            <textarea name="cancel_remarks" x-model="cancelRemarks" rows="3" required placeholder="Enter reason for cancellation..." class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" @click="cancelEInvoiceModal = false">Close</x-ui.button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 text-white font-semibold text-xs hover:bg-rose-700 transition">
                            Confirm Cancellation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal 3: Cancel E-Way Bill --}}
    <div x-show="cancelEwayModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60" @click="cancelEwayModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form method="POST" action="{{ route('invoices.eway.cancel', $invoice) }}">
                    @csrf
                    <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-rose-700">Cancel E-Way Bill</h4>
                        <button type="button" @click="cancelEwayModal = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                    </div>
                    <div class="p-5 space-y-3 text-xs">
                        <p class="text-slate-600 text-[11px]">E-Way Bill can be cancelled within 24 hours of generation provided goods have not been intercepted in transit.</p>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Cancellation Reason *</label>
                            <select name="cancel_reason" x-model="ewayCancelReason" required class="w-full rounded-lg border-gray-300 text-xs">
                                <option value="1">1 - Duplicate</option>
                                <option value="2">2 - Order Cancelled</option>
                                <option value="3">3 - Data Entry Mistake</option>
                                <option value="4">4 - Others</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Remarks *</label>
                            <textarea name="cancel_remarks" x-model="ewayCancelRemarks" rows="3" required placeholder="Enter reason for cancellation..." class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" @click="cancelEwayModal = false">Close</x-ui.button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 text-white font-semibold text-xs hover:bg-rose-700 transition">
                            Confirm Cancellation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal 4: Update Vehicle (Part-B) --}}
    <div x-show="updateVehicleModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60" @click="updateVehicleModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form method="POST" action="{{ route('invoices.eway.update-vehicle', $invoice) }}">
                    @csrf
                    <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-slate-800">Update Vehicle Details</h4>
                        <button type="button" @click="updateVehicleModal = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                    </div>
                    <div class="p-5 space-y-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">New Vehicle Number *</label>
                            <input type="text" name="vehicle_no" x-model="vehicleNo" required placeholder="E.g. MH-14-XY-9876" class="w-full rounded-lg border-gray-300 text-xs font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Vehicle Type</label>
                            <select name="vehicle_type" x-model="vehicleType" class="w-full rounded-lg border-gray-300 text-xs">
                                <option value="R">Regular (R)</option>
                                <option value="O">Over Dimensional Cargo (ODC)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Reason for Update *</label>
                            <select name="reason" x-model="vehicleReason" required class="w-full rounded-lg border-gray-300 text-xs">
                                <option value="Breakdown / Transshipment">Breakdown / Transshipment</option>
                                <option value="Due to Transshipment">Due to Transshipment</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" @click="updateVehicleModal = false">Cancel</x-ui.button>
                        <x-ui.button type="submit" variant="primary">Update Vehicle</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal 5: Print Settings & Copies --}}
    <div x-show="printSettingsModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60" @click="printSettingsModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form method="GET" action="{{ route('invoices.preview', $invoice) }}" target="_blank">
                    <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 bg-indigo-100 text-indigo-700 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Print Tax Invoice</h3>
                                <p class="text-[11px] text-slate-500 font-mono">{{ $invoice->invoice_no }}</p>
                            </div>
                        </div>
                        <button type="button" @click="printSettingsModal = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                    </div>

                    <div class="p-5 space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Type of Copy *</label>
                            <select name="copy_type" x-model="printCopyType" @change="if(printCopyType === 'all') { printCopies = 3; } else { printCopies = 1; }" class="w-full rounded-lg border-slate-300 text-xs focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="all">All Copies (Original + Duplicate + Triplicate)</option>
                                <option value="original">Original for Recipient</option>
                                <option value="duplicate">Duplicate for Transporter</option>
                                <option value="triplicate">Triplicate for Supplier</option>
                                <option value="extra">Extra Copy</option>
                                <option value="na">Not Applicable (No copy label)</option>
                            </select>
                            <p class="mt-1 text-[11px] text-slate-500">Specifies the statutory copy label to render on the invoice heading.</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Number of Copies *</label>
                            <input type="number" name="copies" x-model.number="printCopies" min="1" max="10" class="w-full rounded-lg border-slate-300 text-xs focus:ring-indigo-500 focus:border-indigo-500" required>
                            <p class="mt-1 text-[11px] text-slate-500">When printing multiple copies, each copy begins on a separate page with QR and statutory details.</p>
                        </div>

                        <div class="p-3 bg-indigo-50/70 rounded-lg border border-indigo-100 flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div class="text-[11px] text-slate-600 leading-relaxed">
                                <span class="font-semibold text-slate-800">GST Statutory Rules:</span><br>
                                • <strong>All Copies:</strong> Generates the 3 statutory copies on separate pages.<br>
                                • <strong>QR Verification:</strong> Every copy contains the scannable IRP/invoice verification QR code.
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        <button type="button" @click="printSettingsModal = false" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">Cancel</button>
                        <div class="flex items-center gap-2">
                            <button type="submit" class="px-3.5 py-2 rounded-lg border border-indigo-300 bg-white text-indigo-700 font-semibold text-xs hover:bg-indigo-50 transition">
                                Preview
                            </button>
                            <button type="submit" name="auto_print" value="1" class="px-3.5 py-2 rounded-lg bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print Now
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
