@extends('layouts.dms')
@section('title', 'Products')
@section('content')
<x-ui.page-header title="Products"><x-slot name="actions"><x-ui.button variant="primary" :href="route('masters.products.create')">Add Product</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card>
<form method="GET" class="mb-4 flex gap-2"><x-ui.input name="search" placeholder="Search..." :value="request('search')" class="max-w-xs" /><x-ui.button type="submit" variant="secondary">Search</x-ui.button></form>
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Name</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">SKU</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">UOM</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Tax</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th></tr></x-slot>
@forelse($items as $item)<tr class="hover:bg-gray-50"><td class="px-6 py-4 text-sm">{{ $item->name }}</td><td class="px-6 py-4 text-sm">{{ $item->sku }}</td><td class="px-6 py-4 text-sm">{{ $item->baseUom?->code }}</td><td class="px-6 py-4 text-sm">{{ $item->tax_rate }}%</td><td class="px-6 py-4 text-right space-x-2"><x-ui.button variant="ghost" size="sm" :href="route('masters.products.edit', $item)">Edit</x-ui.button></td></tr>@empty<tr><td colspan="5" class="px-6 py-8"><x-ui.empty-state title="No products" /></td></tr>@endforelse</x-ui.table>
<div class="mt-4">{{ $items->links() }}</div></x-ui.card>
@endsection
