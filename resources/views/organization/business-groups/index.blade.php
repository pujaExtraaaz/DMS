@extends('layouts.dms')
@section('title', 'Sister Concern Groups')
@section('content')
<x-ui.page-header title="Sister Concern Groups">
<x-slot name="actions">
<form method="GET" class="flex gap-2"><input type="search" name="search" value="{{ $search ?? '' }}" placeholder="Search..." class="rounded-lg border-gray-300 text-sm"><x-ui.button type="submit" variant="secondary">Search</x-ui.button></form>
<x-ui.button variant="primary" :href="route('organization.business-groups.create')">Add</x-ui.button>
</x-slot>
</x-ui.page-header>
<x-ui.card>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Name</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Code</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Details</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
<th class="px-3 py-2"></th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr>

<td class="px-3 py-2 font-medium">{{ $item->name }}</td>
<td class="px-3 py-2">{{ $item->code }}</td>
<td class="px-3 py-2">{{ $item->links_count }} companies</td>
<td class="px-3 py-2">{{ $item->is_active ? 'Active' : 'Inactive' }}</td>

<td class="px-3 py-2 text-right whitespace-nowrap"><x-ui.button variant="secondary" size="sm" :href="route('organization.business-groups.edit', $item)">Edit</x-ui.button></td>
</tr>
@empty
<tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No records found.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
