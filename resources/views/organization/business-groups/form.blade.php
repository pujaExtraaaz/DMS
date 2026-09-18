@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Business Group' : 'Create Business Group')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Business Group' : 'Create Business Group'"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('organization.business-groups.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ $item->exists ? route('organization.business-groups.update', $item) : route('organization.business-groups.store') }}" class="space-y-4 max-w-2xl">@csrf @if($item->exists) @method('PUT') @endif
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.input name="code" label="Code" :value="old('code', $item->code)" required />
</div>
<div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('description', $item->description) }}</textarea></div>
<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Linked Companies</label>
<div class="space-y-1 max-h-48 overflow-y-auto border rounded-lg p-3">
@foreach($companies as $c)
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="company_ids[]" value="{{ $c->id }}" @checked(in_array($c->id, old('company_ids', $selectedCompanies))) class="rounded border-gray-300 text-indigo-600"> {{ $c->name }} ({{ $c->code }})</label>
@endforeach
</div>
</div>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active</label>
<x-ui.button type="submit" variant="primary">Save</x-ui.button></form></x-ui.card>
@endsection
