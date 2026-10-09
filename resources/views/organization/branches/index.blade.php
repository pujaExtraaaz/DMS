@extends('layouts.dms')
@section('title', 'Branches')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Branches">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('organization.branches.create')">+ Add Branch</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search branch name, code, city..."
            :reset-url="route('organization.branches.index')"
        >
            <x-slot name="filters">
                <select
                    name="company_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Company: All</option>
                    @foreach($companies ?? [] as $comp)
                        <option value="{{ $comp->id }}" @selected(request('company_id') == $comp->id)>{{ $comp->name }}</option>
                    @endforeach
                </select>

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

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Branch Name
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="code" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Code
                        </x-ui.sortable-th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Company</th>
                        <x-ui.sortable-th column="is_active" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Status
                        </x-ui.sortable-th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $item->name }}</td>
                            <td class="px-4 py-3 text-slate-600 font-mono text-xs">{{ $item->code }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->company?->name ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $item->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <x-ui.button variant="secondary" size="sm" :href="route('organization.branches.edit', $item)">Edit</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-0">
                                <x-ui.empty-state
                                    title="No matching branches found"
                                    description="Try clearing search or filters to see more results."
                                    class="border-0 rounded-none py-10"
                                >
                                    <x-slot name="action">
                                        <a href="{{ route('organization.branches.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                            Reset Filters
                                        </a>
                                    </x-slot>
                                </x-ui.empty-state>
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
