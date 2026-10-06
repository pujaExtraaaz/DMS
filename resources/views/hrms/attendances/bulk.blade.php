@extends('layouts.dms')
@section('title', 'Daily Bulk Attendance')
@section('content')
<x-ui.page-header title="Daily Bulk Attendance" description="Record attendance for all active employees at once">
<x-slot name="actions">
    <x-ui.button variant="secondary" :href="route('hrms.attendances.index')">Back to Attendance</x-ui.button>
</x-slot>
</x-ui.page-header>

<!-- Filter by Date and Department -->
<x-ui.card class="mb-6">
    <form method="GET" action="{{ route('hrms.attendances.bulk') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Attendance Date</label>
            <input type="date" name="date" value="{{ $date }}" class="rounded-lg border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Department</label>
            <select name="department_id" class="rounded-lg border-gray-300 text-sm">
                <option value="">All Departments</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" @selected($departmentId == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <x-ui.button type="submit" variant="secondary">Load Employees</x-ui.button>
    </form>
</x-ui.card>

<x-ui.card>
    <form method="POST" action="{{ route('hrms.attendances.bulk.store') }}">
        @csrf
        <input type="hidden" name="attendance_date" value="{{ $date }}">

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Employee</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Department</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Check In</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Check Out</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($employees as $index => $emp)
                        @php
                            $att = $existingAttendances->get($emp->id);
                            $curStatus = $att ? $att->status : 'present';
                            $curIn = $att && $att->check_in ? substr($att->check_in, 0, 5) : '09:30';
                            $curOut = $att && $att->check_out ? substr($att->check_out, 0, 5) : '18:30';
                            $curNotes = $att ? $att->notes : '';
                        @endphp
                        <tr>
                            <td class="px-3 py-2">
                                <input type="hidden" name="records[{{ $index }}][employee_id]" value="{{ $emp->id }}">
                                <div class="font-medium text-slate-800">{{ $emp->name }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $emp->employee_code }}</div>
                            </td>
                            <td class="px-3 py-2 text-slate-600 text-xs">{{ $emp->department?->name ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <select name="records[{{ $index }}][status]" class="rounded-md border-gray-300 text-xs py-1">
                                    @foreach(['present','absent','half_day','late','on_leave'] as $st)
                                        <option value="{{ $st }}" @selected($curStatus === $st)>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <input type="time" name="records[{{ $index }}][check_in]" value="{{ $curIn }}" class="rounded-md border-gray-300 text-xs py-1">
                            </td>
                            <td class="px-3 py-2">
                                <input type="time" name="records[{{ $index }}][check_out]" value="{{ $curOut }}" class="rounded-md border-gray-300 text-xs py-1">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" name="records[{{ $index }}][notes]" value="{{ $curNotes }}" placeholder="Optional notes" class="rounded-md border-gray-300 text-xs py-1 w-full max-w-xs">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-slate-500">No active employees found matching the filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500">Total active employees: {{ $employees->count() }}</span>
                <x-ui.button type="submit" variant="primary">Submit Bulk Attendance</x-ui.button>
            </div>
        @endif
    </form>
</x-ui.card>
@endsection

