@extends('layouts.dms')
@section('title', $period->name)
@section('content')
<x-ui.page-header :title="$period->name">
    <x-slot name="actions">
        <div class="flex gap-2">
            <x-ui.button variant="secondary" :href="route('targets.index')">Back</x-ui.button>
            @if($period->status !== 'closed')
                <x-ui.button variant="secondary" :href="route('targets.periods.edit', $period)">Edit</x-ui.button>
                <x-ui.button variant="primary" :href="route('targets.create', $period)">Add Target</x-ui.button>
                <form method="POST" action="{{ route('targets.periods.recalculate', $period) }}">@csrf<button class="inline-flex items-center px-4 py-2 text-sm rounded-lg bg-white border border-gray-300">Recalculate</button></form>
                <form method="POST" action="{{ route('targets.periods.close', $period) }}">@csrf<button class="inline-flex items-center px-4 py-2 text-sm rounded-lg bg-emerald-600 text-white">Close</button></form>
            @endif
        </div>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
            Type: {{ ucfirst($period->period_type) }}
        </span>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
            Dates: {{ $period->starts_on->format('d M Y') }} – {{ $period->ends_on->format('d M Y') }}
        </span>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $period->status === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800' }}">
            Status: {{ ucfirst($period->status) }}
        </span>
        @if($period->target_category_label)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                Category: {{ $period->target_category_label }}
            </span>
        @endif
        @if($period->brand)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Brand: {{ $period->brand->name }}
            </span>
        @endif
        @if($period->product)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                Product: {{ $period->product->name }}
            </span>
        @endif
        @if($period->supplier)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                Supplier: {{ $period->supplier->name }}
            </span>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Party</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Salesperson</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Brand</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Target</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Achieved</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">%</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($period->targets as $t)
                    <tr>
                        <td class="px-3 py-2">{{ $t->customer?->name ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $t->salesperson?->name ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $t->brand?->name ?: '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format((float)$t->amount, 2) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format((float)($t->achievement?->achieved_amount ?? 0), 2) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format((float)($t->achievement?->achievement_percent ?? 0), 1) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No targets assigned.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
