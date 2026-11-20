@extends('layouts.dms')
@section('title', 'Payslip')
@section('content')
<div class="mx-auto max-w-2xl rounded-2xl border bg-white p-8 shadow-sm print:shadow-none">
<div class="mb-6 flex justify-between border-b pb-4">
<div>
<div class="text-lg font-bold">{{ $run->company?->name ?? config('app.name') }}</div>
<div class="text-sm text-slate-500">Salary slip · {{ sprintf('%02d', $run->month) }}/{{ $run->year }}</div>
</div>
<div class="flex gap-2 print:hidden">
@php $pdfHref = $pdfRoute ?? route('hrms.payroll-runs.payslip-pdf', [$run, $line]); @endphp
<a href="{{ $pdfHref }}" class="rounded-lg border px-3 py-1.5 text-sm">Download PDF</a>
<button onclick="window.print()" class="rounded-lg border px-3 py-1.5 text-sm">Print</button>
</div>
</div>
<div class="mb-4 grid grid-cols-2 gap-2 text-sm">
<div><span class="text-slate-500">Employee</span><div class="font-semibold">{{ $line->employee?->name }}</div></div>
<div><span class="text-slate-500">Code</span><div class="font-semibold">{{ $line->employee?->employee_code }}</div></div>
<div><span class="text-slate-500">Payable days</span><div>{{ $line->payable_days }}</div></div>
<div><span class="text-slate-500">LWP / OT</span><div>{{ $line->lwp_days }} days / {{ $line->ot_minutes }} min</div></div>
</div>
<div class="grid gap-4 sm:grid-cols-2">
<div>
<h3 class="mb-2 text-sm font-bold uppercase text-slate-500">Earnings</h3>
<ul class="space-y-1 text-sm">@foreach(($line->earnings_json ?? []) as $code => $amt)<li class="flex justify-between"><span>{{ $code }}</span><span>{{ number_format((float)$amt,2) }}</span></li>@endforeach</ul>
<div class="mt-2 flex justify-between border-t pt-2 font-semibold"><span>Gross</span><span>{{ number_format((float)$line->gross_earnings,2) }}</span></div>
</div>
<div>
<h3 class="mb-2 text-sm font-bold uppercase text-slate-500">Deductions</h3>
<ul class="space-y-1 text-sm">@foreach(($line->deductions_json ?? []) as $code => $amt)<li class="flex justify-between"><span>{{ $code }}</span><span>{{ number_format((float)$amt,2) }}</span></li>@endforeach</ul>
<div class="mt-2 flex justify-between border-t pt-2 font-semibold"><span>Total</span><span>{{ number_format((float)$line->total_deductions,2) }}</span></div>
</div>
</div>
<div class="mt-6 rounded-xl bg-slate-900 px-4 py-3 text-white flex justify-between"><span>Net payable</span><span class="text-lg font-bold">₹ {{ number_format((float)$line->net_payable,2) }}</span></div>
</div>
@endsection
