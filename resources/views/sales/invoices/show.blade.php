@extends('layouts.dms')
@section('title', $invoice->invoice_no)
@section('content')
<x-ui.page-header :title="$invoice->invoice_no" :description="$invoice->customer->name">
<x-slot name="actions">
<x-ui.button variant="secondary" :href="route('invoices.index')">Back</x-ui.button>
<x-ui.button variant="ghost" :href="route('invoices.pdf', $invoice)" target="_blank">Download PDF</x-ui.button>
<form method="POST" action="{{ route('invoices.e-invoice', $invoice) }}" class="inline">@csrf<x-ui.button type="submit" variant="secondary">E-Invoice</x-ui.button></form>
<form method="POST" action="{{ route('invoices.eway', $invoice) }}" class="inline">@csrf<x-ui.button type="submit" variant="secondary">E-Way Bill</x-ui.button></form>
<form method="POST" action="{{ route('payment-links.create', $invoice) }}" class="inline">@csrf<x-ui.button type="submit" variant="secondary">Payment Link</x-ui.button></form>
<x-ui.button variant="primary" :href="route('payments.create', ['invoice_id' => $invoice->id])">Record Payment</x-ui.button>
</x-slot></x-ui.page-header>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<x-ui.card class="lg:col-span-2" title="Items"><x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Qty</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Price</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total</th></tr></x-slot>
@foreach($invoice->items as $item)<tr><td class="px-6 py-4 text-sm">{{ $item->product->name }}</td><td class="px-6 py-4 text-sm text-right">{{ $item->quantity }} {{ $item->uom->code }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($item->unit_price, 2) }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($item->line_total, 2) }}</td></tr>@endforeach</x-ui.table></x-ui.card>
<x-ui.card title="Summary"><dl class="space-y-2 text-sm">
<div class="flex justify-between"><dt>Status</dt><dd><x-ui.badge>{{ ucfirst($invoice->status) }}</x-ui.badge></dd></div>
<div class="flex justify-between"><dt>Date</dt><dd>{{ $invoice->invoice_date->format('d M Y') }}</dd></div>
<div class="flex justify-between border-t pt-2"><dt>Grand Total</dt><dd class="font-semibold">₹{{ number_format($invoice->grand_total, 2) }}</dd></div>
<div class="flex justify-between"><dt>Paid</dt><dd>₹{{ number_format($invoice->paid_amount, 2) }}</dd></div>
<div class="flex justify-between"><dt>Outstanding</dt><dd>₹{{ number_format($invoice->grand_total - $invoice->paid_amount, 2) }}</dd></div>
</dl>
@if($invoice->eInvoice)<p class="mt-4 text-xs text-gray-500">E-Invoice IRN: {{ $invoice->eInvoice->irn }}</p>@endif
@if($invoice->eWayBill)<p class="text-xs text-gray-500">E-Way: {{ $invoice->eWayBill->eway_bill_no }}</p>@endif
<div class="mt-4 space-y-2">
<form method="POST" action="{{ route('communications.send-invoice', $invoice) }}">@csrf<x-ui.button type="submit" variant="ghost" class="w-full">Send WhatsApp</x-ui.button></form>
<form method="POST" action="{{ route('communications.send-reminder', $invoice) }}">@csrf<x-ui.button type="submit" variant="ghost" class="w-full">Send Reminder</x-ui.button></form>
</div></x-ui.card></div>
@endsection
