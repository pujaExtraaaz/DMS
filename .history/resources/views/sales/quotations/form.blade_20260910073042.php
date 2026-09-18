@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Quotation' : 'Create Quotation')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Quotation' : 'Create Quotation'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('quotations.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ $item->exists ? route('quotations.update', $item) : route('quotations.store') }}" class="space-y-4">
    @csrf
    @if($item->exists) @method('PUT') @endif
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-ui.select name="customer_id" label="Customer" required>
            <option value="">Select customer</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id', $item->customer_id)==$customer->id)>{{ $customer->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input name="quotation_date" label="Quotation Date" type="date" :value="old('quotation_date', optional($item->quotation_date)->toDateString() ?? now()->toDateString())" required />
        <x-ui.input name="valid_until" label="Valid Until" type="date" :value="old('valid_until', optional($item->valid_until)->toDateString())" />
    </div>
    <x-ui.input name="discount_amount" label="Discount Amount" type="number" step="0.01" :value="old('discount_amount', $item->discount_amount ?? 0)" />
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $item->notes) }}</textarea>
    </div>

    <div class="border rounded-xl overflow-hidden">
        <div class="bg-slate-50 px-3 py-2 text-sm font-semibold">Line Items</div>
        <div class="p-3 space-y-3" x-data="{ rows: {{ Js::from(old('items', $item->exists ? $item->items->map(fn($i)=>['product_id'=>$i->product_id,'uom_id'=>$i->uom_id,'quantity'=>$i->quantity,'unit_price'=>$i->unit_price])->values() : [['product_id'=>'','uom_id'=>'','quantity'=>1,'unit_price'=>'']])) }} }">
            <template x-for="(row, index) in rows" :key="index">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-2 items-end">
                    <div>
                        <label class="text-xs text-slate-500">Product</label>
                        <select :name="`items[${index}][product_id]`" x-model="row.product_id" class="w-full rounded-lg border-gray-300 text-sm" required>
                            <option value="">Select</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">UOM</label>
                        <select :name="`items[${index}][uom_id]`" x-model="row.uom_id" class="w-full rounded-lg border-gray-300 text-sm" required>
                            <option value="">Select</option>
                            @foreach($products->pluck('baseUom')->filter()->unique('id') as $uom)
                                <option value="{{ $uom->id }}">{{ $uom->code }}</option>
                            @endforeach
                            @php $allUoms = \App\Domains\Master\Models\Uom::where('is_active', true)->orderBy('name')->get(); @endphp
                            @foreach($allUoms as $uom)
                                <option value="{{ $uom->id }}">{{ $uom->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Qty</label>
                        <input type="number" step="0.0001" min="0.0001" :name="`items[${index}][quantity]`" x-model="row.quantity" class="w-full rounded-lg border-gray-300 text-sm" required>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Unit Price (optional)</label>
                        <input type="number" step="0.01" min="0" :name="`items[${index}][unit_price]`" x-model="row.unit_price" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <button type="button" class="text-sm text-red-600" @click="rows.splice(index,1)" x-show="rows.length > 1">Remove</button>
                    </div>
                </div>
            </template>
            <button type="button" class="text-sm font-medium text-indigo-600" @click="rows.push({product_id:'',uom_id:'',quantity:1,unit_price:''})">+ Add line</button>
        </div>
    </div>

    <x-ui.button type="submit" variant="primary">Save Quotation</x-ui.button>
</form>
</x-ui.card>
@endsection
