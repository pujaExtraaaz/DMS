@extends('layouts.dms')
@section('title', 'Margin Register')
@section('content')
<x-ui.page-header title="Sales & Margin Register" />
<x-ui.card>
<form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3 items-end">
<x-ui.input name="date_from" type="date" label="From" :value="$dateFrom" />
<x-ui.input name="date_to" type="date" label="To" :value="$dateTo" />
<x-ui.select name="customer_id" label="Party" placeholder="All"><option value=""></option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="salesperson_id" label="Salesperson" placeholder="All"><option value=""></option>@foreach($salespeople as $u)<option value="{{ $u->id }}" @selected(request('salesperson_id')==$u->id)>{{ $u->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="brand_id" label="Brand" placeholder="All"><option value=""></option>@foreach($brands as $b)<option value="{{ $b->id }}" @selected(request('brand_id')==$b->id)>{{ $b->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="category_id" label="Category" placeholder="All"><option value=""></option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="product_id" label="Item" placeholder="All"><option value=""></option>@foreach($products as $p)<option value="{{ $p->id }}" @selected(request('product_id')==$p->id)>{{ $p->name }}</option>@endforeach</x-ui.select>
<x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
</form>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Invoice</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Party</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Sale</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Cost</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Deal Exp</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Gross</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Net</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Net %</th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($rows as $row)
<tr>
<td class="px-3 py-2">{{ $row['invoice']->invoice_no }}</td>
<td class="px-3 py-2">{{ $row['invoice']->customer?->name }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($row['sale'], 2) }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($row['cost'], 2) }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($row['deal_expense'], 2) }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($row['gross'], 2) }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($row['net'], 2) }}</td>
<td class="px-3 py-2 text-right">{{ $row['net_pct'] }}%</td>
</tr>
@empty
<tr><td colspan="8" class="px-3 py-6 text-center text-slate-500">No invoices.</td></tr>
@endforelse
</tbody></table></div>
</x-ui.card>
@endsection
