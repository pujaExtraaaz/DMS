@extends('layouts.dms')
@section('title', 'Quotation '.$item->quotation_no)
@section('content')
<x-ui.page-header :title="'Quotation '.$item->quotation_no" :description="$item->customer?->name">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('quotations.index')">Back</x-ui.button>
        @if(in_array($item->status, ['draft','sent']))
            <x-ui.button variant="secondary" :href="route('quotations.edit', $item)">Edit</x-ui.button>
        @endif
        @if($item->status === 'draft')
            <form method="POST" action="{{ route('quotations.send', $item) }}">@csrf<x-ui.button type="submit" variant="primary">Mark Sent</x-ui.button></form>
        @endif
        @if(in_array($item->status, ['draft','sent']))
            <form method="POST" action="{{ route('quotations.accept', $item) }}">@csrf<x-ui.button type="submit" variant="primary">Accept</x-ui.button></form>
        @endif
    </x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <x-ui.card class="lg:col-span-2">
        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">UOM</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Price</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total</th>
                    @if($canViewProfit)
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Est. Profit</th>
                    @endif
                </tr>
            </x-slot>
            @foreach($item->items as $line)
                <tr>
                    <td class="px-6 py-4 text-sm">{{ $line->product?->name }}</td>
                    <td class="px-6 py-4 text-sm">{{ $line->uom?->code }}</td>
                    <td class="px-6 py-4 text-sm text-right">{{ $line->quantity }}</td>
                    <td class="px-6 py-4 text-sm text-right">₹{{ number_format($line->unit_price, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-right">₹{{ number_format($line->line_total, 2) }}</td>
                    @if($canViewProfit)
                        <td class="px-6 py-4 text-sm text-right">₹{{ number_format($line->estimated_profit, 2) }}</td>
                    @endif
                </tr>
            @endforeach
        </x-ui.table>
    </x-ui.card>
    <x-ui.card title="Summary">
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd>{{ ucfirst($item->status) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Date</dt><dd>{{ $item->quotation_date?->format('d M Y') }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Subtotal</dt><dd>₹{{ number_format($item->subtotal, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Discount</dt><dd>₹{{ number_format($item->discount_amount, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Tax</dt><dd>₹{{ number_format($item->tax_amount, 2) }}</dd></div>
            <div class="flex justify-between font-semibold"><dt>Grand Total</dt><dd>₹{{ number_format($item->grand_total, 2) }}</dd></div>
            @if($canViewProfit)
                <div class="flex justify-between text-emerald-700 font-semibold border-t pt-3"><dt>Est. Profit</dt><dd>₹{{ number_format($item->estimated_profit, 2) }}</dd></div>
            @endif
        </dl>
    </x-ui.card>
</div>
@endsection
