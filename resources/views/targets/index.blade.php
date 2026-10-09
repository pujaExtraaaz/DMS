@extends('layouts.dms')
@section('title', 'Targets')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Targets & Achievements">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('targets.periods.create')">+ New Period</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search period name, type..."
            :reset-url="route('targets.index')"
        >
            <x-slot name="filters">
                <select
                    name="period_type"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Type: All</option>
                    @foreach(['monthly', 'quarterly', 'annual'] as $pt)
                        <option value="{{ $pt }}" @selected(request('period_type') === $pt)>{{ ucfirst($pt) }}</option>
                    @endforeach
                </select>

                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Status: All</option>
                    @foreach(['open', 'closed'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Name</x-ui.sortable-th>
                        <x-ui.sortable-th column="period_type" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Type</x-ui.sortable-th>
                        <x-ui.sortable-th column="starts_on" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Range</x-ui.sortable-th>
                        <x-ui.sortable-th column="targets_count" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'" align="right">Targets</x-ui.sortable-th>
                        <x-ui.sortable-th column="status" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Status</x-ui.sortable-th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($periods as $period)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ $period->name }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ ucfirst($period->period_type) }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $period->starts_on->format('d M Y') }} – {{ $period->ends_on->format('d M Y') }}</td>
                            <td class="px-3 py-2 text-right font-medium text-slate-900">{{ $period->targets_count }}</td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="$period->status === 'open' ? 'emerald' : 'gray'">
                                    {{ ucfirst($period->status) }}
                                </x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <x-ui.button size="sm" variant="secondary" :href="route('targets.periods.show', $period)">Open</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No target periods found" description="Try adjusting your search or filters." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $periods->links() }}</div>
    </x-ui.card>
</div>
@endsection
