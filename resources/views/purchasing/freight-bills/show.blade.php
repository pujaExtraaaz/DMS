@extends('layouts.dms')
@section('title', $freightBill->freight_no)
@section('content')
<x-ui.page-header :title="$freightBill->freight_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.freight-bills.index')">Back</x-ui.button>
        <x-ui.button variant="primary" :href="route('purchasing.landed-costs.create', ['freight_bill_id' => $freightBill->id])">Use in Landed Cost</x-ui.button>
    </x-slot>
</x-ui.page-header>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
<x-ui.card><p class="text-xs text-slate-500">Transporter</p><p class="font-medium">{{ $freightBill->transporter_name ?? '—' }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Vehicle / LR</p><p class="font-medium">{{ $freightBill->vehicle_no ?? '—' }} / {{ $freightBill->lr_no ?? '—' }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Status</p><p class="font-medium">{{ $freightBill->status }}</p></x-ui.card>
<x-ui.card><p class="text-xs text-slate-500">Total</p><p class="font-medium">₹{{ number_format($freightBill->total_amount, 2) }}</p></x-ui.card>
</div>
@endsection
