@extends('layouts.dms')
@section('title', 'Leave Policies')
@section('content')
<x-hrms.leave-nav active="policies" />
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-lg font-bold">Leave Policies</h1>
    <div class="flex gap-2">
        <form method="POST" action="{{ route('hrms.leave-policies.allocate') }}" class="flex items-center gap-2">
            @csrf
            <input type="number" name="year" value="{{ now()->year }}" class="w-24 rounded-lg border-slate-300 text-sm">
            <button class="rounded-lg border px-3 py-2 text-sm">Allocate balances</button>
        </form>
        <a href="{{ route('hrms.leave-policies.create') }}" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">+ Policy</a>
    </div>
</div>
<div class="overflow-hidden rounded-2xl border bg-white">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Entitlement</th><th class="px-4 py-3 text-left">Accrual</th><th class="px-4 py-3 text-left">Carry / Encash</th><th class="px-4 py-3"></th></tr></thead>
        <tbody class="divide-y">
            @forelse($items as $item)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $item->name }}</td>
                    <td class="px-4 py-3">{{ $item->leaveType?->name }}</td>
                    <td class="px-4 py-3">{{ $item->annual_entitlement }}</td>
                    <td class="px-4 py-3">{{ $item->accrual_frequency }}</td>
                    <td class="px-4 py-3">CF {{ $item->carry_forward_max }}{{ $item->allow_encashment ? ' · Encash' : '' }}</td>
                    <td class="px-4 py-3 text-right"><a class="text-indigo-600" href="{{ route('hrms.leave-policies.edit', $item) }}">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No policies yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $items->links() }}
@endsection
