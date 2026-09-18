@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Interest Rule' : 'Create Interest Rule')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Interest Rule' : 'Create Interest Rule'"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('interest.rules.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ $item->exists ? route('interest.rules.update', $item) : route('interest.rules.store') }}" class="space-y-4 max-w-xl">@csrf @if($item->exists) @method('PUT') @endif
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.select name="customer_id" label="Party (blank = all)"><option value=""></option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id', $item->customer_id)==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="annual_rate" type="number" step="0.01" label="Annual Rate %" :value="old('annual_rate', $item->annual_rate ?? 18)" required />
<x-ui.input name="grace_days" type="number" label="Grace Days" :value="old('grace_days', $item->grace_days ?? 0)" required />
<textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm" placeholder="Notes">{{ old('notes', $item->notes) }}</textarea>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $item->is_default ?? false)) class="rounded border-gray-300 text-indigo-600"> Default rule</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active</label>
<x-ui.button type="submit" variant="primary">Save</x-ui.button>
</form>
</x-ui.card>
@endsection
