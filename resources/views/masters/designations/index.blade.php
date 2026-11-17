@extends('layouts.dms')

@section('title', 'Designations')

@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Designations" description="Manage designation master data for party contacts.">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('masters.designations.create')">+ Add Designation</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search Designation Name, Code..."
            :reset-url="route('masters.designations.index')"
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
                        Designation Name
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="code" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                        Code
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="party_contacts_count" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                        Assigned Contacts
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="is_active" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                        Status
                    </x-ui.sortable-th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold tracking-wider text-gray-500">Actions</th>
                </tr>
            </x-slot>
            @forelse ($items as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        {{ $item->name }}
                        @if($item->description)
                            <p class="text-xs text-gray-500 font-normal truncate max-w-xs mt-0.5">{{ $item->description }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $item->code ?: '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">
                            {{ $item->party_contacts_count }} contact{{ $item->party_contacts_count === 1 ? '' : 's' }}
                        </span>
                        @if($item->employees_count > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 ml-1">
                                {{ $item->employees_count }} employee{{ $item->employees_count === 1 ? '' : 's' }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <form method="POST" action="{{ route('masters.designations.toggle-active', $item) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="inline-flex cursor-pointer focus:outline-none" title="Click to {{ $item->is_active ? 'deactivate' : 'activate' }}">
                                <x-ui.badge :variant="$item->is_active ? 'success' : 'default'">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </x-ui.badge>
                            </button>
                        </form>
                    </td>
                    <td class="px-6 py-4 text-right text-sm space-x-2 whitespace-nowrap">
                        <x-ui.button variant="ghost" size="sm" :href="route('masters.designations.edit', $item)">Edit</x-ui.button>
                        @if($item->party_contacts_count === 0 && $item->employees_count === 0)
                            <form method="POST" action="{{ route('masters.designations.destroy', $item) }}" class="inline" onsubmit="return confirm('Delete designation \'{{ $item->name }}\'?')">
                                @csrf @method('DELETE')
                                <x-ui.button type="submit" variant="danger" size="sm">Delete</x-ui.button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('masters.designations.toggle-active', $item) }}" class="inline" onsubmit="return confirm('Designation \'{{ $item->name }}\' is currently assigned and cannot be deleted. Deactivate it instead?')">
                                @csrf @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $item->is_active ? 0 : 1 }}">
                                <x-ui.button type="submit" variant="ghost" size="sm" class="{{ $item->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }}">
                                    {{ $item->is_active ? 'Deactivate' : 'Activate' }}
                                </x-ui.button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="p-0">
                        <x-ui.empty-state
                            title="No matching designations found"
                            description="Try clearing search or filters to see more results."
                            class="border-0 rounded-none py-10"
                        >
                            <x-slot name="action">
                                <a href="{{ route('masters.designations.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
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
