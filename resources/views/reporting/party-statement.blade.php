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
        <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">{{ $customer->name }}</h2>
                    <div class="text-xs text-slate-500 mt-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-200 text-slate-700 mr-2">{{ $customer->party_type_label }}</span>
                        @if($customer->gstin)<span class="mr-2">GSTIN: <span class="font-mono">{{ $customer->gstin }}</span></span>@endif
                        @if($customer->pan)<span class="mr-2">PAN: <span class="font-mono">{{ $customer->pan }}</span></span>@endif
                        <span>Credit status: <strong class="capitalize">{{ $customer->credit_status }}</strong></span>
                        @if($customer->cheque_bounce_count > 0)
                            <span class="ml-2 text-rose-600">· Bounce count: {{ $customer->cheque_bounce_count }}</span>
                        @endif
                    </div>
                </div>
                <div class="text-xs text-slate-500 md:text-right">
                    Statement Period: <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</span> to <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</span>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Opening Balance</span>
                    <div class="mt-1 text-lg font-bold text-slate-800">₹{{ number_format($opening, 2) }}</div>
                </div>
                <div class="rounded-lg border border-rose-100 bg-white p-3 shadow-sm">
                    <span class="text-xs font-medium text-rose-600 uppercase tracking-wider">Total Debit</span>
                    <div class="mt-1 text-lg font-bold text-rose-600">₹{{ number_format($totalDebit, 2) }}</div>
                </div>
                <div class="rounded-lg border border-emerald-100 bg-white p-3 shadow-sm">
                    <span class="text-xs font-medium text-emerald-600 uppercase tracking-wider">Total Credit</span>
                    <div class="mt-1 text-lg font-bold text-emerald-600">₹{{ number_format($totalCredit, 2) }}</div>
                </div>
                <div class="rounded-lg border border-indigo-100 bg-white p-3 shadow-sm">
                    <span class="text-xs font-medium text-indigo-600 uppercase tracking-wider">Closing Balance</span>
                    <div class="mt-1 text-lg font-bold text-indigo-700">₹{{ number_format($closing, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200 shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Voucher Number</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Particulars</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Voucher Type</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Debit</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Credit</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <tr class="bg-slate-50/50 font-medium">
                        <td class="px-3 py-2 text-slate-400">—</td>
                        <td class="px-3 py-2 text-slate-400">—</td>
                        <td class="px-3 py-2 font-semibold text-slate-700">Opening Balance</td>
                        <td class="px-3 py-2"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-200 text-slate-700">Opening</span></td>
                        <td class="px-3 py-2 text-right text-slate-400">—</td>
                        <td class="px-3 py-2 text-right text-slate-400">—</td>
                        <td class="px-3 py-2 text-right font-bold text-slate-800">₹{{ number_format($opening, 2) }}</td>
                    </tr>
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-3 py-2.5 whitespace-nowrap text-slate-700">
                                {{ $entry->date instanceof \DateTimeInterface ? $entry->date->format('d M Y') : \Carbon\Carbon::parse($entry->date)->format('d M Y') }}
                            </td>
                            <td class="px-3 py-2.5 font-mono text-xs font-semibold text-indigo-600">
                                {{ $entry->voucher_number }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-700">
                                {{ $entry->particulars }}
                            </td>
                            <td class="px-3 py-2.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ str_contains(strtolower($entry->voucher_type), 'purchase') ? 'bg-amber-100 text-amber-800' : (str_contains(strtolower($entry->voucher_type), 'sales') ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-800') }}">
                                    {{ $entry->voucher_type }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-rose-600">
                                {{ $entry->debit > 0 ? '₹'.number_format($entry->debit, 2) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-emerald-600">
                                {{ $entry->credit > 0 ? '₹'.number_format($entry->credit, 2) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-bold text-slate-800">
                                ₹{{ number_format($entry->balance, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-slate-500">
                                No ledger entries in this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-100/80 font-semibold border-t-2 border-slate-300">
                    <tr>
                        <td colspan="4" class="px-3 py-2.5 text-right text-slate-700 uppercase tracking-wider text-xs">Period Totals</td>
                        <td class="px-3 py-2.5 text-right font-mono text-rose-600">₹{{ number_format($totalDebit, 2) }}</td>
                        <td class="px-3 py-2.5 text-right font-mono text-emerald-600">₹{{ number_format($totalCredit, 2) }}</td>
                        <td class="px-3 py-2.5 text-right font-mono font-bold text-indigo-700">₹{{ number_format($closing, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <p class="text-slate-500 text-sm">Select a party to view the statement.</p>
    @endif
</x-ui.card>
@endsection
