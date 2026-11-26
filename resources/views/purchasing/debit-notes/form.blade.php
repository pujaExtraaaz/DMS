@extends('layouts.dms')
@section('title', 'Create Debit Note')
@section('content')
<x-ui.page-header title="Create Debit Note">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('purchasing.debit-notes.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('purchasing.debit-notes.store') }}" class="space-y-4">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-ui.select name="supplier_id" label="Supplier" required>
            <option value="">Select supplier</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $item->supplier_id)==$supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="purchase_invoice_id" label="Linked Purchase Invoice">
            <option value="">Optional</option>
            @foreach($invoices as $invoice)
                <option value="{{ $invoice->id }}" @selected(old('purchase_invoice_id', $item->purchase_invoice_id)==$invoice->id)>{{ $invoice->invoice_no }} · {{ $invoice->supplier?->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input name="debit_note_date" label="Date" type="date" :value="old('debit_note_date', optional($item->debit_note_date)->toDateString() ?? now()->toDateString())" required />
        <x-ui.select name="reason" label="Reason" required>
            @foreach(['return','price','damage','shortage','other'] as $reason)
                <option value="{{ $reason }}" @selected(old('reason', $item->reason)===$reason)>{{ ucfirst($reason) }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="warehouse_id" label="Warehouse">
            <option value="">Default</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected(old('warehouse_id')==$warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
        </x-ui.select>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="affects_stock" value="1" @checked(old('affects_stock', true)) class="rounded border-gray-300 text-indigo-600"> Affects stock (purchase return)</label>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
    </div>
    <div class="border rounded-xl overflow-hidden" x-data="{ rows: [{product_id:'',uom_id:'',quantity:1,unit_cost:0,tax_amount:0,description:''}] }">
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
                    <input type="number" step="0.01" :name="`items[${index}][unit_cost]`" x-model="row.unit_cost" class="rounded-lg border-gray-300 text-sm" placeholder="Cost" required>
                    <input type="number" step="0.01" :name="`items[${index}][tax_amount]`" x-model="row.tax_amount" class="rounded-lg border-gray-300 text-sm" placeholder="Tax">
                    <input type="text" :name="`items[${index}][description]`" x-model="row.description" class="rounded-lg border-gray-300 text-sm" placeholder="Description">
                </div>
            </template>
            <button type="button" class="text-sm font-medium text-indigo-600" @click="rows.push({product_id:'',uom_id:'',quantity:1,unit_cost:0,tax_amount:0,description:''})">+ Add line</button>
        </div>
    </div>
    <x-ui.button type="submit" variant="primary">Save Debit Note</x-ui.button>
</form>
</x-ui.card>
@endsection
