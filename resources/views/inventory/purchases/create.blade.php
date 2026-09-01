@extends('layouts.dms')
@section('title', 'New Purchase')
@section('content')
<x-ui.page-header title="Post Purchase"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('inventory.purchases.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ route('inventory.purchases.store') }}" class="space-y-4">@csrf
<div class="grid grid-cols-1 md:grid-cols-2 gap-4"><x-ui.input name="purchase_date" label="Purchase Date" type="date" :value="old('purchase_date', now()->toDateString())" required /><x-ui.input name="supplier_name" label="Supplier" :value="old('supplier_name')" required /></div>
<div class="overflow-x-auto border rounded-lg"><table class="min-w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">UOM</th><th class="px-3 py-2 text-left">Qty</th><th class="px-3 py-2 text-left">Unit Cost</th><th class="px-3 py-2 text-left">New Selling Rate (Optional)</th><th></th></tr></thead><tbody id="line-items"><tr>
<td class="px-3 py-2"><select name="items[0][product_id]" class="block w-full rounded-lg border-gray-300 text-sm" required>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></td>
<td class="px-3 py-2"><select name="items[0][uom_id]" class="block w-full rounded-lg border-gray-300 text-sm" required>@foreach($uoms as $u)<option value="{{ $u->id }}">{{ $u->code }}</option>@endforeach</select></td>
<td class="px-3 py-2"><input type="number" step="0.0001" name="items[0][quantity]" class="block w-full rounded-lg border-gray-300 text-sm" value="1" required></td>
<td class="px-3 py-2"><input type="number" step="0.01" name="items[0][unit_cost]" class="block w-full rounded-lg border-gray-300 text-sm" required></td>
<td class="px-3 py-2"><input type="number" step="0.01" name="items[0][selling_price]" placeholder="Opt. Rate" class="block w-full rounded-lg border-gray-300 text-sm"></td>
<td></td></tr></tbody></table></div>
<x-ui.button type="button" variant="secondary" onclick="addPurchaseRow()">Add Line</x-ui.button>
<x-ui.button type="submit" variant="primary">Post Purchase</x-ui.button></form></x-ui.card>
@push('scripts')<script>let pi=1;function addPurchaseRow(){document.getElementById('line-items').insertAdjacentHTML('beforeend',document.getElementById('line-items').rows[0].outerHTML.replace(/items\[0\]/g,'items['+pi+']'));pi++;}</script>@endpush
@endsection
