@extends('layouts.dms')
@section('title', 'Trial Balance')
@section('content')
<x-ui.page-header title="Trial Balance" description="Ledger-style summary derived from transactions in the selected period.">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
    </x-slot>
</x-ui.page-header>

<x-ui.report-filters
    :date-from="$dateFrom"
    :date-to="$dateTo"
    search-placeholder="Search ledger initials..."
    :reset-url="route('reports.trial-balance')"
    submit-label="Filter"
/>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm" data-report-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Ledger</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Debit</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Credit</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $r)
                    <tr data-search="{{ $r['ledger'] ?? '' }}">
                        <td class="px-3 py-2">{{ $r['ledger'] }}</td>
                        <td class="px-3 py-2 text-right">₹ {{ number_format((float) $r['debit'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₹ {{ number_format((float) $r['credit'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₹ {{ number_format((float) $r['balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500" data-report-empty>No balances in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
