@extends('layouts.dms')
@section('title', 'Purchase Orders')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Purchase Orders">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('purchasing.orders.create')">+ New PO</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search PO no, supplier, notes..."
            :reset-url="route('purchasing.orders.index')"
        >
            <x-slot name="filters">
                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Status: All</option>
                    @foreach(['draft','pending_approval','approved','partially_received','closed','cancelled'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ str_replace('_', ' ', ucfirst($st)) }}</option>
                    @endforeach
                </select>

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
            <x-slot name="actions">
                <div class="relative inline-block text-left" x-data="{ open: false }">
                    <button
                        type="button"
                        @click="open = !open"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none"
                    >
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Export</span>
                        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div
                        x-show="open"
                        x-cloak
                        @click.away="open = false"
                        class="absolute right-0 z-20 mt-1 w-36 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                    >
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="flex items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">
                            Excel (.xlsx)
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="flex items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">
                            CSV
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="flex items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">
                            PDF
                        </a>
                    </div>
                </div>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="po_no" :current-sort="$sort ?? request('sort', 'po_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            PO No
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="po_date" :current-sort="$sort ?? request('sort', 'po_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc">
                            Date
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="supplier" :current-sort="$sort ?? request('sort', 'po_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Supplier
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="status" :current-sort="$sort ?? request('sort', 'po_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Status
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="grand_total" :current-sort="$sort ?? request('sort', 'po_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc" align="right">
                            Total
                        </x-ui.sortable-th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-900 font-mono text-xs">{{ $order->po_no }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $order->po_date?->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-slate-900 font-medium">{{ $order->supplier?->name }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 text-slate-800">
                                    {{ str_replace('_', ' ', ucfirst($order->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-900 font-medium">₹{{ number_format($order->grand_total, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                                <x-ui.button variant="secondary" size="sm" :href="route('purchasing.orders.preview', $order)">Preview</x-ui.button>
                                <x-ui.button variant="secondary" size="sm" :href="route('purchasing.orders.show', $order)">View</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                <x-ui.empty-state
                                    title="No purchase orders found"
                                    description="Try clearing search or filters to see more results."
                                    class="border-0 rounded-none py-10"
                                >
                                    <x-slot name="action">
                                        <a href="{{ route('purchasing.orders.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
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
        <div class="mt-4">{{ $orders->links() }}</div>
    </x-ui.card>
</div>
@endsection
