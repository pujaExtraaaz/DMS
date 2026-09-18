@extends('layouts.dms')
@section('title', 'Party Statement')
@section('content')
<x-ui.page-header title="Party Statement" />
<x-ui.card>
    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2">
        <select name="customer_id" class="rounded-lg border-gray-300 text-sm" required>
            <option value="">Select party</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded-lg border-gray-300 text-sm">
        <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded-lg border-gray-300 text-sm">
        <x-ui.button type="submit" variant="primary">Run Statement</x-ui.button>
    </form>

    @if($customer)
        <div class="mb-4 text-sm">
            <div class="font-semibold text-lg">{{ $customer->name }}</div>
            <div class="text-slate-500">Opening balance: ₹{{ number_format($opening, 2) }} · Credit status: {{ $customer->credit_status }} · Bounce count: {{ $customer->cheque_bounce_count }}</div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50"><tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Type</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Notes</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Debit</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Credit</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Balance</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="px-3 py-2" colspan="5">Opening</td>
                        <td class="px-3 py-2 text-right font-medium">₹{{ number_format($opening, 2) }}</td>
                    </tr>
                    @forelse($entries as $entry)
                        <tr>
                            <td class="px-3 py-2">{{ $entry->created_at?->format('d M Y') }}</td>
                            <td class="px-3 py-2">{{ str_replace('_',' ', $entry->type) }}</td>
                            <td class="px-3 py-2">{{ $entry->notes }}</td>
                            <td class="px-3 py-2 text-right">₹{{ number_format($entry->debit, 2) }}</td>
                            <td class="px-3 py-2 text-right">₹{{ number_format($entry->credit, 2) }}</td>
                            <td class="px-3 py-2 text-right">₹{{ number_format($entry->balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No ledger entries in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <p class="text-slate-500 text-sm">Select a party to view the statement.</p>
    @endif
</x-ui.card>
@endsection
