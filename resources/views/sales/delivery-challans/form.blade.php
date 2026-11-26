@extends('layouts.dms')
@section('title', 'Create Delivery Challan')
@section('content')
<x-ui.page-header title="Create Delivery Challan">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('delivery-challans.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('delivery-challans.store') }}" class="space-y-4">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-ui.select name="customer_id" label="Customer" required>
            <option value="">Select customer</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id', $item->customer_id)==$customer->id)>{{ $customer->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="order_id" label="Sales Order">
            <option value="">Optional</option>
            @foreach($orders as $order)
                <option value="{{ $order->id }}" @selected(old('order_id', $item->order_id)==$order->id)>{{ $order->order_no }} · {{ $order->customer?->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="invoice_id" label="Tax Invoice">
            <option value="">Optional</option>
            @foreach($invoices as $invoice)
                <option value="{{ $invoice->id }}" @selected(old('invoice_id', $item->invoice_id)==$invoice->id)>{{ $invoice->invoice_no }} · {{ $invoice->customer?->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input name="challan_date" label="Date" type="date" :value="old('challan_date', optional($item->challan_date)->toDateString() ?? now()->toDateString())" required />
        <x-ui.select name="warehouse_id" label="Warehouse">
            <option value="">Default</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected(old('warehouse_id')==$warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input name="vehicle_no" label="Vehicle No" :value="old('vehicle_no')" />
        <x-ui.input name="transporter_name" label="Transporter" :value="old('transporter_name')" />
        <x-ui.input name="lr_no" label="LR No" :value="old('lr_no')" />
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Address</label>
        <textarea name="shipping_address" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('shipping_address') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
    </div>
    <div class="border rounded-xl overflow-hidden" x-data="{ rows: [{product_id:'',uom_id:'',quantity:1,description:''}] }">
        <div class="bg-slate-50 px-3 py-2 text-sm font-semibold">Lines</div>
        <div class="p-3 space-y-3">
            <template x-for="(row, index) in rows" :key="index">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
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
                    <input type="text" :name="`items[${index}][description]`" x-model="row.description" class="rounded-lg border-gray-300 text-sm" placeholder="Description">
                </div>
            </template>
            <button type="button" class="text-sm font-medium text-indigo-600" @click="rows.push({product_id:'',uom_id:'',quantity:1,description:''})">+ Add line</button>
        </div>
    </div>
    <x-ui.button type="submit" variant="primary">Save Delivery Challan</x-ui.button>
</form>
</x-ui.card>
@endsection
