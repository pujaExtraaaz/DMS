@extends('layouts.dms')
@section('title', 'Stock Adjustment - ' . $adjustment->adjustment_no)

@section('content')
<x-ui.page-header :title="'Stock Adjustment: ' . $adjustment->adjustment_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('inventory.adjustments.index')">Back to List</x-ui.button>
        @if($adjustment->status === 'draft')
            <x-ui.button variant="primary" :href="route('inventory.adjustments.edit', $adjustment)">Edit Adjustment</x-ui.button>
        @endif
    </x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
    <x-ui.card>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Adjustment No</p>
        <p class="text-sm font-bold text-slate-900 mt-1 font-mono">{{ $adjustment->adjustment_no }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Warehouse</p>
        <p class="text-sm font-semibold text-slate-800 mt-1">{{ $adjustment->warehouse?->name ?? 'Unassigned' }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Reason</p>
        <p class="text-sm font-semibold text-slate-800 mt-1">{{ ucfirst($adjustment->reason) }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</p>
        <div class="mt-1">
            @if($adjustment->status === 'draft')
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                    Draft
                </span>
            @elseif($adjustment->status === 'posted')
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Posted
                </span>
            @else
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-slate-100 text-slate-600">
                    {{ ucfirst($adjustment->status) }}
                </span>
            @endif
        </div>
    </x-ui.card>
</div>

<x-ui.card title="Adjustment Line Items">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Product</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">UOM</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500">Adjustment Quantity</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($adjustment->items as $item)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-900">{{ $item->product?->name }}</span>
                            <span class="block text-xs font-mono text-slate-400">{{ $item->product?->sku }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 font-mono text-xs">{{ $item->uom?->code }}</td>
                        <td class="px-4 py-3 text-right font-mono font-bold text-xs {{ (float)$item->quantity > 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ (float)$item->quantity > 0 ? '+' : '' }}{{ number_format($item->quantity, 2) }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs">{{ $item->notes ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($adjustment->notes)
        <div class="mt-4 pt-4 border-t border-slate-100">
            <span class="text-xs font-semibold uppercase text-slate-500">Remarks / Notes</span>
            <p class="text-sm text-slate-700 mt-1 italic bg-slate-50 p-3 rounded-lg border border-slate-200">{{ $adjustment->notes }}</p>
        </div>
    @endif
</x-ui.card>
@endsection
