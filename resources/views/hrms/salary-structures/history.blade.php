@extends('layouts.dms')
@section('title', 'Salary Revision History')
@section('content')
<x-hrms.payroll-nav active="structures" />

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('hrms.salary-structures.index') }}" class="text-sm text-indigo-600">← Structures</a>
        <h1 class="text-xl font-bold">Revision history · {{ $employee->name }}</h1>
        <p class="text-sm text-slate-500">{{ $employee->employee_code }} · {{ $revisions->count() }} revision(s)</p>
    </div>
    <a href="{{ route('hrms.salary-structures.create', ['employee_id' => $employee->id]) }}" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">+ New revision</a>
</div>

<div class="space-y-4">
@forelse($revisions as $rev)
    <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <div class="font-semibold">{{ $rev->name ?: 'Salary structure' }}</div>
                <div class="text-xs text-slate-500">
                    {{ optional($rev->effective_from)->format('d M Y') ?: '—' }}
                    @if($rev->effective_to) → {{ $rev->effective_to->format('d M Y') }} @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-ui.badge :variant="$rev->is_active ? 'success' : 'secondary'">{{ $rev->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
                <a href="{{ route('hrms.salary-structures.edit', $rev) }}" class="text-sm text-indigo-600">Edit</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500"><tr><th class="py-1 text-left">Pay head</th><th class="py-1 text-right">Amount</th></tr></thead>
                <tbody class="divide-y">
                    @foreach($rev->lines as $line)
                        <tr>
                            <td class="py-1.5">{{ $line->payHead?->name ?? $line->pay_head_id }} <span class="text-slate-400">{{ $line->payHead?->code }}</span></td>
                            <td class="py-1.5 text-right tabular-nums">{{ number_format((float) $line->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t font-semibold">
                        <td class="pt-2">Net (legacy totals)</td>
                        <td class="pt-2 text-right">{{ number_format($rev->netAmount(), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @if($rev->notes)
            <p class="mt-2 text-xs text-slate-500">{{ $rev->notes }}</p>
        @endif
    </div>
@empty
    <div class="rounded-2xl border bg-white px-4 py-10 text-center text-slate-500">No salary structures for this employee yet.</div>
@endforelse
</div>
@endsection
