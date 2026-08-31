@extends('layouts.dms')
@section('title', $loadSheet->load_sheet_no)
@section('content')
<x-ui.page-header :title="$loadSheet->load_sheet_no"><x-slot name="actions">
<x-ui.button variant="secondary" :href="route('logistics.load-sheets.index')">Back</x-ui.button>
@if($loadSheet->status === 'draft')<form method="POST" action="{{ route('logistics.load-sheets.dispatch', $loadSheet) }}" class="inline">@csrf<x-ui.button type="submit" variant="primary">Dispatch</x-ui.button></form>@endif
@if(!$loadSheet->settlement && in_array($loadSheet->status, ['dispatched','delivered','in_transit']))<x-ui.button variant="primary" :href="route('settlements.create', $loadSheet)">Settle</x-ui.button>@endif
</x-slot></x-ui.page-header>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<x-ui.card class="lg:col-span-2" title="Invoices"><x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Invoice</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Value</th></tr></x-slot>
@foreach($loadSheet->items as $item)<tr><td class="px-6 py-4 text-sm">{{ $item->invoice->invoice_no }}</td><td class="px-6 py-4 text-sm">{{ $item->invoice->customer->name }}</td><td class="px-6 py-4 text-sm text-right">₹{{ number_format($item->loaded_value, 2) }}</td></tr>@endforeach</x-ui.table></x-ui.card>
<x-ui.card title="Allocation"><dl class="space-y-2 text-sm">
<div class="flex justify-between"><dt>Status</dt><dd><x-ui.badge>{{ ucfirst($loadSheet->status) }}</x-ui.badge></dd></div>
<div class="flex justify-between"><dt>Route</dt><dd>{{ $loadSheet->route?->name ?? '—' }}</dd></div>
<div class="flex justify-between"><dt>Vehicle</dt><dd>{{ $loadSheet->vehicle?->registration_no ?? '—' }}</dd></div>
<div class="flex justify-between"><dt>Driver</dt><dd>{{ $loadSheet->driver?->name ?? '—' }}</dd></div>
<div class="flex justify-between"><dt>Delivery Person</dt><dd>{{ $loadSheet->deliveryPerson?->name ?? '—' }}</dd></div>
<div class="flex justify-between border-t pt-2 font-semibold"><dt>Total Value</dt><dd>₹{{ number_format($loadSheet->total_value, 2) }}</dd></div>
</dl></x-ui.card></div>
@endsection
