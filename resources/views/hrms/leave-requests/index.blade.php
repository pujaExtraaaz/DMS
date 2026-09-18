@extends('layouts.dms')
@section('title', 'Leave Requests')
@section('content')
<x-ui.page-header title="Leave Requests"><x-slot name="actions"><x-ui.button variant="primary" :href="route('hrms.leave-requests.create')">New Request</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Employee</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Type</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Dates</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Days</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th><th></th></tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr>
<td class="px-3 py-2">{{ $item->employee?->name }}</td>
<td class="px-3 py-2">{{ $item->leaveType?->name }}</td>
<td class="px-3 py-2">{{ $item->from_date->format('d M') }} – {{ $item->to_date->format('d M Y') }}</td>
<td class="px-3 py-2">{{ $item->days }}</td>
<td class="px-3 py-2"><x-ui.badge>{{ ucfirst($item->status) }}</x-ui.badge></td>
<td class="px-3 py-2 text-right whitespace-nowrap">
@if($item->status==='pending')
<form method="POST" action="{{ route('hrms.leave-requests.approve', $item) }}" class="inline">@csrf<x-ui.button type="submit" size="sm" variant="primary">Approve</x-ui.button></form>
<form method="POST" action="{{ route('hrms.leave-requests.reject', $item) }}" class="inline">@csrf<x-ui.button type="submit" size="sm" variant="secondary">Reject</x-ui.button></form>
@endif
</td>
</tr>
@empty<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No leave requests.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $items->links() }}</div></x-ui.card>
@endsection
