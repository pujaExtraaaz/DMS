@extends('layouts.dms')
@section('title', $settlement->settlement_no)
@section('content')
<x-ui.page-header :title="$settlement->settlement_no"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('settlements.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card title="Settlement Lines"><x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Invoice</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Cash</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">UPI</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Outstanding</th></tr></x-slot>
@foreach($settlement->lines as $line)<tr><td class="px-6 py-4 text-sm">{{ $line->customer->name }}</td><td class="px-6 py-4 text-sm">{{ $line->invoice?->invoice_no ?? '—' }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($line->cash_amount, 2) }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($line->upi_amount, 2) }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($line->outstanding_amount, 2) }}</td></tr>@endforeach</x-ui.table>
<p class="mt-4 text-sm text-right">Cash: ₹{{ number_format($settlement->cash_collected, 2) }} · UPI: ₹{{ number_format($settlement->upi_collected, 2) }} · Outstanding: ₹{{ number_format($settlement->outstanding_amount, 2) }}</p></x-ui.card>
@endsection
