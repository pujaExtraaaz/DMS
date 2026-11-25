@extends('layouts.dms')
@section('title', 'Quotation '.$item->quotation_no)
@section('content')
<x-ui.page-header :title="'Quotation '.$item->quotation_no" :description="$item->customer?->name">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('quotations.index')">Back</x-ui.button>
        <x-ui.button variant="secondary" :href="route('quotations.preview', $item)" target="_blank">Preview</x-ui.button>
        <a href="{{ route('quotations.export', ['quotation' => $item, 'format' => 'pdf']) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-xs hover:bg-slate-50 transition-colors">PDF</a>
        <a href="{{ route('quotations.export', ['quotation' => $item, 'format' => 'xlsx']) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-xs hover:bg-slate-50 transition-colors">Excel</a>
        <a href="{{ route('quotations.export', ['quotation' => $item, 'format' => 'csv']) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-xs hover:bg-slate-50 transition-colors">CSV</a>
        @if($item->isEditable())
            <x-ui.button variant="secondary" :href="route('quotations.edit', $item)">Edit</x-ui.button>
        @endif
        @if($item->status === 'draft')
            <form method="POST" action="{{ route('quotations.send', $item) }}">@csrf<x-ui.button type="submit" variant="primary">Mark Sent</x-ui.button></form>
        @endif
        @if(in_array($item->status, ['draft','sent']) && ! $item->converted_order_id)
            <form method="POST" action="{{ route('quotations.accept', $item) }}"
                  onsubmit="return confirm('Accept this quotation and create a Sales Order?');">
                @csrf
                <x-ui.button type="submit" variant="primary">Accept & Create Sales Order</x-ui.button>
            </form>
        @endif
        @if($item->converted_order_id)
            <x-ui.button variant="primary" :href="route('orders.show', $item->converted_order_id)">View Sales Order</x-ui.button>
        @endif
        @if($item->isDeletable())
            <form method="POST" action="{{ route('quotations.destroy', $item) }}"
                  onsubmit="return confirm('Delete quotation {{ $item->quotation_no }}? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger">Delete</x-ui.button>
            </form>
        @endif
    </x-slot>
</x-ui.page-header>

@if($item->convertedOrder)
    <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
        Converted to Sales Order
        <a href="{{ route('orders.show', $item->convertedOrder) }}" class="font-semibold underline">{{ $item->convertedOrder->order_no }}</a>
    </div>
@endif

@if($item->title)
    <div class="mb-3 text-sm font-semibold text-slate-700">{{ $item->title }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <x-ui.card class="lg:col-span-2">
        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">#</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">HSN</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">UOM</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">MRP</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Price</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Tax %</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Total</th>
                    @if($canViewProfit)
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Est. Profit</th>
                    @endif
                </tr>
            </x-slot>
            @foreach($item->items as $line)
                <tr>
                    <td class="px-6 py-4 text-sm text-slate-500">{{ $line->sort_order ?: $loop->iteration }}</td>
                    <td class="px-6 py-4 text-sm">
                        <div class="font-medium text-slate-900">{{ $line->product?->name }}</div>
                        @if($line->description)
                            <div class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ $line->description }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $line->hsn_code ?: '—' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $line->uom?->code }}</td>
                    <td class="px-6 py-4 text-sm text-right">{{ $line->quantity }}</td>
                    <td class="px-6 py-4 text-sm text-right">{{ $line->mrp !== null ? '₹'.number_format($line->mrp, 2) : '—' }}</td>
                    <td class="px-6 py-4 text-sm text-right">₹{{ number_format($line->unit_price, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-right">{{ $line->tax_rate !== null ? rtrim(rtrim(number_format($line->tax_rate, 2), '0'), '.').'%' : '—' }}</td>
                    <td class="px-6 py-4 text-sm text-right">₹{{ number_format($line->taxableDisplayTotal(), 2) }}</td>
                    @if($canViewProfit)
                        <td class="px-6 py-4 text-sm text-right">₹{{ number_format($line->estimated_profit, 2) }}</td>
                    @endif
                </tr>
            @endforeach
        </x-ui.table>
    </x-ui.card>
    <x-ui.card title="Summary">
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd>{{ ucfirst($item->status) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Date</dt><dd>{{ $item->quotation_date?->format('d M Y') }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Valid Until</dt><dd>{{ $item->valid_until?->format('d M Y') ?: '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Subtotal</dt><dd>₹{{ number_format($item->subtotal, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Discount</dt><dd>₹{{ number_format($item->discount_amount, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Tax</dt><dd>₹{{ number_format($item->tax_amount, 2) }}</dd></div>
            <div class="flex justify-between font-semibold"><dt>Grand Total</dt><dd>₹{{ number_format($item->grand_total, 2) }}</dd></div>
            @if($canViewProfit)
                <div class="flex justify-between text-emerald-700 font-semibold border-t pt-3"><dt>Est. Profit</dt><dd>₹{{ number_format($item->estimated_profit, 2) }}</dd></div>
            @endif
        </dl>
        @if($item->special_instructions)
            <div class="mt-4 pt-3 border-t">
                <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Special Instructions</div>
                <div class="text-sm text-slate-700 whitespace-pre-line">{{ $item->special_instructions }}</div>
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
