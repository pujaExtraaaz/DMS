@extends('layouts.dms')
@section('title', 'New Stock Adjustment')
@section('content')
<x-ui.page-header title="New Stock Adjustment">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('inventory.adjustments.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('inventory.adjustments.store') }}" class="space-y-4">@csrf
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<x-ui.input name="adjustment_date" label="Date" type="date" :value="old('adjustment_date', now()->toDateString())" required />
<div>
<label class="block text-sm font-medium text-slate-700 mb-1">Warehouse</label>
<select name="warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm">
<option value="">Unassigned</option>
@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-1">Reason</label>
<select name="reason" class="block w-full rounded-lg border-gray-300 text-sm" required>
@foreach(['opening','damage','shrinkage','found','recount','other'] as $r)
<option value="{{ $r }}">{{ ucfirst($r) }}</option>
@endforeach
</select>
</div>
</div>
<p class="text-xs text-slate-500">Use positive qty to increase stock, negative qty to decrease.</p>
<div class="overflow-x-auto border rounded-lg">
<table class="min-w-full text-sm"><thead class="bg-gray-50"><tr>
<th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">UOM</th><th class="px-3 py-2 text-left">Qty (+/-)</th>
</tr></thead>
<tbody id="adj-lines"><tr>
<td class="px-3 py-2"><select name="items[0][product_id]" class="block w-full rounded-lg border-gray-300 text-sm" required>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></td>
<td class="px-3 py-2"><select name="items[0][uom_id]" class="block w-full rounded-lg border-gray-300 text-sm" required>@foreach($uoms as $u)<option value="{{ $u->id }}">{{ $u->code }}</option>@endforeach</select></td>
<td class="px-3 py-2"><input type="number" step="0.0001" name="items[0][quantity]" class="block w-full rounded-lg border-gray-300 text-sm" required></td>
</tr></tbody></table></div>
<div class="flex gap-3"><x-ui.button type="button" variant="secondary" onclick="addAdjRow()">Add Line</x-ui.button><x-ui.button type="submit" variant="primary">Post Adjustment</x-ui.button></div>
</form></x-ui.card>
@push('scripts')<script>let adi=1;function addAdjRow(){const t=document.getElementById('adj-lines');t.insertAdjacentHTML('beforeend',t.rows[0].outerHTML.replace(/items\[0\]/g,'items['+adi+']'));adi++;}</script>@endpush
@endsection
