@extends('layouts.dms')
@section('title', 'Customers')
@section('content')
<x-ui.page-header title="Customers"><x-slot name="actions"><x-ui.button variant="primary" :href="route('masters.customers.create')">Add Customer</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card>
    <form method="GET" class="flex flex-row items-end gap-4 mb-6">
        <div class="flex-1">
            <x-ui.input name="search" label="Search" :value="request('search')" />
        </div>
        <div class="flex-1">
            <x-ui.select name="area_id" label="Area" placeholder="All"><option value=""></option>@foreach($areas as $a)<option value="{{ $a->id }}" @selected(request('area_id')==$a->id)>{{ $a->name }}</option>@endforeach</x-ui.select>
        </div>
        <div class="flex-1">
            <x-ui.select name="customer_type_id" label="Type" placeholder="All"><option value=""></option>@foreach($customerTypes as $ct)<option value="{{ $ct->id }}" @selected(request('customer_type_id')==$ct->id)>{{ $ct->name }}</option>@endforeach</x-ui.select>
        </div>
        <x-ui.button type="submit" variant="secondary" class="whitespace-nowrap">Filter</x-ui.button>
    </form>
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Name</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Code</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Type</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Area</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th></tr></x-slot>
@forelse($items as $item)<tr><td class="px-6 py-4 text-sm">{{ $item->name }}</td><td class="px-6 py-4 text-sm">{{ $item->code }}</td><td class="px-6 py-4 text-sm">{{ $item->customerType?->name }}</td><td class="px-6 py-4 text-sm">{{ $item->area?->name ?? '—' }}</td><td class="px-6 py-4 text-right"><x-ui.button variant="ghost" size="sm" :href="route('masters.customers.edit', $item)">Edit</x-ui.button></td></tr>@empty<tr><td colspan="5" class="px-6 py-8"><x-ui.empty-state title="No customers" /></td></tr>@endforelse</x-ui.table>
<div class="mt-4">{{ $items->links() }}</div></x-ui.card>
@endsection
