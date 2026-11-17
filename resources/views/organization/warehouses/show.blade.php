@extends('layouts.dms')
@section('title', $item->name)
@section('content')
<x-ui.page-header :title="$item->name">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('organization.warehouses.index')">Back</x-ui.button>
        <x-ui.button variant="secondary" :href="route('organization.warehouses.edit', $item)">Edit</x-ui.button>
    </x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <x-ui.card>
        <p class="text-xs text-slate-500">Company</p>
        <p class="font-medium text-slate-900">{{ $item->company?->name ?: '—' }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs text-slate-500">Branch</p>
        <p class="font-medium text-slate-900">{{ $item->branch?->name ?: '—' }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs text-slate-500">Type</p>
        <p class="font-medium text-slate-900">{{ $item->warehouse_type ?: '—' }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs text-slate-500">City / State</p>
        <p class="font-medium text-slate-900">{{ collect([$item->city, $item->state])->filter()->implode(', ') ?: '—' }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs text-slate-500">Status</p>
        <p class="font-medium text-slate-900">
            {{ $item->is_active ? 'Active' : 'Inactive' }}{{ $item->is_default ? ' · Default' : '' }}
        </p>
    </x-ui.card>
</div>

<x-ui.card>
    <h3 class="text-sm font-semibold text-slate-800 mb-3">Contact & Address</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
        <div><span class="text-slate-500">Contact Person:</span> <span class="text-slate-800">{{ $item->contact_person ?: '—' }}</span></div>
        <div><span class="text-slate-500">Contact Phone:</span> <span class="text-slate-800">{{ $item->contact_phone ?: '—' }}</span></div>
        <div class="md:col-span-2"><span class="text-slate-500">Address Line 1:</span> <span class="text-slate-800">{{ $item->address ?: '—' }}</span></div>
        <div class="md:col-span-2"><span class="text-slate-500">Address Line 2:</span> <span class="text-slate-800">{{ $item->address_line_2 ?: '—' }}</span></div>
        <div><span class="text-slate-500">Country:</span> <span class="text-slate-800">{{ $item->country ?: '—' }}</span></div>
        <div><span class="text-slate-500">Pincode:</span> <span class="text-slate-800">{{ $item->pincode ?: '—' }}</span></div>
        <div class="md:col-span-2"><span class="text-slate-500">Notes:</span> <span class="text-slate-800">{{ $item->notes ?: '—' }}</span></div>
    </div>
</x-ui.card>
@endsection
