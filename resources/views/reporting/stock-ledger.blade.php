@extends('layouts.dms')
@section('title', 'Stock Ledger')
@section('content')
<x-ui.page-header title="Stock Ledger" />
<x-ui.card>
<form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3 items-end">
<x-ui.input name="date_from" type="date" label="From" :value="$dateFrom" />
<x-ui.input name="date_to" type="date" label="To" :value="$dateTo" />
<x-ui.select name="product_id" label="Product" placeholder="All"><option value=""></option>@foreach($products as $p)<option value="{{ $p->id }}" @selected(request('product_id')==$p->id)>{{ $p->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="warehouse_id" label="Warehouse" placeholder="All"><option value=""></option>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected(request('warehouse_id')==$w->id)>{{ $w->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="brand_id" label="Brand" placeholder="All"><option value=""></option>@foreach($brands as $b)<option value="{{ $b->id }}" @selected(request('brand_id')==$b->id)>{{ $b->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="category_id" label="Category" placeholder="All"><option value=""></option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
</form>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">When</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Product</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Type</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Qty</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Balance</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Notes</th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($movements as $m)
<tr>
<td class="px-3 py-2">{{ $m->created_at?->format('d M Y H:i') }}</td>
<td class="px-3 py-2">{{ $m->product?->name }} ({{ $m->uom?->code }})</td>
<td class="px-3 py-2">{{ $m->type }}</td>
<td class="px-3 py-2 text-right">{{ number_format((float)$m->quantity, 4) }}</td>
<td class="px-3 py-2 text-right">{{ number_format((float)$m->balance_after, 4) }}</td>
<td class="px-3 py-2">{{ $m->notes }}</td>
</tr>
@empty
<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No movements.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $movements->links() }}</div>
</x-ui.card>
@endsection
