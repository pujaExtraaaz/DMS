@extends('layouts.dms')
@section('title', 'Credit Notes')
@section('content')
<x-ui.page-header title="Credit Notes">
    <x-slot name="actions">
        <x-ui.button variant="primary" :href="route('credit-notes.create')">New Credit Note</x-ui.button>
    </x-slot>
</x-ui.page-header>
<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search credit notes by number, customer, notes..."
            :searchValue="request('search')"
            :resetUrl="route('credit-notes.index')"
        >
            <x-slot name="filters">
                <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All statuses</option>
                    @foreach(['draft', 'pending', 'approved', 'rejected', 'posted'] as $status)
                        <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>

                <select name="reason" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All reasons</option>
                    @foreach(['return', 'rate_difference', 'discount', 'damage', 'scheme', 'other'] as $reason)
                        <option value="{{ $reason }}" @selected(request('reason')===$reason)>{{ ucfirst(str_replace('_', ' ', $reason)) }}</option>
                    @endforeach
                </select>

                <select name="customer_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Customers</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50"><tr>
                    <x-ui.sortable-th column="credit_note_no" label="Credit Note" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="customer" label="Customer" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="reason" label="Reason" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="status" label="Status" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="grand_total" label="Amount" align="right" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="credit_note_date" label="Date" :currentSort="$sort" :currentDirection="$direction" />
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium font-mono text-xs">{{ $item->credit_note_no }}</td>
                            <td class="px-3 py-2">{{ $item->customer?->name }}</td>
                            <td class="px-3 py-2">{{ str_replace('_',' ', $item->reason) }}</td>
                            <td class="px-3 py-2"><x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge></td>
                            <td class="px-3 py-2 text-right font-semibold">₹{{ number_format($item->grand_total, 2) }}</td>
                            <td class="px-3 py-2 text-slate-600 text-sm">{{ $item->credit_note_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('credit-notes.show', $item)">View</x-ui.button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">No credit notes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
