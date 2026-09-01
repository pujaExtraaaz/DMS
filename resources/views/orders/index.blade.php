@extends('layouts.dms')

@section('title', 'Orders')

@section('content')
    <x-ui.page-header title="Orders" description="Pending and historical orders with filters.">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('orders.create')">Book Order</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <div class="flex flex-wrap gap-2 mb-4">
        <a href="{{ route('orders.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ !request('status') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">All Orders</a>
        <a href="{{ route('orders.index', array_merge(request()->query(), ['status' => 'pending'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'pending' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Pending Orders</a>
        <a href="{{ route('orders.index', array_merge(request()->query(), ['status' => 'approved'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'approved' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Approved Orders</a>
        <a href="{{ route('orders.index', array_merge(request()->query(), ['status' => 'converted'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'converted' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Converted Invoices</a>
    </div>

    <x-ui.card>
        @php
            $sortLink = function (string $column) {
                $next = request('sort') === $column && request('direction') !== 'asc' ? 'asc' : 'desc';
                return route('orders.index', array_merge(request()->query(), ['sort' => $column, 'direction' => $next]));
            };
        @endphp

        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500"><a href="{{ $sortLink('order_no') }}">Order</a></th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500"><a href="{{ $sortLink('order_date') }}">Date</a></th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500"><a href="{{ $sortLink('status') }}">Status</a></th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500"><a href="{{ $sortLink('grand_total') }}">Total</a></th>
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
