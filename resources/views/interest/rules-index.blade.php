@extends('layouts.dms')
@section('title', 'Interest Rules')
@section('content')
<x-ui.page-header title="Interest Rules">
<x-slot name="actions">
<div class="flex gap-2">
<x-ui.button variant="secondary" :href="route('interest.index')">Ledgers</x-ui.button>
<x-ui.button variant="primary" :href="route('interest.rules.create')">Add Rule</x-ui.button>
</div>
</x-slot>
</x-ui.page-header>
<x-ui.card>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Name</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Party</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Rate %</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Grace</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Flags</th>
<th class="px-3 py-2"></th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr>
<td class="px-3 py-2 font-medium">{{ $item->name }}</td>
<td class="px-3 py-2">{{ $item->customer?->name ?: 'Default / All' }}</td>
<td class="px-3 py-2 text-right">{{ number_format((float)$item->annual_rate, 2) }}</td>
<td class="px-3 py-2 text-right">{{ $item->grace_days }}</td>
<td class="px-3 py-2">{{ $item->is_default ? 'Default' : '' }} {{ $item->is_active ? 'Active' : 'Inactive' }}</td>
<td class="px-3 py-2 text-right"><x-ui.button size="sm" variant="secondary" :href="route('interest.rules.edit', $item)">Edit</x-ui.button></td>
</tr>
@empty
<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No rules.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
