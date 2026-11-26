@extends('layouts.dms')
@section('title', 'Supplier Payment '.$item->payment_no)
@section('content')
<x-ui.page-header :title="'Supplier Payment '.$item->payment_no" :description="$item->supplier?->name">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.supplier-payments.index')">Back</x-ui.button>
        @if($item->status === 'completed')
            <form method="POST" action="{{ route('purchasing.supplier-payments.cancel', $item) }}" onsubmit="return confirm('Cancel this supplier payment? Payables will be reversed.');">
                @csrf
                <x-ui.button type="submit" variant="secondary">Cancel Payment</x-ui.button>
            </form>
        @endif
    </x-slot>
</x-ui.page-header>
<x-ui.card title="Payment Details">
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div class="flex justify-between md:block"><dt class="text-gray-500">Supplier</dt><dd class="font-medium">{{ $item->supplier?->name }}</dd></div>
        <div class="flex justify-between md:block"><dt class="text-gray-500">Purchase Invoice</dt><dd class="font-medium">{{ $item->purchaseInvoice?->invoice_no ?? '—' }}</dd></div>
        <div class="flex justify-between md:block"><dt class="text-gray-500">Amount</dt><dd class="font-semibold">₹{{ number_format($item->amount, 2) }}</dd></div>
        <div class="flex justify-between md:block"><dt class="text-gray-500">Method</dt><dd>{{ ucfirst($item->method) }}</dd></div>
        <div class="flex justify-between md:block"><dt class="text-gray-500">Reference</dt><dd>{{ $item->reference_no ?: '—' }}</dd></div>
        <div class="flex justify-between md:block"><dt class="text-gray-500">Status</dt><dd>{{ ucfirst($item->status) }}</dd></div>
        <div class="flex justify-between md:block"><dt class="text-gray-500">Date</dt><dd>{{ $item->payment_date?->format('d M Y') }}</dd></div>
        <div class="flex justify-between md:block"><dt class="text-gray-500">Recorded By</dt><dd>{{ $item->recorder?->name ?? '—' }}</dd></div>
        @if($item->notes)
            <div class="md:col-span-2"><dt class="text-gray-500">Notes</dt><dd>{{ $item->notes }}</dd></div>
        @endif
    </dl>
</x-ui.card>
@endsection
