@extends('layouts.dms')
@section('title', 'Freight Bills')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Freight Bills">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('purchasing.freight-bills.create')">+ New Freight Bill</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search freight no, transporter, LR no..."
            :reset-url="route('purchasing.freight-bills.index')"
        >
            <x-slot name="filters">
                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Status: All</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="posted" @selected(request('status') === 'posted')>Posted</option>
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
                        <x-ui.sortable-th column="freight_no" :current-sort="$sort ?? request('sort', 'bill_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Freight No
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="bill_date" :current-sort="$sort ?? request('sort', 'bill_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc">
                            Date
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="transporter_name" :current-sort="$sort ?? request('sort', 'bill_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Transporter
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="status" :current-sort="$sort ?? request('sort', 'bill_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Status
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="total_amount" :current-sort="$sort ?? request('sort', 'bill_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc" align="right">
                            Total
                        </x-ui.sortable-th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td class="px-3 py-2 font-medium font-mono text-xs text-slate-900">{{ $item->freight_no }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->bill_date?->format('d M Y') }}</td>
                            <td class="px-3 py-2 text-slate-900">{{ $item->transporter_name ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 text-slate-800">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right font-medium text-slate-900">₹{{ number_format($item->total_amount, 2) }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <x-ui.button size="sm" variant="secondary" :href="route('purchasing.freight-bills.show', $item)">View</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                <x-ui.empty-state
                                    title="No freight bills found"
                                    description="Try clearing search or filters to see more results."
                                    class="border-0 rounded-none py-10"
                                >
                                    <x-slot name="action">
                                        <a href="{{ route('purchasing.freight-bills.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
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
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
