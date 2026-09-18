@extends('layouts.dms')
@section('title', 'Schemes')
@section('content')
<x-ui.page-header title="Brand Schemes">
    <x-slot name="actions"><x-ui.button variant="primary" :href="route('schemes.create')">New Scheme</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Code</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Name</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Brand</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Window</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
<th class="px-3 py-2"></th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr>
<td class="px-3 py-2 font-medium">{{ $item->code }}</td>
<td class="px-3 py-2">{{ $item->name }}</td>
<td class="px-3 py-2">{{ $item->brand?->name ?: 'All' }}</td>
<td class="px-3 py-2">{{ $item->starts_on->format('d M Y') }} – {{ $item->ends_on->format('d M Y') }}</td>
<td class="px-3 py-2"><x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge></td>
<td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('schemes.show', $item)">View</x-ui.button></td>
</tr>
@empty
<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No schemes.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
