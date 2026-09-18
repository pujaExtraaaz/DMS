@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Branch' : 'Create Branch')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Branch' : 'Create Branch'"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('organization.branches.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ $item->exists ? route('organization.branches.update', $item) : route('organization.branches.store') }}" class="space-y-4 max-w-2xl">@csrf @if($item->exists) @method('PUT') @endif
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<x-ui.select name="company_id" label="Company" required>@foreach($companies as $c)<option value="{{ $c->id }}" @selected(old('company_id', $item->company_id)==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.input name="code" label="Code" :value="old('code', $item->code)" required />
<x-ui.input name="gstin" label="GSTIN" :value="old('gstin', $item->gstin)" />
<x-ui.input name="phone" label="Phone" :value="old('phone', $item->phone)" />
<x-ui.input name="state" label="State" :value="old('state', $item->state)" />
<x-ui.input name="pincode" label="Pincode" :value="old('pincode', $item->pincode)" />
</div>
<div><label class="block text-sm font-medium text-gray-700 mb-1">Address</label><textarea name="address" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('address', $item->address) }}</textarea></div>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active</label>
<x-ui.button type="submit" variant="primary">Save</x-ui.button></form></x-ui.card>
@endsection
