@extends('layouts.dms')
@section('title', 'New Stock Transfer')
@section('content')
<x-ui.page-header title="New Stock Transfer">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('inventory.transfers.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('inventory.transfers.store') }}" class="space-y-4">@csrf
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<x-ui.input name="transfer_date" label="Date" type="date" :value="old('transfer_date', now()->toDateString())" required />
<div>
<label class="block text-sm font-medium text-slate-700 mb-1">From Warehouse</label>
<select name="from_warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm" required>
@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-1">To Warehouse</label>
<select name="to_warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm" required>
@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
</select>
</div>
</div>
<div class="overflow-x-auto border rounded-lg">
<table class="min-w-full text-sm"><thead class="bg-gray-50"><tr>
<th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">UOM</th><th class="px-3 py-2 text-left">Qty</th>
</tr></thead>
<tbody id="tr-lines"><tr>
<td class="px-3 py-2"><select name="items[0][product_id]" class="block w-full rounded-lg border-gray-300 text-sm" required>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></td>
<td class="px-3 py-2"><select name="items[0][uom_id]" class="block w-full rounded-lg border-gray-300 text-sm" required>@foreach($uoms as $u)<option value="{{ $u->id }}">{{ $u->code }}</option>@endforeach</select></td>
<td class="px-3 py-2"><input type="number" step="0.0001" name="items[0][quantity]" class="block w-full rounded-lg border-gray-300 text-sm" value="1" required></td>
</tr></tbody></table></div>
<label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="dispatch_now" value="1" class="rounded border-gray-300" checked> Dispatch now (stock out from source)</label>
<div class="flex gap-3"><x-ui.button type="button" variant="secondary" onclick="addTrRow()">Add Line</x-ui.button><x-ui.button type="submit" variant="primary">Create Transfer</x-ui.button></div>
</form></x-ui.card>
@push('scripts')<script>let tri=1;function addTrRow(){const t=document.getElementById('tr-lines');t.insertAdjacentHTML('beforeend',t.rows[0].outerHTML.replace(/items\[0\]/g,'items['+tri+']'));tri++;}</script>@endpush
@endsection
