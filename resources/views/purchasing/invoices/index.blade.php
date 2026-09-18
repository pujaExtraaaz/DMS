@extends('layouts.dms')
@section('title', 'Purchase Invoices')
@section('content')
<x-ui.page-header title="Purchase Invoices">
    <x-slot name="actions">
        <form method="GET" class="flex gap-2"><input type="search" name="search" value="{{ request('search') }}" placeholder="Invoice no..." class="rounded-lg border-gray-300 text-sm"><x-ui.button type="submit" variant="secondary">Search</x-ui.button></form>
        <x-ui.button variant="primary" :href="route('purchasing.invoices.create')">+ New PI</x-ui.button>
    </x-slot>
</x-ui.page-header>
<x-ui.card>
<table class="min-w-full text-sm divide-y">
    <thead class="bg-slate-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Invoice</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Supplier Inv</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Supplier</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">Total</th>
            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
        </tr>
    </thead>

    <tbody class="divide-y">
        @forelse($invoices as $invoice)
            <tr>
                <td class="px-6 py-4 text-sm font-medium">
                    {{ $invoice->invoice_no }}
                </td>

                <td class="px-6 py-4 text-sm">
                    {{ $invoice->supplier_invoice_no ?? '—' }}
                </td>

                <td class="px-6 py-4 text-sm">
                    {{ $invoice->supplier?->name ?? '—' }}
                </td>

                <td class="px-6 py-4 text-sm">
                    {{ $invoice->invoice_date?->format('d M Y') }}
                </td>

                <td class="px-6 py-4 text-sm text-right">
                    ₹{{ number_format($invoice->grand_total, 2) }}
                </td>

                <td class="px-6 py-4 text-right">
                    <x-ui.button
                        size="sm"
                        variant="ghost"
                        :href="route('purchasing.invoices.show', $invoice)"
                    >
                        View
                    </x-ui.button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                    No purchase invoices.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
<div class="mt-4">{{ $invoices->links() }}</div>
</x-ui.card>
@endsection
