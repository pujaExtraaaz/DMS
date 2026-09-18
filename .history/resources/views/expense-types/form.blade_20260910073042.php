@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Expense Type' : 'Create Expense Type')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Expense Type' : 'Create Expense Type'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('expense-types.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ $item->exists ? route('expense-types.update', $item) : route('expense-types.store') }}" class="space-y-4 max-w-2xl">
    @csrf
    @if($item->exists) @method('PUT') @endif
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
        <x-ui.input name="code" label="Code" :value="old('code', $item->code)" required />
        <x-ui.select name="accounting_treatment" label="Accounting Treatment" required>
            @foreach(['trade_discount','deal_expense','landed_cost'] as $treatment)
                <option value="{{ $treatment }}" @selected(old('accounting_treatment', $item->accounting_treatment)===$treatment)>{{ ucfirst(str_replace('_',' ', $treatment)) }}</option>
            @endforeach
        </x-ui.select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $item->notes) }}</textarea>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active</label>
    <x-ui.button type="submit" variant="primary">Save</x-ui.button>
</form>
</x-ui.card>
@endsection
