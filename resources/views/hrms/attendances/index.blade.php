@extends('layouts.dms')
@section('title', 'Attendance Management')
@section('content')
<x-ui.page-header title="Attendance Management">
<x-slot name="actions">
    <div class="flex items-center gap-2">
        @if($myEmployee)
            <form method="POST" action="{{ route('hrms.attendances.punch') }}" class="inline">
                @csrf
                @if(!$todayAttendance)
                    <x-ui.button type="submit" variant="primary" class="bg-emerald-600 hover:bg-emerald-700">Punch In</x-ui.button>
                @elseif(!$todayAttendance->check_out)
                    <x-ui.button type="submit" variant="primary" class="bg-amber-600 hover:bg-amber-700">Punch Out ({{ substr($todayAttendance->check_in, 0, 5) }})</x-ui.button>
                @else
                    <span class="inline-flex items-center px-2.5 py-1.5 rounded-md text-xs font-medium bg-emerald-100 text-emerald-800">
                        Punched: {{ substr($todayAttendance->check_in, 0, 5) }} - {{ substr($todayAttendance->check_out, 0, 5) }}
                    </span>
                @endif
            </form>
        @endif
        <x-ui.button variant="secondary" :href="route('hrms.attendances.bulk')">Bulk Daily Sheet</x-ui.button>
        <x-ui.button variant="primary" :href="route('hrms.attendances.create')">+ Record Attendance</x-ui.button>
    </div>
</x-slot>
</x-ui.page-header>

<!-- Monthly Metrics Bar -->
<div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6">
    <div class="p-3 bg-white rounded-lg border border-slate-200">
        <div class="text-xs font-semibold text-slate-500 uppercase">Total Records</div>
        <div class="text-xl font-bold text-slate-800 mt-1">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="p-3 bg-white rounded-lg border border-slate-200">
        <div class="text-xs font-semibold text-emerald-600 uppercase">Present</div>
        <div class="text-xl font-bold text-emerald-700 mt-1">{{ number_format($stats['present']) }}</div>
    </div>
    <div class="p-3 bg-white rounded-lg border border-slate-200">
        <div class="text-xs font-semibold text-red-600 uppercase">Absent</div>
        <div class="text-xl font-bold text-red-700 mt-1">{{ number_format($stats['absent']) }}</div>
    </div>
    <div class="p-3 bg-white rounded-lg border border-slate-200">
        <div class="text-xs font-semibold text-amber-600 uppercase">Half Day</div>
        <div class="text-xl font-bold text-amber-700 mt-1">{{ number_format($stats['half_day']) }}</div>
    </div>
    <div class="p-3 bg-white rounded-lg border border-slate-200">
        <div class="text-xs font-semibold text-orange-600 uppercase">Late</div>
        <div class="text-xl font-bold text-orange-700 mt-1">{{ number_format($stats['late']) }}</div>
    </div>
    <div class="p-3 bg-white rounded-lg border border-slate-200">
        <div class="text-xs font-semibold text-indigo-600 uppercase">Total Hours</div>
        <div class="text-xl font-bold text-indigo-700 mt-1">{{ number_format($stats['total_hours'], 1) }}</div>
    </div>
</div>

<!-- Filters Card -->
<x-ui.card class="mb-6">
    <form method="GET" action="{{ route('hrms.attendances.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Month</label>
            <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Specific Date</label>
            <input type="date" name="date" value="{{ request('date') }}" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Department</label>
            <select name="department_id" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
                <option value="">All Departments</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Employee</label>
            <select name="employee_id" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
                <option value="">All Employees</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" @selected(request('employee_id') == $e->id)>{{ $e->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
            <select name="status" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
                <option value="">All Statuses</option>
                @foreach(['present','absent','half_day','late','on_leave'] as $st)
                    <option value="{{ $st }}" @selected(request('status') == $st)>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <x-ui.button type="submit" variant="secondary" class="w-full">Filter</x-ui.button>
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium rounded-md border border-slate-300 bg-white hover:bg-slate-50 text-slate-700">Export</a>
        </div>
    </form>
</x-ui.card>

<!-- Attendance Table -->
<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Employee</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Department</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold uppercase text-slate-500">In</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold uppercase text-slate-500">Out</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold uppercase text-slate-500">Status</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Hours</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-3 py-2 font-medium text-slate-700">{{ $item->attendance_date->format('d M Y') }}</td>
                        <td class="px-3 py-2">
                            <a href="{{ route('hrms.employees.show', $item->employee_id) }}" class="font-medium text-indigo-600 hover:underline">{{ $item->employee?->name }}</a>
                            <div class="text-xs text-slate-400 font-mono">{{ $item->employee?->employee_code }}</div>
                        </td>
                        <td class="px-3 py-2 text-slate-600 text-xs">{{ $item->employee?->department?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-center font-mono">{{ $item->check_in ? substr($item->check_in, 0, 5) : '—' }}</td>
                        <td class="px-3 py-2 text-center font-mono">{{ $item->check_out ? substr($item->check_out, 0, 5) : '—' }}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $item->status === 'present' ? 'bg-emerald-100 text-emerald-800' : ($item->status === 'absent' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                                {{ ucfirst(str_replace('_',' ',$item->status)) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-right font-mono">{{ $item->hours_worked ? number_format($item->hours_worked, 2) : '—' }}</td>
                        <td class="px-3 py-2 text-right">
                            <x-ui.button size="sm" variant="secondary" :href="route('hrms.attendances.edit', $item)">Edit</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-6 text-center text-slate-500">No attendance records found matching criteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
