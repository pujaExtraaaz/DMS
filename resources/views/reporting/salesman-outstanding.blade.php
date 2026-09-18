@extends('layouts.dms')
@section('title', 'Salesman Outstanding')
@section('content')
<x-ui.page-header title="Salesman-wise Outstanding" />
<x-ui.card>
<form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
<x-ui.input name="date_from" type="date" label="From" :value="$dateFrom" />
<x-ui.input name="date_to" type="date" label="To" :value="$dateTo" />
<x-ui.select name="salesperson_id" label="Salesperson" placeholder="All"><option value=""></option>@foreach($salespeople as $u)<option value="{{ $u->id }}" @selected(request('salesperson_id')==$u->id)>{{ $u->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="customer_id" label="Party" placeholder="All"><option value=""></option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="branch_id" label="Branch" placeholder="All"><option value=""></option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch_id')==$b->id)>{{ $b->name }}</option>@endforeach</x-ui.select>
<x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
</form>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Salesperson</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Party</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Invoice</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Due</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Outstanding</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Overdue</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Bucket</th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($rows as $row)
<tr>
<td class="px-3 py-2">{{ $row['invoice']->salesperson?->name ?: '—' }}</td>
<td class="px-3 py-2">{{ $row['invoice']->customer?->name }}</td>
<td class="px-3 py-2">{{ $row['invoice']->invoice_no }}</td>
<td class="px-3 py-2">{{ $row['due_date']->format('d M Y') }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($row['outstanding'], 2) }}</td>
<td class="px-3 py-2 text-right">{{ $row['overdue_days'] }}</td>
<td class="px-3 py-2">{{ $row['bucket'] }}</td>
</tr>
@empty
<tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">No outstanding invoices.</td></tr>
@endforelse
</tbody></table></div>
</x-ui.card>
@endsection
