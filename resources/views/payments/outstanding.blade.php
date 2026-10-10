@extends('layouts.dms')
@section('title', 'Outstanding')
@section('content')
<x-ui.page-header title="Outstanding Ledger" />
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<x-ui.card class="lg:col-span-1" title="Customer Balances">
@forelse($customerBalances as $row)<div class="flex justify-between py-2 border-b text-sm"><span>{{ $row['customer']->name }}</span><span class="font-medium">₹{{ number_format($row['balance'], 2) }}</span></div>@empty<p class="text-sm text-gray-500">No outstanding balances.</p>@endforelse
</x-ui.card>
<div id="listing-container" data-dynamic-container class="lg:col-span-2">
<x-ui.card title="Ledger Entries">
    <x-ui.table-toolbar
        placeholder="Search ledger by description, type..."
        :searchValue="request('search')"
        :resetUrl="route('outstanding.index')"
    >
        <x-slot name="filters">
            <select name="customer_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                <option value="">All Customers</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </x-slot>
    </x-ui.table-toolbar>

<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
    <x-ui.sortable-th column="created_at" label="Date" :currentSort="$sort" :currentDirection="$direction" />
    <x-ui.sortable-th column="customer" label="Customer" :currentSort="$sort" :currentDirection="$direction" />
    <x-ui.sortable-th column="type" label="Type" :currentSort="$sort" :currentDirection="$direction" />
    <x-ui.sortable-th column="debit" label="Debit" align="right" :currentSort="$sort" :currentDirection="$direction" />
    <x-ui.sortable-th column="credit" label="Credit" align="right" :currentSort="$sort" :currentDirection="$direction" />
    <x-ui.sortable-th column="balance" label="Balance" align="right" :currentSort="$sort" :currentDirection="$direction" />
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($ledger as $entry)
<tr class="hover:bg-slate-50">
    <td class="px-6 py-4 text-sm">{{ $entry->created_at->format('d M Y') }}</td>
    <td class="px-6 py-4 text-sm">{{ $entry->customer->name }}</td>
    <td class="px-6 py-4 text-sm"><x-ui.badge>{{ strtoupper($entry->type) }}</x-ui.badge></td>
    <td class="px-6 py-4 text-sm text-right text-rose-600">{{ $entry->debit ? '₹'.number_format($entry->debit,2) : '—' }}</td>
    <td class="px-6 py-4 text-sm text-right text-emerald-600">{{ $entry->credit ? '₹'.number_format($entry->credit,2) : '—' }}</td>
    <td class="px-6 py-4 text-sm text-right font-medium">₹{{ number_format($entry->balance, 2) }}</td>
</tr>
@empty
<tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">No ledger entries found.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-4">{{ $ledger->links() }}</div>
</x-ui.card>
</div>
</div>
@endsection
