@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Region Policy' : 'Create Region Policy')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Region Policy' : 'Create Region Policy'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('region-policies.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ $item->exists ? route('region-policies.update', $item) : route('region-policies.store') }}" class="space-y-4 max-w-2xl">
    @csrf
    @if($item->exists) @method('PUT') @endif
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-ui.select name="area_id" label="Region / Area" required>
            @foreach($areas as $area)
                <option value="{{ $area->id }}" @selected(old('area_id', $item->area_id)==$area->id)>{{ $area->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="brand_id" label="Brand" required>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}" @selected(old('brand_id', $item->brand_id)==$brand->id)>{{ $brand->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input name="max_discount_percent" label="Max Discount %" type="number" step="0.01" :value="old('max_discount_percent', $item->max_discount_percent)" />
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea name="notes" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $item->notes) }}</textarea>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_allowed" value="1" @checked(old('is_allowed', $item->is_allowed ?? true)) class="rounded border-gray-300 text-indigo-600"> Allowed in region</label>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="requires_approval" value="1" @checked(old('requires_approval', $item->requires_approval ?? false)) class="rounded border-gray-300 text-indigo-600"> Requires approval</label>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active</label>
    <x-ui.button type="submit" variant="primary">Save</x-ui.button>
</form>
</x-ui.card>
@endsection
