@extends('layouts.dms')
@section('title', $purchase->purchase_no)
@section('content')
<x-ui.page-header :title="$purchase->purchase_no" :description="$purchase->supplier_name"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('inventory.purchases.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card title="Items"><x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">UOM</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Qty</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Cost</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total</th></tr></x-slot>
@foreach($purchase->items as $item)<tr><td class="px-6 py-4 text-sm">{{ $item->product->name }}</td><td class="px-6 py-4 text-sm">{{ $item->uom->code }}</td><td class="px-6 py-4 text-sm text-right">{{ $item->quantity }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($item->unit_cost, 2) }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($item->line_total, 2) }}</td></tr>@endforeach</x-ui.table>
<p class="mt-4 text-right font-semibold">Grand Total: ₹{{ number_format($purchase->grand_total, 2) }}</p></x-ui.card>
@endsection
