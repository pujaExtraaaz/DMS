@extends('layouts.dms')
@section('title', 'Interest')
@section('content')
<x-ui.page-header title="Overdue Interest">
    <x-slot name="actions">
        <form method="POST" action="{{ route('interest.preview') }}">@csrf
            <x-ui.button type="submit" variant="primary">Run Preview</x-ui.button>
        </form>
    </x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
<x-ui.card title="Interest Rule" class="xl:col-span-1">
<form method="POST" action="{{ route('interest.rules.store') }}" class="space-y-3">
@csrf
<x-ui.input name="name" label="Name" required />
<x-ui.select name="customer_id" label="Customer (blank = general)">
<option value=""></option>
@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
</x-ui.select>
<x-ui.input name="annual_rate" type="number" step="0.01" label="Annual Rate %" :value="'18'" required />
<x-ui.input name="grace_days" type="number" label="Grace Days" :value="'0'" />
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-indigo-600"> Default rule</label>
<x-ui.button type="submit" variant="secondary">Save Rule</x-ui.button>
</form>
<div class="mt-4 space-y-2 text-sm">
@foreach($rules as $rule)
<div class="rounded-lg bg-slate-50 px-3 py-2">{{ $rule->name }} · {{ number_format($rule->annual_rate,2) }}% @if($rule->is_default)<span class="text-indigo-600">(default)</span>@endif</div>
@endforeach
</div>
</x-ui.card>

<x-ui.card title="Interest Ledgers" class="xl:col-span-2" id="listing-container" data-dynamic-container>
    <x-ui.table-toolbar
        :search="$search ?? request('search')"
        search-placeholder="Search customer, invoice, status..."
        :reset-url="route('interest.index')"
    >
        <x-slot name="filters">
            <select
                name="status"
                data-dynamic-filter
                class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
            >
                <option value="">Status: All</option>
                @foreach(['preview','posted','waived','reversed'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>

            <select
                name="customer_id"
                data-dynamic-filter
                class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
            >
                <option value="">Customer: All</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </x-slot>
    </x-ui.table-toolbar>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <x-ui.sortable-th column="as_of_date" :current-sort="$sort ?? 'as_of_date'" :current-direction="$direction ?? 'desc'">Date</x-ui.sortable-th>
                    <x-ui.sortable-th column="customer" :current-sort="$sort ?? 'as_of_date'" :current-direction="$direction ?? 'desc'">Customer</x-ui.sortable-th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Invoice</th>
                    <x-ui.sortable-th column="overdue_amount" :current-sort="$sort ?? 'as_of_date'" :current-direction="$direction ?? 'desc'" align="right">Overdue</x-ui.sortable-th>
                    <x-ui.sortable-th column="overdue_days" :current-sort="$sort ?? 'as_of_date'" :current-direction="$direction ?? 'desc'" align="right">Days</x-ui.sortable-th>
                    <x-ui.sortable-th column="interest_amount" :current-sort="$sort ?? 'as_of_date'" :current-direction="$direction ?? 'desc'" align="right">Interest</x-ui.sortable-th>
                    <x-ui.sortable-th column="status" :current-sort="$sort ?? 'as_of_date'" :current-direction="$direction ?? 'desc'">Status</x-ui.sortable-th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($ledgers as $row)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $row->as_of_date->format('d M Y') }}</td>
                        <td class="px-3 py-2 font-medium text-slate-900">{{ $row->customer?->name }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $row->invoice?->invoice_no ?: '—' }}</td>
                        <td class="px-3 py-2 text-right font-medium text-slate-900">₹{{ number_format($row->overdue_balance, 2) }}</td>
                        <td class="px-3 py-2 text-right text-slate-600">{{ $row->overdue_days }}</td>
                        <td class="px-3 py-2 text-right font-semibold text-rose-600">₹{{ number_format($row->interest_amount, 2) }}</td>
                        <td class="px-3 py-2">
                            <x-ui.badge variant="info">{{ ucfirst($row->status) }}</x-ui.badge>
                        </td>
                        <td class="px-3 py-2 text-right">
                            @if($row->status === 'preview')
                                <form method="POST" action="{{ route('interest.ledgers.post', $row) }}">
                                    @csrf
                                    <button class="text-emerald-600 hover:text-emerald-800 text-sm font-medium">Post</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-6 text-center text-slate-500">
                            <x-ui.empty-state title="No interest rows" description="Try adjusting filters or run preview." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $ledgers->links() }}</div>
    <p class="mt-3 text-xs text-slate-500">Formula: overdue_balance × annual_rate × days / 365</p>
</x-ui.card>
</div>
@endsection
