@extends('layouts.dms')
@section('title', 'Stock Adjustments')
@section('content')
<x-ui.page-header title="Stock Adjustments">
    <x-slot name="actions"><x-ui.button variant="primary" :href="route('inventory.adjustments.create')">+ New Adjustment</x-ui.button></x-slot>
</x-ui.page-header>
<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search adjustments by no, reason, notes..."
            :searchValue="request('search')"
            :resetUrl="route('inventory.adjustments.index')"
        >
            <x-slot name="filters">
                <select name="warehouse_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" @selected(request('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                    @endforeach
                </select>

                <select name="reason" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Reasons</option>
                    @foreach(['opening', 'damage', 'shrinkage', 'found', 'recount', 'other'] as $r)
                        <option value="{{ $r }}" @selected(request('reason') === $r)>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>

                <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Statuses</option>
                    @foreach(['draft', 'approved', 'applied', 'cancelled'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="adjustment_no" label="No" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="adjustment_date" label="Date" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="warehouse" label="Warehouse" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="reason" label="Reason" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="status" label="Status" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($adjustments as $adjustment)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $adjustment->adjustment_no }}</td>
                        <td class="px-3 py-2">{{ $adjustment->adjustment_date?->format('d M Y') }}</td>
                        <td class="px-3 py-2">{{ $adjustment->warehouse?->name ?? '—' }}</td>
                        <td class="px-3 py-2">{{ ucfirst($adjustment->reason) }}</td>
                        <td class="px-3 py-2">
                            <x-ui.badge>{{ ucfirst($adjustment->status) }}</x-ui.badge>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <x-ui.button size="sm" variant="secondary" :href="route('inventory.adjustments.show', $adjustment)">View</x-ui.button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No adjustments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $adjustments->links() }}</div>
    </x-ui.card>
</div>
@endsection
