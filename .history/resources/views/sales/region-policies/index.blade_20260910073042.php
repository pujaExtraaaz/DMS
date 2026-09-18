@extends('layouts.dms')
@section('title', 'Region Brand Policies')
@section('content')
<x-ui.page-header title="Region Brand Policies">
    <x-slot name="actions">
        <form method="GET" class="flex gap-2">
            <select name="area_id" class="rounded-lg border-gray-300 text-sm">
                <option value="">All regions</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" @selected(request('area_id')==$area->id)>{{ $area->name }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        </form>
        <x-ui.button variant="primary" :href="route('region-policies.create')">Add Policy</x-ui.button>
    </x-slot>
</x-ui.page-header>
<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50"><tr>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Region</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Brand</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Allowed</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Max Discount %</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                <th class="px-3 py-2"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-3 py-2">{{ $item->area?->name }}</td>
                        <td class="px-3 py-2">{{ $item->brand?->name }}</td>
                        <td class="px-3 py-2">{{ $item->is_allowed ? 'Yes' : 'No' }}</td>
                        <td class="px-3 py-2">{{ $item->max_discount_percent ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $item->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('region-policies.edit', $item)">Edit</x-ui.button></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No policies configured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
