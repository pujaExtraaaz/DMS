@extends('layouts.dms')
@section('title', 'Delivery Report')
@section('content')
<x-ui.page-header title="Delivery Report" />
<x-ui.card>
<form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
<x-ui.input name="date_from" type="date" label="From" :value="$dateFrom" />
<x-ui.input name="date_to" type="date" label="To" :value="$dateTo" />
<x-ui.select name="customer_id" label="Party" placeholder="All"><option value=""></option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="salesperson_id" label="Salesperson" placeholder="All"><option value=""></option>@foreach($salespeople as $u)<option value="{{ $u->id }}" @selected(request('salesperson_id')==$u->id)>{{ $u->name }}</option>@endforeach</x-ui.select>
<x-ui.button type="submit" variant="secondary">Run Report</x-ui.button>
</form>
@foreach($summary as $status => $count)<p class="text-sm text-gray-600">{{ ucfirst($status) }}: {{ $count }}</p>@endforeach
<x-ui.table class="mt-4"><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Invoice</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th></tr></x-slot>
@foreach($deliveries as $delivery)<tr><td class="px-6 py-4 text-sm">{{ $delivery->customer->name }}</td><td class="px-6 py-4 text-sm">{{ $delivery->invoice->invoice_no }}</td><td class="px-6 py-4 text-sm">{{ ucfirst($delivery->status) }}</td></tr>@endforeach</x-ui.table></x-ui.card>
@endsection
