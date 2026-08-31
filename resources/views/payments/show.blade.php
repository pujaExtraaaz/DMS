@extends('layouts.dms')
@section('title', $payment->payment_no)
@section('content')
<x-ui.page-header :title="$payment->payment_no"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('payments.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><dl class="space-y-2 text-sm max-w-md">
<div class="flex justify-between"><dt>Customer</dt><dd>{{ $payment->customer->name }}</dd></div>
<div class="flex justify-between"><dt>Invoice</dt><dd>{{ $payment->invoice->invoice_no }}</dd></div>
<div class="flex justify-between"><dt>Amount</dt><dd class="font-semibold">₹{{ number_format($payment->amount, 2) }}</dd></div>
<div class="flex justify-between"><dt>Method</dt><dd>{{ strtoupper($payment->method) }}</dd></div>
<div class="flex justify-between"><dt>Paid At</dt><dd>{{ optional($payment->paid_at)->format('d M Y H:i') }}</dd></div>
@if($payment->notes)<div><dt class="text-gray-500">Notes</dt><dd class="mt-1">{{ $payment->notes }}</dd></div>@endif
</dl></x-ui.card>
@endsection
