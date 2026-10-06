@extends('layouts.dms')
@section('title', $item->exists ? 'Correct Attendance' : 'Record Attendance')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Correct Attendance' : 'Record Attendance'">
<x-slot name="actions">
    <x-ui.button variant="secondary" :href="route('hrms.attendances.index')">Back to Attendance</x-ui.button>
</x-slot>
</x-ui.page-header>

<x-ui.card>
<form method="POST" action="{{ $item->exists ? route('hrms.attendances.update', $item) : route('hrms.attendances.store') }}" class="space-y-4 max-w-xl">
    @csrf
    @if($item->exists)
        @method('PUT')
    @endif

    <x-ui.select name="employee_id" label="Employee" required :disabled="$item->exists">
        <option value="">Select Employee</option>
        @foreach($employees as $e)
            <option value="{{ $e->id }}" @selected(old('employee_id', $item->employee_id) == $e->id)>{{ $e->name }} ({{ $e->employee_code }})</option>
        @endforeach
    </x-ui.select>
    @if($item->exists)
        <input type="hidden" name="employee_id" value="{{ $item->employee_id }}">
    @endif

    <x-ui.input name="attendance_date" label="Attendance Date" type="date" :value="old('attendance_date', optional($item->attendance_date)->format('Y-m-d') ?? now()->toDateString())" required />

    <div class="grid grid-cols-2 gap-4">
        <x-ui.input name="check_in" label="Check In Time" type="time" :value="old('check_in', $item->check_in ? substr($item->check_in, 0, 5) : '')" />
        <x-ui.input name="check_out" label="Check Out Time" type="time" :value="old('check_out', $item->check_out ? substr($item->check_out, 0, 5) : '')" />
    </div>

    <div class="grid grid-cols-2 gap-4">
        <x-ui.select name="status" label="Status" required>
            @foreach(['present','absent','late','half_day','on_leave'] as $s)
                <option value="{{ $s }}" @selected(old('status', $item->status ?? 'present') == $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input name="hours_worked" label="Hours Worked" type="number" step="0.01" :value="old('hours_worked', $item->hours_worked)" placeholder="Leave empty to auto-calculate" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes / Reason for Correction</label>
        <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $item->notes) }}</textarea>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-ui.button type="submit" variant="primary">{{ $item->exists ? 'Save Correction' : 'Save Attendance' }}</x-ui.button>
        <x-ui.button variant="secondary" :href="route('hrms.attendances.index')">Cancel</x-ui.button>
    </div>
</form>
</x-ui.card>
@endsection
