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
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
<x-ui.card><p class="text-xs text-slate-500">Supplier</p><p class="font-medium">{{ $invoice->supplier?->name }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Supplier Inv</p><p class="font-medium">{{ $invoice->supplier_invoice_no ?? '—' }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Date</p><p class="font-medium">{{ $invoice->invoice_date?->format('d M Y') }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Total</p><p class="font-medium">₹{{ number_format($invoice->grand_total, 2) }}</p></x-ui.card>
</div>
@if($invoice->rate_override_reason)
<x-ui.alert type="error" :message="'Rate override: '.$invoice->rate_override_reason" />
@endif
<x-ui.card>
<table class="min-w-full text-sm"><thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-right">Qty</th><th class="px-3 py-2 text-right">Unit Cost</th><th class="px-3 py-2 text-right">Other Vendor</th><th class="px-3 py-2 text-right">Line Total</th>
</tr></thead>
<tbody class="divide-y">
@foreach($invoice->items as $item)
<tr>
<td class="px-3 py-2">{{ $item->product?->name }}</td>
<td class="px-3 py-2 text-right">{{ number_format($item->quantity, 2) }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($item->unit_cost, 2) }}</td>
<td class="px-3 py-2 text-right">{{ $item->other_vendor_rate !== null ? '₹'.number_format($item->other_vendor_rate, 2) : '—' }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($item->line_total, 2) }}</td>
</tr>
@endforeach
</tbody></table>
</x-ui.card>
@endsection
