@extends('layouts.dms')
@section('title', 'Employees')
@section('content')
<x-ui.page-header title="Employees">
<x-slot name="actions">
<form method="GET" class="flex gap-2"><input type="search" name="search" value="{{ request('search') }}" placeholder="Search..." class="rounded-lg border-gray-300 text-sm"><x-ui.button type="submit" variant="secondary">Search</x-ui.button></form>
<x-ui.button variant="primary" :href="route('hrms.employees.create')">Add Employee</x-ui.button>
</x-slot>
</x-ui.page-header>
<x-ui.card>
<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Code</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Name</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Dept</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Branch</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
<th class="px-3 py-2"></th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr>
<td class="px-3 py-2">{{ $item->employee_code }}</td>
<td class="px-3 py-2 font-medium">{{ $item->name }} @if($item->is_salesperson)<x-ui.badge>Sales</x-ui.badge>@endif</td>
<td class="px-3 py-2">{{ $item->department?->name }}</td>
<td class="px-3 py-2">{{ $item->branch?->name }}</td>
<td class="px-3 py-2">{{ ucfirst($item->status) }}</td>
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
@endsection
