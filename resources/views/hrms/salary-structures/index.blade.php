@extends('layouts.dms')
@section('title', 'Payroll · Salary Structures')
@section('content')
<x-hrms.payroll-nav active="structures" />

<div id="listing-container" data-dynamic-container>
    <x-ui.card title="Salary Structures">
        <x-ui.table-toolbar
            placeholder="Search by employee or structure name..."
            :searchValue="request('search')"
            :resetUrl="route('hrms.salary-structures.index')"
        >
            <x-slot name="filters">
                <select name="employee_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Employees</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(request('employee_id') == $e->id)>{{ $e->name }} ({{ $e->employee_code }})</option>
                    @endforeach
                </select>
                <select name="is_active" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Statuses</option>
                    <option value="1" @selected(request('is_active') === '1')>Active</option>
                    <option value="0" @selected(request('is_active') === '0')>Inactive</option>
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="employee" label="Employee" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="effective_from" label="Effective" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Basic</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">HRA</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Allowances</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Deductions</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Net</th>
                        <x-ui.sortable-th column="is_active" label="Status" align="center" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <div class="font-medium text-slate-900">{{ $item->employee?->name ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $item->employee?->employee_code }}@if($item->name) · {{ $item->name }}@endif</div>
                            </td>
                            <td class="px-3 py-2 text-slate-600">
                                {{ optional($item->effective_from)->format('d M Y') ?: '—' }}
                                @if($item->effective_to)
                                    <span class="text-slate-400">→</span> {{ $item->effective_to->format('d M Y') }}
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format((float) $item->basic, 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format((float) $item->hra, 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format((float) $item->allowances, 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format((float) $item->deductions, 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums font-semibold">{{ number_format($item->netAmount(), 2) }}</td>
                            <td class="px-3 py-2 text-center">
                                <x-ui.badge :variant="$item->is_active ? 'success' : 'secondary'">{{ $item->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right space-x-2">
                                @if($item->employee_id)
                                    <x-ui.button variant="secondary" size="sm" :href="route('hrms.salary-structures.history', $item->employee_id)">History</x-ui.button>
                                @endif
                                <x-ui.button variant="secondary" size="sm" :href="route('hrms.salary-structures.edit', $item)">Edit</x-ui.button>
                                <form method="POST" action="{{ route('hrms.salary-structures.destroy', $item) }}" class="inline" onsubmit="return confirm('Delete this salary structure?')">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="danger" size="sm">Delete</x-ui.button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-3 py-6 text-center text-slate-500">No salary structures yet. Create one for an employee to define Basic, HRA, Allowances and other pay heads.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
