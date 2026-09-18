@extends('layouts.dms')
@section('title', $order->po_no)
@section('content')
<x-ui.page-header :title="$order->po_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.orders.index')">Back</x-ui.button>
        @if($order->status==='draft')
            <form method="POST" action="{{ route('purchasing.orders.submit', $order) }}">@csrf<x-ui.button type="submit" variant="secondary">Submit</x-ui.button></form>
            <form method="POST" action="{{ route('purchasing.orders.approve', $order) }}">@csrf<x-ui.button type="submit" variant="primary">Approve</x-ui.button></form>
        @endif
        @if($order->status==='pending_approval')
            <form method="POST" action="{{ route('purchasing.orders.approve', $order) }}">@csrf<x-ui.button type="submit" variant="primary">Approve</x-ui.button></form>
        @endif
        @if($order->isReceivable())
            <x-ui.button variant="primary" :href="route('purchasing.orders.receive', $order)">Receive</x-ui.button>
        @endif
        @if(!in_array($order->status, ['closed','cancelled']))
            <form method="POST" action="{{ route('purchasing.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this PO?')">@csrf<x-ui.button type="submit" variant="ghost">Cancel</x-ui.button></form>
        @endif
    </x-slot>
</x-ui.page-header>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
    <x-ui.card><p class="text-xs text-slate-500">Supplier</p><p class="font-medium">{{ $order->supplier?->name }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Status</p><p class="font-medium">{{ str_replace('_',' ', $order->status) }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Warehouse</p><p class="font-medium">{{ $order->warehouse?->name ?? '—' }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Grand Total</p><p class="font-medium">₹{{ number_format($order->grand_total, 2) }}</p></x-ui.card>
</div>
<x-ui.card>
<div class="overflow-x-auto"><table class="min-w-full text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">UOM</th><th class="px-3 py-2 text-right">Ordered</th><th class="px-3 py-2 text-right">Received</th><th class="px-3 py-2 text-right">Unit Cost</th><th class="px-3 py-2 text-right">Line Total</th>
</tr></thead>
<tbody class="divide-y">
@foreach($order->items as $item)
<tr>
<td class="px-3 py-2">{{ $item->product?->name }}</td>
<td class="px-3 py-2">{{ $item->uom?->code }}</td>
<td class="px-3 py-2 text-right">{{ number_format($item->quantity, 2) }}</td>
<td class="px-3 py-2 text-right">{{ number_format($item->received_qty, 2) }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($item->unit_cost, 2) }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($item->line_total, 2) }}</td>
</tr>
@endforeach
</tbody></table></div>
</x-ui.card>
@endsection
