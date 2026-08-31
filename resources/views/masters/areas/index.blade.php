@extends('layouts.dms')

@section('title', 'Areas')

@section('content')
    <x-ui.page-header title="Areas" description="Manage Area master data.">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('masters.areas.create')">Add Area</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <form method="GET" class="mb-4 flex gap-2">
            <x-ui.input name="search" placeholder="Search..." value="{{ request('search') }}" class="max-w-xs" />
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
        </form>

        <x-ui.table>
            <x-slot name="head">
                <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Code</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Is Active</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
            </x-slot>
            @forelse ($items as $item)
                <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $item->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $item->code }}</td>
                            <td class="px-6 py-4 text-sm"><x-ui.badge :variant="$item->is_active ? 'success' : 'default'">{{ $item->is_active ? 'Active' : 'Inactive' }}</x-ui.badge></td>
                            <td class="px-6 py-4 text-right text-sm space-x-2">
                                <x-ui.button variant="ghost" size="sm" :href="route('masters.areas.edit', $item)">Edit</x-ui.button>
                                <form method="POST" action="{{ route('masters.areas.destroy', $item) }}" class="inline" onsubmit="return confirm('Delete this record?')">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="danger" size="sm">Delete</x-ui.button>
                                </form>
                            </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($items) > 0 ? 99 : 1 }}" class="px-6 py-8"><x-ui.empty-state title="No records" description="Create your first Area." /></td></tr>
            @endforelse
        </x-ui.table>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
@endsection