@extends('layouts.dms')
@section('title', $inward->inward_no)
@section('content')
<x-ui.page-header :title="$inward->inward_no">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('purchasing.inwards.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
<x-ui.card><p class="text-xs text-slate-500">Supplier</p><p class="font-medium">{{ $inward->supplier?->name }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">PO</p><p class="font-medium">{{ $inward->purchaseOrder?->po_no ?? '—' }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Warehouse</p><p class="font-medium">{{ $inward->warehouse?->name ?? '—' }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Date</p><p class="font-medium">{{ $inward->inward_date?->format('d M Y') }}</p></x-ui.card>
</div>
<x-ui.card>
<table class="min-w-full text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-right">Accepted</th><th class="px-3 py-2 text-right">Rejected</th><th class="px-3 py-2 text-left">Batch</th>
</tr></thead>
<tbody class="divide-y">
@foreach($inward->items as $item)
<tr>
<td class="px-3 py-2">{{ $item->product?->name }} ({{ $item->uom?->code }})</td>
<td class="px-3 py-2 text-right">{{ number_format($item->accepted_qty, 2) }}</td>
<td class="px-3 py-2 text-right">{{ number_format($item->rejected_qty, 2) }}</td>
<td class="px-3 py-2">{{ $item->batch_no ?? '—' }}</td>
</tr>
@endforeach
</tbody></table>
</x-ui.card>
@endsection
