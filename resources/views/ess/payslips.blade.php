@extends('layouts.dms')
@section('title', 'My Payslips')
@section('content')
@include('ess.layout-nav')
<div class="overflow-hidden rounded-2xl border bg-white">
<table class="min-w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Period</th><th class="px-4 py-3 text-right">Net</th><th class="px-4 py-3"></th></tr></thead>
<tbody>@forelse($lines as $line)<tr class="border-t"><td class="px-4 py-3">{{ sprintf('%02d', $line->run?->month) }}/{{ $line->run?->year }}</td><td class="px-4 py-3 text-right font-semibold">{{ number_format((float)$line->net_payable,2) }}</td><td class="px-4 py-3 text-right"><a class="text-indigo-600" href="{{ route('ess.payslips.show', $line) }}">View</a></td></tr>@empty<tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">No approved payslips yet.</td></tr>@endforelse</tbody></table>
</div>
@endsection
