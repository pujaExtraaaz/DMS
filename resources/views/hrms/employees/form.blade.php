@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Employee' : 'Create Employee')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Employee' : 'Create Employee'">
<x-slot name="actions"><x-ui.button variant="secondary" :href="route('hrms.employees.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ $item->exists ? route('hrms.employees.update', $item) : route('hrms.employees.store') }}" class="space-y-4 max-w-3xl">
@csrf @if($item->exists) @method('PUT') @endif
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<x-ui.input name="employee_code" label="Employee Code" :value="old('employee_code', $item->employee_code)" />
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.input name="email" label="Email" type="email" :value="old('email', $item->email)" />
<x-ui.input name="phone" label="Phone" :value="old('phone', $item->phone)" />
<x-ui.select name="company_id" label="Company"><option value="">—</option>@foreach($companies as $c)<option value="{{ $c->id }}" @selected(old('company_id', $item->company_id)==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="branch_id" label="Branch"><option value="">—</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $item->branch_id)==$b->id)>{{ $b->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="department_id" label="Department"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $item->department_id)==$d->id)>{{ $d->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="designation_id" label="Designation"><option value="">—</option>@foreach($designations as $d)<option value="{{ $d->id }}" @selected(old('designation_id', $item->designation_id)==$d->id)>{{ $d->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="manager_id" label="Manager"><option value="">—</option>@foreach($managers as $m)<option value="{{ $m->id }}" @selected(old('manager_id', $item->manager_id)==$m->id)>{{ $m->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="user_id" label="Linked User"><option value="">—</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('user_id', $item->user_id)==$u->id)>{{ $u->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="joining_date" label="Joining Date" type="date" :value="old('joining_date', optional($item->joining_date)->format('Y-m-d'))" />
<x-ui.select name="status" label="Status">@foreach(['active','on_leave','resigned','terminated','inactive'] as $s)<option value="{{ $s }}" @selected(old('status', $item->status)==$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</x-ui.select>
</div>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_salesperson" value="1" @checked(old('is_salesperson', $item->is_salesperson)) class="rounded border-gray-300 text-indigo-600"> Salesperson</label>
<div><label class="block text-sm font-medium text-gray-700 mb-1">Address</label><textarea name="address" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('address', $item->address) }}</textarea></div>
<x-ui.button type="submit" variant="primary">Save</x-ui.button>
</form>
</x-ui.card>
@endsection
