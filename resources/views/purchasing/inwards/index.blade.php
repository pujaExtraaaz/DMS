@extends('layouts.dms')
@section('title', 'Purchase Inwards')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Purchase Inwards (GRN)" />

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search inward no, supplier..."
            :reset-url="route('purchasing.inwards.index')"
        >
            <x-slot name="filters">
                <select
                    name="supplier_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Supplier: All</option>
                    @foreach($suppliers ?? [] as $s)
                        <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>

                <select
                    name="warehouse_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Warehouse: All</option>
                    @foreach($warehouses ?? [] as $w)
                        <option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>

                <div class="flex items-center gap-1">
                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        data-dynamic-filter
                        placeholder="From"
                        title="From Date"
                        class="rounded-lg border-slate-300 py-1 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    />
                    <span class="text-xs text-slate-400">to</span>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        data-dynamic-filter
                        placeholder="To"
                        title="To Date"
                        class="rounded-lg border-slate-300 py-1 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    />
                </div>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="inward_no" :current-sort="$sort ?? request('sort', 'inward_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Inward No
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="inward_date" :current-sort="$sort ?? request('sort', 'inward_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc">
                            Date
                        </x-ui.sortable-th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">PO</th>
                        <x-ui.sortable-th column="supplier" :current-sort="$sort ?? request('sort', 'inward_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Supplier
                        </x-ui.sortable-th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Warehouse</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($inwards as $inward)
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td class="px-3 py-2 font-medium font-mono text-xs text-slate-900">{{ $inward->inward_no }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $inward->inward_date?->format('d M Y') }}</td>
                            <td class="px-3 py-2 font-mono text-xs text-slate-600">{{ $inward->purchaseOrder?->po_no ?? '—' }}</td>
                            <td class="px-3 py-2 font-medium text-slate-900">{{ $inward->supplier?->name }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $inward->warehouse?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <x-ui.button size="sm" variant="secondary" :href="route('purchasing.inwards.show', $inward)">View</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                <x-ui.empty-state
                                    title="No purchase inwards found"
                                    description="Try clearing search or filters to see more results."
                                    class="border-0 rounded-none py-10"
                                >
                                    <x-slot name="action">
                                        <a href="{{ route('purchasing.inwards.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                            Reset Filters
                                        </a>
                                    </x-slot>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $inwards->links() }}</div>
    </x-ui.card>
</div>
@endsection
