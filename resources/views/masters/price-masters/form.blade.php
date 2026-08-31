@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Price' : 'Add Price')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Price' : 'Add Price'"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.price-masters.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ $item->exists ? route('masters.price-masters.update', $item) : route('masters.price-masters.store') }}" class="space-y-4 max-w-xl">@csrf @if($item->exists) @method('PUT') @endif
<x-ui.select name="customer_type_id" label="Customer Type" required placeholder="Select">@foreach($customerTypes as $ct)<option value="{{ $ct->id }}" @selected(old('customer_type_id', $item->customer_type_id)==$ct->id)>{{ $ct->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="product_id" label="Product" required placeholder="Select">@foreach($products as $p)<option value="{{ $p->id }}" @selected(old('product_id', $item->product_id)==$p->id)>{{ $p->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="uom_id" label="UOM" required placeholder="Select">@foreach($uoms as $u)<option value="{{ $u->id }}" @selected(old('uom_id', $item->uom_id)==$u->id)>{{ $u->code }}</option>@endforeach</x-ui.select>
<x-ui.input name="rate" label="Rate" type="number" step="0.01" :value="old('rate', $item->rate)" required />
<x-ui.input name="min_qty" label="Min Qty" type="number" step="0.01" :value="old('min_qty', $item->min_qty)" />
<x-ui.button type="submit" variant="primary">Save</x-ui.button></form></x-ui.card>
@endsection
