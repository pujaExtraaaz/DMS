@extends('layouts.dms')
@section('title', 'CRM Leads')
@section('content')
<x-ui.page-header title="CRM Leads">
<x-slot name="actions">
<form method="GET" class="flex gap-2">
<input type="search" name="search" value="{{ request('search') }}" placeholder="Search name/mobile/email" class="rounded-lg border-gray-300 text-sm">
<select name="status" class="rounded-lg border-gray-300 text-sm">
<option value="">All statuses</option>
@foreach(['new','contacted','qualified','unqualified','converted','lost'] as $s)
<option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst($s) }}</option>
@endforeach
</select>
<x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
</form>
</x-slot>
</x-ui.page-header>
<x-ui.card>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Lead</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Contact</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Source</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Assignee</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
<th class="px-3 py-2"></th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr>
<td class="px-3 py-2 font-medium">{{ $item->name }}</td>
<td class="px-3 py-2">{{ $item->mobile }}<br><span class="text-slate-500">{{ $item->email }}</span></td>
<td class="px-3 py-2">{{ $item->source?->name ?: 'Meta' }}</td>
<td class="px-3 py-2">{{ $item->assignee?->name ?: '—' }}</td>
<td class="px-3 py-2">{{ ucfirst($item->status) }}</td>
<td class="px-3 py-2 text-right"><x-ui.button size="sm" variant="secondary" :href="route('crm.leads.show', $item)">Open</x-ui.button></td>
</tr>
@empty
<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No leads yet. Meta webhook will populate this list.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
