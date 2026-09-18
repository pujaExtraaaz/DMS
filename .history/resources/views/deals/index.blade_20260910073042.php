@extends('layouts.dms')
@section('title', 'Deals')
@section('content')
<x-ui.page-header title="Deals / Margin">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('expense-types.index')">Expense Types</x-ui.button>
        <x-ui.button variant="primary" :href="route('deals.create')">New Deal</x-ui.button>
    </x-slot>
</x-ui.page-header>
<x-ui.card>
    <form method="GET" class="mb-4 flex flex-wrap gap-3 items-end">
        <x-ui.select name="customer_id" label="Customer">
            <option value="">All</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="status" label="Status">
            <option value="">All</option>
            @foreach(['draft','active','closed','cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
    </form>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50"><tr>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Reference</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Customer</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Site</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Sale</th>
                <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Net Margin</th>
                <th class="px-3 py-2"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $item->reference }}</td>
                        <td class="px-3 py-2">{{ $item->customer?->name }}</td>
                        <td class="px-3 py-2">{{ $item->site_name ?: '—' }}</td>
                        <td class="px-3 py-2"><x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge></td>
                        <td class="px-3 py-2 text-right">₹{{ number_format($item->sale_amount, 2) }}</td>
                        <td class="px-3 py-2 text-right font-semibold">₹{{ number_format($item->net_margin, 2) }}</td>
                        <td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('deals.show', $item)">View</x-ui.button></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">No deals found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
