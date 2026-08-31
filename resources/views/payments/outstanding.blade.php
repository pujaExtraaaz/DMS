@extends('layouts.dms')
@section('title', 'Outstanding')
@section('content')
<x-ui.page-header title="Outstanding Ledger" />
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<x-ui.card class="lg:col-span-1" title="Customer Balances">
@forelse($customerBalances as $row)<div class="flex justify-between py-2 border-b text-sm"><span>{{ $row['customer']->name }}</span><span class="font-medium">₹{{ number_format($row['balance'], 2) }}</span></div>@empty<p class="text-sm text-gray-500">No outstanding balances.</p>@endforelse
</x-ui.card>
<x-ui.card class="lg:col-span-2" title="Ledger Entries">
<form method="GET" class="mb-4"><x-ui.select name="customer_id" label="Customer" placeholder="All"><option value=""></option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select><x-ui.button type="submit" variant="secondary">Filter</x-ui.button></form>
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Type</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Debit</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Credit</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Balance</th></tr></x-slot>
@foreach($ledger as $entry)<tr><td class="px-6 py-4 text-sm">{{ $entry->created_at->format('d M Y') }}</td><td class="px-6 py-4 text-sm">{{ $entry->customer->name }}</td><td class="px-6 py-4 text-sm">{{ $entry->type }}</td><td class="px-6 py-4 text-sm text-right">{{ $entry->debit ? '₹'.number_format($entry->debit,2) : '—' }}</td><td class="px-6 py-4 text-sm text-right">{{ $entry->credit ? '₹'.number_format($entry->credit,2) : '—' }}</td><td class="px-6 py-4 text-sm text-right font-medium">₹{{ number_format($entry->balance, 2) }}</td></tr>@endforeach</x-ui.table>
<div class="mt-4">{{ $ledger->links() }}</div></x-ui.card></div>
@endsection
