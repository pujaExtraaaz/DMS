@extends('layouts.dms')
@section('title', 'Deal '.$item->reference)
@section('content')
<x-ui.page-header :title="'Deal '.$item->reference" :description="$item->customer?->name">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('deals.index')">Back</x-ui.button>
        <form method="POST" action="{{ route('deals.refresh-margin', $item) }}">@csrf
            <x-ui.button type="submit" variant="secondary">Refresh Margin</x-ui.button>
        </form>
    </x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <x-ui.card title="Deal">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd>{{ ucfirst($item->status) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Site</dt><dd>{{ $item->site_name ?: '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Invoice</dt><dd>{{ $item->invoice?->invoice_no ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Order</dt><dd>{{ $item->order?->order_no ?? ($item->order_id ? '#'.$item->order_id : '—') }}</dd></div>
        </dl>
    </x-ui.card>
    <x-ui.card title="Net Margin" class="lg:col-span-2">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
            <div><p class="text-gray-500">Sale</p><p class="font-semibold">₹{{ number_format($margin['sale'], 2) }}</p></div>
            <div><p class="text-gray-500">Landed</p><p class="font-semibold">₹{{ number_format($margin['landed'], 2) }}</p></div>
            <div><p class="text-gray-500">Discounts</p><p class="font-semibold">₹{{ number_format($margin['discounts'], 2) }}</p></div>
            <div><p class="text-gray-500">Deal Costs</p><p class="font-semibold">₹{{ number_format($margin['deal_costs'], 2) }}</p></div>
            <div><p class="text-gray-500">Net Margin</p><p class="text-lg font-bold text-indigo-700">₹{{ number_format($margin['net_margin'], 2) }}</p></div>
        </div>
        <p class="mt-3 text-xs text-slate-500">Formula: sale − landed/purchase − discounts − deal costs</p>
    </x-ui.card>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <x-ui.card title="Expenses" class="lg:col-span-2">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50"><tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Type</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Party</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Amount</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                    <th class="px-3 py-2"></th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($item->expenses as $expense)
                        <tr>
                            <td class="px-3 py-2">{{ $expense->expenseType?->name }}<div class="text-xs text-slate-400">{{ $expense->expenseType?->accounting_treatment }}</div></td>
                            <td class="px-3 py-2">{{ $expense->party_name ?: '—' }}</td>
                            <td class="px-3 py-2 text-right">₹{{ number_format($expense->amount, 2) }}</td>
                            <td class="px-3 py-2"><x-ui.badge variant="info">{{ str_replace('_',' ', $expense->status) }}</x-ui.badge></td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                @if(in_array($expense->status, ['draft','pending_approval'], true))
                                    <form method="POST" action="{{ route('deals.expenses.approve', $expense) }}" class="inline">@csrf
                                        <x-ui.button type="submit" variant="secondary" size="sm">Approve</x-ui.button>
                                    </form>
                                    <form method="POST" action="{{ route('deals.expenses.reject', $expense) }}" class="inline">@csrf
                                        <x-ui.button type="submit" variant="ghost" size="sm">Reject</x-ui.button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No expenses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
    <x-ui.card title="Add Expense">
        <form method="POST" action="{{ route('deals.expenses.store', $item) }}" class="space-y-3">
            @csrf
            <x-ui.select name="expense_type_id" label="Type" required>
                <option value="">Select</option>
                @foreach($expenseTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.input name="amount" label="Amount" type="number" step="0.01" required />
            <x-ui.input name="party_name" label="Party Name" />
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm"></textarea>
            </div>
            <x-ui.button type="submit" variant="primary">Submit for Approval</x-ui.button>
        </form>
    </x-ui.card>
</div>
@endsection
