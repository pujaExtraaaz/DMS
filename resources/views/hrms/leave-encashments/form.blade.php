@extends('layouts.dms')
@section('title', 'New Leave Encashment')
@section('content')
<x-hrms.leave-nav active="encashments" />
<x-ui.card title="Leave Encashment Request">
<form method="POST" action="{{ route('hrms.leave-encashments.store') }}" class="max-w-xl space-y-4">
@csrf
<x-ui.select name="employee_id" label="Employee" required><option value="">Select</option>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="leave_type_id" label="Leave type" required>@foreach($leaveTypes as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="year" label="Year" type="number" :value="old('year', $item->year)" required />
<x-ui.input name="days" label="Days" type="number" step="0.5" required />
<x-ui.input name="amount" label="Amount (optional)" type="number" step="0.01" />
<textarea name="notes" class="w-full rounded-lg border-slate-300 text-sm" rows="2" placeholder="Notes">{{ old('notes') }}</textarea>
<x-ui.button type="submit" variant="primary">Submit</x-ui.button>
</form>
</x-ui.card>
@endsection
