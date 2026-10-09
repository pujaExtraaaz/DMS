@extends('layouts.dms')
@section('title', 'Quotations')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Quotations">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('quotations.create')">+ New Quotation</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search', request('q'))"
            search-placeholder="Search quotation no, customer, notes..."
            :reset-url="route('quotations.index')"
        >
            <x-slot name="filters">
                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Status: All</option>
                    @foreach(['draft', 'sent', 'accepted', 'rejected', 'converted'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>

                <select
                    name="customer_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Customer: All</option>
                    @foreach($customers ?? [] as $c)
                        <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->name }}</option>
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
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="quotation_no" :current-sort="$sort ?? request('sort', 'quotation_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Quotation No
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="customer" :current-sort="$sort ?? request('sort', 'quotation_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Customer
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="quotation_date" :current-sort="$sort ?? request('sort', 'quotation_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc">
                            Date
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="status" :current-sort="$sort ?? request('sort', 'quotation_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Status
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="grand_total" :current-sort="$sort ?? request('sort', 'quotation_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc" align="right">
                            Total
                        </x-ui.sortable-th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td class="px-3 py-2 font-medium font-mono text-xs text-slate-900">{{ $item->quotation_no }}</td>
                            <td class="px-3 py-2 text-slate-900 font-medium">{{ $item->customer?->name }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->quotation_date?->format('d M Y') }}</td>
                            <td class="px-3 py-2">
                                <x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right font-medium text-slate-900">₹{{ number_format($item->grand_total, 2) }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <x-ui.button variant="secondary" size="sm" :href="route('quotations.show', $item)">View</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                <x-ui.empty-state
                                    title="No quotations found"
                                    description="Try clearing search or filters to see more results."
                                    class="border-0 rounded-none py-10"
                                >
                                    <x-slot name="action">
                                        <a href="{{ route('quotations.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
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
