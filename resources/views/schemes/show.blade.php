@extends('layouts.dms')
@section('title', 'Scheme '.$item->code)
@section('content')
<x-ui.page-header :title="$item->name">
    <x-slot name="actions">
        @if($item->status === 'draft')
        <form method="POST" action="{{ route('schemes.activate', $item) }}">@csrf<x-ui.button type="submit" variant="primary">Activate</x-ui.button></form>
        @endif
        @if($item->status === 'active')
        <form method="POST" action="{{ route('schemes.finalize', $item) }}">@csrf<x-ui.button type="submit" variant="secondary">Finalize Period</x-ui.button></form>
        @endif
        <x-ui.button variant="secondary" :href="route('schemes.index')">Back</x-ui.button>
    </x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
<x-ui.card title="Details">
<dl class="space-y-2 text-sm">
<div><span class="text-slate-500">Code:</span> {{ $item->code }}</div>
<div><span class="text-slate-500">Brand:</span> {{ $item->brand?->name ?: 'All' }}</div>
<div><span class="text-slate-500">Window:</span> {{ $item->starts_on->format('d M Y') }} – {{ $item->ends_on->format('d M Y') }}</div>
<div><span class="text-slate-500">Basis:</span> {{ ucfirst($item->basis) }}</div>
<div><span class="text-slate-500">Status:</span> <x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge></div>
</dl>
</x-ui.card>
<x-ui.card title="Slabs" class="lg:col-span-2">
<table class="min-w-full text-sm"><thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left">From</th><th class="px-3 py-2 text-left">To</th><th class="px-3 py-2 text-right">% </th><th class="px-3 py-2 text-right">Amount</th>
</tr></thead><tbody>
@foreach($item->slabs as $slab)
<tr class="border-t"><td class="px-3 py-2">{{ number_format($slab->from_value,2) }}</td><td class="px-3 py-2">{{ $slab->to_value !== null ? number_format($slab->to_value,2) : '∞' }}</td><td class="px-3 py-2 text-right">{{ number_format($slab->benefit_percent,2) }}%</td><td class="px-3 py-2 text-right">₹{{ number_format($slab->benefit_amount,2) }}</td></tr>
@endforeach
</tbody></table>
</x-ui.card>
</div>

@if($item->status === 'active')
<x-ui.card title="Provisional on Invoice" class="mb-6">
<form method="POST" action="{{ route('schemes.provisional', $item) }}" class="flex flex-col md:flex-row gap-3 items-end">
@csrf
<x-ui.input name="invoice_id" label="Invoice ID" required />
<x-ui.button type="submit" variant="secondary">Calculate Provisional</x-ui.button>
</form>
<p class="mt-2 text-xs text-slate-500">Provisional rows snapshot current slabs; later scheme edits do not rewrite history. Finalize at period close.</p>
</x-ui.card>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
<x-ui.card title="Achievements">
<table class="min-w-full text-sm"><thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left">Customer</th><th class="px-3 py-2 text-right">Qualified</th><th class="px-3 py-2 text-right">Benefit</th><th class="px-3 py-2 text-left">Status</th>
</tr></thead><tbody>
@forelse($item->achievements as $row)
<tr class="border-t"><td class="px-3 py-2">{{ $row->customer?->name }}</td><td class="px-3 py-2 text-right">{{ number_format($row->qualified_value,2) }}</td><td class="px-3 py-2 text-right">₹{{ number_format($row->benefit_amount,2) }}</td><td class="px-3 py-2">{{ $row->status }}</td></tr>
@empty
<tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No achievements.</td></tr>
@endforelse
</tbody></table>
</x-ui.card>
<x-ui.card title="Settlements">
<table class="min-w-full text-sm"><thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left">Customer</th><th class="px-3 py-2 text-right">Amount</th><th class="px-3 py-2 text-left">Status</th>
</tr></thead><tbody>
@forelse($item->settlements as $row)
<tr class="border-t"><td class="px-3 py-2">{{ $row->customer?->name }}</td><td class="px-3 py-2 text-right">₹{{ number_format($row->amount,2) }}</td><td class="px-3 py-2">{{ $row->status }}</td></tr>
@empty
<tr><td colspan="3" class="px-3 py-6 text-center text-slate-500">No settlements.</td></tr>
@endforelse
</tbody></table>
</x-ui.card>
</div>
@endsection
