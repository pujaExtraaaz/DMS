@extends('layouts.dms')
@section('title', 'Outstanding Report')
@section('content')
<x-ui.page-header title="Outstanding Report" description="Live balances snapped from the outstanding ledger.">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
        <button type="button" onclick="window.print()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">🖨 Print</button>
    </x-slot>
</x-ui.page-header>

@include('reporting.partials.integrity-hints')

<x-ui.report-filters
    :date-from="$dateFrom"
    :date-to="$dateTo"
    date-from-label="Invoice From"
    date-to-label="Invoice To"
    search-placeholder="Search party initials..."
    :reset-url="route('reports.outstanding')"
    submit-label="Filter"
    :show-party="true"
    :show-branch="true"
    :customers="$customers"
    :branches="$branches"
/>

<x-ui.card>
    <x-ui.table data-report-table>
        <x-slot name="head">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Customer</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Balance</th>
            </tr>
        </x-slot>
        @forelse($balances as $row)
            <tr data-search="{{ $row['customer']->name }}">
                <td class="px-6 py-4 text-sm">{{ $row['customer']->name }}</td>
                <td class="px-6 py-4 text-sm text-right font-medium">₹{{ number_format($row['balance'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="px-6 py-8" data-report-empty><x-ui.empty-state title="No outstanding balances" /></td></tr>
        @endforelse
    </x-ui.table>
</x-ui.card>
@endsection
