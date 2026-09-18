@extends('layouts.dms')
@section('title', 'Create Deal')
@section('content')
<x-ui.page-header title="Create Deal">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('deals.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('deals.store') }}" class="space-y-4" x-data="{ expenses: [] }">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-ui.input name="reference" label="Reference (optional)" :value="old('reference')" />
        <x-ui.select name="customer_id" label="Customer" required>
            <option value="">Select customer</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id')==$customer->id)>{{ $customer->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input name="site_name" label="Site Name" :value="old('site_name')" />
        <x-ui.select name="invoice_id" label="Linked Invoice">
            <option value="">Optional</option>
            @foreach($invoices as $invoice)
                <option value="{{ $invoice->id }}" @selected(old('invoice_id')==$invoice->id)>{{ $invoice->invoice_no }} · {{ $invoice->customer?->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="order_id" label="Linked Order">
            <option value="">Optional</option>
            @foreach($orders as $order)
                <option value="{{ $order->id }}" @selected(old('order_id')==$order->id)>{{ $order->order_no ?? ('#'.$order->id) }} · {{ $order->customer?->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="status" label="Status">
            @foreach(['draft','active'] as $status)
                <option value="{{ $status }}" @selected(old('status', 'draft')===$status)>{{ ucfirst($status) }}</option>
            @endforeach
        </x-ui.select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
    </div>
    <div class="border rounded-xl overflow-hidden">
        <div class="bg-slate-50 px-3 py-2 text-sm font-semibold flex justify-between items-center">
            <span>Deal Expenses (optional)</span>
            <button type="button" class="text-sm font-medium text-indigo-600" @click="expenses.push({expense_type_id:'',amount:0,party_name:'',notes:''})">+ Add expense</button>
        </div>
        <div class="p-3 space-y-3">
            <template x-for="(row, index) in expenses" :key="index">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <select :name="`expenses[${index}][expense_type_id]`" x-model="row.expense_type_id" class="rounded-lg border-gray-300 text-sm" required>
                        <option value="">Expense type</option>
                        @foreach($expenseTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->accounting_treatment }})</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.01" :name="`expenses[${index}][amount]`" x-model="row.amount" class="rounded-lg border-gray-300 text-sm" placeholder="Amount" required>
                    <input type="text" :name="`expenses[${index}][party_name]`" x-model="row.party_name" class="rounded-lg border-gray-300 text-sm" placeholder="Party">
                    <input type="text" :name="`expenses[${index}][notes]`" x-model="row.notes" class="rounded-lg border-gray-300 text-sm" placeholder="Notes">
                </div>
            </template>
            <p x-show="expenses.length === 0" class="text-sm text-slate-500">No expenses yet — you can add them after create as well.</p>
        </div>
    </div>
    <x-ui.button type="submit" variant="primary">Save Deal</x-ui.button>
</form>
</x-ui.card>
@endsection
