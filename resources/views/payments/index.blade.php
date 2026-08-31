@extends('layouts.dms')
@section('title', 'Payments')
@section('content')
<x-ui.page-header title="Collections"><x-slot name="actions"><x-ui.button variant="primary" :href="route('payments.create')">Record Payment</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Payment</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Invoice</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Method</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Amount</th></tr></x-slot>
@forelse($payments as $payment)<tr><td class="px-6 py-4 text-sm">{{ $payment->payment_no }}</td><td class="px-6 py-4 text-sm">{{ $payment->customer->name }}</td><td class="px-6 py-4 text-sm">{{ $payment->invoice->invoice_no }}</td><td class="px-6 py-4 text-sm">{{ strtoupper($payment->method) }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($payment->amount, 2) }}</td></tr>@empty<tr><td colspan="5" class="px-6 py-8"><x-ui.empty-state title="No payments" /></td></tr>@endforelse</x-ui.table>
<div class="mt-4">{{ $payments->links() }}</div></x-ui.card>
@endsection
