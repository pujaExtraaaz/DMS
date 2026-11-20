@extends('layouts.dms')
@section('title', 'Shifts')
@section('content')
<div class="mb-4 flex justify-between gap-3">
    <h1 class="text-xl font-bold">Shifts & Weekly Offs</h1>
    <a href="{{ route('hrms.shifts.create') }}" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">+ Shift</a>
</div>
<div class="grid gap-6 lg:grid-cols-2">
<div class="overflow-hidden rounded-2xl border bg-white">
<table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">Timing</th><th class="px-4 py-3 text-left">Grace</th><th class="px-4 py-3"></th></tr></thead>
<tbody class="divide-y">@forelse($items as $item)<tr>
<td class="px-4 py-3 font-medium">{{ $item->name }}</td>
<td class="px-4 py-3">{{ substr($item->start_time,0,5) }} – {{ substr($item->end_time,0,5) }}</td>
<td class="px-4 py-3">{{ $item->grace_minutes }} min</td>
<td class="px-4 py-3 text-right"><a href="{{ route('hrms.shifts.edit', $item) }}" class="text-indigo-600">Edit</a></td>
</tr>@empty<tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No shifts.</td></tr>@endforelse</tbody></table>
{{ $items->links() }}
</div>
<x-ui.card title="Assign shift to employee">
<form method="POST" action="{{ route('hrms.shifts.assign') }}" class="space-y-3">
@csrf
@php $employees = \App\Domains\Hrms\Models\Employee::where('status','active')->orderBy('name')->get(); @endphp
<select name="employee_id" required class="w-full rounded-lg border-slate-300 text-sm"><option value="">Employee</option>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select>
<select name="shift_id" required class="w-full rounded-lg border-slate-300 text-sm"><option value="">Shift</option>@foreach($items as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
<input type="date" name="effective_from" required class="w-full rounded-lg border-slate-300 text-sm" value="{{ now()->toDateString() }}">
<select name="weekly_off_mode" class="w-full rounded-lg border-slate-300 text-sm">
<option value="fixed">Fixed weekly off</option>
<option value="rotational">Rotational (every Nth week)</option>
</select>
<label class="text-xs text-slate-500">Off day(s) — fixed: tick all offs · rotational: tick one weekday</label>
<div class="flex flex-wrap gap-2 text-sm">@foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $i => $d)<label class="flex items-center gap-1"><input type="checkbox" name="weekly_offs[]" value="{{ $i }}" @checked($i===0)> {{ $d }}</label>@endforeach</div>
<label class="block text-xs text-slate-500">Rotational every N weeks <input type="number" name="rotational_every_n" min="1" max="12" value="2" class="ml-1 w-16 rounded border-slate-300 text-sm"></label>
<button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Assign</button>
</form>
<ul class="mt-4 divide-y text-sm">@foreach($assignments as $a)
<li class="py-2">
{{ $a->employee?->name }} → {{ $a->shift?->name }} from {{ $a->effective_from?->format('d M Y') }}
<span class="text-slate-400">· {{ $a->weekly_off_mode }} offs {{ is_array($a->weekly_offs) ? implode(',', $a->weekly_offs) : '' }}</span>
</li>
@endforeach</ul>
</x-ui.card>
</div>
@endsection
