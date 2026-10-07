@extends('layouts.dms')
@section('title', 'Invoices')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Tax Invoices">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('invoices.create')">+ Direct Billing</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search invoice no, customer, notes..."
            :reset-url="route('invoices.index')"
        >
            <x-slot name="filters">
                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Status: All</option>
                    @foreach(['draft', 'pending', 'paid', 'partially_paid', 'cancelled'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                    @endforeach
                </select>

                <select
                    name="customer_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Customer: All</option>
                    @foreach($customers as $c)
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

        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <x-ui.sortable-th column="invoice_no" :current-sort="$sort ?? request('sort', 'invoice_date')" :current-direction="$direction ?? request('direction', 'desc')">
                        Invoice No
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="customer" :current-sort="$sort ?? request('sort', 'invoice_date')" :current-direction="$direction ?? request('direction', 'desc')">
                        Customer
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="invoice_date" :current-sort="$sort ?? request('sort', 'invoice_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc">
                        Date
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="status" :current-sort="$sort ?? request('sort', 'invoice_date')" :current-direction="$direction ?? request('direction', 'desc')">
                        Status
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="grand_total" :current-sort="$sort ?? request('sort', 'invoice_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc" align="right">
                        Total
                    </x-ui.sortable-th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
                </tr>
            </x-slot>
            @forelse($invoices as $invoice)
                <tr class="hover:bg-slate-50/75 transition-colors">
                    <td class="px-6 py-4 text-sm font-medium font-mono text-xs text-slate-900">{{ $invoice->invoice_no }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $invoice->customer?->name }}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">{{ $invoice->invoice_date?->format('d M Y') }}</td>
                    <td class="px-6 py-4 text-sm">
                        <x-ui.badge variant="info">{{ ucfirst($invoice->status) }}</x-ui.badge>
                    </td>
                    <td class="px-6 py-4 text-sm text-right font-medium text-slate-900">₹{{ number_format($invoice->grand_total, 2) }}</td>
                    <td class="px-6 py-4 text-right whitespace-nowrap space-x-1">
                        <x-ui.button variant="secondary" size="sm" :href="route('invoices.preview', $invoice)">Preview</x-ui.button>
                        <x-ui.button variant="secondary" size="sm" :href="route('invoices.show', $invoice)">View</x-ui.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-0">
                        <x-ui.empty-state
                            title="No invoices found"
                            description="Try clearing search or filters to see more results."
                            class="border-0 rounded-none py-10"
                        >
                            <x-slot name="action">
                                <a href="{{ route('invoices.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                    Reset Filters
                                </a>
                            </x-slot>
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
        <div class="mt-4">{{ $invoices->links() }}</div>
    </x-ui.card>
</div>
@endsection
