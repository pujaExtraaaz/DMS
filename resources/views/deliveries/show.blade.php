@extends('layouts.dms')
@section('title', 'Delivery Details')
@section('content')
<x-ui.page-header :title="$delivery->invoice->invoice_no"><x-slot name="actions"><x-ui.button variant="primary" :href="route('deliveries.edit', $delivery)">Enter Quantities</x-ui.button><x-ui.button variant="secondary" :href="route('deliveries.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card title="Items"><x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Loaded</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Delivered</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Short</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Returned</th></tr></x-slot>
@foreach($delivery->items as $item)<tr><td class="px-6 py-4 text-sm">{{ $item->product->name }}</td><td class="px-6 py-4 text-sm text-right">{{ $item->loaded_qty }}</td><td class="px-6 py-4 text-sm text-right">{{ $item->delivered_qty }}</td><td class="px-6 py-4 text-sm text-right">{{ $item->short_qty }}</td><td class="px-6 py-4 text-sm text-right">{{ $item->returned_qty }}</td></tr>@endforeach</x-ui.table></x-ui.card>
@endsection
