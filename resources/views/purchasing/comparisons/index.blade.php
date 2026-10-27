@extends('layouts.dms')
@section('title', 'Comparison Sheets')
@section('content')
<div>
    <x-ui.page-header title="Comparison Sheets">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('purchasing.comparisons.create')">+ New Comparison</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
            <input type="search" name="search" value="{{ $search }}" placeholder="Search number, indent, customer or project"
                   class="rounded-lg border-slate-300 py-1.5 text-sm">
            <select name="status" class="rounded-lg border-slate-300 py-1.5 text-xs text-slate-700">
                <option value="">Status: All</option>
                @foreach(['draft' => 'Draft', 'pending_approval' => 'Pending approval', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white">Filter</button>
            <a href="{{ route('purchasing.comparisons.index') }}" class="text-xs text-slate-500">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Number</th>
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Indent</th>
                        <th class="px-3 py-2">Customer / project</th>
                        <th class="px-3 py-2">Vendors</th>
                        <th class="px-3 py-2">Items</th>
                        <th class="px-3 py-2">Prepared by</th>
                        <th class="px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sheets as $sheet)
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2 font-medium">
                                <a href="{{ route('purchasing.comparisons.show', $sheet) }}" class="text-indigo-700 hover:underline">{{ $sheet->number }}</a>
                            </td>
                            <td class="px-3 py-2">{{ $sheet->comparison_date?->format('d M Y') }}</td>
                            <td class="px-3 py-2">{{ $sheet->indent_no ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $sheet->customer_project ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $sheet->vendors->count() }}</td>
                            <td class="px-3 py-2">{{ $sheet->items_count }}</td>
                            <td class="px-3 py-2">{{ $sheet->preparer?->name ?: '—' }}</td>
                            <td class="px-3 py-2">{{ str_replace('_', ' ', ucfirst($sheet->status)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-8 text-center text-slate-500">No comparison sheets yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $sheets->links() }}</div>
    </x-ui.card>
</div>
@endsection
