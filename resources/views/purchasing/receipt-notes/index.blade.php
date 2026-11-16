@extends('layouts.dms')
@section('title', 'Receipt Notes')
@section('content')
<div id="listing-container" class="space-y-6">
    <x-ui.page-header title="Receipt Notes">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('purchasing.receipt-notes.create')">
                <svg class="w-4 h-4 mr-1.5 inline -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                + New Receipt Note
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Receipt Notes</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($metrics['total'] ?? 0) }}</p>
                </div>
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Received (In Stock)</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($metrics['received'] ?? 0) }}</p>
                </div>
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Pending Invoice</p>
                    <p class="text-2xl font-bold text-sky-600 mt-1">{{ number_format($metrics['pending_invoice'] ?? 0) }}</p>
                </div>
                <div class="p-3 bg-sky-50 text-sky-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Returned</p>
                    <p class="text-2xl font-bold text-orange-600 mt-1">{{ number_format($metrics['returned'] ?? 0) }}</p>
                </div>
                <div class="p-3 bg-orange-50 text-orange-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Table & Filters Card --}}
    <x-ui.card>
        <form method="GET" action="{{ route('purchasing.receipt-notes.index') }}" class="p-4 border-b border-slate-200 bg-slate-50/50 flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search RN no, Challan no, Supplier..."
                    class="w-full rounded-lg border-slate-300 py-1.5 px-3 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
            </div>

            <select
                name="status"
                class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
            >
                <option value="">Status: All</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                <option value="received" @selected(request('status') === 'received')>Received (In Stock)</option>
                <option value="partially_invoiced" @selected(request('status') === 'partially_invoiced')>Partially Invoiced</option>
                <option value="fully_invoiced" @selected(request('status') === 'fully_invoiced')>Fully Invoiced</option>
                <option value="partially_returned" @selected(request('status') === 'partially_returned')>Partially Returned</option>
                <option value="fully_returned" @selected(request('status') === 'fully_returned')>Fully Returned</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
            </select>

            <select
                name="supplier_id"
                class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
            >
                <option value="">Supplier: All</option>
                @foreach($suppliers ?? [] as $s)
                    <option value="{{ $s->id }}" @selected((string) request('supplier_id') === (string) $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>

            <select
                name="warehouse_id"
                class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
            >
                <option value="">Warehouse: All</option>
                @foreach($warehouses ?? [] as $w)
                    <option value="{{ $w->id }}" @selected((string) request('warehouse_id') === (string) $w->id)>{{ $w->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium shadow-sm transition">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status', 'supplier_id', 'warehouse_id', 'date_from', 'date_to']))
                <a href="{{ route('purchasing.receipt-notes.index') }}" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-medium transition">
                    Reset
                </a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Receipt Note No</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Warehouse</th>
                        <th class="px-4 py-3">Supplier Challan</th>
                        <th class="px-4 py-3">PO Reference</th>
                        <th class="px-4 py-3 text-right">Received Qty</th>
                        <th class="px-4 py-3 text-right">Pending Invoice</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($receiptNotes as $note)
                        <tr class="hover:bg-slate-50/75 transition">
                            <td class="px-4 py-3 font-medium text-slate-900">
                                <a href="{{ route('purchasing.receipt-notes.show', $note) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold">
                                    {{ $note->receipt_note_no }}
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $note->receipt_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $note->supplier?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $note->warehouse?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($note->supplier_challan_no)
                                    <span class="font-medium text-slate-800">{{ $note->supplier_challan_no }}</span>
                                    @if($note->supplier_challan_date)
                                        <div class="text-[10px] text-slate-400">{{ $note->supplier_challan_date->format('d/m/Y') }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($note->purchaseOrder)
                                    <a href="{{ route('purchasing.orders.show', $note->purchaseOrder) }}" class="text-indigo-600 hover:underline">
                                        {{ $note->purchaseOrder->po_no }}
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-slate-900">
                                {{ number_format($note->total_received_qty, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium {{ $note->pending_invoice_qty > 0 ? 'text-sky-700' : 'text-slate-400' }}">
                                {{ number_format($note->pending_invoice_qty, 2) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $note->status_badge_class }}">
                                    {{ $note->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                                <a href="{{ route('purchasing.receipt-notes.show', $note) }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs transition">
                                    View
                                </a>
                                @if($note->isDraft())
                                    <a href="{{ route('purchasing.receipt-notes.edit', $note) }}" class="px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded text-xs transition">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('purchasing.receipt-notes.confirm', $note) }}" class="inline" onsubmit="return confirm('Confirm receipt and add physical material into warehouse stock?');">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs transition">
                                            Confirm
                                        </button>
                                    </form>
                                @endif
                                @if($note->canBeReturned())
                                    <a href="{{ route('purchasing.receipt-notes.return', $note) }}" class="px-2 py-1 bg-orange-50 hover:bg-orange-100 text-orange-700 rounded text-xs transition">
                                        Return
                                    </a>
                                @endif
                                <a href="{{ route('purchasing.receipt-notes.preview', $note) }}" target="_blank" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs transition" title="Print / PDF">
                                    Print
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600">No Receipt Notes found</p>
                                    <p class="text-xs text-slate-400 mt-1">Record physical receipt of goods from vendors without requiring an immediate Purchase Invoice.</p>
                                    <a href="{{ route('purchasing.receipt-notes.create') }}" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-medium hover:bg-indigo-700 transition">
                                        + Create First Receipt Note
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($receiptNotes->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $receiptNotes->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
