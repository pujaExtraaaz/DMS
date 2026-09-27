@extends('layouts.dms')
@section('title', 'Brands')
@section('content')
<x-ui.page-header title="Brands">
    <x-slot name="actions">
        <form method="GET" class="flex min-w-0 flex-1 gap-2 sm:max-w-md">
            <input
                type="search"
                name="search"
                value="{{ $search ?? '' }}"
                placeholder="Search..."
                class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm"
            >
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
        </form>
        <x-ui.button variant="primary" :href="route('masters.brands.create')">Add</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Code</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Details</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($items as $item)
                    <tr class="hover:bg-slate-50/75 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-slate-600 font-mono text-xs">{{ $item->code }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->detail ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $item->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $item->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <x-ui.button variant="secondary" size="sm" :href="route('masters.brands.edit', $item)">Edit</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection