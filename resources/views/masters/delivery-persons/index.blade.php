@extends('layouts.dms')

@section('title', 'Delivery Persons')

@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Delivery Persons" description="Manage Delivery Person master data.">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('masters.delivery-persons.create')">+ Add Delivery Person</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search delivery person name, phone..."
            :reset-url="route('masters.delivery-persons.index')"
        >
            <x-slot name="filters">
                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Status: All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <x-ui.sortable-th column="name" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                        Name
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="phone" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                        Phone
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="is_active" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                        Status
                    </x-ui.sortable-th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
            </x-slot>
            @forelse ($items as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $item->phone ?: '—' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <x-ui.badge :variant="$item->is_active ? 'success' : 'default'">{{ $item->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm space-x-2">
                        <x-ui.button variant="ghost" size="sm" :href="route('masters.delivery-persons.edit', $item)">Edit</x-ui.button>
                        <form method="POST" action="{{ route('masters.delivery-persons.destroy', $item) }}" class="inline" onsubmit="return confirm('Delete this record?')">
                            @csrf @method('DELETE')
                            <x-ui.button type="submit" variant="danger" size="sm">Delete</x-ui.button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="p-0">
                        <x-ui.empty-state
                            title="No matching delivery persons found"
                            description="Try clearing search or filters to see more results."
                            class="border-0 rounded-none py-10"
                        >
                            <x-slot name="action">
                                <a href="{{ route('masters.delivery-persons.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                    Reset Filters
                                </a>
                            </x-slot>
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection