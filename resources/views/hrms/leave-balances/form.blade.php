@extends('layouts.dms')
@section('title', 'Leave Management · Allocate Balance')
@section('content')
<x-hrms.leave-nav active="balances" />

<x-ui.card title="Allocate / Adjust Leave Balance">
    <div class="mb-4">
        <a href="{{ route('hrms.leave-balances.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">← Back to balances</a>
    </div>
<form method="POST" action="{{ route('hrms.leave-balances.store') }}" class="space-y-4 max-w-xl">
    @csrf

    <x-ui.select name="employee_id" label="Employee" required>
        <option value="">Select Employee</option>
        @foreach($employees as $e)
            <option value="{{ $e->id }}" @selected(old('employee_id', $item->employee_id) == $e->id)>{{ $e->name }} ({{ $e->employee_code }})</option>
        @endforeach
    </x-ui.select>

    <x-ui.select name="leave_type_id" label="Leave Type" required>
        <option value="">Select Leave Type</option>
        @foreach($leaveTypes as $lt)
            <option value="{{ $lt->id }}" @selected(old('leave_type_id', $item->leave_type_id) == $lt->id)>{{ $lt->name }} (Default: {{ $lt->default_days }} days)</option>
        @endforeach
    </x-ui.select>

    <div class="grid grid-cols-2 gap-4">
        <x-ui.input name="year" label="Year" type="number" :value="old('year', $item->year ?? now()->year)" required />
        <x-ui.input name="opening_balance" label="Opening Balance (Days)" type="number" step="0.5" :value="old('opening_balance', $item->opening_balance ?? 0)" required />
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-ui.button type="submit" variant="primary">Save Balance</x-ui.button>
        <x-ui.button variant="secondary" :href="route('hrms.leave-balances.index')">Cancel</x-ui.button>
    </div>
</form>
</x-ui.card>
@endsection

