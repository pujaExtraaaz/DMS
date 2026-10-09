@extends('layouts.dms')
@section('title', 'Payments')
@section('content')
<x-ui.page-header title="Collections"><x-slot name="actions"><x-ui.button variant="primary" :href="route('payments.create')">Record Payment</x-ui.button></x-slot></x-ui.page-header>
<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search payments by number, customer, invoice..."
            :searchValue="request('search')"
            :resetUrl="route('payments.index')"
        >
            <x-slot name="filters">
                <select name="customer_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Customers</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>

                <select name="method" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Methods</option>
                    @foreach(['cash', 'cheque', 'bank_transfer', 'upi', 'card', 'credit_note'] as $m)
                        <option value="{{ $m }}" @selected(request('method') === $m)>{{ strtoupper(str_replace('_', ' ', $m)) }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="payment_no" label="Payment" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="customer" label="Customer" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="invoice" label="Invoice" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="method" label="Method" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="amount" label="Amount" align="right" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="paid_at" label="Date" :currentSort="$sort" :currentDirection="$direction" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-mono font-medium text-slate-900">{{ $payment->payment_no }}</td>
                            <td class="px-6 py-4 text-slate-800">{{ $payment->customer->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $payment->invoice?->invoice_no ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <x-ui.badge>{{ strtoupper(str_replace('_', ' ', $payment->method)) }}</x-ui.badge>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-900">₹{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-6 py-4 text-slate-600 text-sm">{{ $payment->paid_at?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No payments found" />
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
