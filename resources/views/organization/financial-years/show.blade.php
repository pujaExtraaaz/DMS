@extends('layouts.dms')
@section('title', $item->name)
@section('content')
<x-ui.page-header :title="$item->name">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('organization.financial-years.index')">Back</x-ui.button>
        <x-ui.button variant="secondary" :href="route('organization.financial-years.edit', $item)">Edit</x-ui.button>
    </x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <x-ui.card>
        <p class="text-xs text-slate-500">Company</p>
        <p class="font-medium text-slate-900">{{ $item->company?->name ?: '—' }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs text-slate-500">FY Name</p>
        <p class="font-medium text-slate-900">{{ $item->name }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs text-slate-500">Starts On</p>
        <p class="font-medium text-slate-900">{{ $item->starts_on?->format('d M Y') ?: '—' }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs text-slate-500">Ends On</p>
        <p class="font-medium text-slate-900">{{ $item->ends_on?->format('d M Y') ?: '—' }}</p>
    </x-ui.card>
</div>

<x-ui.card>
    <h3 class="text-sm font-semibold text-slate-800 mb-3">Status &amp; Controls</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
        <div>
            <span class="text-slate-500">Current FY:</span>
            <span class="text-slate-800 font-medium">{{ $item->is_current ? 'Yes' : 'No' }}</span>
        </div>
        <div>
            <span class="text-slate-500">Status:</span>
            <span class="text-slate-800 font-medium">{{ $item->is_closed ? 'Closed' : 'Open' }}</span>
        </div>
        <div>
            <span class="text-slate-500">Closed (Block Posting):</span>
            <span class="text-slate-800 font-medium">{{ $item->is_closed ? 'Yes' : 'No' }}</span>
        </div>
        <div>
            <span class="text-slate-500">Closing Date:</span>
            <span class="text-slate-800 font-medium">{{ $item->closed_at?->format('d M Y H:i') ?: '—' }}</span>
        </div>
        <div class="md:col-span-2">
            <span class="text-slate-500">Closed By:</span>
            <span class="text-slate-800 font-medium">{{ $item->closedByUser?->name ?: '—' }}</span>
        </div>
    </div>
</x-ui.card>
@endsection
