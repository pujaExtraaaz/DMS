@extends('layouts.dms')
@section('title', 'My ESS')
@section('content')
@include('ess.layout-nav')
<h1 class="mb-2 text-xl font-bold">Hello, {{ $employee->name }}</h1>
<p class="mb-6 text-sm text-slate-500">Employee Self-Service · {{ $employee->employee_code }}</p>
<div class="mb-6 grid gap-3 sm:grid-cols-3">
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Pending leave</div><div class="text-2xl font-bold">{{ $pendingLeave }}</div></div>
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Leave types with balance</div><div class="text-2xl font-bold">{{ $balances->count() }}</div></div>
<div class="rounded-2xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Recent attendance days</div><div class="text-2xl font-bold">{{ $recentAttendance->count() }}</div></div>
</div>
<div class="grid gap-6 lg:grid-cols-2">
<x-ui.card title="My leave balances">
<ul class="text-sm space-y-1">@forelse($balances as $b)<li class="flex justify-between"><span>{{ $b->leaveType?->name }}</span><span class="font-semibold">{{ $b->closing_balance }}</span></li>@empty<li class="text-slate-500">No balances allocated.</li>@endforelse</ul>
</x-ui.card>
<x-ui.card title="Recent attendance">
<ul class="text-sm space-y-1">@forelse($recentAttendance as $a)<li class="flex justify-between"><span>{{ $a->attendance_date?->format('d M') }}</span><span>{{ $a->status }} {{ $a->check_in }}–{{ $a->check_out }}</span></li>@empty<li class="text-slate-500">No records.</li>@endforelse</ul>
</x-ui.card>
</div>
@endsection
