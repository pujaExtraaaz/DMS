@extends('layouts.dms')
@section('title', 'Allocate Landed Cost')
@section('content')
<x-ui.page-header title="Allocate Landed Cost">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('purchasing.landed-costs.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('purchasing.landed-costs.store') }}" class="space-y-4">@csrf
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<x-ui.input name="landed_date" label="Date" type="date" :value="old('landed_date', now()->toDateString())" required />
<div>
<label class="block text-sm font-medium text-slate-700 mb-1">Purchase Invoice</label>
<select name="purchase_invoice_id" class="block w-full rounded-lg border-gray-300 text-sm" required>
<option value="">Select</option>
@foreach($invoices as $inv)
<option value="{{ $inv->id }}" @selected(old('purchase_invoice_id', request('purchase_invoice_id'))==$inv->id)>{{ $inv->invoice_no }} — {{ $inv->supplier?->name }} (₹{{ number_format($inv->grand_total,2) }})</option>
@endforeach
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-1">Freight Bill</label>
<select name="freight_bill_id" class="block w-full rounded-lg border-gray-300 text-sm">
<option value="">Optional</option>
@foreach($freightBills as $fb)
<option value="{{ $fb->id }}">{{ $fb->freight_no }} (₹{{ number_format($fb->total_amount,2) }})</option>
@endforeach
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-1">Allocation Method</label>
<select name="allocation_method" class="block w-full rounded-lg border-gray-300 text-sm" required>
@foreach(['qty','value','weight','volume','equal','manual'] as $m)
<option value="{{ $m }}">{{ ucfirst($m) }}</option>
@endforeach
</select>
</div>
<x-ui.input
    name="total_additional_cost"
    label="Additional Cost (excluding Freight)"
    type="number"
    step="0.01"
    :value="old('total_additional_cost')"
/>
</div>
<p class="text-xs text-slate-500">Supplier invoice rates are not changed. Allocation updates valuation layers only.</p>
<x-ui.button type="submit" variant="primary">Allocate & Post</x-ui.button>
</form></x-ui.card>
@endsection
