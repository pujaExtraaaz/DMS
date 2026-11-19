@extends('layouts.dms')
@section('title', 'Party Statement')
@section('content')
<x-ui.page-header title="Party Statement" description="Party outstanding ledger for the selected period">
    <x-slot name="actions">
        @if($customer)
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
            <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
            <button type="button" onclick="window.print()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">🖨 Print</button>
        @endif
    </x-slot>
</x-ui.page-header>
@include('reporting.partials.integrity-hints')

<x-ui.report-filters
    :date-from="$dateFrom"
    :date-to="$dateTo"
    search-placeholder="Search ledger notes or type initials..."
    :reset-url="route('reports.party-statement')"
    submit-label="Run Statement"
    :show-party="true"
    :party-required="true"
    :customers="$customers"
/>

<x-ui.card>
    @if($customer)
        <div class="mb-4 text-sm">
            <div class="font-semibold text-lg">{{ $customer->name }}</div>
            <div class="text-slate-500">Opening balance: ₹{{ number_format($opening, 2) }} · Credit status: {{ $customer->credit_status }} · Bounce count: {{ $customer->cheque_bounce_count }}</div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm" data-report-table>
                <thead class="bg-slate-50"><tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Date</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Type</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Notes</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Debit</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Credit</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Balance</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <tr data-report-empty>
                        <td class="px-3 py-2" colspan="5">Opening</td>
                        <td class="px-3 py-2 text-right font-medium">₹{{ number_format($opening, 2) }}</td>
                    </tr>
                    @forelse($entries as $entry)
                        <tr data-search="{{ trim(($entry->type ?? '').' '.($entry->notes ?? '')) }}">
                            <td class="px-3 py-2">{{ $entry->created_at?->format('d M Y') }}</td>
                            <td class="px-3 py-2">{{ str_replace('_',' ', $entry->type) }}</td>
                            <td class="px-3 py-2">{{ $entry->notes }}</td>
                            <td class="px-3 py-2 text-right">₹{{ number_format($entry->debit, 2) }}</td>
                            <td class="px-3 py-2 text-right">₹{{ number_format($entry->credit, 2) }}</td>
                            <td class="px-3 py-2 text-right">₹{{ number_format($entry->balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500" data-report-empty>No ledger entries in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <p class="text-slate-500 text-sm">Select a party to view the statement.</p>
    @endif
</x-ui.card>
@endsection
