@extends('layouts.dms')
@section('title', 'Load Sheets')
@section('content')
<x-ui.page-header title="Dispatch / Load Sheets"><x-slot name="actions"><x-ui.button variant="primary" :href="route('logistics.load-sheets.create')">Create Load Sheet</x-ui.button></x-slot></x-ui.page-header>
<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search load sheets by number, vehicle, driver..."
            :searchValue="request('search')"
            :resetUrl="route('logistics.load-sheets.index')"
        >
            <x-slot name="filters">
                <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Statuses</option>
                    @foreach(['draft', 'dispatched', 'completed', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>

                <select name="route_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Routes</option>
                    @foreach($routes as $r)
                        <option value="{{ $r->id }}" @selected(request('route_id') == $r->id)>{{ $r->name }}</option>
                    @endforeach
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
                        <x-ui.sortable-th column="vehicle" label="Vehicle" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="status" label="Status" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($loadSheets as $ls)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-mono font-medium text-slate-900">{{ $ls->load_sheet_no }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $ls->load_date?->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-slate-800">{{ $ls->route?->name ?? '—' }}</td>
                            <td class="px-6 py-4 font-mono text-slate-600 text-xs">{{ $ls->vehicle?->registration_no ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <x-ui.badge>{{ ucfirst($ls->status) }}</x-ui.badge>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <x-ui.button variant="ghost" size="sm" :href="route('logistics.load-sheets.show', $ls)">View</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No load sheets found" />
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
