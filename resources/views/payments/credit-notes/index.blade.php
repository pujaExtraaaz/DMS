@extends('layouts.dms')
@section('title', 'Credit Notes')
@section('content')
<x-ui.page-header title="Credit Notes">
    <x-slot name="actions">
        <x-ui.button variant="primary" :href="route('credit-notes.create')">New Credit Note</x-ui.button>
    </x-slot>
</x-ui.page-header>
<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50"><tr>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Credit Note</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Customer</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Reason</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Amount</th>
                <th class="px-3 py-2"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $item->credit_note_no }}</td>
                        <td class="px-3 py-2">{{ $item->customer?->name }}</td>
                        <td class="px-3 py-2">{{ str_replace('_',' ', $item->reason) }}</td>
                        <td class="px-3 py-2"><x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge></td>
                        <td class="px-3 py-2 text-right">₹{{ number_format($item->grand_total, 2) }}</td>
                        <td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('credit-notes.show', $item)">View</x-ui.button></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No credit notes found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
