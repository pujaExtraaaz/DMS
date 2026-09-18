@extends('layouts.dms')
@section('title', 'Purchase Orders')
@section('content')
<x-ui.page-header title="Purchase Orders">
    <x-slot name="actions">
        <form method="GET" class="flex gap-2">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="PO no..." class="rounded-lg border-gray-300 text-sm">
            <select name="status" class="rounded-lg border-gray-300 text-sm">
                <option value="">All statuses</option>
                @foreach(['draft','pending_approval','approved','partially_received','closed','cancelled'] as $st)
                    <option value="{{ $st }}" @selected(request('status')===$st)>{{ str_replace('_',' ', ucfirst($st)) }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        </form>
        <x-ui.button variant="primary" :href="route('purchasing.orders.create')">+ New PO</x-ui.button>
    </x-slot>
</x-ui.page-header>
<x-ui.card>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">PO No</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Supplier</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Total</th>
<th class="px-3 py-2"></th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($orders as $order)
<tr>
<td class="px-3 py-2 font-medium">{{ $order->po_no }}</td>
<td class="px-3 py-2">{{ $order->po_date?->format('d M Y') }}</td>
<td class="px-3 py-2">{{ $order->supplier?->name }}</td>
<td class="px-3 py-2"><x-ui.badge>{{ str_replace('_',' ', $order->status) }}</x-ui.badge></td>
<td class="px-3 py-2 text-right">₹{{ number_format($order->grand_total, 2) }}</td>
<td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('purchasing.orders.show', $order)">View</x-ui.button></td>
</tr>
@empty
<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No purchase orders yet.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $orders->links() }}</div>
</x-ui.card>
@endsection
