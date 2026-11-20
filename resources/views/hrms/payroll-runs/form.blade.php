@extends('layouts.dms')
@section('title', 'Create Payroll Run')
@section('content')
<x-hrms.payroll-nav active="runs" />
<x-ui.card title="Create payroll run">
<form method="POST" action="{{ route('hrms.payroll-runs.store') }}" class="max-w-xl space-y-4">
@csrf
<x-ui.select name="company_id" label="Company"><option value="">—</option>@foreach($companies as $c)<option value="{{ $c->id }}" @selected(old('company_id',$item->company_id)==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="branch_id" label="Branch"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</x-ui.select>
<div class="grid grid-cols-2 gap-4">
<x-ui.input name="month" label="Month" type="number" min="1" max="12" :value="old('month',$item->month)" required />
<x-ui.input name="year" label="Year" type="number" :value="old('year',$item->year)" required />
</div>
<textarea name="notes" class="w-full rounded-lg border-slate-300 text-sm" rows="2" placeholder="Notes">{{ old('notes') }}</textarea>
<x-ui.button type="submit" variant="primary">Compute draft</x-ui.button>
</form>
</x-ui.card>
@endsection
