@extends('layouts.dms')
@section('title', 'Leave Utilisation')
@section('content')
<div class="mb-4 flex justify-between"><h1 class="text-xl font-bold">Leave Utilisation {{ $year }}</h1><a href="{{ request()->fullUrlWithQuery(['export'=>'csv']) }}" class="rounded-lg border px-3 py-2 text-sm">Export CSV</a></div>
<form method="GET" class="mb-4"><input type="number" name="year" value="{{ $year }}" class="rounded-lg border-slate-300 text-sm"><button class="ml-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">Go</button></form>
<div class="overflow-auto rounded-2xl border bg-white"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-3 py-2 text-left">Employee</th><th class="px-3 py-2 text-left">Type</th><th class="px-3 py-2 text-right">Opening</th><th class="px-3 py-2 text-right">Accrued</th><th class="px-3 py-2 text-right">Used</th><th class="px-3 py-2 text-right">Encashed</th><th class="px-3 py-2 text-right">Closing</th></tr></thead>
<tbody>@foreach($balances as $b)<tr class="border-t"><td class="px-3 py-2">{{ $b->employee?->name }}</td><td class="px-3 py-2">{{ $b->leaveType?->name }}</td><td class="px-3 py-2 text-right">{{ $b->opening_balance }}</td><td class="px-3 py-2 text-right">{{ $b->accrued_balance }}</td><td class="px-3 py-2 text-right">{{ $b->used_balance }}</td><td class="px-3 py-2 text-right">{{ $b->encashed_balance }}</td><td class="px-3 py-2 text-right font-semibold">{{ $b->closing_balance }}</td></tr>@endforeach</tbody></table></div>
@endsection
