@extends('layouts.dms')
@section('title', 'Payroll '.$run->month.'/'.$run->year)
@section('content')
<x-hrms.payroll-nav active="runs" />
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
<div>
<a href="{{ route('hrms.payroll-runs.index') }}" class="text-sm text-indigo-600">← Runs</a>
<h1 class="text-xl font-bold">Payroll {{ sprintf('%02d', $run->month) }}/{{ $run->year }}</h1>
<p class="text-sm text-slate-500">Status: {{ $run->status }} · {{ $run->branch?->name ?? 'All branches' }}</p>
</div>
<div class="flex flex-wrap gap-2">
@if($run->status==='draft')
<form method="POST" action="{{ route('hrms.payroll-runs.recompute', $run) }}">@csrf<button class="rounded-lg border px-3 py-2 text-sm">Recompute</button></form>
<form method="POST" action="{{ route('hrms.payroll-runs.approve', $run) }}">@csrf<button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">Approve</button></form>
@endif
@if($run->status==='approved')
<form method="POST" action="{{ route('hrms.payroll-runs.lock', $run) }}">@csrf<button class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white">Lock</button></form>
@endif
<a href="{{ route('hrms.payroll-runs.bank-file', $run) }}" class="rounded-lg border px-3 py-2 text-sm">Bank CSV</a>
<a href="{{ route('hrms.payroll-runs.department-report', $run) }}" class="rounded-lg border px-3 py-2 text-sm">Dept report</a>
<a href="{{ route('hrms.payroll-runs.employee-report', $run) }}" class="rounded-lg border px-3 py-2 text-sm">Employee report</a>
</div>
</div>
<div class="overflow-hidden rounded-2xl border bg-white">
<table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-right">Payable</th><th class="px-4 py-3 text-right">LWP</th><th class="px-4 py-3 text-right">OT min</th><th class="px-4 py-3 text-right">Gross</th><th class="px-4 py-3 text-right">Ded.</th><th class="px-4 py-3 text-right">Net</th><th class="px-4 py-3"></th></tr></thead>
<tbody class="divide-y">@foreach($run->lines as $line)<tr>
<td class="px-4 py-3">{{ $line->employee?->name }}</td>
<td class="px-4 py-3 text-right">{{ $line->payable_days }}</td>
<td class="px-4 py-3 text-right">{{ $line->lwp_days }}</td>
<td class="px-4 py-3 text-right">{{ $line->ot_minutes }}</td>
<td class="px-4 py-3 text-right">{{ number_format((float)$line->gross_earnings,2) }}</td>
<td class="px-4 py-3 text-right">{{ number_format((float)$line->total_deductions,2) }}</td>
<td class="px-4 py-3 text-right font-semibold">{{ number_format((float)$line->net_payable,2) }}</td>
<td class="px-4 py-3 text-right space-x-2">
<a class="text-indigo-600" href="{{ route('hrms.payroll-runs.payslip', [$run, $line]) }}">Slip</a>
<a class="text-slate-600" href="{{ route('hrms.payroll-runs.payslip-pdf', [$run, $line]) }}">PDF</a>
</td>
</tr>@endforeach</tbody></table>
</div>
@endsection
