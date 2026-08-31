@extends('layouts.dms')
@section('title', 'Payments Report')
@section('content')
<x-ui.page-header title="Payments Report" />
<x-ui.card><form method="GET" class="flex gap-3 mb-4"><x-ui.input name="date_from" type="date" label="From" :value="$dateFrom" /><x-ui.input name="date_to" type="date" label="To" :value="$dateTo" /><x-ui.button type="submit" variant="secondary">Run Report</x-ui.button></form>
@foreach($byMethod as $method => $total)<p class="text-sm text-gray-600">{{ strtoupper($method) }}: ₹{{ number_format($total, 2) }}</p>@endforeach
<x-ui.table class="mt-4"><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Method</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Amount</th></tr></x-slot>
@foreach($payments as $payment)<tr><td class="px-6 py-4 text-sm">{{ optional($payment->paid_at)->format('d M Y') }}</td><td class="px-6 py-4 text-sm">{{ $payment->customer->name }}</td><td class="px-6 py-4 text-sm">{{ strtoupper($payment->method) }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($payment->amount, 2) }}</td></tr>@endforeach</x-ui.table></x-ui.card>
@endsection
