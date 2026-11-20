@extends('layouts.dms')
@section('title', 'Holiday Calendars')
@section('content')
<div class="mb-4 flex items-center justify-between gap-3">
    <h1 class="text-xl font-bold text-slate-900">Holiday Calendars</h1>
    <a href="{{ route('hrms.holidays.create') }}" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">+ New Calendar</a>
</div>
<form method="GET" class="mb-4 flex gap-2">
    <input type="number" name="year" value="{{ $filters['year'] ?? '' }}" placeholder="Year" class="rounded-lg border-slate-300 text-sm">
    <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Filter</button>
</form>
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr><th class="px-4 py-3">Year</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Branch</th><th class="px-4 py-3">Holidays</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($items as $item)
                <tr>
                    <td class="px-4 py-3">{{ $item->year }}</td>
                    <td class="px-4 py-3 font-medium">{{ $item->name }}</td>
                    <td class="px-4 py-3">{{ $item->branch?->name ?? 'All branches' }}</td>
                    <td class="px-4 py-3">{{ $item->holidays_count }}</td>
                    <td class="px-4 py-3">{{ $item->is_published ? 'Published' : 'Draft' }}</td>
                    <td class="px-4 py-3 text-right"><a href="{{ route('hrms.holidays.show', $item) }}" class="text-indigo-600 hover:underline">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No holiday calendars yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
