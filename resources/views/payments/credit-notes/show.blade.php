@extends('layouts.dms')
@section('title', 'Credit Note '.$item->credit_note_no)
@section('content')
<x-ui.page-header :title="'Credit Note '.$item->credit_note_no" :description="$item->customer?->name">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('credit-notes.index')">Back</x-ui.button>
        @if($item->status === 'draft')
            <form method="POST" action="{{ route('credit-notes.approve', $item) }}">@csrf<x-ui.button type="submit" variant="secondary">Approve</x-ui.button></form>
            <form method="POST" action="{{ route('credit-notes.post', $item) }}">@csrf<x-ui.button type="submit" variant="primary">Post</x-ui.button></form>
        @elseif($item->status === 'approved')
            <form method="POST" action="{{ route('credit-notes.post', $item) }}">@csrf<x-ui.button type="submit" variant="primary">Post</x-ui.button></form>
        @endif
    </x-slot>
</x-ui.page-header>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <x-ui.card class="lg:col-span-2">
        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Price</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Tax</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total</th>
                </tr>
            </x-slot>
            @foreach($item->items as $line)
                <tr>
                    <td class="px-6 py-4 text-sm">{{ $line->product?->name ?? $line->description ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-right">{{ $line->quantity }} {{ $line->uom?->code }}</td>
                    <td class="px-6 py-4 text-sm text-right">₹{{ number_format($line->unit_price, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-right">₹{{ number_format($line->tax_amount, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-right">₹{{ number_format($line->line_total, 2) }}</td>
                </tr>
            @endforeach
        </x-ui.table>
    </x-ui.card>
    <x-ui.card title="Summary">
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd>{{ ucfirst($item->status) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Reason</dt><dd>{{ str_replace('_',' ', $item->reason) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Invoice</dt><dd>{{ $item->invoice?->invoice_no ?? '—' }}</dd></div>
            <div class="flex justify-between font-semibold"><dt>Grand Total</dt><dd>₹{{ number_format($item->grand_total, 2) }}</dd></div>
        </dl>
    </x-ui.card>
</div>
@endsection
