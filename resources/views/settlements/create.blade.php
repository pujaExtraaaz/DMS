@extends('layouts.dms')
@section('title', 'Settle '.$loadSheet->load_sheet_no)
@section('content')
<x-ui.page-header title="Single-Screen Settlement" :description="$loadSheet->load_sheet_no"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('settlements.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ route('settlements.store', $loadSheet) }}" class="space-y-4">@csrf
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Invoice</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Due</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Cash</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">UPI</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Outstanding</th></tr></x-slot>
@foreach($lines as $index => $line)<tr>
<input type="hidden" name="lines[{{ $index }}][invoice_id]" value="{{ $line['invoice']->id }}">
<input type="hidden" name="lines[{{ $index }}][customer_id]" value="{{ $line['customer']->id }}">
<td class="px-6 py-4 text-sm">{{ $line['customer']->name }}</td>
<td class="px-6 py-4 text-sm">{{ $line['invoice']->invoice_no }}</td>
<td class="px-6 py-4 text-sm text-right">₹{{ number_format($line['amount'], 2) }}</td>
<td class="px-6 py-4 text-sm text-right"><input type="number" step="0.01" name="lines[{{ $index }}][cash_amount]" value="0" class="w-24 rounded border-gray-300 text-sm text-right"></td>
<td class="px-6 py-4 text-sm text-right"><input type="number" step="0.01" name="lines[{{ $index }}][upi_amount]" value="0" class="w-24 rounded border-gray-300 text-sm text-right"></td>
<td class="px-6 py-4 text-sm text-right"><input type="number" step="0.01" name="lines[{{ $index }}][outstanding_amount]" value="{{ $line['amount'] }}" class="w-24 rounded border-gray-300 text-sm text-right"></td>
</tr>@endforeach</x-ui.table>
<x-ui.button type="submit" variant="primary">Complete Settlement</x-ui.button></form></x-ui.card>
@endsection
