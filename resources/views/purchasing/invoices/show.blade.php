@extends('layouts.dms')
@section('title', $invoice->invoice_no)
@section('content')
<x-ui.page-header :title="$invoice->invoice_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.index')">
            Back
        </x-ui.button>

        <div class="flex items-center gap-2">
            <x-ui.button
                variant="secondary"
                :href="route('purchasing.invoices.preview', $invoice)"
            >
                Preview Invoice
            </x-ui.button>

            <x-ui.button
                variant="primary"
                :href="route('purchasing.landed-costs.create', ['purchase_invoice_id' => $invoice->id])"
            >
                Allocate Landed Cost
            </x-ui.button>
        </div>
    </x-slot>
</x-ui.page-header>
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
    <x-ui.card><p class="text-xs text-slate-500">Supplier</p><p class="font-medium">{{ $invoice->supplier?->name }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Supplier Inv</p><p class="font-medium">{{ $invoice->supplier_invoice_no ?? '—' }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Date</p><p class="font-medium">{{ $invoice->invoice_date?->format('d M Y') }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Due Date</p><p class="font-medium">{{ $invoice->due_date?->format('d M Y') ?? '—' }} @if($invoice->credit_days) <span class="text-xs text-slate-400">({{ $invoice->credit_days }} days)</span> @endif</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Total</p><p class="font-medium">₹{{ number_format($invoice->grand_total, 2) }}</p></x-ui.card>
</div>

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
<th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">Batch</th><th class="px-3 py-2 text-right">Qty</th><th class="px-3 py-2 text-right">Unit Cost</th><th class="px-3 py-2 text-right">Other Vendor</th><th class="px-3 py-2 text-right">Line Total</th>
</tr></thead>
<tbody class="divide-y">
@foreach($invoice->items as $item)
<tr>
<td class="px-3 py-2">{{ $item->product?->name }}</td>
<td class="px-3 py-2">{{ $item->batch_no ?: '—' }}</td>
<td class="px-3 py-2 text-right">{{ number_format($item->quantity, 2) }}</td>
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
                <span class="font-mono font-medium">₹{{ number_format((float) $invoice->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Tax Amount:</span>
                <span class="font-mono font-medium">₹{{ number_format((float) $invoice->tax_amount, 2) }}</span>
            </div>
            @if((float) ($invoice->freight_charge ?? 0) > 0)
                <div class="flex justify-between text-slate-600">
                    <span>Freight / Landed Charge:</span>
                    <span class="font-mono font-medium">₹{{ number_format((float) $invoice->freight_charge, 2) }}</span>
                </div>
            @endif
            @if((float) ($invoice->other_charges ?? 0) > 0)
                <div class="flex justify-between text-slate-600">
                    <span>Other Charges:</span>
                    <span class="font-mono font-medium">₹{{ number_format((float) $invoice->other_charges, 2) }}</span>
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