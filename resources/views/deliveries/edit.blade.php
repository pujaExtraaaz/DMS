@extends('layouts.dms')
@section('title', 'Delivery Entry')
@section('content')
<x-ui.page-header :title="$delivery->invoice->invoice_no" :description="$delivery->customer->name"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('deliveries.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ route('deliveries.update', $delivery) }}" class="space-y-4">@csrf @method('PUT')
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Loaded</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Delivered</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Short</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Returned</th></tr></x-slot>
@foreach($delivery->items as $item)<tr>
<input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
<td class="px-6 py-4 text-sm">{{ $item->product->name }} ({{ $item->uom->code }})</td>
<td class="px-6 py-4 text-sm text-right">{{ $item->loaded_qty }}</td>
<td class="px-6 py-4 text-sm text-right"><input type="number" step="0.0001" name="items[{{ $loop->index }}][delivered_qty]" value="{{ old('items.'.$loop->index.'.delivered_qty', $item->delivered_qty) }}" class="w-24 rounded border-gray-300 text-sm text-right"></td>
<td class="px-6 py-4 text-sm text-right"><input type="number" step="0.0001" name="items[{{ $loop->index }}][short_qty]" value="{{ old('items.'.$loop->index.'.short_qty', $item->short_qty) }}" class="w-24 rounded border-gray-300 text-sm text-right"></td>
<td class="px-6 py-4 text-sm text-right"><input type="number" step="0.0001" name="items[{{ $loop->index }}][returned_qty]" value="{{ old('items.'.$loop->index.'.returned_qty', $item->returned_qty) }}" class="w-24 rounded border-gray-300 text-sm text-right"></td>
</tr>@endforeach</x-ui.table>
<x-ui.button type="submit" variant="primary">Save Delivery</x-ui.button></form></x-ui.card>
@endsection
