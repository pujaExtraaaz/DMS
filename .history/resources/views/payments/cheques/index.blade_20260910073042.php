@extends('layouts.dms')
@section('title', 'Cheques')
@section('content')
<x-ui.page-header title="Cheques / PDC">
    <x-slot name="actions">
        <form method="GET" class="flex gap-2">
            <select name="status" class="rounded-lg border-gray-300 text-sm">
                <option value="">All statuses</option>
                @foreach(['pending','deposited','cleared','bounced','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        </form>
        <x-ui.button variant="primary" :href="route('cheques.create')">Record Cheque</x-ui.button>
    </x-slot>
</x-ui.page-header>
<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50"><tr>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Cheque</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Party</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Purpose</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Amount</th>
                <th class="px-3 py-2"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $item->cheque_no }}<div class="text-xs text-slate-500">{{ $item->bank_name }}</div></td>
                        <td class="px-3 py-2">{{ $item->customer?->name }}</td>
                        <td class="px-3 py-2">{{ strtoupper($item->purpose) }}</td>
                        <td class="px-3 py-2"><x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge></td>
                        <td class="px-3 py-2 text-right">₹{{ number_format($item->amount, 2) }}</td>
                        <td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('cheques.show', $item)">View</x-ui.button></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No cheques found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
