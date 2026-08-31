@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Customer' : 'Create Customer')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Customer' : 'Create Customer'"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.customers.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ $item->exists ? route('masters.customers.update', $item) : route('masters.customers.store') }}" class="space-y-4 max-w-2xl">@csrf @if($item->exists) @method('PUT') @endif
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.input name="code" label="Code" :value="old('code', $item->code)" required />
<x-ui.select name="customer_type_id" label="Customer Type" required>@foreach($customerTypes as $ct)<option value="{{ $ct->id }}" @selected(old('customer_type_id', $item->customer_type_id)==$ct->id)>{{ $ct->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="area_id" label="Area" placeholder="Select"><option value=""></option>@foreach($areas as $a)<option value="{{ $a->id }}" @selected(old('area_id', $item->area_id)==$a->id)>{{ $a->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="route_id" label="Route" placeholder="Select"><option value=""></option>@foreach($routes as $r)<option value="{{ $r->id }}" @selected(old('route_id', $item->route_id)==$r->id)>{{ $r->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="salesperson_id" label="Salesperson" placeholder="Select"><option value=""></option>@foreach($salespersons as $s)<option value="{{ $s->id }}" @selected(old('salesperson_id', $item->salesperson_id)==$s->id)>{{ $s->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="phone" label="Phone" :value="old('phone', $item->phone)" />
<x-ui.input name="email" label="Email" type="email" :value="old('email', $item->email)" />
<x-ui.input name="gstin" label="GSTIN" :value="old('gstin', $item->gstin)" />
</div>
<div><label class="block text-sm font-medium text-gray-700 mb-1">Address</label><textarea name="address" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('address', $item->address) }}</textarea></div>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active</label>
<x-ui.button type="submit" variant="primary">Save</x-ui.button></form></x-ui.card>
@endsection
