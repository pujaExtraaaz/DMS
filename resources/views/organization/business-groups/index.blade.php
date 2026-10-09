@extends('layouts.dms')
@section('title', 'Sister Concern Groups')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Sister Concern Groups">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('organization.business-groups.create')">+ Add Group</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search name, code..."
            :reset-url="route('organization.business-groups.index')"
        />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? 'name'" :current-direction="$direction ?? 'asc'">Name</x-ui.sortable-th>
                        <x-ui.sortable-th column="code" :current-sort="$sort ?? 'name'" :current-direction="$direction ?? 'asc'">Code</x-ui.sortable-th>
                        <x-ui.sortable-th column="links_count" :current-sort="$sort ?? 'name'" :current-direction="$direction ?? 'asc'">Companies</x-ui.sortable-th>
                        <x-ui.sortable-th column="is_active" :current-sort="$sort ?? 'name'" :current-direction="$direction ?? 'asc'">Status</x-ui.sortable-th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ $item->name }}</td>
                            <td class="px-3 py-2 text-slate-600 font-mono text-xs">{{ $item->code }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->links_count }} companies</td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="$item->is_active ? 'emerald' : 'gray'">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <x-ui.button variant="secondary" size="sm" :href="route('organization.business-groups.edit', $item)">Edit</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No business groups found" description="Try adjusting your search query." />
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
