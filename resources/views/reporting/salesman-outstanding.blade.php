@extends('layouts.dms')
@section('title', 'Salesman Outstanding')
@section('content')
<x-ui.page-header title="Salesman-wise Outstanding">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
    </x-slot>
</x-ui.page-header>

<x-ui.report-filters
    :date-from="$dateFrom"
    :date-to="$dateTo"
    search-placeholder="Search salesperson, party, invoice initials..."
    :reset-url="route('reports.salesman-outstanding')"
    submit-label="Filter"
    :show-salesperson="true"
    :show-party="true"
    :show-branch="true"
    :salespeople="$salespeople"
    :customers="$customers"
    :branches="$branches"
/>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm" data-report-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Salesperson</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Party</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Invoice</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Due</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Outstanding</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Overdue</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Bucket</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $row)
                    <tr data-search="{{ trim(($row['invoice']->salesperson?->name ?? '').' '.($row['invoice']->customer?->name ?? '').' '.($row['invoice']->invoice_no ?? '').' '.$row['bucket']) }}">
                        <td class="px-3 py-2">{{ $row['invoice']->salesperson?->name ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $row['invoice']->customer?->name }}</td>
                        <td class="px-3 py-2">{{ $row['invoice']->invoice_no }}</td>
                        <td class="px-3 py-2">{{ $row['due_date']->format('d M Y') }}</td>
                        <td class="px-3 py-2 text-right">₹{{ number_format($row['outstanding'], 2) }}</td>
                        <td class="px-3 py-2 text-right">{{ $row['overdue_days'] }}</td>
                        <td class="px-3 py-2">{{ $row['bucket'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-6 text-center text-slate-500" data-report-empty>No outstanding invoices.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
