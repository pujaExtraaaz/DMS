@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Leave Policy' : 'Create Leave Policy')
@section('content')
<x-hrms.leave-nav active="policies" />
<x-ui.card :title="$item->exists ? 'Edit Leave Policy' : 'Create Leave Policy'">
<form method="POST" action="{{ $item->exists ? route('hrms.leave-policies.update', $item) : route('hrms.leave-policies.store') }}" class="max-w-2xl space-y-4">
    @csrf
    @if($item->exists) @method('PUT') @endif
    <x-ui.select name="company_id" label="Company"><option value="">All companies</option>@foreach($companies as $c)<option value="{{ $c->id }}" @selected(old('company_id', $item->company_id)==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
    <x-ui.select name="leave_type_id" label="Leave type" required>@foreach($leaveTypes as $t)<option value="{{ $t->id }}" @selected(old('leave_type_id', $item->leave_type_id)==$t->id)>{{ $t->name }} ({{ $t->code }})</option>@endforeach</x-ui.select>
    <x-ui.input name="name" label="Policy name" :value="old('name', $item->name)" required />
    <div class="grid grid-cols-2 gap-4">
        <x-ui.input name="annual_entitlement" label="Annual entitlement" type="number" step="0.5" :value="old('annual_entitlement', $item->annual_entitlement)" required />
        <x-ui.select name="accrual_frequency" label="Accrual">
            <option value="yearly" @selected(old('accrual_frequency', $item->accrual_frequency)==='yearly')>Yearly</option>
            <option value="monthly" @selected(old('accrual_frequency', $item->accrual_frequency)==='monthly')>Monthly</option>
        </x-ui.select>
        <x-ui.input name="carry_forward_max" label="Carry-forward max days" type="number" step="0.5" :value="old('carry_forward_max', $item->carry_forward_max)" />
        <x-ui.input name="encashment_max" label="Encashment max days" type="number" step="0.5" :value="old('encashment_max', $item->encashment_max)" />
        <x-ui.input name="short_leave_hours" label="Short leave hours" type="number" step="0.5" :value="old('short_leave_hours', $item->short_leave_hours ?? 2)" />
    </div>
    <div class="flex flex-wrap gap-4 text-sm">
        <label class="flex items-center gap-2"><input type="checkbox" name="allow_half_day" value="1" @checked(old('allow_half_day', $item->allow_half_day ?? true))> Half-day</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="allow_short_leave" value="1" @checked(old('allow_short_leave', $item->allow_short_leave))> Short leave</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="allow_encashment" value="1" @checked(old('allow_encashment', $item->allow_encashment))> Encashment</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))> Active</label>
    </div>
    <div>
        <p class="mb-1 text-sm font-medium text-slate-700">Apply to departments (optional)</p>
        <select name="department_ids[]" multiple class="w-full rounded-lg border-slate-300 text-sm" size="4">
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(collect(old('department_ids', $item->rules?->pluck('department_id')->filter()->all() ?? []))->contains($d->id))>{{ $d->name }}</option>@endforeach
        </select>
    </div>
    <div>
        <p class="mb-1 text-sm font-medium text-slate-700">Apply to designations (optional)</p>
        <select name="designation_ids[]" multiple class="w-full rounded-lg border-slate-300 text-sm" size="4">
            @foreach($designations as $d)<option value="{{ $d->id }}" @selected(collect(old('designation_ids', $item->rules?->pluck('designation_id')->filter()->all() ?? []))->contains($d->id))>{{ $d->name }}</option>@endforeach
        </select>
    </div>
    <x-ui.select name="employee_category" label="Employee category (optional)">
        <option value="">Any</option>
        <option value="staff" @selected(old('employee_category')==='staff')>Staff</option>
        <option value="salesperson" @selected(old('employee_category')==='salesperson')>Salesperson</option>
    </x-ui.select>
    <x-ui.button type="submit" variant="primary">Save policy</x-ui.button>
</form>
</x-ui.card>
@endsection
