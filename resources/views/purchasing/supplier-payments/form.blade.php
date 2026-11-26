@extends('layouts.dms')
@section('title', 'Record Supplier Payment')
@section('content')
<x-ui.page-header title="Record Supplier Payment">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('purchasing.supplier-payments.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('purchasing.supplier-payments.store') }}" class="space-y-4">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-ui.select name="supplier_id" label="Supplier" required>
            <option value="">Select supplier</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $item->supplier_id)==$supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="purchase_invoice_id" label="Purchase Invoice">
            <option value="">Optional</option>
            @foreach($invoices as $invoice)
                <option value="{{ $invoice->id }}" @selected(old('purchase_invoice_id', $item->purchase_invoice_id)==$invoice->id)>{{ $invoice->invoice_no }} · {{ $invoice->supplier?->name }} · ₹{{ number_format($invoice->grand_total, 2) }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input name="payment_date" label="Date" type="date" :value="old('payment_date', optional($item->payment_date)->toDateString() ?? now()->toDateString())" required />
        <x-ui.input name="amount" label="Amount" type="number" step="0.01" min="0.01" :value="old('amount')" required />
        <x-ui.select name="method" label="Method" required>
            @foreach(['cash','upi','bank','cheque','other'] as $method)
                <option value="{{ $method }}" @selected(old('method', $item->method)===$method)>{{ ucfirst($method) }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input name="reference_no" label="Reference No" :value="old('reference_no')" />
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
    </div>
    <x-ui.button type="submit" variant="primary">Save Payment</x-ui.button>
</form>
</x-ui.card>
@endsection
