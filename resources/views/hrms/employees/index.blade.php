@extends('layouts.dms')
@section('title', 'Employees')
@section('content')
<x-ui.page-header title="Employees">
<x-slot name="actions">
<x-ui.button variant="primary" :href="route('hrms.employees.create')">Add Employee</x-ui.button>
</x-slot>
</x-ui.page-header>
<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search employees by name, code, email..."
            :searchValue="request('search')"
            :resetUrl="route('hrms.employees.index')"
        >
            <x-slot name="filters">
                <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Statuses</option>
                    @foreach(['active', 'probation', 'notice_period', 'resigned', 'terminated'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>

                <select name="department_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50"><tr>
        <x-ui.sortable-th column="employee_code" label="Code" :currentSort="$sort" :currentDirection="$direction" />
        <x-ui.sortable-th column="name" label="Name" :currentSort="$sort" :currentDirection="$direction" />
        <x-ui.sortable-th column="department" label="Dept" :currentSort="$sort" :currentDirection="$direction" />
        <x-ui.sortable-th column="branch" label="Branch" :currentSort="$sort" :currentDirection="$direction" />
        <x-ui.sortable-th column="status" label="Status" :currentSort="$sort" :currentDirection="$direction" />
        <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($items as $item)
        <tr class="hover:bg-slate-50">
        <td class="px-3 py-2 font-mono text-xs">{{ $item->employee_code }}</td>
        <td class="px-3 py-2 font-medium">{{ $item->name }} @if($item->is_salesperson)<x-ui.badge>Sales</x-ui.badge>@endif</td>
        <td class="px-3 py-2">{{ $item->department?->name }}</td>
        <td class="px-3 py-2">{{ $item->branch?->name }}</td>
        <td class="px-3 py-2">
            <x-ui.badge :variant="$item->status === 'active' ? 'success' : 'secondary'">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</x-ui.badge>
        </td>
        <td class="px-3 py-2 text-right whitespace-nowrap">
        <x-ui.button variant="secondary" size="sm" :href="route('hrms.employees.show', $item)">View</x-ui.button>
        <x-ui.button variant="secondary" size="sm" :href="route('hrms.employees.edit', $item)">Edit</x-ui.button>
        </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No employees found.</td></tr>
        @endforelse
        </tbody></table></div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
