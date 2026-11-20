@extends('layouts.dms')
@section('title', 'Payroll Runs')
@section('content')
<x-hrms.payroll-nav active="runs" />
<div class="mb-4 flex justify-between"><h1 class="text-lg font-bold">Payroll Runs</h1><a href="{{ route('hrms.payroll-runs.create') }}" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">+ New run</a></div>
<div class="overflow-hidden rounded-2xl border bg-white">
<table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Period</th><th class="px-4 py-3 text-left">Branch</th><th class="px-4 py-3 text-left">Employees</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3"></th></tr></thead>
<tbody class="divide-y">@forelse($items as $item)<tr>
<td class="px-4 py-3 font-medium">{{ sprintf('%02d', $item->month) }}/{{ $item->year }}</td>
<td class="px-4 py-3">{{ $item->branch?->name ?? 'All' }}</td>
<td class="px-4 py-3">{{ $item->lines_count }}</td>
<td class="px-4 py-3">{{ $item->status }}</td>
<td class="px-4 py-3 text-right"><a href="{{ route('hrms.payroll-runs.show', $item) }}" class="text-indigo-600">Open</a></td>
</tr>@empty<tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No payroll runs.</td></tr>@endforelse</tbody></table>
</div>{{ $items->links() }}
@endsection
