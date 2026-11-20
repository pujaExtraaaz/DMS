@extends('layouts.dms')
@section('title', 'Attendance Regularisation')
@section('content')
<div class="mb-4 flex justify-between"><h1 class="text-xl font-bold">Attendance Regularisation</h1><a href="{{ route('hrms.regularisations.create') }}" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">+ Request</a></div>
<div class="overflow-hidden rounded-2xl border bg-white">
<table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Requested</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3"></th></tr></thead>
<tbody class="divide-y">@forelse($items as $item)<tr>
<td class="px-4 py-3">{{ $item->employee?->name }}</td>
<td class="px-4 py-3">{{ $item->attendance_date?->format('d M Y') }}</td>
<td class="px-4 py-3">{{ $item->requested_check_in }} – {{ $item->requested_check_out }}</td>
<td class="px-4 py-3">{{ $item->status }}</td>
<td class="px-4 py-3 text-right space-x-2">@if($item->status==='pending')
<form class="inline" method="POST" action="{{ route('hrms.regularisations.approve', $item) }}">@csrf<button class="text-emerald-600">Approve</button></form>
<form class="inline" method="POST" action="{{ route('hrms.regularisations.reject', $item) }}">@csrf<button class="text-rose-600">Reject</button></form>
@endif</td>
</tr>@empty<tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No requests.</td></tr>@endforelse</tbody></table>
</div>{{ $items->links() }}
@endsection
