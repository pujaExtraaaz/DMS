@extends('layouts.dms')
@section('title', 'Leave Encashment')
@section('content')
<x-hrms.leave-nav active="encashments" />
<div class="mb-4 flex justify-between"><h1 class="text-lg font-bold">Leave Encashment</h1><a href="{{ route('hrms.leave-encashments.create') }}" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">+ Request</a></div>
<div class="overflow-hidden rounded-2xl border bg-white">
<table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Days</th><th class="px-4 py-3 text-left">Amount</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3"></th></tr></thead>
<tbody class="divide-y">
@forelse($items as $item)
<tr>
<td class="px-4 py-3">{{ $item->employee?->name }}</td>
<td class="px-4 py-3">{{ $item->leaveType?->name }} / {{ $item->year }}</td>
<td class="px-4 py-3">{{ $item->days }}</td>
<td class="px-4 py-3">{{ number_format((float)$item->amount, 2) }}</td>
<td class="px-4 py-3">{{ $item->status }}</td>
<td class="px-4 py-3 text-right space-x-2">
@if($item->status==='pending')
<form class="inline" method="POST" action="{{ route('hrms.leave-encashments.approve', $item) }}">@csrf<button class="text-emerald-600">Approve</button></form>
<form class="inline" method="POST" action="{{ route('hrms.leave-encashments.reject', $item) }}">@csrf<button class="text-rose-600">Reject</button></form>
@endif
</td>
</tr>
@empty<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No encashment requests.</td></tr>@endforelse
</tbody></table></div>
{{ $items->links() }}
@endsection
