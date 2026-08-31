@extends('layouts.dms')

@section('title', $item->exists ? 'Edit Route' : 'Create Route')

@section('content')
    <x-ui.page-header :title="$item->exists ? 'Edit Route' : 'Create Route'">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('masters.routes.index')">Back</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <form method="POST" action="{{ $item->exists ? route('masters.routes.update', $item) : route('masters.routes.store') }}" class="space-y-4 max-w-xl">
            @csrf
            @if($item->exists) @method('PUT') @endif
            <x-ui.input name="name" label="Name" type="text" :value="old('name', $item->name)" required />
            <x-ui.input name="code" label="Code" type="text" :value="old('code', $item->code)" required />
            <x-ui.select name="area_id" label="Area" placeholder="Select..."><option value=""></option>@foreach($areas ?? [] as $opt)<option value="{{ $opt->id }}" @selected(old('area_id', $item->area_id) == $opt->id)>{{ $opt->name }}</option>@endforeach</x-ui.select>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active)) class="rounded border-gray-300 text-indigo-600"> Active</label>
            <div class="flex gap-2 pt-2">
                <x-ui.button type="submit" variant="primary">Save</x-ui.button>
                <x-ui.button variant="ghost" :href="route('masters.routes.index')">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection