@extends('layouts.dms')
@section('title', 'Delivery Report')
@section('content')
<x-ui.page-header title="Delivery Report" description="Deliveries by period">
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
    search-placeholder="Search party, invoice, status initials..."
    :reset-url="route('reports.delivery')"
    :show-party="true"
    :show-salesperson="true"
    :customers="$customers"
    :salespeople="$salespeople"
/>

<x-ui.card>
    @foreach($summary as $status => $count)
        <p class="text-sm text-gray-600">{{ ucfirst($status) }}: {{ $count }}</p>
    @endforeach
    <x-ui.table class="mt-4" data-report-table>
        <x-slot name="head">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Invoice</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
            </tr>
        </x-slot>
        @forelse($deliveries as $delivery)
            <tr data-search="{{ trim(($delivery->customer?->name ?? '').' '.($delivery->invoice?->invoice_no ?? '').' '.($delivery->status ?? '')) }}">
                <td class="px-6 py-4 text-sm">{{ $delivery->customer?->name }}</td>
                <td class="px-6 py-4 text-sm">{{ $delivery->invoice?->invoice_no ?: '—' }}</td>
                <td class="px-6 py-4 text-sm">{{ ucfirst($delivery->status) }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="px-6 py-8 text-center text-slate-500" data-report-empty>No deliveries in this period.</td></tr>
        @endforelse
    </x-ui.table>
</x-ui.card>
@endsection
