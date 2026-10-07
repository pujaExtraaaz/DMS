@extends('layouts.dms')
@section('title', 'Deliveries')
@section('content')
<x-ui.page-header title="Deliveries" />

<div id="listing-container" data-dynamic-container>
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('deliveries.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ !request('status') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">All</a>
        <a href="{{ route('deliveries.index', array_merge(request()->query(), ['status' => 'delivered'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'delivered' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Delivered</a>
        <a href="{{ route('deliveries.index', array_merge(request()->query(), ['status' => 'pending'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'pending' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Pending</a>
        <a href="{{ route('deliveries.index', array_merge(request()->query(), ['status' => 'returned'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'returned' ? 'bg-rose-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Returned</a>
        <a href="{{ route('deliveries.index', array_merge(request()->query(), ['status' => 'out_for_delivery'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'out_for_delivery' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Out for Delivery</a>
    </div>

    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search deliveries by customer, invoice, load sheet..."
            :searchValue="request('search')"
            :resetUrl="route('deliveries.index')"
        />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="customer" label="Customer" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="invoice" label="Invoice" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="loadSheet" label="Load Sheet" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="status" label="Status" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($deliveries as $delivery)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $delivery->customer->name }}</td>
                            <td class="px-6 py-4 font-mono text-slate-600 text-xs">{{ $delivery->invoice->invoice_no }}</td>
                            <td class="px-6 py-4 font-mono text-slate-600 text-xs">{{ $delivery->loadSheet->load_sheet_no }}</td>
                            <td class="px-6 py-4">
                                <x-ui.badge>{{ ucfirst(str_replace('_', ' ', $delivery->status)) }}</x-ui.badge>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <x-ui.button variant="ghost" size="sm" :href="route('deliveries.show', $delivery)">View</x-ui.button>
                                <x-ui.button variant="primary" size="sm" :href="route('deliveries.edit', $delivery)">Enter Qty</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No deliveries found" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $deliveries->links() }}</div>
    </x-ui.card>
</div>
@endsection
