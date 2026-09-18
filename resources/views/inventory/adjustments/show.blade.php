@extends('layouts.dms')
@section('title', $adjustment->adjustment_no)
@section('content')
<x-ui.page-header :title="$adjustment->adjustment_no">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('inventory.adjustments.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
<x-ui.card><p class="text-xs text-slate-500">Warehouse</p><p class="font-medium">{{ $adjustment->warehouse?->name ?? '—' }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Reason</p><p class="font-medium">{{ $adjustment->reason }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Status</p><p class="font-medium">{{ $adjustment->status }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Date</p><p class="font-medium">{{ $adjustment->adjustment_date?->format('d M Y') }}</p></x-ui.card>
</div>
<x-ui.card>
<table class="min-w-full text-sm"><thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">UOM</th><th class="px-3 py-2 text-right">Qty</th>
</tr></thead>
<tbody class="divide-y">
@foreach($adjustment->items as $item)
<tr>
<td class="px-3 py-2">{{ $item->product?->name }}</td>
<td class="px-3 py-2">{{ $item->uom?->code }}</td>
<td class="px-3 py-2 text-right">{{ number_format($item->quantity, 2) }}</td>
</tr>
@endforeach
</tbody></table>
</x-ui.card>
@endsection
