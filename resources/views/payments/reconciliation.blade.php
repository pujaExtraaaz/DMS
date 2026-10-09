@extends('layouts.dms')
@section('title', 'Reconciliation')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Payment Reconciliation" />

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-ui.stat-card label="Cash" value="₹{{ number_format($summary['cash'], 2) }}" change-type="neutral" />
        <x-ui.stat-card label="UPI" value="₹{{ number_format($summary['upi'], 2) }}" change-type="neutral" />
        <x-ui.stat-card label="Bank" value="₹{{ number_format($summary['bank'], 2) }}" change-type="neutral" />
        <x-ui.stat-card label="Total" value="₹{{ number_format($summary['total'], 2) }}" change-type="positive" />
    </div>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search customer, invoice, method, reference..."
            :reset-url="route('reconciliation.index')"
        >
            <x-slot name="filters">
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span>From:</span>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    />
                </div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span>To:</span>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    />
                </div>

                <select
                    name="method"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Method: All</option>
                    @foreach(['cash', 'bank', 'upi', 'cheque'] as $m)
                        <option value="{{ $m }}" @selected(request('method') === $m)>{{ strtoupper($m) }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="paid_at" :current-sort="$sort ?? 'paid_at'" :current-direction="$direction ?? 'desc'">Date</x-ui.sortable-th>
                        <x-ui.sortable-th column="customer" :current-sort="$sort ?? 'paid_at'" :current-direction="$direction ?? 'desc'">Customer</x-ui.sortable-th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Invoice</th>
                        <x-ui.sortable-th column="method" :current-sort="$sort ?? 'paid_at'" :current-direction="$direction ?? 'desc'">Method</x-ui.sortable-th>
                        <x-ui.sortable-th column="amount" :current-sort="$sort ?? 'paid_at'" :current-direction="$direction ?? 'desc'" align="right">Amount</x-ui.sortable-th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-sm text-slate-600 whitespace-nowrap">{{ optional($payment->paid_at)->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $payment->customer?->name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $payment->invoice?->invoice_no ?: '—' }}</td>
                            <td class="px-6 py-4 text-sm">
                                <x-ui.badge variant="gray">{{ strtoupper($payment->method) }}</x-ui.badge>
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-medium text-slate-900">₹{{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No payments found" description="Try adjusting your date range or filters." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $payments->links() }}</div>
    </x-ui.card>
</div>
@endsection
