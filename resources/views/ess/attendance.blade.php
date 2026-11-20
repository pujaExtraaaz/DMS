@extends('layouts.dms')
@section('title', 'My Attendance')
@section('content')
@include('ess.layout-nav')
<form method="GET" class="mb-4 flex gap-2">
<select name="month" class="rounded-lg border-slate-300 text-sm">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($month==$m)>{{ $m }}</option>@endfor</select>
<input type="number" name="year" value="{{ $year }}" class="w-24 rounded-lg border-slate-300 text-sm">
<button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">Go</button>
</form>
<div class="grid gap-6 lg:grid-cols-2">
<div class="overflow-hidden rounded-2xl border bg-white">
<table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-3 py-2 text-left">Date</th><th class="px-3 py-2">In</th><th class="px-3 py-2">Out</th><th class="px-3 py-2">Status</th></tr></thead>
<tbody>@foreach($rows as $row)<tr class="border-t"><td class="px-3 py-2">{{ $row->attendance_date?->format('d M') }}</td><td class="px-3 py-2 text-center">{{ $row->check_in }}</td><td class="px-3 py-2 text-center">{{ $row->check_out }}</td><td class="px-3 py-2 text-center">{{ $row->status }}</td></tr>@endforeach</tbody></table>
</div>
<x-ui.card title="Request regularisation">
<form method="POST" action="{{ route('ess.regularisation.store') }}" class="space-y-3">
@csrf
<input type="date" name="attendance_date" required class="w-full rounded-lg border-slate-300 text-sm">
<select name="type" class="w-full rounded-lg border-slate-300 text-sm"><option value="missing_punch">Missing punch</option><option value="correction">Correction</option></select>
<div class="grid grid-cols-2 gap-2"><input type="time" name="requested_check_in" class="rounded-lg border-slate-300 text-sm"><input type="time" name="requested_check_out" class="rounded-lg border-slate-300 text-sm"></div>
<textarea name="reason" class="w-full rounded-lg border-slate-300 text-sm" rows="2" placeholder="Reason"></textarea>
<button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Submit</button>
</form>
<ul class="mt-4 divide-y text-sm">@foreach($regularisations as $r)<li class="py-2">{{ $r->attendance_date?->format('d M') }} · {{ $r->status }}</li>@endforeach</ul>
</x-ui.card>
</div>
@endsection
