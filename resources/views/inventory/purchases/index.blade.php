@extends('layouts.dms')
@section('title', 'Purchases')
@section('content')
<x-ui.page-header title="Purchases">
    <x-slot name="actions">
        <x-ui.button variant="primary" :href="route('inventory.purchases.create')">+ New Purchase</x-ui.button>
    </x-slot>
</x-ui.page-header>

{{-- Filters --}}
<x-ui.card class="mb-6">
    <form method="GET" class="flex flex-col gap-4 md:flex-row md:items-end">
        <div class="flex-1">
            <x-ui.input name="date_from" type="date" label="From" :value="request('date_from')" />
        </div>
        <div class="flex-1">
            <x-ui.input name="date_to" type="date" label="To" :value="request('date_to')" />
        </div>
        <x-ui.button type="submit" variant="secondary" class="w-full md:w-auto">Filter</x-ui.button>
    </form>
</x-ui.card>

{{-- Purchases List --}}
<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Name</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">SKU</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">UOM</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Quantity</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Unit Cost</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Amount</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Supplier</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($purchases as $purchase)
                    @forelse($purchase->items as $item)
                        <tr class="hover:bg-slate-50">
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
                            <td colspan="9" class="px-4 py-3 text-slate-500 text-center italic">No items in this purchase</td>
                        </tr>
                    @endforelse
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center">
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
@endsection
