@extends('layouts.dms')
@section('title', 'Freight Bills')
@section('content')
<x-ui.page-header title="Freight Bills">
    <x-slot name="actions"><x-ui.button variant="primary" :href="route('purchasing.freight-bills.create')">+ New Freight Bill</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<table class="min-w-full text-sm divide-y"><thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">No</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Transporter</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Total</th>
<th class="px-3 py-2"></th>
</tr></thead>
<tbody class="divide-y">
@forelse($items as $item)
<tr>
<td class="px-3 py-2 font-medium">{{ $item->freight_no }}</td>
<td class="px-3 py-2">{{ $item->bill_date?->format('d M Y') }}</td>
<td class="px-3 py-2">{{ $item->transporter_name ?? '—' }}</td>
<td class="px-3 py-2">{{ $item->status }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($item->total_amount, 2) }}</td>
<td class="px-3 py-2 text-right"><x-ui.button size="sm" variant="secondary" :href="route('purchasing.freight-bills.show', $item)">View</x-ui.button></td>
</tr>
@empty
<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No freight bills.</td></tr>
@endforelse
</tbody></table>
<div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
