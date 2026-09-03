@extends('layouts.dms')

@section('title', 'Edit Order '.$order->order_no)

@section('content')
    <x-ui.page-header
        :title="'Edit '.$order->order_no"
        description="Edit this order before approval."
    >
        <x-slot name="actions">
            <x-ui.button
                variant="secondary"
                :href="route('orders.show', $order)"
            >
                Cancel
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <form
            method="POST"
            action="{{ route('orders.update', $order) }}"
            class="space-y-6"
        >
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Customer
                    </label>

                    <input
                        type="text"
                        value="{{ $order->customer->name }} ({{ $order->customer->code }})"
                        readonly
                        class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 text-sm"
                    >

                    <input
                        type="hidden"
                        name="customer_id"
                        value="{{ $order->customer_id }}"
                    >
                </div>

                <x-ui.input
                    name="order_date"
                    label="Order Date"
                    type="date"
                    :value="old('order_date', $order->order_date->toDateString())"
                    max="{{ now()->toDateString() }}"
                    required
                />

                <x-ui.input
                    name="discount_amount"
                    label="Discount"
                    type="number"
                    step="0.01"
                    min="0"
                    :value="old('discount_amount', $order->discount_amount)"
                />

                <x-ui.input
                    name="updated_by_name"
                    label="Edited By"
                    :value="old('updated_by_name', auth()->user()?->name)"
                    maxlength="120"
                    required
                />
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left">Product</th>
                            <th class="px-3 py-3 text-left">UOM</th>
                            <th class="px-3 py-3 text-right">Quantity</th>
                            <th class="px-3 py-3 text-right">Price</th>
                            <th class="px-3 py-3 text-right">Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($order->items as $index => $item)
                            <tr class="border-b">
                                <td class="px-3 py-3">
                                    {{ $item->product->name }}
                                    <input
                                        type="hidden"
                                        name="items[{{ $index }}][product_id]"
                                        value="{{ $item->product_id }}"
                                    >
                                </td>

                                <td class="px-3 py-3">
                                    {{ $item->uom->code }}
                                    <input
                                        type="hidden"
                                        name="items[{{ $index }}][uom_id]"
                                        value="{{ $item->uom_id }}"
                                    >
                                </td>

                                <td class="px-3 py-3">
                                    <input
                                        type="number"
                                        name="items[{{ $index }}][quantity]"
                                        value="{{ old("items.$index.quantity", $item->quantity) }}"
                                        min="0.0001"
                                        step="0.0001"
                                        class="block w-full rounded-lg border-gray-300 text-sm text-right"
                                        required
                                    >
                                </td>

                                <td class="px-3 py-3 text-right">
                                    ₹{{ number_format($item->unit_price, 2) }}

                                    <input
                                        type="hidden"
                                        name="items[{{ $index }}][unit_price]"
                                        value="{{ $item->unit_price }}"
                                    >
                                </td>

                                <td class="px-3 py-3 text-right">
                                    ₹{{ number_format($item->line_total, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-ui.input
                name="notes"
                label="Notes"
                :value="old('notes', $order->notes)"
            />

            <div class="flex gap-3">
                <x-ui.button type="submit" variant="primary">
                    Save Changes
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="secondary"
                    :href="route('orders.show', $order)"
                >
                    Cancel
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection