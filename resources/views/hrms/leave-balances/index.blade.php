@extends('layouts.dms')
@section('title', 'Employee Leave Balances')
@section('content')
<x-ui.page-header title="Leave Balances" description="Yearly opening, utilized, and available balances per employee">
<x-slot name="actions">
    <x-ui.button variant="secondary" :href="route('hrms.leave-requests.index')">Leave Requests</x-ui.button>
    <x-ui.button variant="primary" :href="route('hrms.leave-balances.create')">+ Allocate / Adjust Balance</x-ui.button>
</x-slot>
</x-ui.page-header>

<x-ui.card class="mb-6">
    <form method="GET" action="{{ route('hrms.leave-balances.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Year</label>
            <select name="year" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
                @foreach(range(now()->year - 2, now()->year + 2) as $y)
                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Employee</label>
            <select name="employee_id" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
                <option value="">All Employees</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" @selected(request('employee_id') == $e->id)>{{ $e->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Leave Type</label>
            <select name="leave_type_id" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
                <option value="">All Leave Types</option>
                @foreach($leaveTypes as $lt)
                    <option value="{{ $lt->id }}" @selected(request('leave_type_id') == $lt->id)>{{ $lt->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-ui.button type="submit" variant="secondary" class="w-full">Filter</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Employee</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Department</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Leave Type</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold uppercase text-slate-500">Year</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Opening</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Used</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Closing / Available</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-3 py-2">
                            <a href="{{ route('hrms.employees.show', $item->employee_id) }}" class="font-medium text-indigo-600 hover:underline">{{ $item->employee?->name }}</a>
                            <div class="text-xs text-slate-400 font-mono">{{ $item->employee?->employee_code }}</div>
                        </td>
                        <td class="px-3 py-2 text-slate-600 text-xs">{{ $item->employee?->department?->name ?? '—' }}</td>
                        <td class="px-3 py-2 font-medium text-slate-800">{{ $item->leaveType?->name }}</td>
                        <td class="px-3 py-2 text-center text-slate-600">{{ $item->year }}</td>
                        <td class="px-3 py-2 text-right font-mono">{{ number_format($item->opening_balance, 1) }}</td>
                        <td class="px-3 py-2 text-right font-mono text-amber-600">{{ number_format($item->used_balance, 1) }}</td>
                        <td class="px-3 py-2 text-right font-mono font-semibold {{ $item->closing_balance < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                            {{ number_format($item->closing_balance, 1) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-slate-500">No leave balances allocated for year {{ $year }}.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection

