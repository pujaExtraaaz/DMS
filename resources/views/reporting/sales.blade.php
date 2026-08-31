@extends('layouts.dms')
@section('title', 'Sales Report')
@section('content')
<x-ui.page-header title="Sales Report" />
<x-ui.card><form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4"><x-ui.input name="date_from" type="date" label="From" :value="$dateFrom" /><x-ui.input name="date_to" type="date" label="To" :value="$dateTo" /><x-ui.select name="customer_id" label="Customer" placeholder="All"><option value=""></option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select><x-ui.button type="submit" variant="secondary">Run Report</x-ui.button></form>
<p class="text-sm text-gray-600 mb-4">{{ $summary['count'] }} invoices · Total ₹{{ number_format($summary['total'], 2) }} · Collected ₹{{ number_format($summary['collected'], 2) }}</p>
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Invoice</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total</th></tr></x-slot>
@foreach($invoices as $invoice)<tr><td class="px-6 py-4 text-sm">{{ $invoice->invoice_no }}</td><td class="px-6 py-4 text-sm">{{ $invoice->customer->name }}</td><td class="px-6 py-4 text-sm">{{ $invoice->invoice_date->format('d M Y') }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($invoice->grand_total, 2) }}</td></tr>@endforeach</x-ui.table></x-ui.card>
@endsection
