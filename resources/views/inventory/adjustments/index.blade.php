@extends('layouts.dms')
@section('title', 'Stock Adjustments')
@section('content')
<x-ui.page-header title="Stock Adjustments">
    <x-slot name="actions"><x-ui.button variant="primary" :href="route('inventory.adjustments.create')">+ New Adjustment</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<table class="min-w-full text-sm divide-y"><thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">No</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Warehouse</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Reason</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
<th class="px-3 py-2"></th>
</tr></thead>
<tbody class="divide-y">
@forelse($adjustments as $adjustment)
<tr>
<td class="px-3 py-2 font-medium">{{ $adjustment->adjustment_no }}</td>
<td class="px-3 py-2">{{ $adjustment->adjustment_date?->format('d M Y') }}</td>
<td class="px-3 py-2">{{ $adjustment->warehouse?->name ?? '—' }}</td>
<td class="px-3 py-2">{{ $adjustment->reason }}</td>
<td class="px-3 py-2">{{ $adjustment->status }}</td>
<td class="px-3 py-2 text-right"><x-ui.button size="sm" variant="secondary" :href="route('inventory.adjustments.show', $adjustment)">View</x-ui.button></td>
</tr>
@empty
<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No adjustments.</td></tr>
@endforelse
</tbody></table>
<div class="mt-4">{{ $adjustments->links() }}</div>
</x-ui.card>
@endsection
