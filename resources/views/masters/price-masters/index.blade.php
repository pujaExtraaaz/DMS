@extends('layouts.dms')
@section('title', 'Price Master')
@section('content')
<x-ui.page-header title="Price Master"><x-slot name="actions"><x-ui.button variant="primary" :href="route('masters.price-masters.create')">Add Price</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card>
<form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
<x-ui.select name="customer_type_id" label="Customer Type" placeholder="All"><option value=""></option>@foreach($customerTypes as $ct)<option value="{{ $ct->id }}" @selected(request('customer_type_id')==$ct->id)>{{ $ct->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="product_id" label="Product" placeholder="All"><option value=""></option>@foreach($products as $p)<option value="{{ $p->id }}" @selected(request('product_id')==$p->id)>{{ $p->name }}</option>@endforeach</x-ui.select>
<x-ui.button type="submit" variant="secondary">Filter</x-ui.button></form>
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer Type</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">UOM</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Rate</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Min Qty</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th></tr></x-slot>
@forelse($items as $item)<tr><td class="px-6 py-4 text-sm">{{ $item->customerType->name }}</td><td class="px-6 py-4 text-sm">{{ $item->product->name }}</td><td class="px-6 py-4 text-sm">{{ $item->uom->code }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($item->rate, 2) }}</td><td class="px-6 py-4 text-sm text-right">{{ $item->min_qty ?? '—' }}</td><td class="px-6 py-4 text-right"><x-ui.button variant="ghost" size="sm" :href="route('masters.price-masters.edit', $item)">Edit</x-ui.button></td></tr>@empty<tr><td colspan="6" class="px-6 py-8"><x-ui.empty-state title="No prices" /></td></tr>@endforelse</x-ui.table>
<div class="mt-4">{{ $items->links() }}</div></x-ui.card>
@endsection
