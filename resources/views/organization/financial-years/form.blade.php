@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Financial Year' : 'Create Financial Year')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Financial Year' : 'Create Financial Year'"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('organization.financial-years.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ $item->exists ? route('organization.financial-years.update', $item) : route('organization.financial-years.store') }}" class="space-y-4 max-w-2xl">@csrf @if($item->exists) @method('PUT') @endif
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<x-ui.select name="company_id" label="Company" required>@foreach($companies as $c)<option value="{{ $c->id }}" @selected(old('company_id', $item->company_id)==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.input name="starts_on" label="Starts On" type="date" :value="old('starts_on', optional($item->starts_on)->format('Y-m-d'))" required />
<x-ui.input name="ends_on" label="Ends On" type="date" :value="old('ends_on', optional($item->ends_on)->format('Y-m-d'))" required />
</div>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_current" value="1" @checked(old('is_current', $item->is_current ?? false)) class="rounded border-gray-300 text-indigo-600"> Current FY</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_closed" value="1" @checked(old('is_closed', $item->is_closed ?? false)) class="rounded border-gray-300 text-indigo-600"> Closed (block posting)</label>
<x-ui.button type="submit" variant="primary">Save</x-ui.button></form></x-ui.card>
@endsection
