@extends('layouts.dms')
@section('title', 'Stock Transfers')
@section('content')
<x-ui.page-header title="Stock Transfers">
    <x-slot name="actions"><x-ui.button variant="primary" :href="route('inventory.transfers.create')">+ New Transfer</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<table class="min-w-full text-sm divide-y"><thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Transfer</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">From</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">To</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
<th class="px-3 py-2"></th>
</tr></thead>
<tbody class="divide-y">
@forelse($transfers as $transfer)
<tr>
<td class="px-3 py-2 font-medium">{{ $transfer->transfer_no }}</td>
<td class="px-3 py-2">{{ $transfer->transfer_date?->format('d M Y') }}</td>
<td class="px-3 py-2">{{ $transfer->fromWarehouse?->name }}</td>
<td class="px-3 py-2">{{ $transfer->toWarehouse?->name }}</td>
<td class="px-3 py-2">{{ str_replace('_',' ', $transfer->status) }}</td>
<td class="px-3 py-2 text-right"><x-ui.button size="sm" variant="secondary" :href="route('inventory.transfers.show', $transfer)">View</x-ui.button></td>
</tr>
@empty
<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No transfers.</td></tr>
@endforelse
</tbody></table>
<div class="mt-4">{{ $transfers->links() }}</div>
</x-ui.card>
@endsection
