@extends('layouts.dms')
@section('title', 'Reconciliation')
@section('content')
<x-ui.page-header title="Payment Reconciliation" />
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
<x-ui.stat-card label="Cash" :value="'₹'.number_format($summary['cash'], 2)" change-type="neutral" />
<x-ui.stat-card label="UPI" :value="'₹'.number_format($summary['upi'], 2)" change-type="neutral" />
<x-ui.stat-card label="Bank" :value="'₹'.number_format($summary['bank'], 2)" change-type="neutral" />
<x-ui.stat-card label="Total" :value="'₹'.number_format($summary['total'], 2)" change-type="positive" />
</div>
<x-ui.card><form method="GET" class="flex gap-3 mb-4"><x-ui.input name="date_from" type="date" label="From" :value="request('date_from')" /><x-ui.input name="date_to" type="date" label="To" :value="request('date_to')" /><x-ui.button type="submit" variant="secondary">Filter</x-ui.button></form>
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Method</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Amount</th></tr></x-slot>
@foreach($payments as $payment)<tr><td class="px-6 py-4 text-sm">{{ optional($payment->paid_at)->format('d M Y') }}</td><td class="px-6 py-4 text-sm">{{ $payment->customer->name }}</td><td class="px-6 py-4 text-sm">{{ strtoupper($payment->method) }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($payment->amount, 2) }}</td></tr>@endforeach</x-ui.table>
<div class="mt-4">{{ $payments->links() }}</div></x-ui.card>
@endsection
