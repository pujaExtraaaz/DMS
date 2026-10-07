@extends('layouts.dms')
@section('title', 'Deals')
@section('content')
<x-ui.page-header title="Deals / Margin">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('expense-types.index')">Expense Types</x-ui.button>
        <x-ui.button variant="primary" :href="route('deals.create')">New Deal</x-ui.button>
    </x-slot>
</x-ui.page-header>
<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search deals by reference, site..."
            :searchValue="request('search')"
            :resetUrl="route('deals.index')"
        >
            <x-slot name="filters">
                <select name="customer_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Customers</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Statuses</option>
                    @foreach(['draft','active','closed','cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50"><tr>
                    <x-ui.sortable-th column="reference" label="Reference" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="customer" label="Customer" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="site_name" label="Site" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="status" label="Status" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="sale_amount" label="Sale" align="right" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="net_margin" label="Net Margin" align="right" :currentSort="$sort" :currentDirection="$direction" />
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium">{{ $item->reference }}</td>
                            <td class="px-3 py-2">{{ $item->customer?->name }}</td>
                            <td class="px-3 py-2">{{ $item->site_name ?: '—' }}</td>
                            <td class="px-3 py-2"><x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge></td>
                            <td class="px-3 py-2 text-right">₹{{ number_format($item->sale_amount, 2) }}</td>
                            <td class="px-3 py-2 text-right font-semibold">₹{{ number_format($item->net_margin, 2) }}</td>
                            <td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('deals.show', $item)">View</x-ui.button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">No deals found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
