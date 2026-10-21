@extends('layouts.dms')
@section('title', $order->po_no)
@section('content')
<x-ui.page-header :title="$order->po_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.orders.index')">Back</x-ui.button>
        <x-ui.button variant="secondary" :href="route('purchasing.orders.preview', $order)">Print Preview</x-ui.button>
        @if($order->isEditable())
            <x-ui.button variant="secondary" :href="route('purchasing.orders.edit', $order)">Edit</x-ui.button>
        @endif
        @if($order->status==='draft')
            <form method="POST" action="{{ route('purchasing.orders.submit', $order) }}">@csrf<x-ui.button type="submit" variant="secondary">Submit</x-ui.button></form>
            <form method="POST" action="{{ route('purchasing.orders.approve', $order) }}">@csrf<x-ui.button type="submit" variant="primary">Approve</x-ui.button></form>
        @endif
        @if($order->status==='pending_approval')
            <form method="POST" action="{{ route('purchasing.orders.approve', $order) }}">@csrf<x-ui.button type="submit" variant="primary">Approve</x-ui.button></form>
        @endif
        @if($order->isReceivable())
            <x-ui.button variant="primary" :href="route('purchasing.orders.receive', $order)">Receive</x-ui.button>
        @endif
        @if(!in_array($order->status, ['closed','cancelled']))
            <form method="POST" action="{{ route('purchasing.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this PO?')">@csrf<x-ui.button type="submit" variant="ghost">Cancel</x-ui.button></form>
        @endif
    </x-slot>
</x-ui.page-header>
<div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-4">
    <x-ui.card><p class="text-xs text-slate-500">Supplier</p><p class="font-medium">{{ $order->supplier?->name }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Status</p><p class="font-medium">{{ str_replace('_',' ', $order->status) }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">PO Date</p><p class="font-medium">{{ $order->po_date?->format('d M Y') ?? '—' }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Due Date</p><p class="font-medium">{{ $order->due_date?->format('d M Y') ?? '—' }} @if($order->credit_days) <span class="text-xs text-slate-400">({{ $order->credit_days }} days)</span> @endif</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Warehouse</p><p class="font-medium">{{ $order->warehouse?->name ?? '—' }}</p></x-ui.card>
    <x-ui.card><p class="text-xs text-slate-500">Grand Total</p><p class="font-medium">₹{{ number_format($order->grand_total, 2) }}</p></x-ui.card>
</div>
<x-ui.card class="mb-4">
    <h4 class="text-xs font-bold tracking-wider text-slate-500 mb-2">Addresses</h4>
    @php
        $supplierDetails = $order->resolveSupplierDetails();
        $consigneeDetails = $order->resolveConsigneeDetails();
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
            <span class="text-xs font-semibold uppercase text-slate-500">Billing Address (Supplier)</span>
            <div class="mt-1 font-medium text-slate-800 whitespace-pre-line">{{ $supplierDetails['billing_address'] }}</div>
        </div>
        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
            <span class="text-xs font-semibold uppercase text-slate-500">Delivery / Dispatch Address (Consignee)</span>
            <div class="mt-1 font-medium text-slate-800 whitespace-pre-line">{{ $consigneeDetails['delivery_address'] }}</div>
        </div>
    </div>
</x-ui.card>
<x-ui.card>
<div class="overflow-x-auto"><table class="min-w-full text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">Batch Name</th><th class="px-3 py-2 text-left">UOM</th><th class="px-3 py-2 text-right">Ordered</th><th class="px-3 py-2 text-right">Received</th><th class="px-3 py-2 text-right">Unit Cost</th><th class="px-3 py-2 text-right">Line Total</th>
</tr></thead>
<tbody class="divide-y">
@foreach($order->items as $item)
<tr>
<td class="px-3 py-2">{{ $item->product?->name }}</td>
<td class="px-3 py-2 font-mono text-xs">{{ $item->batch_no ?? '-' }}</td>
<td class="px-3 py-2">{{ $item->uom?->code }}</td>
<td class="px-3 py-2 text-right">{{ number_format($item->quantity, 2) }}</td>
<td class="px-3 py-2 text-right">{{ number_format($item->received_qty, 2) }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($item->unit_cost, 2) }}</td>
<td class="px-3 py-2 text-right">₹{{ number_format($item->line_total, 2) }}</td>
</tr>
@endforeach
</tbody></table></div>
</x-ui.card>

<div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-ui.card>
        <h4 class="text-xs font-bold tracking-wider text-slate-500 mb-3">Freight &amp; Order Notes</h4>
        <div class="space-y-3 text-sm">
            <div>
                <span class="text-xs text-slate-500 block">Freight Applicable</span>
                <span class="font-medium inline-flex items-center px-2 py-0.5 rounded text-xs {{ $order->is_freight_applicable ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                    {{ $order->is_freight_applicable ? 'Yes' : 'No' }}
                </span>
            </div>
            @if($order->freight_notes)
                <div>
                    <span class="text-xs text-slate-500 block">Freight Notes / Transporter Details</span>
                    <p class="font-medium text-slate-800 whitespace-pre-line mt-0.5">{{ $order->freight_notes }}</p>
                </div>
            @endif
            @if($order->notes)
                <div>
                    <span class="text-xs text-slate-500 block">Order Notes / Instructions</span>
                    <p class="font-medium text-slate-800 whitespace-pre-line mt-0.5">{{ $order->notes }}</p>
                </div>
            @endif
            @if(!$order->freight_notes && !$order->notes)
                <p class="text-xs text-slate-400">No additional notes provided.</p>
            @endif
        </div>
    </x-ui.card>

    <x-ui.card>
        <h4 class="text-xs font-bold tracking-wider text-slate-500 mb-3">Order Total Summary</h4>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between text-slate-600">
                <span>Items Subtotal (Taxable):</span>
                <span class="font-mono font-medium">₹{{ number_format($order->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Items Tax (GST):</span>
                <span class="font-mono font-medium">₹{{ number_format($order->tax_amount, 2) }}</span>
            </div>
            @if($order->is_freight_applicable || (float) $order->freight_amount > 0)
                <div class="flex justify-between text-slate-600">
                    <span>
                        Freight Charges
                        @if(($order->freight_mode ?? 'amount') === 'percent')
                            ({{ rtrim(rtrim(number_format((float) $order->freight_percent, 2), '0'), '.') }}% of taxable amount)
                        @endif
                        :
                    </span>
                    <span class="font-mono font-medium">₹{{ number_format((float) $order->freight_amount, 2) }}</span>
                </div>
                @if((float) $order->freight_tax_amount > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Freight Tax ({{ number_format((float) $order->freight_tax_rate, 1) }}%):</span>
                        <span class="font-mono font-medium">₹{{ number_format((float) $order->freight_tax_amount, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-slate-600 font-semibold border-t border-slate-100 pt-1">
                    <span>Freight Total:</span>
                    <span class="font-mono">₹{{ number_format((float) $order->freight_total, 2) }}</span>
                </div>
            @endif
            <div class="pt-2 border-t border-slate-200 flex justify-between text-base font-bold text-slate-900">
                <span>Grand Total:</span>
                <span class="font-mono text-indigo-600">₹{{ number_format($order->grand_total, 2) }}</span>
            </div>
        </div>
    </x-ui.card>
</div>
@endsection
