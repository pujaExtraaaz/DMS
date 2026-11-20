@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Holiday Calendar' : 'Create Holiday Calendar')
@section('content')
<x-ui.card :title="$item->exists ? 'Edit Holiday Calendar' : 'Create Holiday Calendar'">
    <form method="POST" action="{{ $item->exists ? route('hrms.holidays.update', $item) : route('hrms.holidays.store') }}" class="max-w-xl space-y-4">
        @csrf
        @if($item->exists) @method('PUT') @endif
        <x-ui.select name="company_id" label="Company"><option value="">—</option>@foreach($companies as $c)<option value="{{ $c->id }}" @selected(old('company_id', $item->company_id)==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
        <x-ui.select name="branch_id" label="Branch (optional)"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $item->branch_id)==$b->id)>{{ $b->name }}</option>@endforeach</x-ui.select>
        <x-ui.input name="year" label="Year" type="number" :value="old('year', $item->year)" required />
        <x-ui.input name="name" label="Calendar name" :value="old('name', $item->name)" required />
        <x-ui.button type="submit" variant="primary">Save</x-ui.button>
    </form>
</x-ui.card>
@endsection
