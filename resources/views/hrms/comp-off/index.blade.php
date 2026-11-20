@extends('layouts.dms')
@section('title', 'Comp-off Credit')
@section('content')
<div class="mb-4">
    <h1 class="text-xl font-bold">Comp-off auto-credit</h1>
    <p class="text-sm text-slate-500">Credits COFF leave balance from OT minutes and compulsory-holiday work for the selected month. Already-credited days are skipped.</p>
</div>
@if(session('error'))
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
@endif
@if(session('status'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('hrms.comp-off.credit') }}" class="mb-6 max-w-lg space-y-3 rounded-2xl border bg-white p-4 shadow-sm">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Year</label>
            <input type="number" name="year" value="{{ old('year', $year) }}" required class="w-full rounded-lg border-slate-300 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Month</label>
            <input type="number" name="month" min="1" max="12" value="{{ old('month', $month) }}" required class="w-full rounded-lg border-slate-300 text-sm">
        </div>
    </div>
    <div>
        <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Company (optional)</label>
        <select name="company_id" class="w-full rounded-lg border-slate-300 text-sm">
            <option value="">All companies</option>
            @foreach($companies as $c)
                <option value="{{ $c->id }}" @selected(old('company_id') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Credit comp-off</button>
</form>

@if(!empty($summary))
<div class="overflow-hidden rounded-2xl border bg-white">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-right">Days credited</th></tr></thead>
        <tbody class="divide-y">
            @foreach($summary as $row)
                <tr>
                    <td class="px-4 py-3">{{ $row['name'] }}</td>
                    <td class="px-4 py-3 text-right font-semibold">{{ number_format((float) $row['days'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
