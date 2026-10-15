@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Category' : 'Create Category')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Category' : 'Create Category'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.categories.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
    <form method="POST" action="{{ $item->exists ? route('masters.categories.update', $item) : route('masters.categories.store') }}" class="space-y-4 max-w-xl">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <x-ui.select name="brand_id" label="Brand" placeholder="Select">
            <option value=""></option>
            @foreach($brands as $b)
                <option value="{{ $b->id }}" @selected(old('brand_id', $item->brand_id)==$b->id)>{{ $b->name }}</option>
            @endforeach
        </x-ui.select>

        <x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Code</label>
            <input
                type="text"
                name="code"
                value="{{ old('code', $item->code ?? '') }}"
                readonly
                title="auto gen code"
                placeholder="Auto-generated on save"
                class="block w-full rounded-lg border-gray-300 bg-gray-100 text-gray-700 text-sm font-mono cursor-not-allowed focus:ring-0 focus:border-gray-300"
            >
            <p class="mt-1 text-xs text-slate-500">
                @if($item->exists)
                    Code is system-generated and cannot be edited.
                @else
                    A unique category code is generated on save.
                @endif
            </p>
            @error('code')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active
        </label>

        <x-ui.button type="submit" variant="primary">Save</x-ui.button>
    </form>
</x-ui.card>
@endsection
