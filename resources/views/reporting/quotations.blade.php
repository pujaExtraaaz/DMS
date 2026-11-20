@extends('layouts.dms')
@section('title', 'Quotation Register')
@section('content')
<x-ui.page-header title="Quotation Register" description="All quotations by period — view, export, and track conversion">
    <x-slot name="actions">
        @if(\Illuminate\Support\Facades\Route::has('quotations.index'))
            <a href="{{ route('quotations.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Sales Quotations</a>
        @endif
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
        <button type="button" onclick="window.print()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">🖨 Print</button>
    </x-slot>
</x-ui.page-header>

@include('reporting.partials.integrity-hints')

<x-ui.report-filters
    :date-from="$dateFrom"
    :date-to="$dateTo"
    search-placeholder="Search quotation no, party, salesperson initials..."
    :reset-url="route('reports.quotations')"
    :show-party="true"
    :show-salesperson="true"
    :show-status="true"
    :statuses="['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted']"
    :show-branch="true"
    :customers="$customers"
    :salespeople="$salespeople"
    :branches="$branches"
/>

<div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-4">
    <x-ui.stat-card label="Quotations" value="{{ $summary['count'] }}" accent="indigo" />
    <x-ui.stat-card label="Grand Total" value="₹{{ number_format($summary['total'], 2) }}" accent="violet" />
    <x-ui.stat-card label="Open" value="{{ $summary['open'] }}" accent="amber" />
    <x-ui.stat-card label="Converted" value="{{ $summary['converted'] }}" accent="emerald" />
</div>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm" data-report-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Date</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Quotation</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Customer</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Salesperson</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Status</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Total</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Converted</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($quotations as $q)
                    <tr data-search="{{ trim(($q->quotation_no ?? '').' '.($q->customer?->name ?? '').' '.($q->salesperson?->name ?? '').' '.($q->status ?? '')) }}">
                        <td class="px-3 py-2">{{ optional($q->quotation_date)->format('d M Y') }}</td>
                        <td class="px-3 py-2">
                            <a href="{{ route('quotations.show', $q) }}" class="text-indigo-600 hover:underline font-medium">{{ $q->quotation_no }}</a>
                        </td>
                        <td class="px-3 py-2">{{ $q->customer?->name }}</td>
                        <td class="px-3 py-2">{{ $q->salesperson?->name }}</td>
                        <td class="px-3 py-2"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $q->status }}</span></td>
                        <td class="px-3 py-2 text-right">₹{{ number_format((float) $q->grand_total, 2) }}</td>
                        <td class="px-3 py-2">{{ $q->convertedOrder?->order_no ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-8 text-center text-slate-500">
                            No quotations in this period.
                            @if(\Illuminate\Support\Facades\Route::has('quotations.create'))
                                <a href="{{ route('quotations.create') }}" class="ml-1 text-indigo-600 hover:underline">Create one in Sales</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
