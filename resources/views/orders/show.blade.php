@extends('layouts.dms')

@section('title', 'Order '.$order->order_no)

@section('content')
    <x-ui.page-header :title="'Order '.$order->order_no" :description="$order->customer->name">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('orders.index')">Back</x-ui.button>
            @if(in_array($order->status, ['pending', 'draft']))
                <form method="POST" action="{{ route('orders.approve', $order) }}" class="inline">@csrf
                    <x-ui.button type="submit" variant="primary">Approve</x-ui.button>
                </form>
            @endif
            @if(in_array($order->status, ['approved', 'pending']))
                <form method="POST" action="{{ route('orders.convert', $order) }}" class="inline">@csrf
                    <x-ui.button type="submit" variant="primary">Convert to Invoice</x-ui.button>
                </form>
            @endif
            @if($order->invoice)
                <x-ui.button variant="ghost" :href="route('invoices.show', $order->invoice)">View Invoice</x-ui.button>
            @endif
        </x-slot>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-ui.card class="lg:col-span-2" title="Line Items">
            <x-ui.table>
                <x-slot name="head">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">UOM</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Qty</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Price</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total</th>
                    </tr>
                </x-slot>
                @foreach($order->items as $item)
                    <tr>
                        <td class="px-6 py-4 text-sm">{{ $item->product->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ $item->uom->code }}</td>
                        <td class="px-6 py-4 text-sm text-right">{{ $item->quantity }}</td>
                        <td class="px-6 py-4 text-sm text-right">₹{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-right">₹{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>

        <x-ui.card title="Summary">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd><x-ui.badge variant="info">{{ ucfirst($order->status) }}</x-ui.badge></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Date</dt><dd>{{ $order->order_date->format('d M Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Salesperson</dt><dd>{{ $order->salesperson->name ?? '—' }}</dd></div>
                <div class="flex justify-between border-t pt-2"><dt class="text-gray-500">Subtotal</dt><dd>₹{{ number_format($order->subtotal, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Discount</dt><dd>₹{{ number_format($order->discount_amount, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Tax</dt><dd>₹{{ number_format($order->tax_amount, 2) }}</dd></div>
                <div class="flex justify-between font-semibold"><dt>Grand Total</dt><dd>₹{{ number_format($order->grand_total, 2) }}</dd></div>
            </dl>
            @if($order->notes)
                <p class="mt-4 text-sm text-gray-600">{{ $order->notes }}</p>
            @endif
        </x-ui.card>
    </div>
@endsection
