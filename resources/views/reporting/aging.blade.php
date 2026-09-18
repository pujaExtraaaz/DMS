@extends('layouts.dms')
@section('title', 'Outstanding Aging')
@section('content')
<x-ui.page-header title="Outstanding Aging">
<x-slot name="actions">
<a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
</x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3 items-end">
<x-ui.input name="date_from" type="date" label="Invoice From" :value="$dateFrom ?? ''" />
<x-ui.input name="date_to" type="date" label="Invoice To" :value="$dateTo ?? ''" />
<x-ui.select name="customer_id" label="Party" placeholder="All"><option value=""></option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="salesperson_id" label="Salesperson" placeholder="All"><option value=""></option>@foreach($salespeople as $u)<option value="{{ $u->id }}" @selected(request('salesperson_id')==$u->id)>{{ $u->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="branch_id" label="Branch" placeholder="All"><option value=""></option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch_id')==$b->id)>{{ $b->name }}</option>@endforeach</x-ui.select>
<x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
</form>
@forelse($groups as $bucket => $rows)
<h3 class="text-sm font-semibold text-slate-700 mt-4 mb-2">{{ $bucket }} ({{ $rows->count() }})</h3>
<div class="overflow-x-auto mb-4">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Party</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Invoice</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Due</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Outstanding</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Days</th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@foreach($rows as $row)
<tr>
<td class="px-3 py-2">{{ $row['invoice']->customer?->name }}</td>
<td class="px-3 py-2">{{ $row['invoice']->invoice_no }}</td>
<td class="px-3 py-2">{{ $row['due_date']->format('d M Y') }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($row['outstanding'], 2) }}</td>
<td class="px-3 py-2 text-right">{{ $row['overdue_days'] }}</td>
</tr>
@endforeach
</tbody></table></div>
@empty
<p class="text-sm text-slate-500">No outstanding invoices.</p>
@endforelse
</x-ui.card>
@endsection
