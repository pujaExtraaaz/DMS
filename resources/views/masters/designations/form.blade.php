@extends('layouts.dms')

@section('title', $item->exists ? 'Edit Designation' : 'Create Designation')

@section('content')
    <x-ui.page-header :title="$item->exists ? 'Edit Designation' : 'Create Designation'">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('masters.designations.index')">Back</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <form method="POST" action="{{ $item->exists ? route('masters.designations.update', $item) : route('masters.designations.store') }}" class="space-y-4 max-w-xl">
            @csrf
            @if($item->exists) @method('PUT') @endif
            <x-ui.input name="name" label="Designation Name" type="text" :value="old('name', $item->name)" required />
            <x-ui.input name="code" label="Code" type="text" :value="old('code', $item->code)" placeholder="Optional unique code (auto-generated if empty)" />
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $item->description) }}</textarea>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Active (Active designations appear in the Party Contact designation dropdown)
            </label>
            <div class="flex gap-2 pt-2">
                <x-ui.button type="submit" variant="primary">Save</x-ui.button>
                <x-ui.button variant="ghost" :href="route('masters.designations.index')">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
