@extends('layouts.dms')
@section('title', 'Serial Lifecycle')
@section('content')
<x-ui.page-header title="Serial Lifecycle" description="Lookup, reserve, deliver, and return tracked serials" />

<x-ui.card class="mb-6">
<form method="GET" class="flex flex-col md:flex-row gap-3 md:items-end">
<div class="flex-1"><x-ui.input name="serial_number" label="Serial Number" :value="$query" /></div>
<x-ui.button type="submit" variant="primary">Lookup</x-ui.button>
</form>
</x-ui.card>

@if($query !== '')
    @if($serial)
        <x-ui.card class="mb-6" title="Serial Detail">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div><p class="text-xs text-slate-500">Serial</p><p class="font-semibold">{{ $serial->serial_number }}</p></div>
                <div><p class="text-xs text-slate-500">Status</p><p class="font-semibold">{{ $serial->status }}</p></div>
                <div><p class="text-xs text-slate-500">Product</p><p class="font-semibold">{{ $serial->product?->name }}</p></div>
                <div><p class="text-xs text-slate-500">Warehouse</p><p class="font-semibold">{{ $serial->warehouse?->name ?? 'Unassigned' }}</p></div>
                <div><p class="text-xs text-slate-500">Inward</p><p class="font-semibold">{{ $serial->inwardItem?->inward?->inward_no ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Reservation</p><p class="font-semibold">{{ $serial->reservation_note ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Delivered</p><p class="font-semibold">{{ $serial->delivered_at?->format('d M Y H:i') ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Returned</p><p class="font-semibold">{{ $serial->returned_at?->format('d M Y H:i') ?? '—' }}</p></div>
            </div>
        </x-ui.card>
    @else
        <x-ui.empty-state title="Serial not found" description="No product serial matches this number." class="mb-6" />
    @endif
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <x-ui.card title="Reserve">
        <form method="POST" action="{{ route('inventory.serials.reserve') }}" class="space-y-3">@csrf
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Serial numbers</label>
                <textarea name="serial_numbers" rows="3" required class="block w-full rounded-lg border-gray-300 text-sm" placeholder="One per line or comma-separated">{{ $serial?->serial_number }}</textarea>
            </div>
            <x-ui.input name="owner_label" label="Reservation note" :value="old('owner_label', 'Sales hold')" required />
            <x-ui.button type="submit" variant="primary">Reserve</x-ui.button>
        </form>
    </x-ui.card>

    <x-ui.card title="Deliver / Sell">
        <form method="POST" action="{{ route('inventory.serials.deliver') }}" class="space-y-3">@csrf
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Serial numbers</label>
                <textarea name="serial_numbers" rows="3" required class="block w-full rounded-lg border-gray-300 text-sm" placeholder="One per line or comma-separated">{{ $serial?->serial_number }}</textarea>
            </div>
            <x-ui.button type="submit" variant="primary">Mark Delivered</x-ui.button>
        </form>
    </x-ui.card>

    <x-ui.card title="Return to Stock">
        <form method="POST" action="{{ route('inventory.serials.return') }}" class="space-y-3">@csrf
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Serial numbers</label>
                <textarea name="serial_numbers" rows="3" required class="block w-full rounded-lg border-gray-300 text-sm" placeholder="One per line or comma-separated">{{ $serial?->serial_number }}</textarea>
            </div>
            <x-ui.select name="warehouse_id" label="Return warehouse" placeholder="Keep current">
                <option value=""></option>
                @foreach($warehouses as $w)
                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.button type="submit" variant="secondary">Return</x-ui.button>
        </form>
    </x-ui.card>
</div>

<x-ui.card title="Recent Serials">
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Serial</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Product</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Warehouse</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Note</th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($recent as $row)
<tr>
<td class="px-3 py-2"><a class="text-indigo-600 hover:underline" href="{{ route('inventory.serials.index', ['serial_number' => $row->serial_number]) }}">{{ $row->serial_number }}</a></td>
<td class="px-3 py-2">{{ $row->product?->name }}</td>
<td class="px-3 py-2">{{ $row->status }}</td>
<td class="px-3 py-2">{{ $row->warehouse?->name ?? '—' }}</td>
<td class="px-3 py-2">{{ $row->reservation_note ?? '—' }}</td>
</tr>
@empty
<tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No serials yet.</td></tr>
@endforelse
</tbody></table>
</div>
</x-ui.card>
@endsection
