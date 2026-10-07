@extends('layouts.dms')
@section('title', 'Stock Transfers')

@section('content')
<div id="listing-container" data-dynamic-container class="space-y-4">
    <x-ui.page-header title="Stock Transfers">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('inventory.transfers.create', ['tab' => $tab ?? 'location'])">
                + New Transfer
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <!-- Operation Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6 text-sm font-medium">
            <a href="{{ route('inventory.transfers.index', ['tab' => 'location']) }}"
               class="{{ ($tab ?? 'location') === 'location' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700' }} whitespace-nowrap py-3 px-1 border-b-2 flex items-center gap-2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                Location Transfers (Warehouse &rarr; Warehouse)
            </a>

            <a href="{{ route('inventory.transfers.index', ['tab' => 'name']) }}"
               class="{{ ($tab ?? 'location') === 'name' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700' }} whitespace-nowrap py-3 px-1 border-b-2 flex items-center gap-2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
                Item / Stock Name Transfers (Stock Reclassification)
            </a>
        </nav>
    </div>

    @if(($tab ?? 'location') === 'name')
        <!-- ITEM / STOCK NAME TRANSFERS TABLE -->
        <x-ui.card>
            <x-ui.table-toolbar
                :search="$search ?? request('search')"
                search-placeholder="Search reference no, remarks, item names..."
                :reset-url="route('inventory.transfers.index', ['tab' => 'name'])"
            >
                <x-slot name="filters">
                    <input type="hidden" name="tab" value="name" data-dynamic-filter>

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
                            <x-ui.sortable-th column="reclassification_no" :current-sort="$sort ?? 'reclassification_date'" :current-direction="$direction ?? 'desc'">
                                Reference No
                            </x-ui.sortable-th>
                            <x-ui.sortable-th column="reclassification_date" :current-sort="$sort ?? 'reclassification_date'" :current-direction="$direction ?? 'desc'" default-direction="desc">
                                Date
                            </x-ui.sortable-th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Warehouse</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Current Item</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">New Item</th>
                            <x-ui.sortable-th column="quantity" :current-sort="$sort ?? 'reclassification_date'" :current-direction="$direction ?? 'desc'" align="right">
                                Quantity
                            </x-ui.sortable-th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Performed By</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($reclassifications as $rec)
                            <tr class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-3 py-2 font-medium font-mono text-xs text-indigo-700">{{ $rec->reclassification_no }}</td>
                                <td class="px-3 py-2 text-slate-600 text-xs">{{ $rec->reclassification_date?->format('d M Y') }}</td>
                                <td class="px-3 py-2 text-slate-900 font-medium text-xs">{{ $rec->warehouse?->name ?: '—' }}</td>
                                <td class="px-3 py-2 text-slate-900 text-xs font-medium">
                                    <span class="text-rose-700 font-semibold">{{ $rec->fromProduct?->name }}</span>
                                    <span class="block text-[11px] text-slate-400 font-mono">{{ $rec->fromProduct?->sku }}</span>
                                </td>
                                <td class="px-3 py-2 text-slate-900 text-xs font-medium">
                                    <span class="text-emerald-700 font-semibold">{{ $rec->toProduct?->name }}</span>
                                    <span class="block text-[11px] text-slate-400 font-mono">{{ $rec->toProduct?->sku }}</span>
                                </td>
                                <td class="px-3 py-2 text-right font-mono font-bold text-xs text-slate-800">
                                    {{ number_format((float) $rec->quantity, 2) }} {{ $rec->uom?->code }}
                                </td>
                                <td class="px-3 py-2 text-slate-600 text-xs">{{ $rec->creator?->name ?: 'System' }}</td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    <x-ui.button size="sm" variant="secondary" :href="route('inventory.transfers.reclassifications.show', $rec)">View</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-0">
                                    <x-ui.empty-state
                                        title="No item name transfers found"
                                        description="Stock name transfers (reclassifications) will appear here once created."
                                        class="border-0 rounded-none py-10"
                                    >
                                        <x-slot name="action">
                                            <a href="{{ route('inventory.transfers.create', ['tab' => 'name']) }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                                + New Name Transfer
                                            </a>
                                        </x-slot>
                                    </x-ui.empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reclassifications->hasPages())
                <div class="mt-4">{{ $reclassifications->links() }}</div>
            @endif
        </x-ui.card>
    @else
        <!-- LOCATION TRANSFERS TABLE (Existing Functionality Preserved 100%) -->
        <x-ui.card>
            <x-ui.table-toolbar
                :search="$search ?? request('search')"
                search-placeholder="Search transfer no, notes..."
                :reset-url="route('inventory.transfers.index', ['tab' => 'location'])"
            >
                <x-slot name="filters">
                    <input type="hidden" name="tab" value="location" data-dynamic-filter>

                    <select
                        name="status"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Status: All</option>
                        @foreach(['draft', 'in_transit', 'received', 'completed', 'cancelled'] as $st)
                            <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                        @endforeach
                    </select>

                    <select
                        name="from_warehouse_id"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">From: All</option>
                        @foreach($warehouses ?? [] as $w)
                            <option value="{{ $w->id }}" @selected(request('from_warehouse_id') == $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>

                    <select
                        name="to_warehouse_id"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">To: All</option>
                        @foreach($warehouses ?? [] as $w)
                            <option value="{{ $w->id }}" @selected(request('to_warehouse_id') == $w->id)>{{ $w->name }}</option>
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
                            <x-ui.sortable-th column="transfer_no" :current-sort="$sort ?? request('sort', 'transfer_date')" :current-direction="$direction ?? request('direction', 'desc')">
                                Transfer No
                            </x-ui.sortable-th>
                            <x-ui.sortable-th column="transfer_date" :current-sort="$sort ?? request('sort', 'transfer_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc">
                                Date
                            </x-ui.sortable-th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">From</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">To</th>
                            <x-ui.sortable-th column="status" :current-sort="$sort ?? request('sort', 'transfer_date')" :current-direction="$direction ?? request('direction', 'desc')">
                                Status
                            </x-ui.sortable-th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($transfers as $transfer)
                            <tr class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-3 py-2 font-medium font-mono text-xs text-slate-900">{{ $transfer->transfer_no }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $transfer->transfer_date?->format('d M Y') }}</td>
                                <td class="px-3 py-2 text-slate-900 font-medium">{{ $transfer->fromWarehouse?->name ?: '—' }}</td>
                                <td class="px-3 py-2 text-slate-900 font-medium">{{ $transfer->toWarehouse?->name ?: '—' }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 text-slate-800">
                                        {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    <x-ui.button size="sm" variant="secondary" :href="route('inventory.transfers.show', $transfer)">View</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-0">
                                    <x-ui.empty-state
                                        title="No stock transfers found"
                                        description="Try clearing search or filters to see more results."
                                        class="border-0 rounded-none py-10"
                                    >
                                        <x-slot name="action">
                                            <a href="{{ route('inventory.transfers.index', ['tab' => 'location']) }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
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
            @if($transfers->hasPages())
                <div class="mt-4">{{ $transfers->links() }}</div>
            @endif
        </x-ui.card>
    @endif
</div>
@endsection
