@extends('layouts.dms')
@section('title', 'Late & OT Report')
@section('content')
<div class="mb-4 flex justify-between"><h1 class="text-xl font-bold">Late Coming & Overtime</h1><a href="{{ request()->fullUrlWithQuery(['export'=>'csv']) }}" class="rounded-lg border px-3 py-2 text-sm">Export CSV</a></div>
<form method="GET" class="mb-4 flex gap-2">
<select name="month" class="rounded-lg border-slate-300 text-sm">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($month==$m)>{{ $m }}</option>@endfor</select>
<input type="number" name="year" value="{{ $year }}" class="w-24 rounded-lg border-slate-300 text-sm">
<button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">Apply</button>
</form>
<div class="overflow-auto rounded-2xl border bg-white"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-3 py-2 text-left">Date</th><th class="px-3 py-2 text-left">Employee</th><th class="px-3 py-2 text-right">Late</th><th class="px-3 py-2 text-right">OT</th><th class="px-3 py-2">Status</th></tr></thead>
<tbody>@foreach($rows as $row)<tr class="border-t"><td class="px-3 py-2">{{ $row->attendance_date?->format('d M Y') }}</td><td class="px-3 py-2">{{ $row->employee?->name }}</td><td class="px-3 py-2 text-right">{{ $row->late_minutes }}</td><td class="px-3 py-2 text-right">{{ $row->ot_minutes }}</td><td class="px-3 py-2 text-center">{{ $row->status }}</td></tr>@endforeach</tbody></table></div>
@endsection
