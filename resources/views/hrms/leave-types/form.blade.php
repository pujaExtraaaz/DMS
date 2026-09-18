@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Leave Type' : 'Create Leave Type')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Leave Type' : 'Create Leave Type'"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('hrms.leave-types.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ $item->exists ? route('hrms.leave-types.update', $item) : route('hrms.leave-types.store') }}" class="space-y-4 max-w-xl">@csrf @if($item->exists) @method('PUT') @endif
<x-ui.select name="company_id" label="Company"><option value="">—</option>@foreach($companies as $c)<option value="{{ $c->id }}" @selected(old('company_id', $item->company_id)==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.input name="code" label="Code" :value="old('code', $item->code)" />
<x-ui.input name="default_days" label="Default Days" type="number" :value="old('default_days', $item->default_days)" />
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_paid" value="1" @checked(old('is_paid', $item->is_paid ?? true)) class="rounded border-gray-300 text-indigo-600"> Paid</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active</label>
<x-ui.button type="submit" variant="primary">Save</x-ui.button></form></x-ui.card>
@endsection
