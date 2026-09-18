@extends('layouts.dms')
@section('title', $period->name)
@section('content')
<x-ui.page-header :title="$period->name">
<x-slot name="actions">
<div class="flex gap-2">
<x-ui.button variant="secondary" :href="route('targets.index')">Back</x-ui.button>
@if($period->status !== 'closed')
<x-ui.button variant="primary" :href="route('targets.create', $period)">Add Target</x-ui.button>
<form method="POST" action="{{ route('targets.periods.recalculate', $period) }}">@csrf<button class="inline-flex items-center px-4 py-2 text-sm rounded-lg bg-white border border-gray-300">Recalculate</button></form>
<form method="POST" action="{{ route('targets.periods.close', $period) }}">@csrf<button class="inline-flex items-center px-4 py-2 text-sm rounded-lg bg-emerald-600 text-white">Close</button></form>
@endif
</div>
</x-slot>
</x-ui.page-header>
<x-ui.card>
<p class="text-sm text-slate-600 mb-4">{{ ucfirst($period->period_type) }} · {{ $period->starts_on->format('d M Y') }} – {{ $period->ends_on->format('d M Y') }} · {{ ucfirst($period->status) }}</p>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Party</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Salesperson</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Brand</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Target</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Achieved</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">%</th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($period->targets as $t)
<tr>
<td class="px-3 py-2">{{ $t->customer?->name ?: '—' }}</td>
<td class="px-3 py-2">{{ $t->salesperson?->name ?: '—' }}</td>
<td class="px-3 py-2">{{ $t->brand?->name ?: '—' }}</td>
<td class="px-3 py-2 text-right">{{ number_format((float)$t->amount, 2) }}</td>
<td class="px-3 py-2 text-right">{{ number_format((float)($t->achievement?->achieved_amount ?? 0), 2) }}</td>
<td class="px-3 py-2 text-right">{{ number_format((float)($t->achievement?->achievement_percent ?? 0), 1) }}%</td>
</tr>
@empty
<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No targets assigned.</td></tr>
@endforelse
</tbody></table></div>
</x-ui.card>
@endsection
