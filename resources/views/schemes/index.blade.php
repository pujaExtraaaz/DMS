@extends('layouts.dms')
@section('title', 'Schemes')
@section('content')
<x-ui.page-header title="Brand Schemes">
    <x-slot name="actions"><x-ui.button variant="primary" :href="route('schemes.create')">New Scheme</x-ui.button></x-slot>
</x-ui.page-header>
<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search schemes by code, name..."
            :searchValue="request('search')"
            :resetUrl="route('schemes.index')"
        >
            <x-slot name="filters">
                <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Statuses</option>
                    @foreach(['draft', 'active', 'finalized', 'cancelled'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>

                <select name="brand_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Brands</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}" @selected(request('brand_id') == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50"><tr>
                    <x-ui.sortable-th column="code" label="Code" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="name" label="Name" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="brand" label="Brand" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="starts_on" label="Window" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="status" label="Status" :currentSort="$sort" :currentDirection="$direction" />
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-mono font-medium text-slate-900">{{ $item->code }}</td>
                            <td class="px-3 py-2 text-slate-900 font-medium">{{ $item->name }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->brand?->name ?: 'All' }}</td>
                            <td class="px-3 py-2 text-slate-600 text-xs">{{ $item->starts_on->format('d M Y') }} – {{ $item->ends_on->format('d M Y') }}</td>
                            <td class="px-3 py-2"><x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge></td>
                            <td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('schemes.show', $item)">View</x-ui.button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No schemes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
