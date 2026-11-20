@extends('layouts.dms')
@section('title', 'Profit & Loss')
@section('content')
<x-ui.page-header title="Profit &amp; Loss Statement" description="Derived from posted sales, purchases, credit notes and freight bills for the selected period.">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
    </x-slot>
</x-ui.page-header>

<x-ui.report-filters
    :date-from="$dateFrom"
    :date-to="$dateTo"
    search-placeholder="Search section or line item initials..."
    :reset-url="route('reports.profit-loss')"
    submit-label="Filter"
/>

<x-ui.card>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-3 py-3"><span class="text-xs text-slate-500">Net Revenue</span><div class="text-xl font-semibold text-emerald-700">₹ {{ number_format($summary['revenue'], 2) }}</div></div>
        <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-3"><span class="text-xs text-slate-500">COGS</span><div class="text-xl font-semibold text-slate-700">₹ {{ number_format($summary['cogs'], 2) }}</div></div>
        <div class="rounded-lg bg-indigo-50 border border-indigo-100 px-3 py-3"><span class="text-xs text-slate-500">Gross Profit</span><div class="text-xl font-semibold text-indigo-700">₹ {{ number_format($summary['grossProfit'], 2) }}</div></div>
        <div class="rounded-lg bg-amber-50 border border-amber-100 px-3 py-3"><span class="text-xs text-slate-500">Operating Profit</span><div class="text-xl font-semibold text-amber-700">₹ {{ number_format($summary['operatingProfit'], 2) }}</div></div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm" data-report-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Section</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Line Item</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($rows as $r)
                    <tr @class(['bg-slate-50 font-semibold' => ($r['_total'] ?? false)]) data-search="{{ ($r['Section'] ?? '').' '.($r['Line Item'] ?? '') }}">
                        <td class="px-3 py-2">{{ $r['Section'] }}</td>
                        <td class="px-3 py-2">{{ $r['Line Item'] }}</td>
                        <td class="px-3 py-2 text-right">₹ {{ $r['Amount'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
