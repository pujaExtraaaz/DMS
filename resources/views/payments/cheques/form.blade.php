@extends('layouts.dms')
@section('title', 'Record Cheque')
@section('content')
<x-ui.page-header title="Record Cheque">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('cheques.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('cheques.store') }}" class="space-y-4 max-w-3xl">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-ui.input name="cheque_no" label="Cheque No" :value="old('cheque_no')" required />
        <x-ui.select name="customer_id" label="Party" required>
            <option value="">Select party</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id')==$customer->id)>{{ $customer->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="purpose" label="Purpose" required>
            <option value="pdc" @selected(old('purpose', $item->purpose)==='pdc')">PDC</option>
            <option value="security" @selected(old('purpose', $item->purpose)==='security')">Security</option>
        </x-ui.select>
        <x-ui.select name="direction" label="Direction" required>
            <option value="received_from_client" @selected(old('direction', $item->direction)==='received_from_client')">Received from client</option>
            <option value="received_from_vendor" @selected(old('direction')==='received_from_vendor')">Received from vendor</option>
            <option value="issued_to_vendor" @selected(old('direction')==='issued_to_vendor')">Issued to vendor</option>
        </x-ui.select>
        <x-ui.input name="amount" label="Amount" type="number" step="0.01" :value="old('amount')" required />
        <x-ui.input name="cheque_date" label="Cheque Date" type="date" :value="old('cheque_date', optional($item->cheque_date)->toDateString())" />
        <x-ui.input name="bank_name" label="Bank" :value="old('bank_name')" />
        <x-ui.input name="branch_name" label="Branch" :value="old('branch_name')" />
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea name="notes" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
    </div>
    <x-ui.button type="submit" variant="primary">Save Cheque</x-ui.button>
</form>
</x-ui.card>
@endsection
