@extends('layouts.dms')
@section('title', $employee->name)
@section('content')
<x-ui.page-header :title="$employee->name" :description="$employee->employee_code">
<x-slot name="actions">
<x-ui.button variant="secondary" :href="route('hrms.employees.index')">Back</x-ui.button>
<x-ui.button variant="primary" :href="route('hrms.employees.edit', $employee)">Edit</x-ui.button>
</x-slot>
</x-ui.page-header>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
<x-ui.card title="Profile"><dl class="space-y-2 text-sm">
<div class="flex justify-between"><dt>Department</dt><dd>{{ $employee->department?->name ?? '—' }}</dd></div>
<div class="flex justify-between"><dt>Designation</dt><dd>{{ $employee->designation?->name ?? '—' }}</dd></div>
<div class="flex justify-between"><dt>Branch</dt><dd>{{ $employee->branch?->name ?? '—' }}</dd></div>
<div class="flex justify-between"><dt>Manager</dt><dd>{{ $employee->manager?->name ?? '—' }}</dd></div>
<div class="flex justify-between"><dt>User</dt><dd>{{ $employee->user?->name ?? '—' }}</dd></div>
<div class="flex justify-between"><dt>Status</dt><dd>{{ ucfirst($employee->status) }}</dd></div>
<div class="flex justify-between"><dt>Salesperson</dt><dd>{{ $employee->is_salesperson ? 'Yes' : 'No' }}</dd></div>
</dl></x-ui.card>
<x-ui.card title="Leave Balances">
@forelse($employee->leaveBalances as $bal)
<p class="text-sm py-1">{{ $bal->leaveType?->name }} ({{ $bal->year }}): closing {{ $bal->closing_balance }}</p>
@empty
<p class="text-sm text-slate-500">No leave balances.</p>
@endforelse
</x-ui.card>
</div>
@endsection
