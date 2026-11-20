@extends('layouts.dms')
@section('title', 'Margin Register')
@section('content')
<x-ui.page-header title="Sales & Margin Register">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
    </x-slot>
</x-ui.page-header>

<x-ui.report-filters
    :date-from="$dateFrom"
    :date-to="$dateTo"
    search-placeholder="Search invoice or party initials..."
    :reset-url="route('reports.margin')"
    submit-label="Filter"
    :show-party="true"
    :show-salesperson="true"
    :show-brand="true"
    :show-category="true"
    :show-product="true"
    :customers="$customers"
    :salespeople="$salespeople"
    :brands="$brands"
    :categories="$categories"
    :products="$products"
/>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm" data-report-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Invoice</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Party</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Sale</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Cost</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Deal Exp</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Gross</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Net</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Net %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $row)
                    <tr data-search="{{ trim(($row['invoice']->invoice_no ?? '').' '.($row['invoice']->customer?->name ?? '')) }}">
                        <td class="px-3 py-2">{{ $row['invoice']->invoice_no }}</td>
                        <td class="px-3 py-2">{{ $row['invoice']->customer?->name }}</td>
                        <td class="px-3 py-2 text-right">₹{{ number_format($row['sale'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₹{{ number_format($row['cost'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₹{{ number_format($row['deal_expense'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₹{{ number_format($row['gross'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₹{{ number_format($row['net'], 2) }}</td>
                        <td class="px-3 py-2 text-right">{{ $row['net_pct'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-3 py-6 text-center text-slate-500" data-report-empty>No invoices.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
