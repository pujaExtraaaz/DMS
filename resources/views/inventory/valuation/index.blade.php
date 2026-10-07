@extends('layouts.dms')
@section('title', 'Stock Valuation')
@section('content')
<x-ui.page-header title="FIFO / LIFO Valuation" description="Configure costing method and view inventory value from cost layers" />

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <x-ui.stat-card label="Method" value="{{ strtoupper($valuation['method']) }}" accent="indigo" />
    <x-ui.stat-card label="At Cost" value="₹{{ number_format($valuation['at_cost'], 2) }}" accent="blue" />
    <x-ui.stat-card label="At Landed" value="₹{{ number_format($valuation['at_landed'], 2) }}" accent="emerald" />
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
<x-ui.card title="Set Valuation Method">
<form method="POST" action="{{ route('inventory.valuation.store') }}" class="space-y-3">@csrf
<x-ui.select name="company_id" label="Company" required placeholder="Select company">
@foreach($companies as $c)
<option value="{{ $c->id }}" @selected(old('company_id', auth()->user()?->company_id)==$c->id)>{{ $c->name }}</option>
@endforeach
</x-ui.select>
<x-ui.select name="financial_year_id" label="Financial Year" placeholder="All / current">
<option value=""></option>
@foreach($financialYears as $fy)
<option value="{{ $fy->id }}" @selected(old('financial_year_id')==$fy->id)>{{ $fy->name ?? ($fy->starts_on.' → '.$fy->ends_on) }}</option>
@endforeach
</x-ui.select>
<x-ui.select name="method" label="Method" required>
<option value="fifo" @selected(old('method', 'fifo')==='fifo')>FIFO</option>
<option value="lifo" @selected(old('method')==='lifo')>LIFO</option>
</x-ui.select>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-indigo-600"> Active</label>
<x-ui.button type="submit" variant="primary">Save Method</x-ui.button>
</form>
</x-ui.card>

<div id="listing-container" data-dynamic-container>
<x-ui.card title="Configured Settings">
<x-ui.table-toolbar
    placeholder="Search by company or method..."
    :searchValue="request('search')"
    :resetUrl="route('inventory.valuation.index')"
>
    <x-slot name="filters">
        <select name="company_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
            <option value="">All Companies</option>
            @foreach($companies as $c)
                <option value="{{ $c->id }}" @selected(request('company_id') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="method" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
            <option value="">All Methods</option>
            <option value="fifo" @selected(request('method') === 'fifo')>FIFO</option>
            <option value="lifo" @selected(request('method') === 'lifo')>LIFO</option>
        </select>
    </x-slot>
</x-ui.table-toolbar>

<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<x-ui.sortable-th column="company" label="Company" :currentSort="$sort" :currentDirection="$direction" />
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">FY</th>
<x-ui.sortable-th column="method" label="Method" :currentSort="$sort" :currentDirection="$direction" />
<x-ui.sortable-th column="is_active" label="Active" :currentSort="$sort" :currentDirection="$direction" />
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr>
<td class="px-3 py-2 font-medium">{{ $item->company?->name }}</td>
<td class="px-3 py-2">{{ $item->financialYear?->name ?? '—' }}</td>
<td class="px-3 py-2 uppercase font-semibold">{{ $item->method }}</td>
<td class="px-3 py-2">
    <x-ui.badge :variant="$item->is_active ? 'success' : 'secondary'">{{ $item->is_active ? 'Yes' : 'No' }}</x-ui.badge>
</td>
</tr>
@empty
<tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No settings found.</td></tr>
@endforelse
</tbody></table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
</div>
</div>
@endsection
