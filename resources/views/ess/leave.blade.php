@extends('layouts.dms')
@section('title', 'My Leave')
@section('content')
@include('ess.layout-nav')
<div class="grid gap-6 lg:grid-cols-2">
<x-ui.card title="Apply leave">
<form method="POST" action="{{ route('ess.leave.store') }}" class="space-y-3">
@csrf
<select name="leave_type_id" required class="w-full rounded-lg border-slate-300 text-sm">@foreach($leaveTypes as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
<div class="grid grid-cols-2 gap-2">
<input type="date" name="from_date" required class="rounded-lg border-slate-300 text-sm" value="{{ now()->toDateString() }}">
<input type="date" name="to_date" required class="rounded-lg border-slate-300 text-sm" value="{{ now()->toDateString() }}">
</div>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_half_day" value="1"> Half day</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_short_leave" value="1"> Short leave</label>
<input type="number" step="0.5" name="short_leave_hours" placeholder="Short leave hours" class="w-full rounded-lg border-slate-300 text-sm">
<textarea name="reason" rows="2" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Reason"></textarea>
<button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Submit</button>
</form>
<ul class="mt-4 text-sm space-y-1">@foreach($balances as $b)<li>{{ $b->leaveType?->code }}: <strong>{{ $b->closing_balance }}</strong></li>@endforeach</ul>
</x-ui.card>
<x-ui.card title="My leave history">
<ul class="divide-y text-sm">@forelse($requests as $r)<li class="py-2 flex justify-between gap-2"><span>{{ $r->leaveType?->name }} · {{ $r->from_date?->format('d M') }}–{{ $r->to_date?->format('d M') }} ({{ $r->days }}d)</span><span>{{ $r->status }}</span></li>@empty<li class="py-6 text-center text-slate-500">No requests.</li>@endforelse</ul>
</x-ui.card>
</div>
@endsection
