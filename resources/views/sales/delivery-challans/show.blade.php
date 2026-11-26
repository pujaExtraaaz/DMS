@extends('layouts.dms')
@section('title', 'Delivery Challan '.$item->challan_no)
@section('content')
<x-ui.page-header :title="'Delivery Challan '.$item->challan_no" :description="$item->customer?->name">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('delivery-challans.index')">Back</x-ui.button>
        @if($item->status === 'draft')
            <form method="POST" action="{{ route('delivery-challans.dispatch', $item) }}">@csrf<x-ui.button type="submit" variant="secondary">Dispatch</x-ui.button></form>
            <form method="POST" action="{{ route('delivery-challans.deliver', $item) }}">@csrf<x-ui.button type="submit" variant="primary">Mark Delivered</x-ui.button></form>
            <form method="POST" action="{{ route('delivery-challans.cancel', $item) }}" onsubmit="return confirm('Cancel this challan?');">@csrf<x-ui.button type="submit" variant="secondary">Cancel</x-ui.button></form>
        @elseif($item->status === 'dispatched')
            <form method="POST" action="{{ route('delivery-challans.deliver', $item) }}">@csrf<x-ui.button type="submit" variant="primary">Mark Delivered</x-ui.button></form>
            <form method="POST" action="{{ route('delivery-challans.cancel', $item) }}" onsubmit="return confirm('Cancel this challan?');">@csrf<x-ui.button type="submit" variant="secondary">Cancel</x-ui.button></form>
        @endif
    </x-slot>
</x-ui.page-header>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <x-ui.card class="lg:col-span-2">
        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Product</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Qty</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Description</th>
                </tr>
            </x-slot>
            @foreach($item->items as $line)
                <tr>
                    <td class="px-6 py-4 text-sm">{{ $line->product?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-right">{{ $line->quantity }} {{ $line->uom?->code }}</td>
                    <td class="px-6 py-4 text-sm">{{ $line->description ?? '—' }}</td>
                </tr>
            @endforeach
        </x-ui.table>
    </x-ui.card>
    <x-ui.card title="Summary">
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd>{{ ucfirst($item->status) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Order</dt><dd>{{ $item->order?->order_no ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Invoice</dt><dd>{{ $item->invoice?->invoice_no ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Vehicle</dt><dd>{{ $item->vehicle_no ?: '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">LR No</dt><dd>{{ $item->lr_no ?: '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Transporter</dt><dd>{{ $item->transporter_name ?: '—' }}</dd></div>
        </dl>
    </x-ui.card>
</div>
@endsection
