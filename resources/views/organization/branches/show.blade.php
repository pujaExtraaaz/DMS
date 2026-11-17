@extends('layouts.dms')
@section('title', $item->name)
@section('content')
<x-ui.page-header :title="$item->name">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('organization.branches.index')">Back</x-ui.button>
        <x-ui.button variant="secondary" :href="route('organization.branches.edit', $item)">Edit</x-ui.button>
    </x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <x-ui.card>
        <p class="text-xs text-slate-500">Company</p>
        <p class="font-medium text-slate-900">{{ $item->company?->name ?: '—' }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs text-slate-500">Branch Type</p>
        <p class="font-medium text-slate-900">{{ $item->branch_type ?: '—' }}</p>
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

<x-ui.card class="mb-4">
    <h3 class="text-sm font-semibold text-slate-800 mb-3">Contact & Address</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
        <div><span class="text-slate-500">Phone:</span> <span class="text-slate-800">{{ $item->phone ?: '—' }}</span></div>
        <div><span class="text-slate-500">Alternate Phone:</span> <span class="text-slate-800">{{ $item->alternate_phone ?: '—' }}</span></div>
        <div><span class="text-slate-500">Email:</span> <span class="text-slate-800">{{ $item->email ?: '—' }}</span></div>
        <div><span class="text-slate-500">Alternate Email:</span> <span class="text-slate-800">{{ $item->alternate_email ?: '—' }}</span></div>
        <div class="md:col-span-2"><span class="text-slate-500">Address Line 1:</span> <span class="text-slate-800">{{ $item->address ?: '—' }}</span></div>
        <div class="md:col-span-2"><span class="text-slate-500">Address Line 2:</span> <span class="text-slate-800">{{ $item->address_line_2 ?: '—' }}</span></div>
        <div><span class="text-slate-500">Country:</span> <span class="text-slate-800">{{ $item->country ?: '—' }}</span></div>
        <div><span class="text-slate-500">Pincode:</span> <span class="text-slate-800">{{ $item->pincode ?: '—' }}</span></div>
        <div class="md:col-span-2"><span class="text-slate-500">Notes:</span> <span class="text-slate-800">{{ $item->notes ?: '—' }}</span></div>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="text-sm font-semibold text-slate-800 mb-3">Warehouses</h3>
    @if($item->warehouses->isEmpty())
        <p class="text-sm text-slate-500">No warehouses linked to this branch.</p>
    @else
        <ul class="divide-y divide-slate-100 text-sm">
            @foreach($item->warehouses as $warehouse)
                <li class="py-2 flex items-center justify-between">
                    <span class="text-slate-800">{{ $warehouse->name }}</span>
                    <a href="{{ route('organization.warehouses.show', $warehouse) }}" class="text-xs font-semibold text-indigo-600 hover:underline">View</a>
                </li>
            @endforeach
        </ul>
    @endif
</x-ui.card>
@endsection
