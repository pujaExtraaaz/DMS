@extends('layouts.dms')
@section('title', 'Payments Report')
@section('content')
<x-ui.page-header title="Payments Report" description="Completed receipts and payments by period">
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
    search-placeholder="Search payment, party, invoice, method initials..."
    :reset-url="route('reports.payments')"
    :show-party="true"
    :show-salesperson="true"
    :customers="$customers"
    :salespeople="$salespeople"
/>

<x-ui.card>
    @foreach($byMethod as $method => $total)
        <p class="text-sm text-gray-600">{{ strtoupper($method) }}: ₹{{ number_format($total, 2) }}</p>
    @endforeach
    <x-ui.table class="mt-4" data-report-table>
        <x-slot name="head">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Date</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Invoice</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Method</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Amount</th>
            </tr>
        </x-slot>
        @forelse($payments as $payment)
            <tr data-search="{{ trim(($payment->customer?->name ?? '').' '.($payment->invoice?->invoice_no ?? '').' '.($payment->method ?? '').' '.($payment->payment_no ?? '')) }}">
                <td class="px-6 py-4 text-sm">{{ optional($payment->paid_at)->format('d M Y') }}</td>
                <td class="px-6 py-4 text-sm">{{ $payment->customer?->name }}</td>
                <td class="px-6 py-4 text-sm">{{ $payment->invoice?->invoice_no ?: '—' }}</td>
                <td class="px-6 py-4 text-sm">{{ strtoupper($payment->method) }}</td>
                <td class="px-6 py-4 text-sm text-right">₹{{ number_format($payment->amount, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500" data-report-empty>No payments in this period.</td></tr>
        @endforelse
    </x-ui.table>
</x-ui.card>
@endsection
