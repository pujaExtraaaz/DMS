@extends('layouts.dms')
@section('title', 'Attendance Register')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-xl font-bold">Monthly Attendance Register</h1>
    <a href="{{ request()->fullUrlWithQuery(['export'=>'csv']) }}" class="rounded-lg border px-3 py-2 text-sm">Export CSV</a>
</div>
<form method="GET" class="mb-4 flex flex-row flex-wrap items-center gap-2">
    <select name="month" class="w-auto shrink-0 rounded-lg border-slate-300 text-sm">
        @for($m=1;$m<=12;$m++)
            <option value="{{ $m }}" @selected($month==$m)>{{ \Carbon\Carbon::create()->month($m)->format('M') }} ({{ $m }})</option>
        @endfor
    </select>
    <input type="number" name="year" value="{{ $year }}" class="w-24 shrink-0 rounded-lg border-slate-300 text-sm">
    <select name="employee_id" class="min-w-[12rem] flex-1 rounded-lg border-slate-300 text-sm sm:flex-none sm:w-64">
        <option value="">All employees</option>
        @foreach($employees as $e)
            <option value="{{ $e->id }}" @selected(($filters['employee_id'] ?? null)==$e->id)>{{ $e->name }}</option>
        @endforeach
    </select>
    <button class="shrink-0 rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">Apply</button>
</form>
<div class="overflow-auto rounded-2xl border bg-white">
<table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-3 py-2 text-left">Date</th><th class="px-3 py-2 text-left">Employee</th><th class="px-3 py-2">In</th><th class="px-3 py-2">Out</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Late</th><th class="px-3 py-2">OT</th></tr></thead>
<tbody>@foreach($rows as $row)<tr class="border-t"><td class="px-3 py-2">{{ $row->attendance_date?->format('d M') }}</td><td class="px-3 py-2">{{ $row->employee?->name }}</td><td class="px-3 py-2 text-center">{{ $row->check_in }}</td><td class="px-3 py-2 text-center">{{ $row->check_out }}</td><td class="px-3 py-2 text-center">{{ $row->status }}</td><td class="px-3 py-2 text-center">{{ $row->late_minutes }}</td><td class="px-3 py-2 text-center">{{ $row->ot_minutes }}</td></tr>@endforeach</tbody></table>
</div>
@endsection
