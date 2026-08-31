@extends('layouts.dms')

@section('title', $item->exists ? 'Edit Driver' : 'Create Driver')

@section('content')
    <x-ui.page-header :title="$item->exists ? 'Edit Driver' : 'Create Driver'">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('masters.drivers.index')">Back</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <form method="POST" action="{{ $item->exists ? route('masters.drivers.update', $item) : route('masters.drivers.store') }}" class="space-y-4 max-w-xl">
            @csrf
            @if($item->exists) @method('PUT') @endif
            <x-ui.input name="name" label="Name" type="text" :value="old('name', $item->name)" required />
            <x-ui.input name="phone" label="Phone" type="text" :value="old('phone', $item->phone)" />
            <x-ui.input name="license_no" label="License No" type="text" :value="old('license_no', $item->license_no)" />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active)) class="rounded border-gray-300 text-indigo-600"> Active</label>
            <div class="flex gap-2 pt-2">
                <x-ui.button type="submit" variant="primary">Save</x-ui.button>
                <x-ui.button variant="ghost" :href="route('masters.drivers.index')">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection