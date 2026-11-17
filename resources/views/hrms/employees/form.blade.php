@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Employee' : 'Create Employee')

@php
    $effectiveReturnUrl = old('return_url', $returnUrl ?? request('return_url'));
    $effectiveReturnTo = $returnTo ?? request('return_to', old('return_to'));
@endphp

@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Employee' : 'Create Employee'">
<x-slot name="actions">
    @if(filled($effectiveReturnUrl))
        <x-ui.button variant="secondary" :href="$effectiveReturnUrl">Cancel &amp; Go Back</x-ui.button>
    @elseif($effectiveReturnTo === 'invoices.create' || $effectiveReturnTo === 'sales.invoices.create')
        <x-ui.button variant="secondary" :href="route('invoices.create')">Back to Tax Invoice</x-ui.button>
    @else
        <x-ui.button variant="secondary" :href="route('hrms.employees.index')">Back</x-ui.button>
    @endif
</x-slot>
</x-ui.page-header>
<x-ui.card>
    @if(! $item->exists && filled($effectiveReturnUrl))
        <div class="mb-4 bg-indigo-50/90 border border-indigo-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-indigo-900 shadow-2xs max-w-3xl">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </div>
                <div>
                    <span class="font-bold">Adding Salesperson for Tax Invoice</span>
                    <p class="text-indigo-700 text-[11px]">
                        Save this employee, then you will be returned to the Tax Invoice with the salesperson selected.
                    </p>
                </div>
            </div>
            <a href="{{ $effectiveReturnUrl }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white text-indigo-700 border border-indigo-200 font-semibold rounded-xl hover:bg-indigo-100 hover:text-indigo-900 transition shadow-2xs shrink-0">
                &larr; Cancel &amp; Go Back
            </a>
        </div>
    @endif

<form method="POST" action="{{ $item->exists ? route('hrms.employees.update', $item) : route('hrms.employees.store') }}" class="space-y-4 max-w-3xl">
@csrf @if($item->exists) @method('PUT') @endif
@if(! $item->exists && filled($effectiveReturnUrl))
    <input type="hidden" name="return_url" value="{{ $effectiveReturnUrl }}">
@endif
@if($effectiveReturnTo === 'invoices.create' || $effectiveReturnTo === 'sales.invoices.create')
    <input type="hidden" name="return_to" value="{{ $effectiveReturnTo }}">
@endif
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
<div class="flex items-center gap-3 pt-2">
    <x-ui.button type="submit" variant="primary">Save</x-ui.button>
    @php
        $cancelUrl = filled($effectiveReturnUrl)
            ? $effectiveReturnUrl
            : (($effectiveReturnTo === 'invoices.create' || $effectiveReturnTo === 'sales.invoices.create')
                ? route('invoices.create')
                : route('hrms.employees.index'));
    @endphp
    <x-ui.button variant="secondary" :href="$cancelUrl">Cancel</x-ui.button>
</div>
</form>
</x-ui.card>
@endsection
