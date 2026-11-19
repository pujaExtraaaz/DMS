@extends('layouts.dms')
@section('title', 'Outstanding Aging')
@section('content')
<x-ui.page-header title="Outstanding Aging">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
    </x-slot>
</x-ui.page-header>

<x-ui.report-filters
    :date-from="$dateFrom ?? ''"
    :date-to="$dateTo ?? ''"
    date-from-label="Invoice From"
    date-to-label="Invoice To"
    search-placeholder="Search party or invoice initials..."
    :reset-url="route('reports.aging')"
    submit-label="Filter"
    :show-party="true"
    :show-salesperson="true"
    :show-branch="true"
    :customers="$customers"
    :salespeople="$salespeople"
    :branches="$branches"
/>

<x-ui.card>
    @forelse($groups as $bucket => $rows)
        <h3 class="text-sm font-semibold text-slate-700 mt-4 mb-2">{{ $bucket }} ({{ $rows->count() }})</h3>
        <div class="overflow-x-auto mb-4">
            <table class="min-w-full divide-y divide-slate-200 text-sm" data-report-table>
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Party</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Invoice</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Due</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Outstanding</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Days</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($rows as $row)
                        <tr data-search="{{ trim(($row['invoice']->customer?->name ?? '').' '.($row['invoice']->invoice_no ?? '').' '.$bucket) }}">
                            <td class="px-3 py-2">{{ $row['invoice']->customer?->name }}</td>
                            <td class="px-3 py-2">{{ $row['invoice']->invoice_no }}</td>
                            <td class="px-3 py-2">{{ $row['due_date']->format('d M Y') }}</td>
                            <td class="px-3 py-2 text-right">₹{{ number_format($row['outstanding'], 2) }}</td>
                            <td class="px-3 py-2 text-right">{{ $row['overdue_days'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="text-sm text-slate-500">No outstanding invoices.</p>
    @endforelse
</x-ui.card>
@endsection
