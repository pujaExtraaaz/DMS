@extends('layouts.dms')

@section('title', 'Order '.$order->order_no)

@section('content')
    <x-ui.page-header
        :title="'Order '.$order->order_no"
        :description="$order->customer->name"
    >
        <x-slot name="actions">
            <x-ui.button
                variant="secondary"
                :href="route('orders.index')"
            >
                Back
            </x-ui.button>

                @if(in_array($order->status, ['draft', 'pending']))
                    <x-ui.button
                        variant="secondary"
                        :href="route('orders.edit', $order)"
                    >
                        Edit Order
                    </x-ui.button>
                @endif

            <a
                href="{{ route('orders.pdf', ['q' => $order->order_no]) }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Export PDF
            </a>

            @can('update', $order)
                <x-ui.button
                    variant="secondary"
                    :href="route('orders.edit', $order)"
                >
                    Edit
                </x-ui.button>
            @endcan

            @can('approve', $order)
                @if(in_array($order->status, ['pending', 'draft']))
                    <form
                        method="POST"
                        action="{{ route('orders.approve', $order) }}"
                        class="inline-flex items-center gap-2"
                    >
                        @csrf

                        <input
                            type="text"
                            name="approved_by_name"
                            value="{{ old('approved_by_name', auth()->user()->name ?? '') }}"
                            maxlength="100"
                            required
                            placeholder="Approved by"
                            class="w-44 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >

                        <x-ui.button
                            type="submit"
                            variant="primary"
                        >
                            Approve
                        </x-ui.button>
                    </form>
                @endif
            @endcan

            @can('convert', $order)
                @if(in_array($order->status, ['approved', 'pending']))
                    <form
                        method="POST"
                        action="{{ route('orders.convert', $order) }}"
                        class="inline"
                        onsubmit="return confirm('Convert this order into an invoice?')"
                    >
                        @csrf

                        <x-ui.button
                            type="submit"
                            variant="primary"
                        >
                            Convert to Invoice
                        </x-ui.button>
                    </form>
                @endif
            @endcan

            @can('cancel', $order)
                @if(in_array($order->status, ['draft', 'pending', 'approved']))
                    <form
                        method="POST"
                        action="{{ route('orders.cancel', $order) }}"
                        class="inline"
                        onsubmit="return confirm('Cancel this order? This cannot be undone.')"
                    >
                        @csrf

                        <x-ui.button
                            type="submit"
                            variant="danger"
                        >
                            Cancel
                        </x-ui.button>
                    </form>
                @endif
            @endcan

            @if($order->invoice)
                <x-ui.button
                    variant="ghost"
                    :href="route('invoices.show', $order->invoice)"
                >
                    View Invoice
                </x-ui.button>
            @endif
        </x-slot>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-ui.card
            class="lg:col-span-2"
            title="Line Items"
        >
            <x-ui.table>
                <x-slot name="head">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                            Product
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                            UOM
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                            Qty
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                            Price
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                            Total
                        </th>
                    </tr>
                </x-slot>

                @foreach($order->items as $item)
                    <tr>
                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium">
                                {{ $item->product->name }}
                            </div>

                            <div class="text-xs text-gray-500">
                                {{ $item->product->sku }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-sm">
                            {{ $item->uom->code }}
                        </td>

                        <td class="px-6 py-4 text-sm text-right">
                            {{ $item->quantity }}
                        </td>

                        <td class="px-6 py-4 text-sm text-right">
                            ₹{{ number_format($item->unit_price, 2) }}
                        </td>

                        <td class="px-6 py-4 text-sm font-medium text-right">
                            ₹{{ number_format($item->line_total, 2) }}
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>

        <x-ui.card title="Summary">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        <x-ui.badge variant="info">
                            {{ ucfirst($order->status) }}
                        </x-ui.badge>
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Order Date</dt>
                    <dd>
                        {{ $order->order_date->format('d M Y') }}
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Customer</dt>
                    <dd class="text-right">
                        {{ $order->customer->name }}
                        <div class="text-xs text-gray-500">
                            {{ $order->customer->code }}
                        </div>
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Customer Type</dt>
                    <dd>
                        {{ $order->customer->customerType?->name ?? '—' }}
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Area</dt>
                    <dd>
                        {{ $order->customer->area?->name ?? '—' }}
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Created By</dt>
                    <dd class="font-medium">
                        {{ $order->created_by_name ?: ($order->salesperson->name ?? '—') }}
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Last Edited By</dt>
                    <dd class="font-medium">
                        {{ $order->updated_by_name ?: '—' }}
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Approved By</dt>
                    <dd class="font-medium">
                        {{ $order->approved_by_name ?: ($order->approver->name ?? '—') }}
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Approved At</dt>
                    <dd>
                        {{ $order->approved_at?->format('d M Y, h:i A') ?? '—' }}
                    </dd>
                </div>

                <div class="border-t pt-3 flex justify-between">
                    <dt class="text-gray-500">Subtotal</dt>
                    <dd>
                        ₹{{ number_format($order->subtotal, 2) }}
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Discount</dt>
                    <dd>
                        ₹{{ number_format($order->discount_amount, 2) }}
                    </dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Tax</dt>
                    <dd>
                        ₹{{ number_format($order->tax_amount, 2) }}
                    </dd>
                </div>

                <div class="border-t pt-3 flex justify-between font-semibold text-base">
                    <dt>Grand Total</dt>
                    <dd>
                        ₹{{ number_format($order->grand_total, 2) }}
                    </dd>
                </div>
            </dl>

            @if($order->approved_at)
                <div class="mt-5 rounded-lg bg-gray-50 p-3 text-sm">
                    <p class="font-medium">
                        Approved
                    </p>

                    <p class="text-gray-500">
                        {{ $order->approved_at->format('d M Y H:i') }}
                        by
                        {{ $order->approver?->name ?? '—' }}
                    </p>
                </div>
            @endif

            @if($order->notes)
                <div class="mt-5">
                    <p class="text-xs font-semibold uppercase text-gray-500">
                        Notes
                    </p>

                    <p class="mt-1 text-sm text-gray-700 whitespace-pre-line">
                        {{ $order->notes }}
                    </p>
                </div>
            @endif

            @if($order->invoice)
                <div class="mt-5 rounded-lg bg-emerald-50 p-4">
                    <p class="text-sm font-semibold text-emerald-800">
                        Invoice Created
                    </p>

                    <a
                        href="{{ route('invoices.show', $order->invoice) }}"
                        class="mt-1 inline-block text-sm text-emerald-700 underline"
                    >
                        {{ $order->invoice->invoice_no }}
                    </a>
                </div>
            @endif
        </x-ui.card>
    </div>
@endsection