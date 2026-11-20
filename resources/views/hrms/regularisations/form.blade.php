@extends('layouts.dms')
@section('title', 'New Regularisation')
@section('content')
<x-ui.card title="Attendance Regularisation / Missing Punch">
<form method="POST" action="{{ route('hrms.regularisations.store') }}" class="max-w-xl space-y-4">
@csrf
<x-ui.select name="employee_id" label="Employee" required><option value="">Select</option>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="attendance_date" label="Date" type="date" required />
<x-ui.select name="type" label="Type"><option value="missing_punch">Missing punch</option><option value="correction">Correction</option><option value="other">Other</option></x-ui.select>
<div class="grid grid-cols-2 gap-4">
<x-ui.input name="requested_check_in" label="Check in" type="time" />
<x-ui.input name="requested_check_out" label="Check out" type="time" />
</div>
<textarea name="reason" rows="3" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Reason">{{ old('reason') }}</textarea>
<x-ui.button type="submit" variant="primary">Submit</x-ui.button>
</form>
</x-ui.card>
@endsection
