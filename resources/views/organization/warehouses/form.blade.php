@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Warehouse' : 'Create Warehouse')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Warehouse' : 'Create Warehouse'"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('organization.warehouses.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ $item->exists ? route('organization.warehouses.update', $item) : route('organization.warehouses.store') }}" class="space-y-4 max-w-2xl">@csrf @if($item->exists) @method('PUT') @endif
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<x-ui.select name="company_id" label="Company" required>@foreach($companies as $c)<option value="{{ $c->id }}" @selected(old('company_id', $item->company_id)==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="branch_id" label="Branch" required>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $item->branch_id)==$b->id)>{{ $b->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.input name="code" label="Code" :value="old('code', $item->code)" required />
</div>
<div><label class="block text-sm font-medium text-gray-700 mb-1">Address</label><textarea name="address" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('address', $item->address) }}</textarea></div>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $item->is_default ?? false)) class="rounded border-gray-300 text-indigo-600"> Default warehouse</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active</label>
<x-ui.button type="submit" variant="primary">Save</x-ui.button></form></x-ui.card>
@endsection
