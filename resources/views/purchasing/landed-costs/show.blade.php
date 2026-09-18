@extends('layouts.dms')
@section('title', $landedCost->landed_no)
@section('content')
<x-ui.page-header :title="$landedCost->landed_no">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('purchasing.landed-costs.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
@php
    $invoice = $landedCost->purchaseInvoice;

    $productCost = (float) ($invoice?->subtotal ?? 0);
    $gstTotal = (float) ($invoice?->tax_amount ?? 0);
    $invoiceTotal = (float) ($invoice?->grand_total ?? 0);

    $freightCost = (float) ($landedCost->freightBill?->total_amount ?? 0);

    $additionalCost = max(
        0,
        (float) $landedCost->total_additional_cost - $freightCost
    );

    $cgst = $gstTotal / 2;
    $sgst = $gstTotal / 2;

    $gstRate = $productCost > 0
        ? ($gstTotal / $productCost) * 100
        : 0;

    $cgstRate = $gstRate / 2;
    $sgstRate = $gstRate / 2;

    $totalLandedAmount = $productCost + $freightCost + $additionalCost + $gstTotal;
@endphp

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">

    <x-ui.card>
        <p class="text-xs text-slate-500">Purchase Invoice</p>
        <p class="font-medium">
            {{ $invoice?->invoice_no ?? '—' }}
        </p>
    </x-ui.card>

    <x-ui.card>
        <p class="text-xs text-slate-500">Allocation Method</p>
        <p class="font-medium">
            {{ ucfirst($landedCost->allocation_method) }}
        </p>
    </x-ui.card>

    <x-ui.card>
        <p class="text-xs text-slate-500">Freight Bill</p>

        <p class="font-medium">
            {{ $landedCost->freightBill?->freight_no ?? '—' }}
        </p>

        @if($landedCost->freightBill)
            <p class="text-xs text-slate-500 mt-1">
                ₹{{ number_format($freightCost, 2) }}
            </p>
        @endif
    </x-ui.card>

    <x-ui.card>
        <p class="text-xs text-slate-500">Total Landed Cost Added</p>
        <p class="font-medium">
            ₹{{ number_format((float) $landedCost->total_additional_cost, 2) }}
        </p>
    </x-ui.card>

</div>

{{-- Cost Breakdown --}}
<x-ui.card class="mb-4">

    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-base font-semibold text-slate-900">
                Cost Breakdown
            </h3>

            <p class="text-xs text-slate-500 mt-1">
                Purchase invoice cost, GST and landed-cost additions
            </p>
        </div>
    </div>

    <div class="divide-y divide-slate-100">

        <div class="flex items-center justify-between py-3">
            <span class="text-sm text-slate-600">
                Product / Base Cost
            </span>

            <span class="text-sm font-medium text-slate-900">
                ₹{{ number_format($productCost, 2) }}
            </span>
        </div>

        <div class="flex items-center justify-between py-3">
            <span class="text-sm text-slate-600">
                Freight Cost
            </span>

            <span class="text-sm font-medium text-slate-900">
                ₹{{ number_format($freightCost, 2) }}
            </span>
        </div>

        <div class="flex items-center justify-between py-3">
            <span class="text-sm text-slate-600">
                Additional Cost
            </span>

            <span class="text-sm font-medium text-slate-900">
                ₹{{ number_format($additionalCost, 2) }}
            </span>
        </div>

        <div class="flex items-center justify-between py-3">
            <span class="text-sm text-slate-600">
                CGST @ {{ number_format($cgstRate, 2) }}%
            </span>

            <span class="text-sm font-medium text-slate-900">
                ₹{{ number_format($cgst, 2) }}
            </span>
        </div>

        <div class="flex items-center justify-between py-3">
            <span class="text-sm text-slate-600">
                SGST @ {{ number_format($sgstRate, 2) }}%
            </span>

            <span class="text-sm font-medium text-slate-900">
                ₹{{ number_format($sgst, 2) }}
            </span>
        </div>

        <div class="flex items-center justify-between py-3">
            <span class="text-sm font-medium text-slate-700">
                Total GST
            </span>

            <span class="text-sm font-semibold text-slate-900">
                ₹{{ number_format($gstTotal, 2) }}
            </span>
        </div>

        <div class="flex items-center justify-between py-4">
            <span class="text-base font-semibold text-slate-900">
                Overall Cost
            </span>

            <span class="text-lg font-bold text-slate-900">
                ₹{{ number_format($totalLandedAmount, 2) }}
            </span>
        </div>

    </div>

</x-ui.card>
<x-ui.card>
<table class="min-w-full text-sm"><thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-right">Qty</th><th class="px-3 py-2 text-right">Allocated</th><th class="px-3 py-2 text-right">Landed Unit Cost</th>
</tr></thead>
<tbody class="divide-y">
@foreach($landedCost->items as $item)
<tr>
<td class="px-3 py-2">{{ $item->product?->name }}</td>
<td class="px-3 py-2 text-right">{{ number_format($item->quantity, 2) }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($item->allocated_cost, 2) }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($item->landed_unit_cost, 4) }}</td>
</tr>
@endforeach
</tbody></table>
</x-ui.card>
@endsection
