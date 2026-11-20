@extends('layouts.dms')
@section('title', 'Payroll Employee Report')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('hrms.payroll-runs.show', $run) }}" class="text-sm text-indigo-600">← Payroll run</a>
        <h1 class="text-xl font-bold">Employee report · {{ sprintf('%02d', $run->month) }}/{{ $run->year }}</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('hrms.payroll-runs.employee-report', [$run, 'export' => 'csv']) }}" class="rounded-lg border px-3 py-2 text-sm">CSV</a>
        <a href="{{ route('hrms.payroll-runs.employee-report', [$run, 'export' => 'pdf']) }}" class="rounded-lg border px-3 py-2 text-sm">PDF</a>
    </div>
</div>
<div class="overflow-hidden rounded-2xl border bg-white">
<table class="min-w-full text-sm">
<thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr>
<th class="px-4 py-3 text-left">Code</th><th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-left">Dept</th>
<th class="px-4 py-3 text-right">Payable</th><th class="px-4 py-3 text-right">LWP</th><th class="px-4 py-3 text-right">OT</th>
<th class="px-4 py-3 text-right">Gross</th><th class="px-4 py-3 text-right">Ded</th><th class="px-4 py-3 text-right">Net</th>
</tr></thead>
<tbody class="divide-y">
@foreach($rows->sortBy(fn ($l) => $l->employee?->name) as $line)
<tr>
<td class="px-4 py-3">{{ $line->employee?->employee_code }}</td>
<td class="px-4 py-3">{{ $line->employee?->name }}</td>
<td class="px-4 py-3">{{ $line->employee?->department?->name }}</td>
<td class="px-4 py-3 text-right">{{ $line->payable_days }}</td>
<td class="px-4 py-3 text-right">{{ $line->lwp_days }}</td>
<td class="px-4 py-3 text-right">{{ $line->ot_minutes }}</td>
<td class="px-4 py-3 text-right">{{ number_format((float)$line->gross_earnings,2) }}</td>
<td class="px-4 py-3 text-right">{{ number_format((float)$line->total_deductions,2) }}</td>
<td class="px-4 py-3 text-right font-semibold">{{ number_format((float)$line->net_payable,2) }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection