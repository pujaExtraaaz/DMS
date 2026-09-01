@extends('layouts.dms')

@section('title', 'E-Way Bill - '.$invoice->invoice_no)

@section('content')
<x-ui.page-header title="E-Way Bill" :description="$invoice->invoice_no">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('invoices.show', $invoice)">Back to Invoice</x-ui.button></x-slot>
</x-ui.page-header>

<x-ui.card>
    <div id="eway-document" class="mx-auto max-w-4xl border border-slate-300 bg-white p-8 text-sm text-slate-900">
        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-4">
            <div><p class="text-lg font-bold">E-Way Bill</p><p class="mt-1 text-slate-600">Generated against Tax Invoice {{ $invoice->invoice_no }}</p></div>
            <div class="text-right"><p class="font-semibold">E-Way Bill No.</p><p>{{ $eWayBill->eway_bill_no }}</p><p class="mt-1 text-xs text-slate-500">{{ optional($eWayBill->created_at)->format('d M Y, h:i A') }}</p></div>
        </div>
        <div class="grid grid-cols-1 gap-6 py-5 md:grid-cols-2">
            <div><p class="mb-2 font-semibold">From</p><p>{{ config('app.company_name') }}</p><p>{{ config('app.company_address') }}</p><p>GSTIN: {{ config('app.company_gstin') ?: '-' }}</p></div>
            <div><p class="mb-2 font-semibold">To</p><p>{{ $invoice->customer->shipping_name ?: $invoice->customer->name }}</p><p>{{ $invoice->customer->shipping_address ?: $invoice->customer->address }}</p><p>GSTIN: {{ $invoice->customer->shipping_gstin ?: ($invoice->customer->gstin ?: '-') }}</p></div>
        </div>
        <table class="w-full border-collapse border border-slate-300 text-left"><thead class="bg-slate-50"><tr><th class="border border-slate-300 px-3 py-2">Product</th><th class="border border-slate-300 px-3 py-2">HSN</th><th class="border border-slate-300 px-3 py-2 text-right">Quantity</th></tr></thead><tbody>@foreach($invoice->items as $item)<tr><td class="border border-slate-300 px-3 py-2">{{ $item->product->name }}</td><td class="border border-slate-300 px-3 py-2">{{ $item->product->hsn_code ?: '-' }}</td><td class="border border-slate-300 px-3 py-2 text-right">{{ number_format($item->quantity, 4) }} {{ $item->uom->code }}</td></tr>@endforeach</tbody></table>
        <div class="mt-5 text-right font-semibold">Consignment Value: ₹{{ number_format($invoice->grand_total, 2) }}</div>
    </div>
    <div class="mt-4 flex justify-end border-t border-slate-100 pt-4"><x-ui.button type="button" variant="primary" onclick="window.print()">Print</x-ui.button></div>
</x-ui.card>
@endsection
