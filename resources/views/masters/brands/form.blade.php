@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Brand' : 'Create Brand')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Brand' : 'Create Brand'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.brands.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
    <form method="POST" action="{{ $item->exists ? route('masters.brands.update', $item) : route('masters.brands.store') }}" class="space-y-4 max-w-xl">
        @csrf @if($item->exists) @method('PUT') @endif
        <x-ui.select name="company_id" label="Company" placeholder="Select">
            <option value=""></option>
            @foreach($companies as $c)
                <option value="{{ $c->id }}" @selected(old('company_id', $item->company_id)==$c->id)>{{ $c->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input name="name" label="Brand Name" :value="old('name', $item->name)" required />
        
        <div>
            <label for="detail" class="block text-sm font-medium text-slate-700 mb-1">
                Detail
            </label>

            <textarea
                id="detail"
                name="detail"
                rows="4"
                maxlength="2000"
                placeholder="Enter brand details..."
                class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >{{ old('detail', $item->detail) }}</textarea>

            @error('detail')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        @if($item->exists)
            <x-ui.input name="code" label="Code" :value="old('code', $item->code)" readonly />
            <p class="text-xs text-slate-500 -mt-3">Code is system-generated when a brand is created and cannot be edited.</p>
        @else
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-xs text-slate-600">
                <strong>Auto code:</strong> a unique brand code (<code>BR-&lt;company&gt;-&lt;seq&gt;</code>) is generated on save to prevent duplicates.
            </div>
        @endif
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active
        </label>
        <x-ui.button type="submit" variant="primary">Save</x-ui.button>
    </form>
</x-ui.card>
@endsection
