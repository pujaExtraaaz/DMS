@extends('layouts.dms')
@section('title', 'Supplier Payments')
@section('content')
<x-ui.page-header title="Supplier Payments">
    <x-slot name="actions">
        <x-ui.button variant="primary" :href="route('purchasing.supplier-payments.create')">Record Payment</x-ui.button>
    </x-slot>
</x-ui.page-header>
<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search Payments By Number, Reference, Notes..."
            :searchValue="request('search')"
            :resetUrl="route('purchasing.supplier-payments.index')"
        >
            <x-slot name="filters">
                <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All statuses</option>
                    @foreach(['completed', 'cancelled', 'draft'] as $status)
                        <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <select name="method" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All methods</option>
                    @foreach(['cash', 'upi', 'bank', 'cheque', 'other'] as $method)
                        <option value="{{ $method }}" @selected(request('method')===$method)>{{ ucfirst($method) }}</option>
                    @endforeach
                </select>
                <select name="supplier_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(request('supplier_id')==$s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50"><tr>
                    <x-ui.sortable-th column="payment_no" label="Payment" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="supplier" label="Supplier" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="amount" label="Amount" align="right" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="method" label="Method" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="status" label="Status" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="payment_date" label="Date" :currentSort="$sort" :currentDirection="$direction" />
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium font-mono text-xs">{{ $item->payment_no }}</td>
                            <td class="px-3 py-2">{{ $item->supplier?->name }}</td>
                            <td class="px-3 py-2 text-right font-semibold">₹{{ number_format($item->amount, 2) }}</td>
                            <td class="px-3 py-2">{{ ucfirst($item->method) }}</td>
                            <td class="px-3 py-2"><x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge></td>
                            <td class="px-3 py-2 text-slate-600 text-sm">{{ $item->payment_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('purchasing.supplier-payments.show', $item)">View</x-ui.button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">No supplier payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
