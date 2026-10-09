@extends('layouts.dms')
@section('title', 'Financial Years')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Financial Years">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('organization.financial-years.create')">+ Add Financial Year</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search financial year name, company..."
            :reset-url="route('organization.financial-years.index')"
        />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Name</x-ui.sortable-th>
                        <x-ui.sortable-th column="company" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Company</x-ui.sortable-th>
                        <x-ui.sortable-th column="starts_on" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Date Range</x-ui.sortable-th>
                        <x-ui.sortable-th column="is_current" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Status</x-ui.sortable-th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ $item->name }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->company?->name }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->starts_on?->format('d M Y') }} – {{ $item->ends_on?->format('d M Y') }}</td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="$item->is_current ? 'emerald' : ($item->is_closed ? 'gray' : 'blue')">
                                    {{ $item->is_current ? 'Current' : ($item->is_closed ? 'Closed' : 'Open') }}
                                </x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap space-x-1">
                                <x-ui.button variant="secondary" size="sm" :href="route('organization.financial-years.edit', $item)">Edit</x-ui.button>
                                @unless($item->is_current)
                                    <form method="POST" action="{{ route('organization.financial-years.set-current', $item) }}" class="inline">
                                        @csrf
                                        <button class="rounded-lg bg-indigo-600 px-2 py-1 text-xs font-semibold text-white hover:bg-indigo-700" onclick="return confirm('Switch current period to {{ $item->name }}?')">Set Current</button>
                                    </form>
                                @endunless
                                @if($item->is_closed)
                                    <form method="POST" action="{{ route('organization.financial-years.reopen', $item) }}" class="inline">
                                        @csrf
                                        <button class="rounded-lg bg-amber-600 px-2 py-1 text-xs font-semibold text-white hover:bg-amber-700" onclick="return confirm('Re-open {{ $item->name }}?')">Re-open</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('organization.financial-years.close', $item) }}" class="inline">
                                        @csrf
                                        <button class="rounded-lg bg-red-600 px-2 py-1 text-xs font-semibold text-white hover:bg-red-700" onclick="return confirm('Close {{ $item->name }}? No back-dated postings will be allowed.')">Close</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No financial years found" description="Try adjusting your search query." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
