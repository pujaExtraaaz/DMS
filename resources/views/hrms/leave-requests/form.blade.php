@extends('layouts.dms')
@section('title', 'New Leave Request')
@section('content')
<x-ui.page-header title="New Leave Request"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('hrms.leave-requests.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ route('hrms.leave-requests.store') }}" class="space-y-4 max-w-xl">@csrf
<x-ui.select name="employee_id" label="Employee" required>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="leave_type_id" label="Leave Type" required>@foreach($leaveTypes as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="from_date" label="From" type="date" :value="old('from_date')" required />
<x-ui.input name="to_date" label="To" type="date" :value="old('to_date')" required />
<div><label class="block text-sm font-medium text-gray-700 mb-1">Reason</label><textarea name="reason" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('reason') }}</textarea></div>
<x-ui.button type="submit" variant="primary">Submit</x-ui.button></form></x-ui.card>
@endsection
