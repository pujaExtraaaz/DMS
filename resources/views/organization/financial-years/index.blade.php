@extends('layouts.dms')
@section('title', 'Financial Years')
@section('content')
<x-ui.page-header title="Financial Years">
<x-slot name="actions">
<form method="GET" class="flex gap-2"><input type="search" name="search" value="{{ $search ?? '' }}" placeholder="Search..." class="rounded-lg border-gray-300 text-sm"><x-ui.button type="submit" variant="secondary">Search</x-ui.button></form>
<x-ui.button variant="primary" :href="route('organization.financial-years.create')">Add</x-ui.button>
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
<td class="px-3 py-2">{{ $item->company?->name }}</td>
<td class="px-3 py-2">{{ $item->starts_on?->format('d M Y') }} – {{ $item->ends_on?->format('d M Y') }}</td>
<td class="px-3 py-2">{{ $item->is_current ? 'Current' : ($item->is_closed ? 'Closed' : 'Open') }}</td>

<td class="px-3 py-2 text-right whitespace-nowrap space-x-1">
    <x-ui.button variant="secondary" size="sm" :href="route('organization.financial-years.edit', $item)">Edit</x-ui.button>
    @unless($item->is_current)
        <form method="POST" action="{{ route('organization.financial-years.set-current', $item) }}" class="inline">@csrf<button class="rounded-lg bg-indigo-600 px-2 py-1 text-xs font-semibold text-white hover:bg-indigo-700" onclick="return confirm('Switch current period to {{ $item->name }}?')">Set Current</button></form>
    @endunless
    @if($item->is_closed)
        <form method="POST" action="{{ route('organization.financial-years.reopen', $item) }}" class="inline">@csrf<button class="rounded-lg bg-amber-600 px-2 py-1 text-xs font-semibold text-white hover:bg-amber-700" onclick="return confirm('Re-open {{ $item->name }}?')">Re-open</button></form>
    @else
        <form method="POST" action="{{ route('organization.financial-years.close', $item) }}" class="inline">@csrf<button class="rounded-lg bg-red-600 px-2 py-1 text-xs font-semibold text-white hover:bg-red-700" onclick="return confirm('Close {{ $item->name }}? No back-dated postings will be allowed.')">Close</button></form>
    @endif
</td>
</tr>
@empty
<tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No records found.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
