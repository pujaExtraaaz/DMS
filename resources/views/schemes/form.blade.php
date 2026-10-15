@extends('layouts.dms')
@section('title', 'Create Scheme')
@section('content')
<x-ui.page-header title="Create Scheme">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('schemes.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('schemes.store') }}" class="space-y-4" x-data="{ schemeType: '{{ old('scheme_type', $item->scheme_type ?? '') }}', slabs: [{from_value:0,to_value:'',benefit_percent:0,benefit_amount:0}] }">
@csrf
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<x-ui.input name="name" label="Name" :value="old('name', $item->name ?? '')" required />
<x-ui.input name="code" label="Code (optional)" :value="old('code', $item->code ?? '')" />

<x-ui.select name="scheme_type" label="Scheme Type (Optional)" x-model="schemeType">
    <option value="">Select Scheme Type (Optional)</option>
    <option value="product_based" @selected(old('scheme_type', $item->scheme_type ?? '') === 'product_based')>Product-Based Scheme</option>
    <option value="brand_based" @selected(old('scheme_type', $item->scheme_type ?? '') === 'brand_based')>Brand-Based Scheme</option>
</x-ui.select>

<div x-show="schemeType === 'brand_based' || !schemeType" x-transition>
    <x-ui.select name="brand_id" label="Brand">
        <option value="">All brands</option>
        @foreach($brands as $brand)
            <option value="{{ $brand->id }}" @selected(old('brand_id', $item->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
        @endforeach
    </x-ui.select>
    <p class="mt-1 text-xs text-gray-500" x-show="schemeType === 'brand_based'">Select the applicable brand for this brand-based scheme.</p>
</div>

<x-ui.input name="starts_on" type="date" label="Starts" :value="old('starts_on', optional($item->starts_on)->toDateString())" required />
<x-ui.input name="ends_on" type="date" label="Ends" :value="old('ends_on', optional($item->ends_on)->toDateString())" required />
<x-ui.select name="basis" label="Basis" required>
    <option value="value" @selected(old('basis', $item->basis ?? 'value') === 'value')>Value</option>
    <option value="quantity" @selected(old('basis', $item->basis ?? '') === 'quantity')>Quantity</option>
</x-ui.select>
<x-ui.select name="status" label="Status">
    @foreach(['draft','active'] as $s)
        <option value="{{ $s }}" @selected(old('status', $item->status ?? 'draft') === $s)>{{ ucfirst($s) }}</option>
    @endforeach
</x-ui.select>
</div>

<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="net_credit_notes" value="1" @checked(old('net_credit_notes', true)) class="rounded border-gray-300 text-indigo-600"> Net credit notes</label>

<div x-show="schemeType === 'product_based' || !schemeType" x-transition>
<label class="block text-sm font-medium text-gray-700 mb-1">Products (optional)</label>
<select name="product_ids[]" multiple class="block w-full rounded-lg border-gray-300 text-sm h-32 focus:border-indigo-500 focus:ring-indigo-500">
@foreach($products as $product)
    <option value="{{ $product->id }}" @selected(in_array($product->id, old('product_ids', isset($item->products) ? $item->products->pluck('product_id')->toArray() : [])))>{{ $product->name }}</option>
@endforeach
</select>
<p class="mt-1 text-xs text-gray-500" x-show="schemeType === 'product_based'">Select one or more products applicable for this product-based scheme.</p>
</div>

<div class="border rounded-xl p-3 space-y-3">
<div class="text-sm font-semibold">Slabs</div>
<template x-for="(slab, index) in slabs" :key="index">
<div class="grid grid-cols-2 md:grid-cols-4 gap-2">
<input type="number" step="0.01" :name="`slabs[${index}][from_value]`" x-model="slab.from_value" class="rounded-lg border-gray-300 text-sm" placeholder="From" required>
<input type="number" step="0.01" :name="`slabs[${index}][to_value]`" x-model="slab.to_value" class="rounded-lg border-gray-300 text-sm" placeholder="To">
<input type="number" step="0.01" :name="`slabs[${index}][benefit_percent]`" x-model="slab.benefit_percent" class="rounded-lg border-gray-300 text-sm" placeholder="% Benefit">
<input type="number" step="0.01" :name="`slabs[${index}][benefit_amount]`" x-model="slab.benefit_amount" class="rounded-lg border-gray-300 text-sm" placeholder="Flat Benefit">
</div>
</template>
<button type="button" class="text-sm font-medium text-indigo-600" @click="slabs.push({from_value:0,to_value:'',benefit_percent:0,benefit_amount:0})">+ Add slab</button>
</div>
<x-ui.button type="submit" variant="primary">Save Scheme</x-ui.button>
</form>
</x-ui.card>
@endsection
