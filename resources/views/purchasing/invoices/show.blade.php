@extends('layouts.dms')
@section('title', $invoice->invoice_no)
@section('content')
<x-ui.page-header :title="$invoice->invoice_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.index')">
            Back
        </x-ui.button>

        <div class="flex items-center gap-2">
            <x-ui.button
                variant="secondary"
                :href="route('purchasing.invoices.preview', $invoice)"
            >
                Preview Invoice
            </x-ui.button>

            <x-ui.button
                variant="primary"
                :href="route('purchasing.landed-costs.create', ['purchase_invoice_id' => $invoice->id])"
            >
                Allocate Landed Cost
            </x-ui.button>
        </div>
    </x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-4">
    <x-ui.card><p class="text-xs text-slate-500">Supplier</p><p class="font-medium truncate">{{ $invoice->supplier?->name }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Supplier Inv</p><p class="font-medium">{{ $invoice->supplier_invoice_no ?? '—' }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Invoice Date</p><p class="font-medium">{{ $invoice->invoice_date?->format('d M Y') }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Credit Days</p><p class="font-medium">{{ $invoice->credit_days !== null ? $invoice->credit_days . ' Days' : '0 Days' }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Due Date</p><p class="font-medium">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Grand Total</p><p class="font-medium">₹{{ number_format($invoice->grand_total, 2) }}</p></x-ui.card>
</div>

@if($invoice->rate_override_reason)
<x-ui.alert type="error" :message="'Rate override: '.$invoice->rate_override_reason" />
@endif

<x-ui.card>
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-3 py-2 text-left">Product</th>
                <th class="px-3 py-2 text-right">Qty</th>
                <th class="px-3 py-2 text-right">Unit Cost (₹)</th>
                <th class="px-3 py-2 text-right">Batch Selling ₹</th>
                <th class="px-3 py-2 text-right">Batch MRP ₹</th>
                <th class="px-3 py-2 text-right">Tax %</th>
                <th class="px-3 py-2 text-right">Line Total</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($invoice->items as $item)
            <tr>
                <td class="px-3 py-2">
                    <div class="font-medium text-slate-800">{{ $item->product?->name }}</div>
                    @if($item->batch_no)
                        <div class="text-xs text-slate-500">Batch: {{ $item->batch_no }}</div>
                    @endif
                    @if($item->serials->isNotEmpty())
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                            <span class="text-[11px] font-semibold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-100">Serials ({{ $item->serials->count() }}):</span>
                            @foreach($item->serials as $s)
                                <span class="text-[11px] font-mono bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded border border-slate-200">{{ $s->serial_number }}</span>
                            @endforeach
                        </div>
                    @endif
                </td>
                <td class="px-3 py-2 text-right">{{ number_format($item->quantity, 2) }} {{ $item->uom?->code }}</td>
                <td class="px-3 py-2 text-right">₹{{ number_format($item->unit_cost, 2) }}</td>
                <td class="px-3 py-2 text-right">{{ $item->batch_selling_price !== null ? '₹'.number_format($item->batch_selling_price, 2) : '—' }}</td>
                <td class="px-3 py-2 text-right">{{ $item->batch_mrp !== null ? '₹'.number_format($item->batch_mrp, 2) : '—' }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($item->tax_percent, 2) }}%</td>
                <td class="px-3 py-2 text-right">₹{{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</x-ui.card>
@endsection