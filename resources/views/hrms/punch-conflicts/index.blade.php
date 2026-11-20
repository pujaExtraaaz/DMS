@extends('layouts.dms')
@section('title', 'Punch Conflicts')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-xl font-bold">Punch Conflicts</h1>
        <p class="text-sm text-slate-500">Days where both eSSL device and web/manual punches exist. Prefer one source to rebuild attendance.</p>
    </div>
    <a href="{{ route('hrms.attendances.index') }}" class="text-sm text-indigo-600">← Attendance</a>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="date" name="from" value="{{ $from }}" class="rounded-lg border-slate-300 text-sm">
    <input type="date" name="to" value="{{ $to }}" class="rounded-lg border-slate-300 text-sm">
    <button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Filter</button>
</form>

<div class="space-y-4">
@forelse($conflicts as $c)
    <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
            <div>
                <div class="font-semibold text-slate-900">{{ $c['employee']?->name ?? 'Employee #'.$c['employee']?->id }}</div>
                <div class="text-xs text-slate-500">{{ $c['employee']?->employee_code }} · {{ \Carbon\Carbon::parse($c['date'])->format('d M Y') }}</div>
                @if($c['attendance'])
                    <div class="mt-1 text-xs text-slate-500">Current attendance: {{ $c['attendance']->status }} · {{ $c['attendance']->check_in }}–{{ $c['attendance']->check_out }}</div>
                @endif
            </div>
            <form method="POST" action="{{ route('hrms.punch-conflicts.resolve') }}" class="flex flex-wrap gap-2">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $c['employee']->id }}">
                <input type="hidden" name="date" value="{{ $c['date'] }}">
                <button name="prefer" value="essl" class="rounded-lg border border-emerald-600 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Prefer eSSL</button>
                <button name="prefer" value="web" class="rounded-lg border border-indigo-600 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Prefer Web/Manual</button>
            </form>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 text-sm">
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="mb-1 text-xs font-semibold uppercase text-slate-500">eSSL</div>
                <ul class="space-y-0.5">@foreach($c['essl'] as $p)<li>{{ $p->punched_at->format('H:i:s') }} <span class="text-slate-400">{{ $p->direction }}</span></li>@endforeach</ul>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="mb-1 text-xs font-semibold uppercase text-slate-500">Web / Manual</div>
                <ul class="space-y-0.5">@foreach($c['other'] as $p)<li>{{ $p->punched_at->format('H:i:s') }} <span class="text-slate-400">{{ $p->source }} / {{ $p->direction }}</span></li>@endforeach</ul>
            </div>
        </div>
    </div>
@empty
    <div class="rounded-2xl border bg-white px-4 py-10 text-center text-slate-500">No punch conflicts in this range.</div>
@endforelse
</div>
@endsection
