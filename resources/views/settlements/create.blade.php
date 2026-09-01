@extends('layouts.dms')
@section('title', 'Settle '.$loadSheet->load_sheet_no)
@section('content')
<x-ui.page-header title="Load Sheet Settlement" :description="$loadSheet->load_sheet_no" />

<div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-4">
    <x-ui.stat-card label="Route" :value="$loadSheet->route?->name ?? '—'" accent="indigo" />
    <x-ui.stat-card label="Vehicle" :value="$loadSheet->vehicle?->registration_no ?? '—'" accent="violet" />
    <x-ui.stat-card label="Load Date" :value="$loadSheet->load_date->format('d M Y')" accent="sky" />
    <x-ui.stat-card label="Status" :value="ucfirst($loadSheet->status)" accent="emerald" />
</div>

<x-ui.card>
    <form method="POST" action="{{ route('settlements.store', $loadSheet) }}" class="space-y-4">
        @csrf
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Customer</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Invoice</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Loaded</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Delivered</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Short</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Returned</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Due Amount</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Cash</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">UPI</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Outstanding</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php $totalLoaded = 0; $totalDelivered = 0; $totalShort = 0; $totalReturned = 0; $totalCash = 0; $totalUpi = 0; $totalOutstanding = 0; @endphp
                    @foreach($lines as $index => $line)
                        @php
                            $delivery = $loadSheet->deliveries->where('invoice_id', $line['invoice']->id)->first();
                            $loadedQty = $delivery ? $delivery->items->sum('loaded_qty') : 0;
                            $deliveredQty = $delivery ? $delivery->items->sum('delivered_qty') : 0;
                            $shortQty = $delivery ? $delivery->items->sum('short_qty') : 0;
                            $returnedQty = $delivery ? $delivery->items->sum('returned_qty') : 0;
                            $deliveryStatus = $delivery?->status ?? 'pending';
                            
                            $totalLoaded += $loadedQty;
                            $totalDelivered += $deliveredQty;
                            $totalShort += $shortQty;
                            $totalReturned += $returnedQty;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <input type="hidden" name="lines[{{ $index }}][invoice_id]" value="{{ $line['invoice']->id }}">
                            <input type="hidden" name="lines[{{ $index }}][customer_id]" value="{{ $line['customer']->id }}">
                            
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $line['customer']->name }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('invoices.show', $line['invoice']) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">{{ $line['invoice']->invoice_no }}</a>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <x-ui.badge :variant="$deliveryStatus === 'delivered' ? 'success' : ($deliveryStatus === 'partial' ? 'warning' : 'primary')">
                                    {{ ucfirst(str_replace('_', ' ', $deliveryStatus)) }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format($loadedQty, 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format($deliveredQty, 2) }}</td>
                            <td class="px-4 py-3 text-right {{ $shortQty > 0 ? 'text-amber-600 font-semibold' : 'text-slate-700' }}">{{ number_format($shortQty, 2) }}</td>
                            <td class="px-4 py-3 text-right {{ $returnedQty > 0 ? 'text-blue-600 font-semibold' : 'text-slate-700' }}">{{ number_format($returnedQty, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-900">₹{{ number_format($line['amount'], 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <input type="number" step="0.01" name="lines[{{ $index }}][cash_amount]" value="0" placeholder="0.00" class="w-20 px-2 py-1 rounded border border-slate-300 text-right text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent" min="0" max="{{ $line['amount'] }}">
                            </td>
                            <td class="px-4 py-3 text-right">
                                <input type="number" step="0.01" name="lines[{{ $index }}][upi_amount]" value="0" placeholder="0.00" class="w-20 px-2 py-1 rounded border border-slate-300 text-right text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent" min="0" max="{{ $line['amount'] }}">
                            </td>
                            <td class="px-4 py-3 text-right">
                                <input type="number" step="0.01" name="lines[{{ $index }}][outstanding_amount]" value="{{ $line['amount'] }}" placeholder="0.00" class="w-20 px-2 py-1 rounded border border-slate-300 text-right text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent" min="0" max="{{ $line['amount'] }}">
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-slate-50 font-semibold text-slate-900 border-t-2 border-slate-300">
                        <td colspan="3" class="px-4 py-4 text-right">TOTALS</td>
                        <td class="px-4 py-4 text-right">{{ number_format($totalLoaded, 2) }}</td>
                        <td class="px-4 py-4 text-right">{{ number_format($totalDelivered, 2) }}</td>
                        <td class="px-4 py-4 text-right {{ $totalShort > 0 ? 'text-amber-600' : '' }}">{{ number_format($totalShort, 2) }}</td>
                        <td class="px-4 py-4 text-right {{ $totalReturned > 0 ? 'text-blue-600' : '' }}">{{ number_format($totalReturned, 2) }}</td>
                        <td class="px-4 py-4 text-right" id="total-due">₹0.00</td>
                        <td class="px-4 py-4 text-right" id="total-cash">₹0.00</td>
                        <td class="px-4 py-4 text-right" id="total-upi">₹0.00</td>
                        <td class="px-4 py-4 text-right" id="total-outstanding">₹0.00</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex gap-3 justify-end border-t pt-4 mt-4">
            <x-ui.button variant="secondary" :href="route('settlements.index')">Cancel</x-ui.button>
            <x-ui.button type="submit" variant="primary">Complete Settlement</x-ui.button>
        </div>
    </form>
</x-ui.card>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const calculateTotals = () => {
            let totalCash = 0, totalUpi = 0, totalOutstanding = 0;
            const inputs = document.querySelectorAll('input[name*="cash_amount"], input[name*="upi_amount"], input[name*="outstanding_amount"]');
            
            inputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                if (input.name.includes('cash_amount')) totalCash += value;
                else if (input.name.includes('upi_amount')) totalUpi += value;
                else if (input.name.includes('outstanding_amount')) totalOutstanding += value;
            });

            document.getElementById('total-cash').textContent = '₹' + totalCash.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('total-upi').textContent = '₹' + totalUpi.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('total-outstanding').textContent = '₹' + totalOutstanding.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        };

        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('change', calculateTotals);
            input.addEventListener('input', calculateTotals);
        });

        calculateTotals();
    });
</script>
@endsection
