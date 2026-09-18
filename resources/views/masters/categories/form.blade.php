@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Category' : 'Create Category')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Category' : 'Create Category'"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.categories.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ $item->exists ? route('masters.categories.update', $item) : route('masters.categories.store') }}" class="space-y-4 max-w-xl">@csrf @if($item->exists) @method('PUT') @endif
<x-ui.select name="brand_id" label="Brand" placeholder="Select"><option value=""></option>@foreach($brands as $b)<option value="{{ $b->id }}" @selected(old('brand_id', $item->brand_id)==$b->id)>{{ $b->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.input name="code" label="Code" :value="old('code', $item->code)" required />
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active</label>
<x-ui.button type="submit" variant="primary">Save</x-ui.button></form></x-ui.card>
@endsection
