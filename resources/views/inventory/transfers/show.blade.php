@extends('layouts.dms')
@section('title', $transfer->transfer_no)
@section('content')
<x-ui.page-header :title="$transfer->transfer_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('inventory.transfers.index')">Back</x-ui.button>
        @if($transfer->status==='draft')
            <form method="POST" action="{{ route('inventory.transfers.dispatch', $transfer) }}">@csrf<x-ui.button type="submit" variant="primary">Dispatch</x-ui.button></form>
        @endif
        @if($transfer->status==='in_transit')
            <form method="POST" action="{{ route('inventory.transfers.receive', $transfer) }}">@csrf<x-ui.button type="submit" variant="primary">Receive</x-ui.button></form>
        @endif
    </x-slot>
</x-ui.page-header>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
<x-ui.card><p class="text-xs text-slate-500">From</p><p class="font-medium">{{ $transfer->fromWarehouse?->name }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">To</p><p class="font-medium">{{ $transfer->toWarehouse?->name }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Status</p><p class="font-medium">{{ str_replace('_',' ', $transfer->status) }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Date</p><p class="font-medium">{{ $transfer->transfer_date?->format('d M Y') }}</p></x-ui.card>
</div>
<x-ui.card>
<table class="min-w-full text-sm"><thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">UOM</th><th class="px-3 py-2 text-right">Qty</th>
</tr></thead>
<tbody class="divide-y">
@foreach($transfer->items as $item)
<tr>
<td class="px-3 py-2">{{ $item->product?->name }}</td>
<td class="px-3 py-2">{{ $item->uom?->code }}</td>
<td class="px-3 py-2 text-right">{{ number_format($item->quantity, 2) }}</td>
</tr>
@endforeach
</tbody></table>
</x-ui.card>
@endsection
