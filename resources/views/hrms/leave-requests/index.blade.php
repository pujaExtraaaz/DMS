@extends('layouts.dms')
@section('title', 'Leave Requests')
@section('content')
<x-ui.page-header title="Leave Requests">
<x-slot name="actions">
    <div class="flex items-center gap-2">
        <x-ui.button variant="secondary" :href="route('hrms.leave-balances.index')">Leave Balances</x-ui.button>
        <x-ui.button variant="secondary" :href="route('hrms.leave-types.index')">Leave Types</x-ui.button>
        <x-ui.button variant="primary" :href="route('hrms.leave-requests.create')">+ New Request</x-ui.button>
    </div>
</x-slot>
</x-ui.page-header>

<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search leave requests by reason, employee..."
            :searchValue="request('search')"
            :resetUrl="route('hrms.leave-requests.index')"
        >
            <x-slot name="filters">
                <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Statuses</option>
                    @foreach(['pending', 'approved', 'rejected', 'cancelled'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>

                <select name="employee_id" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter>
                    <option value="">All Employees</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(request('employee_id') == $e->id)>{{ $e->name }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="employee" label="Employee" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="leave_type" label="Type" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="from_date" label="Dates" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="days" label="Days" align="center" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Reason</th>
                        <x-ui.sortable-th column="status" label="Status" align="center" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-3 py-2">
                            <a href="{{ route('hrms.employees.show', $item->employee_id) }}" class="font-medium text-indigo-600 hover:underline">{{ $item->employee?->name }}</a>
                            <div class="text-xs text-slate-400 font-mono">{{ $item->employee?->employee_code }}</div>
                        </td>
                        <td class="px-3 py-2 font-medium text-slate-800">{{ $item->leaveType?->name }}</td>
                        <td class="px-3 py-2 text-slate-600 text-xs whitespace-nowrap">
                            {{ $item->from_date->format('d M Y') }}
                            @if(!$item->from_date->equalTo($item->to_date))
                                – {{ $item->to_date->format('d M Y') }}
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center font-mono font-semibold text-slate-700">{{ number_format($item->days, 1) }}</td>
                        <td class="px-3 py-2 text-xs text-slate-500 max-w-xs truncate" title="{{ $item->reason }}">{{ $item->reason ?? '—' }}</td>
                        <td class="px-3 py-2 text-center">
                            @php
                                $badgeCls = match($item->status) {
                                    'approved' => 'bg-emerald-100 text-emerald-800',
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'cancelled' => 'bg-slate-100 text-slate-700',
                                    default => 'bg-slate-100 text-slate-800',
                                };
                            @endphp
                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $badgeCls }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-right whitespace-nowrap space-x-1">
                            @if($item->status === 'pending')
                                <form method="POST" action="{{ route('hrms.leave-requests.approve', $item) }}" class="inline">
                                    @csrf
                                    <x-ui.button type="submit" size="sm" variant="primary">Approve</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('hrms.leave-requests.reject', $item) }}" class="inline">
                                    @csrf
                                    <x-ui.button type="submit" size="sm" variant="secondary">Reject</x-ui.button>
                                </form>
                            @endif
                            @if(in_array($item->status, ['pending', 'approved']))
                                <form method="POST" action="{{ route('hrms.leave-requests.cancel', $item) }}" class="inline" onsubmit="return confirm('Cancel this leave request? {{ $item->status === 'approved' ? 'Leave balance will be restored.' : '' }}');">
                                    @csrf
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium px-2 py-1">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-slate-500">No leave requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
</div>
@endsection
