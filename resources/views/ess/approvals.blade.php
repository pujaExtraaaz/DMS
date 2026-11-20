@extends('layouts.dms')
@section('title', 'Team Approvals')
@section('content')
@include('ess.layout-nav')
<h1 class="mb-2 text-xl font-bold">Team approvals</h1>
<p class="mb-6 text-sm text-slate-500">Pending leave and regularisation requests from your direct reports.</p>

<div class="mb-8 overflow-hidden rounded-2xl border bg-white">
    <div class="border-b bg-slate-50 px-4 py-2 text-xs font-semibold uppercase text-slate-500">Leave</div>
    <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-2">Employee</th><th class="px-4 py-2">Type</th><th class="px-4 py-2">Dates</th><th class="px-4 py-2">Days</th><th class="px-4 py-2"></th></tr></thead>
        <tbody class="divide-y">
        @forelse($leaveRequests as $item)
            <tr>
                <td class="px-4 py-3">{{ $item->employee?->name }}</td>
                <td class="px-4 py-3">{{ $item->leaveType?->code }}</td>
                <td class="px-4 py-3">{{ optional($item->from_date)->format('d M') }} – {{ optional($item->to_date)->format('d M') }}</td>
                <td class="px-4 py-3">{{ $item->days }}</td>
                <td class="px-4 py-3 text-right space-x-2">
                    <form class="inline" method="POST" action="{{ route('ess.approvals.leave.approve', $item) }}">@csrf<button class="text-emerald-600 font-semibold">Approve</button></form>
                    <form class="inline" method="POST" action="{{ route('ess.approvals.leave.reject', $item) }}">@csrf<button class="text-rose-600">Reject</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No pending leave from reports.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="overflow-hidden rounded-2xl border bg-white">
    <div class="border-b bg-slate-50 px-4 py-2 text-xs font-semibold uppercase text-slate-500">Regularisations</div>
    <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-2">Employee</th><th class="px-4 py-2">Date</th><th class="px-4 py-2">Type</th><th class="px-4 py-2"></th></tr></thead>
        <tbody class="divide-y">
        @forelse($regularisations as $item)
            <tr>
                <td class="px-4 py-3">{{ $item->employee?->name }}</td>
                <td class="px-4 py-3">{{ optional($item->attendance_date)->format('d M Y') }}</td>
                <td class="px-4 py-3">{{ $item->type }}</td>
                <td class="px-4 py-3 text-right space-x-2">
                    <form class="inline" method="POST" action="{{ route('ess.approvals.regularisation.approve', $item) }}">@csrf<button class="text-emerald-600 font-semibold">Approve</button></form>
                    <form class="inline" method="POST" action="{{ route('ess.approvals.regularisation.reject', $item) }}">@csrf<button class="text-rose-600">Reject</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No pending regularisations.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
