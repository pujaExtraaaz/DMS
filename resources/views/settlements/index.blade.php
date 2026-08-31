@extends('layouts.dms')
@section('title', 'Cash Settlement')
@section('content')
<x-ui.page-header title="Cash Settlement" description="Settle load sheets in a single screen." />
<x-ui.card><x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Load Sheet</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Route</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th></tr></x-slot>
@forelse($loadSheets as $ls)<tr><td class="px-6 py-4 text-sm">{{ $ls->load_sheet_no }}</td><td class="px-6 py-4 text-sm">{{ $ls->load_date->format('d M Y') }}</td><td class="px-6 py-4 text-sm">{{ $ls->route?->name ?? '—' }}</td><td class="px-6 py-4 text-sm"><x-ui.badge>{{ ucfirst($ls->status) }}</x-ui.badge></td><td class="px-6 py-4 text-right">@if(!$ls->settlement)<x-ui.button variant="primary" size="sm" :href="route('settlements.create', $ls)">Settle</x-ui.button>@else<x-ui.button variant="ghost" size="sm" :href="route('settlements.show', $ls->settlement)">View</x-ui.button>@endif</td></tr>@empty<tr><td colspan="5" class="px-6 py-8"><x-ui.empty-state title="Nothing to settle" /></td></tr>@endforelse</x-ui.table>
<div class="mt-4">{{ $loadSheets->links() }}</div></x-ui.card>
@endsection
