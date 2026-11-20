@extends('layouts.dms')
@section('title', 'Payroll by Department')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('hrms.payroll-runs.show', $run) }}" class="text-sm text-indigo-600">← Payroll run</a>
        <h1 class="text-xl font-bold">Department report · {{ sprintf('%02d', $run->month) }}/{{ $run->year }}</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('hrms.payroll-runs.department-report', [$run, 'export' => 'csv']) }}" class="rounded-lg border px-3 py-2 text-sm">CSV</a>
        <a href="{{ route('hrms.payroll-runs.department-report', [$run, 'export' => 'pdf']) }}" class="rounded-lg border px-3 py-2 text-sm">PDF</a>
    </div>
</div>
<div class="overflow-hidden rounded-2xl border bg-white">
<table class="min-w-full text-sm">
<thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Department</th><th class="px-4 py-3 text-right">Employees</th><th class="px-4 py-3 text-right">Gross</th><th class="px-4 py-3 text-right">Deductions</th><th class="px-4 py-3 text-right">Net</th></tr></thead>
<tbody class="divide-y">
@foreach($rows as $row)
<tr>
<td class="px-4 py-3">{{ $row[0] }}</td>
<td class="px-4 py-3 text-right">{{ $row[1] }}</td>
<td class="px-4 py-3 text-right">{{ $row[2] }}</td>
<td class="px-4 py-3 text-right">{{ $row[3] }}</td>
<td class="px-4 py-3 text-right font-semibold">{{ $row[4] }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection
