@extends('layouts.dms')
@section('title', 'Record Payment')
@section('content')
<x-ui.page-header title="Record Payment"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('payments.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ route('payments.store') }}" class="space-y-4 max-w-xl">@csrf
<x-ui.select name="invoice_id" label="Invoice" required>@foreach($invoices as $inv)<option value="{{ $inv->id }}" @selected(($invoice?->id ?? null) == $inv->id)>{{ $inv->invoice_no }} — {{ $inv->customer->name }} (₹{{ number_format($inv->grand_total - $inv->paid_amount, 2) }} due)</option>@endforeach</x-ui.select>
<x-ui.input name="amount" label="Amount" type="number" step="0.01" :value="$invoice ? ($invoice->grand_total - $invoice->paid_amount) : ''" required />
<x-ui.select name="method" label="Method" required><option value="cash">Cash</option><option value="upi">UPI</option><option value="bank">Bank</option><option value="other">Other</option></x-ui.select>
<x-ui.input name="paid_at" label="Paid At" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" />
<x-ui.input name="notes" label="Notes" />
<x-ui.button type="submit" variant="primary">Save Payment</x-ui.button></form></x-ui.card>
@endsection
