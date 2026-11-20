@extends('layouts.dms')
@section('title', 'HR Dashboard')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
<h1 class="text-xl font-bold">HR Dashboard</h1>
<form method="GET" class="flex gap-2"><input type="date" name="date" value="{{ $date->toDateString() }}" class="rounded-lg border-slate-300 text-sm"><button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">Go</button></form>
</div>
<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Active employees</div><div class="text-2xl font-bold">{{ $activeEmployees }}</div></div>
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Present / Late</div><div class="text-2xl font-bold">{{ $present->count() }}</div></div>
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Absent</div><div class="text-2xl font-bold">{{ $absent->count() }}</div></div>
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">On leave</div><div class="text-2xl font-bold">{{ $onLeave->count() }}</div></div>
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Pending leave</div><div class="text-2xl font-bold">{{ $pendingLeave }}</div><a href="{{ route('hrms.leave-requests.index', ['status' => 'pending']) }}" class="text-xs text-indigo-600">Review →</a></div>
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Pending regularisations</div><div class="text-2xl font-bold">{{ $pendingRegularisations }}</div><a href="{{ route('hrms.regularisations.index') }}" class="text-xs text-indigo-600">Review →</a></div>
</div>
@if($latestPayroll)
<div class="mb-4 rounded-2xl border border-indigo-100 bg-indigo-50/60 px-4 py-3 text-sm flex flex-wrap items-center justify-between gap-2">
    <span>Latest payroll: <strong>{{ sprintf('%02d', $latestPayroll->month) }}/{{ $latestPayroll->year }}</strong> · {{ $latestPayroll->status }}</span>
    <a href="{{ route('hrms.payroll-runs.show', $latestPayroll) }}" class="font-semibold text-indigo-700">Open run →</a>
</div>
@endif
<div class="mb-4 flex flex-wrap gap-2 text-sm">
<a class="rounded-full border px-3 py-1.5" href="{{ route('hrms.reports.attendance-register') }}">Attendance register</a>
<a class="rounded-full border px-3 py-1.5" href="{{ route('hrms.reports.late-ot') }}">Late / OT</a>
<a class="rounded-full border px-3 py-1.5" href="{{ route('hrms.reports.leave-utilisation') }}">Leave utilisation</a>
<a class="rounded-full border px-3 py-1.5" href="{{ route('hrms.payroll-runs.index') }}">Payroll runs</a>
<a class="rounded-full border px-3 py-1.5" href="{{ route('hrms.punch-conflicts.index') }}">Punch conflicts</a>
<a class="rounded-full border px-3 py-1.5" href="{{ route('hrms.comp-off.index') }}">Comp-off credit</a>
<a class="rounded-full border px-3 py-1.5" href="{{ route('hrms.regularisations.index') }}">Regularisations</a>
</div>
<div class="grid gap-6 lg:grid-cols-2">
@foreach([['Present / Late',$present],['Absent',$absent],['Late',$late],['On leave',$onLeave]] as [$title,$list])
@php $cardTitle = $title.' · '.$date->format('d M Y'); @endphp
<x-ui.card :title="$cardTitle">
<ul class="max-h-64 space-y-1 overflow-y-auto text-sm">@forelse($list as $row)<li>{{ $row->employee?->name }} <span class="text-slate-400">{{ $row->check_in ?? '—' }}–{{ $row->check_out ?? '—' }}@if(!empty($row->leaveType)) · {{ $row->leaveType->code }}@endif</span></li>@empty<li class="text-slate-500">None</li>@endforelse</ul>
</x-ui.card>
@endforeach
</div>
<x-ui.card title="Leave balances (sample)" class="mt-6">
<table class="min-w-full text-sm"><thead><tr class="text-left text-xs uppercase text-slate-500"><th class="py-2">Employee</th><th>Type</th><th>Closing</th></tr></thead>
<tbody>@foreach($leaveBalances as $b)<tr class="border-t"><td class="py-2">{{ $b->employee?->name }}</td><td>{{ $b->leaveType?->code }}</td><td>{{ $b->closing_balance }}</td></tr>@endforeach</tbody></table>
</x-ui.card>
@endsection
