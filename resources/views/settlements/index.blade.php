@extends('layouts.dms')
@section('title', 'Cash Settlement')
@section('content')
<x-ui.page-header title="Cash Settlement" description="Settle load sheets in a single screen." />
<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search load sheets by number, route, driver..."
            :searchValue="request('search')"
            :resetUrl="route('settlements.index')"
        >
            <x-slot name="filters">
                <select name="unsettled" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Load Sheets</option>
                    <option value="1" @selected(request('unsettled') === '1')>Unsettled Only</option>
                    <option value="0" @selected(request('unsettled') === '0')>Settled</option>
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="load_sheet_no" label="Load Sheet" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="load_date" label="Date" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="route" label="Route" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="status" label="Status" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($loadSheets as $ls)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-mono font-medium text-slate-900">{{ $ls->load_sheet_no }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $ls->load_date->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-slate-800">{{ $ls->route?->name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <x-ui.badge>{{ ucfirst($ls->status) }}</x-ui.badge>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if(!$ls->settlement)
                                    <x-ui.button variant="primary" size="sm" :href="route('settlements.create', $ls)">Settle</x-ui.button>
                                @else
                                    <x-ui.button variant="ghost" size="sm" :href="route('settlements.show', $ls->settlement)">View</x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="Nothing to settle" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $loadSheets->links() }}</div>
    </x-ui.card>
</div>
@endsection
