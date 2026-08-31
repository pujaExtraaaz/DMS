@extends('layouts.dms')

@section('title', 'Orders')

@section('content')
    <x-ui.page-header title="Orders" description="Pending and historical orders with filters.">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('orders.create')">Book Order</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-4">
            <x-ui.select name="customer_id" label="Customer" placeholder="All">
                <option value=""></option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="salesperson_id" label="Salesperson" placeholder="All">
                <option value=""></option>
                @foreach($salespersons as $s)
                    <option value="{{ $s->id }}" @selected(request('salesperson_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="area_id" label="Area" placeholder="All">
                <option value=""></option>
                @foreach($areas as $a)
                    <option value="{{ $a->id }}" @selected(request('area_id') == $a->id)>{{ $a->name }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="status" label="Status" placeholder="All">
                <option value=""></option>
                @foreach($statuses as $st)
                    <option value="{{ $st }}" @selected(request('status') == $st)>{{ ucfirst($st) }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.input name="date_from" label="From" type="date" :value="request('date_from')" />
            <x-ui.input name="date_to" label="To" type="date" :value="request('date_to')" />
            <div class="md:col-span-6"><x-ui.button type="submit" variant="secondary">Filter</x-ui.button></div>
        </form>

        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Order</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
                </tr>
            </x-slot>
            @forelse($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium">{{ $order->order_no }}</td>
                    <td class="px-6 py-4 text-sm">{{ $order->customer->name }}</td>
                    <td class="px-6 py-4 text-sm">{{ $order->order_date->format('d M Y') }}</td>
                    <td class="px-6 py-4 text-sm"><x-ui.badge variant="info">{{ ucfirst($order->status) }}</x-ui.badge></td>
                    <td class="px-6 py-4 text-sm text-right">₹{{ number_format($order->grand_total, 2) }}</td>
                    <td class="px-6 py-4 text-right"><x-ui.button variant="ghost" size="sm" :href="route('orders.show', $order)">View</x-ui.button></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-8"><x-ui.empty-state title="No orders" description="Book your first order." /></td></tr>
            @endforelse
        </x-ui.table>
        <div class="mt-4">{{ $orders->links() }}</div>
    </x-ui.card>
@endsection
