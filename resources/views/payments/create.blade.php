@extends('layouts.dms')
@section('title', 'Record Payment')
@section('content')
<x-ui.page-header title="Record Payment"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('payments.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('payments.store') }}" class="space-y-4 max-w-xl" x-data="{ method: '{{ old('method', 'cash') }}' }">
@csrf
<x-ui.select name="invoice_id" label="Invoice" required>
@foreach($invoices as $inv)
<option value="{{ $inv->id }}" @selected(old('invoice_id', $invoice?->id ?? null) == $inv->id)>{{ $inv->invoice_no }} — {{ $inv->customer->name }} (₹{{ number_format($inv->grand_total - $inv->paid_amount, 2) }} due)</option>
@endforeach
</x-ui.select>
<x-ui.input name="amount" label="Amount" type="number" step="0.01" :value="old('amount', $invoice ? ($invoice->grand_total - $invoice->paid_amount) : '')" required />
<x-ui.select name="method" label="Method" required x-model="method">
<option value="cash">Cash</option>
<option value="upi">UPI</option>
<option value="bank">Bank</option>
<option value="other">Other</option>
</x-ui.select>
<div x-show="method === 'bank'" x-cloak>
    <x-ui.select name="bank_ledger_id" label="Bank account" :required="false">
        <option value="">Select bank account</option>
        @foreach($bankLedgers as $ledger)
            <option value="{{ $ledger->id }}" @selected((string) old('bank_ledger_id') === (string) $ledger->id)>
                {{ $ledger->name }}@if($ledger->bankAccount?->account_number) — {{ $ledger->bankAccount->account_number }}@endif
            </option>
        @endforeach
    </x-ui.select>
    @if($bankLedgers->isEmpty())
        <p class="text-xs text-amber-700">No bank accounts are set up in Bank Master for this company yet.</p>
    @else
        <p class="text-xs text-slate-500">The receipt is posted to this bank ledger for bank reports and reconciliation.</p>
    @endif
</div>
<x-ui.input name="paid_at" label="Paid At" type="datetime-local" :value="old('paid_at', now()->format('Y-m-d\TH:i'))" />
<x-ui.input name="notes" label="Notes" :value="old('notes')" />
<x-ui.button type="submit" variant="primary">Save Payment</x-ui.button>
</form>
</x-ui.card>
@endsection