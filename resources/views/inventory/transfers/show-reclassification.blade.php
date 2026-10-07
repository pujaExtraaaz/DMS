@extends('layouts.dms')
@section('title', 'Stock Name Transfer - ' . $reclassification->reclassification_no)

@section('content')
<x-ui.page-header :title="'Stock Name Transfer: ' . $reclassification->reclassification_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('inventory.transfers.index', ['tab' => 'name'])">Back to List</x-ui.button>
        <x-ui.button variant="primary" :href="route('inventory.transfers.create', ['tab' => 'name'])">+ New Name Transfer</x-ui.button>
    </x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <x-ui.card>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Reference No</p>
        <p class="text-sm font-bold text-slate-900 mt-1 font-mono">{{ $reclassification->reclassification_no }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Warehouse / Location</p>
        <p class="text-sm font-semibold text-slate-800 mt-1">{{ $reclassification->warehouse?->name ?? '—' }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Transfer Date</p>
        <p class="text-sm font-semibold text-slate-800 mt-1">{{ $reclassification->reclassification_date?->format('d M Y') }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Performed By</p>
        <p class="text-sm font-semibold text-slate-800 mt-1">{{ $reclassification->creator?->name ?? 'System' }}</p>
    </x-ui.card>
</div>

<x-ui.card class="mb-6">
    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 border-b pb-2">Item Reclassification Details</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-4 rounded-xl bg-rose-50/60 border border-rose-200">
            <span class="text-xs font-semibold uppercase text-rose-700 tracking-wider">Source Item (Deducted)</span>
            <div class="mt-2 text-base font-bold text-slate-900">{{ $reclassification->fromProduct?->name }}</div>
            <div class="text-xs font-mono text-slate-500 mt-0.5">SKU: {{ $reclassification->fromProduct?->sku ?? '—' }}</div>
            <div class="mt-3 text-sm font-semibold text-rose-700 font-mono">
                -{{ number_format((float) $reclassification->quantity, 2) }} {{ $reclassification->uom?->code ?? '' }}
            </div>
        </div>

        <div class="flex flex-col items-center justify-center text-slate-400">
            <div class="p-3 bg-slate-100 rounded-full border border-slate-200 text-slate-600 font-bold text-lg mb-1">
                &rarr;
            </div>
            <span class="text-xs font-semibold text-slate-500 uppercase">Renamed / Reclassified</span>
        </div>

        <div class="p-4 rounded-xl bg-emerald-50/60 border border-emerald-200">
            <span class="text-xs font-semibold uppercase text-emerald-700 tracking-wider">Destination Item (Credited)</span>
            <div class="mt-2 text-base font-bold text-slate-900">{{ $reclassification->toProduct?->name }}</div>
            <div class="text-xs font-mono text-slate-500 mt-0.5">SKU: {{ $reclassification->toProduct?->sku ?? '—' }}</div>
            <div class="mt-3 text-sm font-semibold text-emerald-700 font-mono">
                +{{ number_format((float) $reclassification->quantity, 2) }} {{ $reclassification->uom?->code ?? '' }}
            </div>
        </div>
    </div>

    @if($reclassification->notes)
        <div class="mt-6 pt-4 border-t border-slate-100">
            <span class="text-xs font-semibold uppercase text-slate-500">Reason / Remarks</span>
            <p class="text-sm text-slate-700 mt-1 italic bg-slate-50 p-3 rounded-lg border border-slate-200">{{ $reclassification->notes }}</p>
        </div>
    @endif
</x-ui.card>

<x-ui.card title="Audit Trail: Stock Movements Recorded">
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs divide-y divide-slate-200">
            <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                <tr>
                    <th class="px-4 py-3 text-left">Date / Time</th>
                    <th class="px-4 py-3 text-left">Product</th>
                    <th class="px-4 py-3 text-left">Warehouse</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-right">Quantity</th>
                    <th class="px-4 py-3 text-right">Balance After</th>
                    <th class="px-4 py-3 text-left">Audit Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($reclassification->movements as $movement)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-mono text-slate-500">{{ $movement->created_at?->format('d M Y, H:i') }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $movement->product?->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $movement->warehouse?->name }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-slate-100 text-slate-700 border border-slate-200">
                                {{ str_replace('_', ' ', $movement->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-bold {{ (float)$movement->quantity >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ (float)$movement->quantity > 0 ? '+' : '' }}{{ number_format((float)$movement->quantity, 2) }} {{ $movement->uom?->code }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-bold text-slate-800">{{ number_format((float)$movement->balance_after, 2) }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $movement->notes }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-400">No stock movements found for this transaction.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection

