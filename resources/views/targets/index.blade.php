@extends('layouts.dms')
@section('title', 'Targets')
@section('content')
<x-ui.page-header title="Targets & Achievements">
<x-slot name="actions"><x-ui.button variant="primary" :href="route('targets.periods.create')">New Period</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Name</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Type</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Range</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Targets</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
<th class="px-3 py-2"></th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($periods as $period)
<tr>
<td class="px-3 py-2 font-medium">{{ $period->name }}</td>
<td class="px-3 py-2">{{ ucfirst($period->period_type) }}</td>
<td class="px-3 py-2">{{ $period->starts_on->format('d M Y') }} – {{ $period->ends_on->format('d M Y') }}</td>
<td class="px-3 py-2">{{ $period->targets_count }}</td>
<td class="px-3 py-2">{{ ucfirst($period->status) }}</td>
<td class="px-3 py-2 text-right"><x-ui.button size="sm" variant="secondary" :href="route('targets.periods.show', $period)">Open</x-ui.button></td>
</tr>
@empty
<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No periods.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $periods->links() }}</div>
</x-ui.card>
@endsection
