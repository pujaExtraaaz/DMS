@extends('layouts.dms')
@section('title', 'Purchases')
@section('content')
<x-ui.page-header title="Purchases">
    <x-slot name="actions">
        <x-ui.button variant="primary" :href="route('inventory.purchases.create')">+ New Purchase</x-ui.button>
    </x-slot>
</x-ui.page-header>

<div id="listing-container" data-dynamic-container>
    {{-- Purchases List --}}
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search purchases by number, supplier, or product..."
            :searchValue="request('search')"
            :resetUrl="route('inventory.purchases.index')"
        >
            <x-slot name="filters">
                <input
                    type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="From date"
                    data-dynamic-filter
                />
                <input
                    type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="To date"
                    data-dynamic-filter
                />
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <x-ui.sortable-th column="purchase_no" label="Purchase No" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Product</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">SKU</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Unit</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Quantity</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Unit Cost</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Amount</th>
                        <x-ui.sortable-th column="supplier_name" label="Supplier" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="purchase_date" label="Date" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($purchases as $purchase)
                        @forelse($purchase->items as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900 font-mono text-xs">{{ $purchase->purchase_no }}</td>
                                <td class="px-4 py-3 text-slate-900 font-medium">{{ $item->product->name }}</td>
                                <td class="px-4 py-3 text-slate-600 text-xs font-mono">{{ $item->product->sku }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->uom->code }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($item->quantity, 2) }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">₹{{ number_format($item->unit_cost, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">₹{{ number_format($item->line_total, 2) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $purchase->supplier_name }}</td>
                                <td class="px-4 py-3 text-slate-700 text-sm">{{ $purchase->purchase_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <x-ui.button variant="ghost" size="sm" :href="route('inventory.purchases.show', $purchase)">View</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900 font-mono text-xs">{{ $purchase->purchase_no }}</td>
                                <td colspan="6" class="px-4 py-3 text-slate-500 italic">No items</td>
                                <td class="px-4 py-3 text-slate-700">{{ $purchase->supplier_name }}</td>
                                <td class="px-4 py-3 text-slate-700 text-sm">{{ $purchase->purchase_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <x-ui.button variant="ghost" size="sm" :href="route('inventory.purchases.show', $purchase)">View</x-ui.button>
                                </td>
                            </tr>
                        @endforelse
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center">
                                <x-ui.empty-state title="No purchases found" description="Create a new purchase to get started" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($purchases->hasPages())
            <div class="mt-4">
                {{ $purchases->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
