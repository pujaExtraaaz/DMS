@extends('layouts.dms')
@section('title', 'Mark Attendance')
@section('content')
<x-ui.page-header title="Mark Attendance"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('hrms.attendances.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ route('hrms.attendances.store') }}" class="space-y-4 max-w-xl">@csrf
<x-ui.select name="employee_id" label="Employee" required>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="attendance_date" label="Date" type="date" :value="old('attendance_date', optional($item->attendance_date)->format('Y-m-d') ?? now()->toDateString())" required />
<x-ui.input name="check_in" label="Check In" type="time" :value="old('check_in')" />
<x-ui.input name="check_out" label="Check Out" type="time" :value="old('check_out')" />
<x-ui.select name="status" label="Status">@foreach(['present','absent','late','half_day','on_leave'] as $s)<option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</x-ui.select>
<x-ui.input name="hours_worked" label="Hours" type="number" step="0.01" :value="old('hours_worked')" />
<div><label class="block text-sm font-medium text-gray-700 mb-1">Notes</label><textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea></div>
<x-ui.button type="submit" variant="primary">Save</x-ui.button></form></x-ui.card>
@endsection
