@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Product' : 'Create Product')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Product' : 'Create Product'"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.products.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ $item->exists ? route('masters.products.update', $item) : route('masters.products.store') }}" class="space-y-4 max-w-xl">@csrf @if($item->exists) @method('PUT') @endif
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.input name="sku" label="SKU" :value="old('sku', $item->sku)" required />
<div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('description', $item->description) }}</textarea></div>
<x-ui.select name="base_uom_id" label="Base UOM" required placeholder="Select UOM">@foreach($uoms as $u)<option value="{{ $u->id }}" @selected(old('base_uom_id', $item->base_uom_id)==$u->id)>{{ $u->name }} ({{ $u->code }})</option>@endforeach</x-ui.select>
<x-ui.input name="tax_rate" label="Tax Rate (%)" type="number" step="0.01" :value="old('tax_rate', $item->tax_rate ?? 0)" />
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active</label>
<x-ui.button type="submit" variant="primary">Save</x-ui.button></form></x-ui.card>
@endsection
