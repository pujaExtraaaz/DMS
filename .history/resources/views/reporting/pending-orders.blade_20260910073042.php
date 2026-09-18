@extends('layouts.dms')
@section('title', 'Pending Orders')
@section('content')
<x-ui.page-header title="Pending Orders Report" description="Ordered / reserved / delivered / pending with back-order flags" />
<x-ui.card>
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <select name="customer_id" class="rounded-lg border-gray-300 text-sm">
            <option value="">All customers</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected(request('customer_id')==$customer->id)>{{ $customer->name }}</option>
            @endforeach
        </select>
        <select name="fulfilment_mode" class="rounded-lg border-gray-300 text-sm">
            <option value="">All modes</option>
            <option value="warehouse" @selected(request('fulfilment_mode')==='warehouse')">Warehouse</option>
            <option value="van" @selected(request('fulfilment_mode')==='van')">Van</option>
        </select>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="back_order_only" value="1" @checked(request()->boolean('back_order_only')) class="rounded border-gray-300 text-indigo-600"> Back-order only</label>
        <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
    </form>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50"><tr>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Order</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Customer</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Mode</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Due</th>
                <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Ordered</th>
                <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Reserved</th>
                <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Delivered</th>
                <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Pending</th>
                <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Back Order</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $row)
                    <tr>
                        <td class="px-3 py-2 font-medium"><a class="text-indigo-600" href="{{ route('orders.show', $row['order']) }}">{{ $row['order']->order_no }}</a></td>
                        <td class="px-3 py-2">{{ $row['order']->customer?->name }}</td>
                        <td class="px-3 py-2">{{ ucfirst($row['order']->fulfilment_mode ?? 'van') }}</td>
                        <td class="px-3 py-2">{{ $row['order']->due_date?->format('d M Y') ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($row['ordered'], 4) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($row['reserved'], 4) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($row['delivered'], 4) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($row['pending'], 4) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($row['back_order'], 4) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-3 py-6 text-center text-slate-500">No pending orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
