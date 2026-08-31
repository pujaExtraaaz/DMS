@extends('layouts.dms')
@section('title', 'Create Load Sheet')
@section('content')
<x-ui.page-header title="Create Load Sheet"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('logistics.load-sheets.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ route('logistics.load-sheets.store') }}" class="space-y-4">@csrf
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<x-ui.input name="load_date" label="Load Date" type="date" :value="now()->toDateString()" required />
<x-ui.select name="route_id" label="Route" placeholder="Select"><option value=""></option>@foreach($routes as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="vehicle_id" label="Vehicle" placeholder="Select"><option value=""></option>@foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->name }} ({{ $v->registration_no }})</option>@endforeach</x-ui.select>
<x-ui.select name="driver_id" label="Driver" placeholder="Select"><option value=""></option>@foreach($drivers as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="delivery_person_id" label="Delivery Person" placeholder="Select"><option value=""></option>@foreach($deliveryPersons as $dp)<option value="{{ $dp->id }}">{{ $dp->name }}</option>@endforeach</x-ui.select>
</div>
<div class="border rounded-lg p-4 space-y-2 max-h-64 overflow-y-auto">
<p class="text-sm font-medium text-gray-700">Select Invoices</p>
@foreach($invoices as $invoice)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="rounded border-gray-300"> {{ $invoice->invoice_no }} — {{ $invoice->customer->name }} (₹{{ number_format($invoice->grand_total, 2) }})</label>@endforeach
</div>
<x-ui.button type="submit" variant="primary">Create Load Sheet</x-ui.button></form></x-ui.card>
@endsection
