@extends('layouts.dms')
@section('title', 'Direct Billing')
@section('content')
<x-ui.page-header title="Direct Billing"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('invoices.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ route('invoices.store') }}" class="space-y-4">@csrf
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<x-ui.select name="customer_id" id="customer_id" label="Customer" required placeholder="Select">@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="invoice_date" label="Invoice Date" type="date" :value="now()->toDateString()" required />
<x-ui.input name="discount_amount" label="Discount" type="number" step="0.01" value="0" /></div>
<div class="overflow-x-auto border rounded-lg">
    <table class="min-w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">UOM</th><th class="px-3 py-2 text-left">Qty</th><th class="px-3 py-2 text-left">Price</th><th></th></tr></thead>
    <tbody id="line-items"><tr>
        <td class="px-3 py-2"><select name="items[0][product_id]" class="product-select block w-full rounded-lg border-gray-300 text-sm" required><option value="">Product</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></td>
        <td class="px-3 py-2"><select name="items[0][uom_id]" class="uom-select block w-full rounded-lg border-gray-300 text-sm" required><option value="">UOM</option>@foreach($uoms as $u)<option value="{{ $u->id }}">{{ $u->code }}</option>@endforeach</select></td>
        <td class="px-3 py-2"><input type="number" step="0.0001" name="items[0][quantity]" class="qty-input block w-full rounded-lg border-gray-300 text-sm" value="1" required></td>
        <td class="px-3 py-2"><input type="number" step="0.01" name="items[0][unit_price]" class="price-input block w-full rounded-lg border-gray-300 text-sm" required></td><td></td>
    </tr></tbody></table></div>
<x-ui.button type="button" variant="secondary" onclick="addRow()">Add Line</x-ui.button>
<x-ui.button type="submit" variant="primary">Create Invoice</x-ui.button></form></x-ui.card>
@push('scripts')@include('partials.line-items-script')@endpush
@endsection
