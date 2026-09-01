@extends('layouts.dms')
@section('title', $settlement->settlement_no)
@section('content')
<x-ui.page-header :title="$settlement->settlement_no" :description="'Settled on ' . $settlement->settled_at?->format('d M Y H:i')"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('settlements.index')">Back</x-ui.button></x-slot></x-ui.page-header>

<div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
    <x-ui.stat-card label="Cash Collected" :value="'₹' . number_format($settlement->cash_collected, 2)" accent="emerald" />
    <x-ui.stat-card label="UPI/Online" :value="'₹' . number_format($settlement->upi_collected, 2)" accent="blue" />
    <x-ui.stat-card label="Outstanding" :value="'₹' . number_format($settlement->outstanding_amount, 2)" accent="rose" />
</div>

<x-ui.card title="Settlement Details">
    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Customer</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Invoice</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Cash</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">UPI</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Outstanding</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($settlement->lines as $line)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $line->customer->name }}</td>
                        <td class="px-4 py-3">
                            @if($line->invoice)
                                <a href="{{ route('invoices.show', $line->invoice) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">{{ $line->invoice->invoice_no }}</a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-emerald-600 font-semibold">₹{{ number_format($line->cash_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right text-blue-600 font-semibold">₹{{ number_format($line->upi_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right {{ $line->outstanding_amount > 0 ? 'text-rose-600 font-semibold' : 'text-slate-700' }}">₹{{ number_format($line->outstanding_amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="bg-slate-50 font-semibold text-slate-900 border-t-2 border-slate-300">
                    <td colspan="2" class="px-4 py-4 text-right">TOTAL</td>
                    <td class="px-4 py-4 text-right text-emerald-600">₹{{ number_format($settlement->cash_collected, 2) }}</td>
                    <td class="px-4 py-4 text-right text-blue-600">₹{{ number_format($settlement->upi_collected, 2) }}</td>
                    <td class="px-4 py-4 text-right text-rose-600">₹{{ number_format($settlement->outstanding_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</x-ui.card>

<x-ui.card title="Load Sheet Info" class="mt-6">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Load Sheet</p>
            <p class="text-lg font-semibold text-slate-900">{{ $settlement->loadSheet->load_sheet_no }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Route</p>
            <p class="text-lg font-semibold text-slate-900">{{ $settlement->loadSheet->route?->name ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Settled By</p>
            <p class="text-lg font-semibold text-slate-900">{{ $settlement->settler?->name ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Settlement Status</p>
            <x-ui.badge :variant="$settlement->status === 'completed' ? 'success' : 'warning'">{{ ucfirst($settlement->status) }}</x-ui.badge>
        </div>
    </div>
</x-ui.card>
@endsection
