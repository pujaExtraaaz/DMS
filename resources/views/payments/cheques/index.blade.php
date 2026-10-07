@extends('layouts.dms')
@section('title', 'Cheques')
@section('content')
<x-ui.page-header title="Cheques / PDC">
    <x-slot name="actions">
        <x-ui.button variant="primary" :href="route('cheques.create')">Record Cheque</x-ui.button>
    </x-slot>
</x-ui.page-header>
<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search cheques by number, bank, notes..."
            :searchValue="request('search')"
            :resetUrl="route('cheques.index')"
        >
            <x-slot name="filters">
                <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All statuses</option>
                    @foreach(['pending','deposited','cleared','bounced','cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>

                <select name="purpose" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All purposes</option>
                    <option value="pdc" @selected(request('purpose')==='pdc')>PDC</option>
                    <option value="security" @selected(request('purpose')==='security')>Security</option>
                </select>

                <select name="customer_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Parties</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50"><tr>
                    <x-ui.sortable-th column="cheque_no" label="Cheque" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="customer" label="Party" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="purpose" label="Purpose" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="status" label="Status" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="amount" label="Amount" align="right" :currentSort="$sort" :currentDirection="$direction" />
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium">{{ $item->cheque_no }}<div class="text-xs text-slate-500">{{ $item->bank_name }}</div></td>
                            <td class="px-3 py-2">{{ $item->customer?->name }}</td>
                            <td class="px-3 py-2 font-mono uppercase text-xs">{{ strtoupper($item->purpose) }}</td>
                            <td class="px-3 py-2"><x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge></td>
                            <td class="px-3 py-2 text-right font-semibold">₹{{ number_format($item->amount, 2) }}</td>
                            <td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('cheques.show', $item)">View</x-ui.button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No cheques found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
