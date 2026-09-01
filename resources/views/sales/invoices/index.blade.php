@extends('layouts.dms')
@section('title', 'Invoices')
@section('content')
<x-ui.page-header title="Invoices"><x-slot name="actions"><x-ui.button variant="primary" :href="route('invoices.create')">Direct Billing</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div>
            <x-ui.select name="customer_id" label="Customer" placeholder="All"><option value=""></option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
        </div>
        <div>
            <x-ui.input name="date_from" type="date" label="From" :value="request('date_from')" />
        </div>
        <div>
            <x-ui.input name="date_to" type="date" label="To" :value="request('date_to')" />
        </div>
        <div class="flex items-end">
            <x-ui.button type="submit" variant="secondary" class="w-full">Filter</x-ui.button>
        </div>
    </form>
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Invoice</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th></tr></x-slot>
@forelse($invoices as $invoice)<tr><td class="px-6 py-4 text-sm">{{ $invoice->invoice_no }}</td><td class="px-6 py-4 text-sm">{{ $invoice->customer->name }}</td><td class="px-6 py-4 text-sm">{{ $invoice->invoice_date->format('d M Y') }}</td><td class="px-6 py-4 text-sm"><x-ui.badge variant="info">{{ ucfirst($invoice->status) }}</x-ui.badge></td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($invoice->grand_total, 2) }}</td><td class="px-6 py-4 text-right"><x-ui.button variant="ghost" size="sm" :href="route('invoices.show', $invoice)">View</x-ui.button></td></tr>@empty<tr><td colspan="6" class="px-6 py-8"><x-ui.empty-state title="No invoices" /></td></tr>@endforelse</x-ui.table>
<div class="mt-4">{{ $invoices->links() }}</div></x-ui.card>
@endsection
