@extends('layouts.dms')
@section('title', $payment->payment_no)
@section('content')
<x-ui.page-header :title="$payment->payment_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('payments.index')">Back</x-ui.button>
        <x-ui.button variant="secondary" :href="route('payments.edit', $payment)">Edit</x-ui.button>
        <form method="POST" action="{{ route('payments.destroy', $payment) }}" class="inline" onsubmit="return confirm('Delete payment {{ $payment->payment_no }}?');">
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="danger">Delete</x-ui.button>
        </form>
    </x-slot>
</x-ui.page-header>
<x-ui.card>
    <dl class="space-y-2 text-sm max-w-md">
        <div class="flex justify-between"><dt>Customer</dt><dd>{{ $payment->customer->name }}</dd></div>
        <div class="flex justify-between"><dt>Invoice</dt><dd>{{ $payment->invoice?->invoice_no ?? '—' }}</dd></div>
        <div class="flex justify-between"><dt>Amount</dt><dd class="font-semibold">₹{{ number_format($payment->amount, 2) }}</dd></div>
        <div class="flex justify-between"><dt>Method</dt><dd>{{ strtoupper(str_replace('_', ' ', $payment->method)) }}</dd></div>
        @if($payment->bankLedger)
            <div class="flex justify-between gap-4">
                <dt>Bank account</dt>
                <dd class="text-right">
                    {{ $payment->bankLedger->name }}
                    @if($payment->bankLedger->bankAccount?->account_number)
                        — {{ $payment->bankLedger->bankAccount->account_number }}
                    @endif
                    @if($payment->bankLedger->bankAccount?->ifsc)
                        <div class="text-xs text-slate-500">IFSC: {{ $payment->bankLedger->bankAccount->ifsc }}</div>
                    @endif
                </dd>
            </div>
        @endif
        @if($payment->method === 'upi' && $payment->reference_no)
            <div class="flex justify-between"><dt>UPI ID</dt><dd>{{ $payment->reference_no }}</dd></div>
        @endif
        <div class="flex justify-between"><dt>Paid At</dt><dd>{{ optional($payment->paid_at)->format('d M Y H:i') }}</dd></div>
        @if($payment->notes)
            <div>
                <dt class="text-gray-500">Notes</dt>
                <dd class="mt-1">{{ $payment->notes }}</dd>
            </div>
        @endif
    </dl>
</x-ui.card>
@endsection
