@extends('layouts.dms')
@section('title', 'Attendance')
@section('content')
<x-ui.page-header title="Attendance"><x-slot name="actions"><x-ui.button variant="primary" :href="route('hrms.attendances.create')">Mark Attendance</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Employee</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">In</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Out</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th></tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr><td class="px-3 py-2">{{ $item->attendance_date->format('d M Y') }}</td><td class="px-3 py-2">{{ $item->employee?->name }}</td><td class="px-3 py-2">{{ $item->check_in }}</td><td class="px-3 py-2">{{ $item->check_out }}</td><td class="px-3 py-2">{{ ucfirst(str_replace('_',' ',$item->status)) }}</td></tr>
@empty<tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No attendance records.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $items->links() }}</div></x-ui.card>
@endsection
