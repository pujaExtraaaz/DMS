@extends('layouts.dms')
@section('title', 'Managers')
@section('content')
<x-ui.page-header title="Managers" description="Employees who can be assigned as managers on other employee records">
    <x-slot name="actions">
        <x-ui.button variant="primary" href="{{ route('hrms.employees.create') }}">Add Employee</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card class="mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]"><x-ui.input name="search" label="Search" value="{{ request('search') }}" placeholder="Name, code, email" /></div>
        <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
    </form>
</x-ui.card>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Code</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Department</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Designation</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Reports</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $item->name }}</td>
                        <td class="px-3 py-2">{{ $item->employee_code ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $item->department?->name ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $item->designation?->name ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $item->direct_reports_count }}</td>
                        <td class="px-3 py-2 text-right">
                            <x-ui.button variant="secondary" size="sm" href="{{ route('hrms.employees.edit', $item) }}">Edit</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No employees found. Add employees first, then assign Manager on the employee form.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
