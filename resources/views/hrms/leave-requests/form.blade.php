@extends('layouts.dms')
@section('title', 'New Leave Request')
@section('content')
<x-ui.page-header title="New Leave Request">
<x-slot name="actions">
    <x-ui.button variant="secondary" :href="route('hrms.leave-requests.index')">Back to Requests</x-ui.button>
</x-slot>
</x-ui.page-header>

<x-ui.card>
<form method="POST" action="{{ route('hrms.leave-requests.store') }}" class="space-y-4 max-w-xl">
    @csrf

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 p-4 border border-red-200">
            <h4 class="text-sm font-semibold text-red-800">Please correct the errors:</h4>
            <ul class="mt-2 list-disc list-inside text-xs text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-ui.select name="employee_id" label="Employee" required>
        <option value="">Select Employee</option>
        @foreach($employees as $e)
            <option value="{{ $e->id }}" @selected(old('employee_id') == $e->id)>{{ $e->name }} ({{ $e->employee_code }})</option>
        @endforeach
    </x-ui.select>

    <x-ui.select name="leave_type_id" label="Leave Type" required>
        <option value="">Select Leave Type</option>
        @foreach($leaveTypes as $t)
            <option value="{{ $t->id }}" @selected(old('leave_type_id') == $t->id)>{{ $t->name }} (Default: {{ $t->default_days }} days)</option>
        @endforeach
    </x-ui.select>

    <div class="grid grid-cols-2 gap-4">
        <x-ui.input name="from_date" label="From Date" type="date" :value="old('from_date', now()->toDateString())" required />
        <x-ui.input name="to_date" label="To Date" type="date" :value="old('to_date', now()->toDateString())" required />
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="is_half_day" value="1" @checked(old('is_half_day')) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
        Half-Day Leave (0.5 day)
    </label>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Leave</label>
        <textarea name="reason" rows="3" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Please provide details...">{{ old('reason') }}</textarea>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-ui.button type="submit" variant="primary">Submit Leave Request</x-ui.button>
        <x-ui.button variant="secondary" :href="route('hrms.leave-requests.index')">Cancel</x-ui.button>
    </div>
</form>
</x-ui.card>
@endsection
