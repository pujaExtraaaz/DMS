@extends('layouts.dms')
@section('title', 'Quotations')
@section('content')
<x-ui.page-header title="Quotations">
    <x-slot name="actions">
        <form method="GET" class="flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search..." class="rounded-lg border-gray-300 text-sm">
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
        </form>
        <x-ui.button variant="primary" :href="route('quotations.create')">New Quotation</x-ui.button>
    </x-slot>
</x-ui.page-header>
<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Quotation</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Customer</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Total</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $item->quotation_no }}</td>
                        <td class="px-3 py-2">{{ $item->customer?->name }}</td>
                        <td class="px-3 py-2">{{ $item->quotation_date?->format('d M Y') }}</td>
                        <td class="px-3 py-2"><x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge></td>
                        <td class="px-3 py-2 text-right">₹{{ number_format($item->grand_total, 2) }}</td>
                        <td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('quotations.show', $item)">View</x-ui.button></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No quotations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
