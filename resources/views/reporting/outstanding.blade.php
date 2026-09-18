@extends('layouts.dms')
@section('title', 'Outstanding Report')
@section('content')
<x-ui.page-header title="Outstanding Report" description="Live balances snapped from the outstanding ledger.">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        <x-ui.input name="date_from" type="date" label="Invoice From" :value="request('date_from', now()->subMonths(3)->toDateString())" />
        <x-ui.input name="date_to" type="date" label="Invoice To" :value="request('date_to', now()->toDateString())" />
        <x-ui.select name="customer_id" label="Party" placeholder="All">
            <option value=""></option>
            @foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach
        </x-ui.select>
        <x-ui.select name="branch_id" label="Branch" placeholder="All">
            <option value=""></option>
            @foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch_id')==$b->id)>{{ $b->name }}</option>@endforeach
        </x-ui.select>
        <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
    </form>

    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Balance</th>
            </tr>
        </x-slot>
        @forelse($balances as $row)
            <tr>
                <td class="px-6 py-4 text-sm">{{ $row['customer']->name }}</td>
                <td class="px-6 py-4 text-sm text-right font-medium">₹{{ number_format($row['balance'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="px-6 py-8"><x-ui.empty-state title="No outstanding balances" /></td></tr>
        @endforelse
    </x-ui.table>
</x-ui.card>
@endsection
