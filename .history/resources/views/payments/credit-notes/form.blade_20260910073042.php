@extends('layouts.dms')
@section('title', 'Create Credit Note')
@section('content')
<x-ui.page-header title="Create Credit Note">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('credit-notes.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('credit-notes.store') }}" class="space-y-4">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-ui.select name="customer_id" label="Customer" required>
            <option value="">Select customer</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id', $item->customer_id)==$customer->id)>{{ $customer->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="invoice_id" label="Linked Invoice">
            <option value="">Optional</option>
            @foreach($invoices as $invoice)
                <option value="{{ $invoice->id }}" @selected(old('invoice_id', $item->invoice_id)==$invoice->id)>{{ $invoice->invoice_no }} · {{ $invoice->customer?->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input name="credit_note_date" label="Date" type="date" :value="old('credit_note_date', optional($item->credit_note_date)->toDateString() ?? now()->toDateString())" required />
        <x-ui.select name="reason" label="Reason" required>
            @foreach(['return','price','scheme','damage','settlement','interest_reversal','other'] as $reason)
                <option value="{{ $reason }}" @selected(old('reason', $item->reason)===$reason)>{{ ucfirst(str_replace('_',' ', $reason)) }}</option>
            @endforeach
        </x-ui.select>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="affects_stock" value="1" @checked(old('affects_stock', true)) class="rounded border-gray-300 text-indigo-600"> Affects stock (return)</label>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
    </div>
    <div class="border rounded-xl overflow-hidden" x-data="{ rows: [{product_id:'',uom_id:'',quantity:1,unit_price:0,tax_amount:0,description:''}] }">
        <div class="bg-slate-50 px-3 py-2 text-sm font-semibold">Lines</div>
        <div class="p-3 space-y-3">
            <template x-for="(row, index) in rows" :key="index">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-2">
                    <select :name="`items[${index}][product_id]`" x-model="row.product_id" class="rounded-lg border-gray-300 text-sm">
                        <option value="">Product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <select :name="`items[${index}][uom_id]`" x-model="row.uom_id" class="rounded-lg border-gray-300 text-sm">
                        <option value="">UOM</option>
                        @foreach($uoms as $uom)
                            <option value="{{ $uom->id }}">{{ $uom->code }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.0001" :name="`items[${index}][quantity]`" x-model="row.quantity" class="rounded-lg border-gray-300 text-sm" placeholder="Qty" required>
                    <input type="number" step="0.01" :name="`items[${index}][unit_price]`" x-model="row.unit_price" class="rounded-lg border-gray-300 text-sm" placeholder="Price" required>
                    <input type="number" step="0.01" :name="`items[${index}][tax_amount]`" x-model="row.tax_amount" class="rounded-lg border-gray-300 text-sm" placeholder="Tax">
                    <input type="text" :name="`items[${index}][description]`" x-model="row.description" class="rounded-lg border-gray-300 text-sm" placeholder="Description">
                </div>
            </template>
            <button type="button" class="text-sm font-medium text-indigo-600" @click="rows.push({product_id:'',uom_id:'',quantity:1,unit_price:0,tax_amount:0,description:''})">+ Add line</button>
        </div>
    </div>
    <x-ui.button type="submit" variant="primary">Save Credit Note</x-ui.button>
</form>
</x-ui.card>
@endsection
