@extends('layouts.dms')
@section('title', 'Purchases')
@section('content')
<x-ui.page-header title="Purchases"><x-slot name="actions"><x-ui.button variant="primary" :href="route('inventory.purchases.create')">New Purchase</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card>
<form method="GET" class="flex gap-3 mb-4"><x-ui.input name="date_from" type="date" label="From" :value="request('date_from')" /><x-ui.input name="date_to" type="date" label="To" :value="request('date_to')" /><x-ui.button type="submit" variant="secondary">Filter</x-ui.button></form>
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Purchase No</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Supplier</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th></tr></x-slot>
@forelse($purchases as $purchase)<tr><td class="px-6 py-4 text-sm">{{ $purchase->purchase_no }}</td><td class="px-6 py-4 text-sm">{{ $purchase->purchase_date->format('d M Y') }}</td><td class="px-6 py-4 text-sm">{{ $purchase->supplier_name }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($purchase->grand_total, 2) }}</td><td class="px-6 py-4 text-right"><x-ui.button variant="ghost" size="sm" :href="route('inventory.purchases.show', $purchase)">View</x-ui.button></td></tr>@empty<tr><td colspan="5" class="px-6 py-8"><x-ui.empty-state title="No purchases" /></td></tr>@endforelse</x-ui.table>
<div class="mt-4">{{ $purchases->links() }}</div></x-ui.card>
@endsection
