@extends('layouts.dms')
@section('title', $invoice->invoice_no)
@section('content')
@php
    $materialBadge = match ($invoice->material_receipt_status) {
        'received' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'partially_received' => 'bg-amber-50 text-amber-800 border-amber-200',
        default => 'bg-sky-50 text-sky-800 border-sky-200',
    };
@endphp
<x-ui.page-header :title="$invoice->invoice_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.index')">
            Back
        </x-ui.button>

        <div class="flex items-center gap-2">
            @if($invoice->canReceiveMaterial())
                <x-ui.button variant="primary" :href="route('purchasing.invoices.receive-material', $invoice)">
                    Receive Material
                </x-ui.button>
            @endif
            <x-ui.button
                variant="secondary"
                :href="route('purchasing.invoices.preview', $invoice)"
            >
                Preview Invoice
            </x-ui.button>
        </div>
    </x-slot>
</x-ui.page-header>
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-4">
    <x-ui.card><p class="text-xs text-slate-500">Supplier</p><p class="font-medium truncate" title="{{ $invoice->supplier?->name }}">{{ $invoice->supplier?->name }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Purchase Order</p><p class="font-medium truncate">@if($invoice->purchaseOrder)<a href="{{ route('purchasing.orders.show', $invoice->purchaseOrder) }}" class="text-indigo-600 hover:underline font-mono">{{ $invoice->purchaseOrder->po_no }}</a>@else—@endif</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Supplier Inv</p><p class="font-medium">{{ $invoice->supplier_invoice_no ?? '—' }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Supplier Invoice Date</p><p class="font-medium">{{ $invoice->invoice_date?->format('d M Y') ?? '—' }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Material Status</p><p class="font-medium"><span class="inline-flex px-2 py-0.5 rounded border text-xs font-semibold {{ $materialBadge }}">{{ $invoice->materialReceiptLabel() }}</span></p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Payment Order Date</p><p class="font-medium">{{ $invoice->payment_order_date?->format('d M Y') ?? '—' }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Payment Due Date</p><p class="font-medium">{{ $invoice->due_date?->format('d M Y') ?? '—' }} @if($invoice->credit_days) <span class="text-xs text-slate-400">({{ $invoice->credit_days }} days)</span> @endif</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Total</p><p class="font-medium">₹{{ number_format($invoice->grand_total, 2) }}</p></x-ui.card>
</div>

<div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <h3 class="text-sm font-semibold text-slate-800">Material Receipt Tracking</h3>
        <span class="text-xs text-slate-500">Invoice recorded ≠ stock available. Stock posts only on receipt confirmation.</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 text-sm">
        <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Dispatch Date</p>
            <p class="font-medium">{{ $invoice->material_dispatch_date?->format('d M Y') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Expected Delivery</p>
            <p class="font-medium {{ $invoice->isDeliveryOverdue() ? 'text-rose-600' : '' }}">
                {{ $invoice->expected_delivery_date?->format('d M Y') ?? '—' }}
                @if($invoice->isDeliveryOverdue()) <span class="text-[10px] font-bold">OVERDUE</span> @endif
            </p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Actual Receipt</p>
            <p class="font-medium">{{ $invoice->actual_material_receipt_date?->format('d M Y') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Days in Transit</p>
            <p class="font-medium font-mono">{{ $invoice->daysInTransit() ?? '—' }}</p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Material Status</p>
            <p class="font-medium">{{ $invoice->materialReceiptLabel() }}</p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Payment Due</p>
            <p class="font-medium">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Payment Status</p>
            <p class="font-medium">{{ $invoice->paymentStatusLabel() }}</p>
        </div>
    </div>
</div>

<div class="mb-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Receipt Details</h3>
        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <div><dt class="text-[11px] uppercase text-slate-500">Receipt Note No(s)</dt><dd class="font-medium">{{ $invoice->receipt_note_nos ?: '—' }}</dd></div>
            <div><dt class="text-[11px] uppercase text-slate-500">Receipt Doc No.</dt><dd class="font-medium">{{ $invoice->receipt_doc_no ?: '—' }}</dd></div>
            <div><dt class="text-[11px] uppercase text-slate-500">Dispatched Through</dt><dd class="font-medium">{{ $invoice->dispatched_through ?: '—' }}</dd></div>
            <div><dt class="text-[11px] uppercase text-slate-500">Carrier / Agent</dt><dd class="font-medium">{{ $invoice->carrier_name ?: '—' }}</dd></div>
            <div><dt class="text-[11px] uppercase text-slate-500">LR / RR No.</dt><dd class="font-medium">{{ $invoice->lr_number ?: '—' }}</dd></div>
            <div><dt class="text-[11px] uppercase text-slate-500">LR Date</dt><dd class="font-medium">{{ $invoice->lr_date?->format('d M Y') ?? '—' }}</dd></div>
            <div><dt class="text-[11px] uppercase text-slate-500">Vehicle No.</dt><dd class="font-medium">{{ $invoice->vehicle_number ?: '—' }}</dd></div>
        </dl>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Order Details</h3>
        <dl class="grid grid-cols-1 gap-y-2 text-sm">
            <div><dt class="text-[11px] uppercase text-slate-500">Order No(s)</dt><dd class="font-medium">{{ $invoice->order_nos ?: ($invoice->purchaseOrder?->po_no ?: '—') }}</dd></div>
            <div><dt class="text-[11px] uppercase text-slate-500">Mode / Terms of Payment</dt><dd class="font-medium">{{ $invoice->payment_terms ?: '—' }}</dd></div>
            <div><dt class="text-[11px] uppercase text-slate-500">Other References</dt><dd class="font-medium">{{ $invoice->other_references ?: '—' }}</dd></div>
            <div><dt class="text-[11px] uppercase text-slate-500">Terms of Delivery</dt><dd class="font-medium">{{ $invoice->terms_of_delivery ?: '—' }}</dd></div>
            @if($invoice->freight_notes)
                <div><dt class="text-[11px] uppercase text-slate-500">Freight Notes</dt><dd class="font-medium">{{ $invoice->freight_notes }}</dd></div>
            @endif
        </dl>
    </div>
</div>

@if($invoice->odAccount)
    <div class="mb-4 bg-indigo-50 border border-indigo-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-lg bg-indigo-600 text-white shadow-2xs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-900">OD Financed Invoice</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-100 text-indigo-800">
                        OD Utilized
                    </span>
                </div>
                <p class="text-sm font-semibold text-slate-800">
                    {{ $invoice->odAccount->bank_name }}
                    <span class="text-slate-500 font-normal font-mono">({{ '•••• ' . substr($invoice->odAccount->account_number, -4) }})</span>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-6 text-right sm:text-right">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">OD Utilization Amount</p>
                <p class="text-base font-bold font-mono text-indigo-700">₹{{ number_format((float) $invoice->grand_total, 2) }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Current Available OD Limit</p>
                <p class="text-base font-bold font-mono text-emerald-600">₹{{ number_format((float) $invoice->odAccount->getAvailableLimit(), 2) }}</p>
            </div>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <x-ui.card>
        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Billing Address</div>
        @if($invoice->billing_address)
            <p class="text-sm text-slate-800 whitespace-pre-line">{{ $invoice->billing_address }}</p>
        @elseif($invoice->billingAddress)
            <p class="text-sm font-semibold text-slate-800">{{ $invoice->billingAddress->label }}</p>
            <p class="text-xs text-slate-600 mt-1 whitespace-pre-line">{{ $invoice->billingAddress->full_address }}</p>
        @else
            <p class="text-sm text-slate-500 italic">No billing address specified</p>
        @endif
    </x-ui.card>

    <x-ui.card>
        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Delivery / Dispatch Address</div>
        @if($invoice->shipping_address)
            <p class="text-sm text-slate-800 whitespace-pre-line">{{ $invoice->shipping_address }}</p>
        @elseif($invoice->shippingAddress)
            <p class="text-sm font-semibold text-slate-800">{{ $invoice->shippingAddress->label }}</p>
            <p class="text-xs text-slate-600 mt-1 whitespace-pre-line">{{ $invoice->shippingAddress->full_address }}</p>
        @else
            <p class="text-sm text-slate-500 italic">Same as billing / No delivery address specified</p>
        @endif
    </x-ui.card>
</div>
@if($invoice->rate_override_reason)
    <x-ui.alert type="error" :message="'Rate override: '.$invoice->rate_override_reason" />
@endif
<x-ui.card>
<table class="min-w-full text-sm"><thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">Batch</th><th class="px-3 py-2 text-right">Invoiced</th><th class="px-3 py-2 text-right">Received</th><th class="px-3 py-2 text-right">In Transit</th><th class="px-3 py-2 text-right">MRP</th><th class="px-3 py-2 text-right">Purchase Price Incl. GST</th><th class="px-3 py-2 text-right">Disc. %</th><th class="px-3 py-2 text-right">Unit Cost</th><th class="px-3 py-2 text-right">Other Vendor</th><th class="px-3 py-2 text-right">Line Total</th>
</tr></thead>
<tbody class="divide-y">
@foreach($invoice->items as $item)
<tr>
<td class="px-3 py-2">{{ $item->product?->name }}</td>
<td class="px-3 py-2">{{ $item->batch_no ?: '—' }}</td>
<td class="px-3 py-2 text-right">{{ number_format($item->quantity, 2) }}</td>
<td class="px-3 py-2 text-right text-emerald-700">{{ number_format((float) $item->received_qty, 2) }}</td>
<td class="px-3 py-2 text-right text-amber-700">{{ number_format($item->remainingQty(), 2) }}</td>
<td class="px-3 py-2 text-right">{{ $item->batch_mrp !== null ? '₹'.number_format((float) $item->batch_mrp, 2) : '—' }}</td>
<td class="px-3 py-2 text-right">{{ $item->purchase_price_inclusive !== null ? '₹'.number_format((float) $item->purchase_price_inclusive, 2) : '—' }}</td>
<td class="px-3 py-2 text-right">{{ $item->purchase_discount_percent !== null ? number_format((float) $item->purchase_discount_percent, 2).'%' : '—' }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($item->unit_cost, 2) }}</td>
<td class="px-3 py-2 text-right">{{ $item->other_vendor_rate !== null ? '₹'.number_format($item->other_vendor_rate, 2) : '—' }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($item->line_total, 2) }}</td>
</tr>
@endforeach
</tbody></table>

    <div class="mt-4 pt-4 border-t border-slate-200 flex justify-end">
        <div class="w-full max-w-xs space-y-2 text-xs">
            <div class="flex justify-between text-slate-600">
                <span>Taxable Subtotal:</span>
                <span class="font-mono font-medium">₹{{ number_format((float) ($invoice->taxable_amount ?? $invoice->subtotal), 2) }}</span>
            </div>
            @if(($invoice->tax_type ?? '') === 'intra' || (!$invoice->tax_type && ($invoice->cgst_amount > 0 || $invoice->sgst_amount > 0)))
                <div class="flex justify-between text-slate-600">
                    <span>CGST:</span>
                    <span class="font-mono font-medium">₹{{ number_format((float) $invoice->cgst_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>SGST:</span>
                    <span class="font-mono font-medium">₹{{ number_format((float) $invoice->sgst_amount, 2) }}</span>
                </div>
            @elseif(($invoice->tax_type ?? '') === 'inter' || $invoice->igst_amount > 0)
                <div class="flex justify-between text-slate-600">
                    <span>IGST:</span>
                    <span class="font-mono font-medium">₹{{ number_format((float) $invoice->igst_amount, 2) }}</span>
                </div>
            @else
                <div class="flex justify-between text-slate-600">
                    <span>Tax Amount:</span>
                    <span class="font-mono font-medium">₹{{ number_format((float) $invoice->tax_amount, 2) }}</span>
                </div>
            @endif
            @php
                $freightBaseShown = (float) $invoice->freight_amount > 0
                    ? (float) $invoice->freight_amount
                    : (float) $invoice->freight_charge;
                $freightShown = (float) $invoice->freight_total > 0
                    ? (float) $invoice->freight_total
                    : $freightBaseShown;
            @endphp
            @if($freightShown > 0)
                <div class="flex justify-between text-slate-600">
                    <span>Freight{{ $invoice->freight_mode === 'percent' && (float) $invoice->freight_percent > 0 ? ' ('.rtrim(rtrim(number_format((float) $invoice->freight_percent, 2), '0'), '.').'%)' : '' }}:</span>
                    <span class="font-mono font-medium">₹{{ number_format($freightBaseShown, 2) }}</span>
                </div>
                @if((float) ($invoice->freight_tax_amount ?? 0) > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Freight Tax ({{ number_format((float) $invoice->freight_tax_rate, 1) }}%):</span>
                        <span class="font-mono font-medium">₹{{ number_format((float) $invoice->freight_tax_amount, 2) }}</span>
                    </div>
                @endif
            @endif
            @if((float) ($invoice->other_charges ?? 0) > 0)
                <div class="flex justify-between text-slate-600">
                    <span>Other Charges:</span>
                    <span class="font-mono font-medium">₹{{ number_format((float) $invoice->other_charges, 2) }}</span>
                </div>
            @endif
            @if($invoice->round_off !== null && (float) $invoice->round_off != 0)
                <div class="flex justify-between text-slate-600">
                    <span>Round Off:</span>
                    <span class="font-mono font-medium">{{ (float)$invoice->round_off > 0 ? '+' : '' }}₹{{ number_format((float) $invoice->round_off, 2) }}</span>
                </div>
            @endif
            <div class="flex justify-between text-base font-bold text-slate-900 pt-2 border-t border-slate-200">
                <span>Grand Total:</span>
                <span class="font-mono text-indigo-600">₹{{ number_format((float) $invoice->grand_total, 2) }}</span>
            </div>
        </div>
    </div>
</x-ui.card>
@endsection