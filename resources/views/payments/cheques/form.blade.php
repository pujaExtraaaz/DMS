@extends('layouts.dms')
@section('title', 'Record Cheque')
@section('content')
<x-ui.page-header title="Record Cheque">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('cheques.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('cheques.store') }}" class="space-y-4 max-w-3xl">
    @csrf
    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
        Use this screen to record <span class="font-semibold text-slate-800">security</span>, <span class="font-semibold text-slate-800">credit</span>, and <span class="font-semibold text-slate-800">PDC</span> cheques for a party (moved from Party master).
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-ui.input name="cheque_no" label="Cheque No" :value="old('cheque_no')" required />
        <x-ui.select name="customer_id" label="Party" required>
            <option value="">Select party</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) old('customer_id', request('customer_id')) === (string) $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="purpose" label="Cheque Type / Purpose" required>
            <option value="security" @selected(old('purpose', $item->purpose)==='security')">Security Cheque</option>
            <option value="credit" @selected(old('purpose', $item->purpose)==='credit')">Credit Cheque</option>
            <option value="pdc" @selected(old('purpose', $item->purpose)==='pdc')">Post-Dated Cheque (PDC)</option>
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
