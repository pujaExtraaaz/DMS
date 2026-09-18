@extends('layouts.dms')
@section('title', 'Purchase Inwards')
@section('content')
<x-ui.page-header title="Purchase Inwards (GRN)">
    <x-slot name="actions">
        <form method="GET" class="flex gap-2"><input type="search" name="search" value="{{ request('search') }}" placeholder="Inward no..." class="rounded-lg border-gray-300 text-sm"><x-ui.button type="submit" variant="secondary">Search</x-ui.button></form>
    </x-slot>
</x-ui.page-header>
<x-ui.card>
<div class="overflow-x-auto"><table class="min-w-full text-sm divide-y divide-slate-200">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Inward No</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">PO</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Supplier</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Warehouse</th>
<th class="px-3 py-2"></th>
</tr></thead>
<tbody class="divide-y">
@forelse($inwards as $inward)
<tr>
<td class="px-3 py-2 font-medium">{{ $inward->inward_no }}</td>
<td class="px-3 py-2">{{ $inward->inward_date?->format('d M Y') }}</td>
<td class="px-3 py-2">{{ $inward->purchaseOrder?->po_no ?? '—' }}</td>
<td class="px-3 py-2">{{ $inward->supplier?->name }}</td>
<td class="px-3 py-2">{{ $inward->warehouse?->name ?? '—' }}</td>
<td class="px-3 py-2 text-right"><x-ui.button size="sm" variant="secondary" :href="route('purchasing.inwards.show', $inward)">View</x-ui.button></td>
</tr>
@empty
<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No inwards yet. Receive against an approved PO.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $inwards->links() }}</div>
</x-ui.card>
@endsection
