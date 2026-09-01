@extends('layouts.dms')
@section('title', 'Deliveries')
@section('content')
<x-ui.page-header title="Deliveries" />

<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('deliveries.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ !request('status') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">All</a>
    <a href="{{ route('deliveries.index', array_merge(request()->query(), ['status' => 'delivered'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'delivered' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Delivered</a>
    <a href="{{ route('deliveries.index', array_merge(request()->query(), ['status' => 'pending'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'pending' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Pending</a>
    <a href="{{ route('deliveries.index', array_merge(request()->query(), ['status' => 'returned'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'returned' ? 'bg-rose-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Returned</a>
    <a href="{{ route('deliveries.index', array_merge(request()->query(), ['status' => 'out_for_delivery'])) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request('status') === 'out_for_delivery' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Out for Delivery</a>
</div>

<x-ui.card><x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Invoice</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Load Sheet</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th></tr></x-slot>
@forelse($deliveries as $delivery)<tr><td class="px-6 py-4 text-sm">{{ $delivery->customer->name }}</td><td class="px-6 py-4 text-sm">{{ $delivery->invoice->invoice_no }}</td><td class="px-6 py-4 text-sm">{{ $delivery->loadSheet->load_sheet_no }}</td><td class="px-6 py-4 text-sm"><x-ui.badge>{{ ucfirst(str_replace('_', ' ', $delivery->status)) }}</x-ui.badge></td><td class="px-6 py-4 text-right space-x-2"><x-ui.button variant="ghost" size="sm" :href="route('deliveries.show', $delivery)">View</x-ui.button><x-ui.button variant="primary" size="sm" :href="route('deliveries.edit', $delivery)">Enter Qty</x-ui.button></td></tr>@empty<tr><td colspan="5" class="px-6 py-8"><x-ui.empty-state title="No deliveries" /></td></tr>@endforelse</x-ui.table>
<div class="mt-4">{{ $deliveries->links() }}</div></x-ui.card>
@endsection
