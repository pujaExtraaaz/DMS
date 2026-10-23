@extends('layouts.dms')

@section('title', 'Target Details: ' . $salesTarget->name)

@php
    $ach = $salesTarget->achievement;
    $achAmt = $ach ? (float) $ach->achieved_amount : 0.0;
    $percent = $ach ? (float) $ach->achievement_percent : 0.0;
    $pendingAmt = $ach ? (float) $ach->pending_amount : max(0, (float)$salesTarget->target_amount - $achAmt);
@endphp

@section('content')
<div class="space-y-6" x-data="{ carryForwardOpen: false, cancelOpen: false }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('sales-targets.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1">
                    &larr; Back to Targets
                </a>
            </div>
            <div class="flex items-center gap-2 mt-1">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $salesTarget->name }}</h1>
                <span class="text-xs font-mono text-slate-400">#TGT-{{ str_pad((string)$salesTarget->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500">
                Assigned to <span class="font-semibold text-slate-800">{{ $salesTarget->owner_name }}</span> ({{ ucfirst($salesTarget->owner_type) }}) &bull;
                {{ $salesTarget->starts_on->format('d M Y') }} &rarr; {{ $salesTarget->ends_on->format('d M Y') }}
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <a href="{{ route('sales-targets.edit', $salesTarget) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Revise Target
            </a>

            <a href="{{ route('sales-targets.create', ['copy_from' => $salesTarget->id]) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                Copy to Next Period
            </a>

            @if($salesTarget->status === 'active' && $pendingAmt > 0)
                <button type="button"
                        @click="carryForwardOpen = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 shadow-xs hover:bg-amber-100 transition">
                    Carry Forward
                </button>
            @endif

            @if($salesTarget->status === 'pending_approval')
                <form action="{{ route('sales-targets.approve', $salesTarget) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 transition">
                        ✓ Approve Target
                    </button>
                </form>
            @endif

            @if($salesTarget->status !== 'cancelled')
                <button type="button"
                        @click="cancelOpen = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 shadow-xs hover:bg-rose-100 transition">
                    Cancel
                </button>
            @endif
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Target Sales --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase">Target Goal</span>
            <div class="text-2xl font-bold text-slate-900 mt-1">₹ {{ number_format($salesTarget->target_amount, 2) }}</div>
            @if($salesTarget->target_quantity > 0)
                <p class="text-xs text-slate-500 mt-1">{{ number_format($salesTarget->target_quantity, 0) }} Units Goal</p>
            @endif
        </div>

        {{-- Card 2: Invoiced Achievement --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase">Actual Invoiced Sales</span>
            <div class="text-2xl font-bold text-emerald-700 mt-1">₹ {{ number_format($achAmt, 2) }}</div>
            <div class="mt-2 w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="h-1.5 rounded-full {{ $percent >= 100 ? 'bg-emerald-600' : 'bg-indigo-600' }}" style="width: {{ min(100, $percent) }}%"></div>
            </div>
        </div>

        {{-- Card 3: Achievement % --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase">Achievement %</span>
            <div class="text-2xl font-bold text-indigo-700 mt-1">{{ $percent }}%</div>
            <p class="text-xs text-slate-500 mt-1">
                {{ $percent >= 100 ? 'Target fully achieved!' : number_format(100 - $percent, 1) . '% remaining' }}
            </p>
        </div>

        {{-- Card 4: Shortfall / Pending --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase">Pending / Gap</span>
            <div class="text-2xl font-bold text-rose-700 mt-1">₹ {{ number_format($pendingAmt, 2) }}</div>
            <p class="text-xs text-slate-500 mt-1">{{ $ach ? $ach->invoices_count : 0 }} Contributing Invoices</p>
        </div>
    </div>

    {{-- Details & Scope Grid --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">
            Target Scope &amp; Master Criteria
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block mb-0.5">Target Type</span>
                <span class="font-semibold text-slate-800">{{ ucfirst($salesTarget->target_type) }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Frequency</span>
                <span class="font-semibold text-slate-800">{{ ucfirst(str_replace('_', ' ', $salesTarget->period_type)) }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Brand Filter</span>
                <span class="font-semibold text-slate-800">{{ $salesTarget->brand?->name ?? 'Any / Unrestricted' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Product Filter</span>
                <span class="font-semibold text-slate-800">{{ $salesTarget->product?->name ?? 'Any / Unrestricted' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Category</span>
                <span class="font-semibold text-slate-800">{{ $salesTarget->category?->name ?? 'Any' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Created By</span>
                <span class="font-semibold text-slate-800">{{ $salesTarget->creator?->name ?? 'System' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Approved By</span>
                <span class="font-semibold text-slate-800">{{ $salesTarget->approver?->name ?? 'Pending' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Last Calculated</span>
                <span class="font-semibold text-slate-800">{{ $ach?->last_calculated_at?->diffForHumans() ?? 'Never' }}</span>
            </div>
        </div>

        @if($salesTarget->notes)
            <div class="pt-2 border-t border-slate-100 text-xs">
                <span class="text-slate-400 block font-semibold mb-0.5">Notes:</span>
                <p class="text-slate-700">{{ $salesTarget->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Multi-Criteria Line Items (if present) --}}
    @if($salesTarget->items->isNotEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-3">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">
                Criteria Line Items Breakdown
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                    <thead class="bg-slate-50 text-slate-600 font-semibold">
                        <tr>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Brand / Product</th>
                            <th class="px-3 py-2 text-right">Target Qty</th>
                            <th class="px-3 py-2 text-right">Achieved Qty</th>
                            <th class="px-3 py-2 text-right">Target Value (₹)</th>
                            <th class="px-3 py-2 text-right">Achieved Value (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($salesTarget->items as $item)
                            <tr>
                                <td class="px-3 py-2 font-medium">{{ ucfirst($item->target_type) }}</td>
                                <td class="px-3 py-2">{{ $item->product?->name ?? ($item->brand?->name ?? ($item->category?->name ?? '—')) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($item->target_quantity, 0) }}</td>
                                <td class="px-3 py-2 text-right text-emerald-700 font-semibold">{{ number_format($item->achieved_quantity, 0) }}</td>
                                <td class="px-3 py-2 text-right">₹ {{ number_format($item->target_amount, 2) }}</td>
                                <td class="px-3 py-2 text-right text-emerald-700 font-bold">₹ {{ number_format($item->achieved_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Contributing Sales Invoices Drill-down --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700">Contributing Sales Invoices</h2>
                <p class="text-[11px] text-slate-500">Live source invoices from which target achievement is calculated.</p>
            </div>
            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-md">
                Total: {{ $invoices->total() }} Invoices
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th class="px-3 py-2">Invoice No</th>
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Customer</th>
                        <th class="px-3 py-2">Salesperson</th>
                        <th class="px-3 py-2 text-right">Taxable Subtotal</th>
                        <th class="px-3 py-2 text-right">Grand Total (₹)</th>
                        <th class="px-3 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-mono font-semibold text-indigo-600">
                                <a href="{{ route('sales.invoices.show', $inv) }}" class="hover:underline">
                                    {{ $inv->invoice_no }}
                                </a>
                            </td>
                            <td class="px-3 py-2 text-slate-600">{{ $inv->invoice_date?->format('d M Y') }}</td>
                            <td class="px-3 py-2 font-medium text-slate-800">{{ $inv->customer?->name }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $inv->salesperson?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">₹ {{ number_format($inv->subtotal, 2) }}</td>
                            <td class="px-3 py-2 text-right font-bold text-slate-900">₹ {{ number_format($inv->grand_total, 2) }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold bg-emerald-50 text-emerald-700">{{ ucfirst($inv->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-slate-400">
                                No sales invoices have been posted in this target timeframe for this owner yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>
            {{ $invoices->links() }}
        </div>
    </div>

    {{-- Audit Log & Revision History --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-3">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">
            Audit Trail &amp; Revision Governance
        </h2>
        <div class="divide-y divide-slate-100 space-y-2">
            @forelse($salesTarget->audits as $audit)
                <div class="pt-2 first:pt-0 flex items-start justify-between text-xs">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">{{ $audit->action }}</span>
                            <span class="text-slate-400">&bull;</span>
                            <span class="text-slate-600">by {{ $audit->user?->name ?? 'System' }}</span>
                            <span class="text-slate-400">&bull;</span>
                            <span class="text-slate-400 text-[11px]">{{ $audit->created_at->format('d M Y, h:i A') }}</span>
                        </div>
                        @if($audit->reason)
                            <p class="text-slate-600 mt-1 italic font-serif">"{{ $audit->reason }}"</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400">No revisions or status modifications recorded.</p>
            @endforelse
        </div>
    </div>

    {{-- Carry Forward Modal --}}
    <div x-show="carryForwardOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4" @click.outside="carryForwardOpen = false">
            <h3 class="text-base font-bold text-slate-900">Carry Forward Unachieved Target</h3>
            <p class="text-xs text-slate-600">
                You are carrying forward the unachieved balance of <strong class="text-slate-900">₹ {{ number_format($pendingAmt, 2) }}</strong> to a new period.
            </p>

            <form action="{{ route('sales-targets.carry-forward', $salesTarget) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">New Start Date</label>
                    <input type="date" name="next_starts_on" required value="{{ $salesTarget->ends_on->copy()->addDay()->format('Y-m-d') }}" class="w-full rounded-lg border-gray-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">New End Date</label>
                    <input type="date" name="next_ends_on" required value="{{ $salesTarget->ends_on->copy()->addMonth()->format('Y-m-d') }}" class="w-full rounded-lg border-gray-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Authorization Reason <span class="text-rose-500">*</span></label>
                    <textarea name="authorization_reason" required rows="2" placeholder="Management approval reason..." class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="carryForwardOpen = false" class="rounded-lg border px-3 py-1.5 text-xs font-semibold text-slate-600">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white">Confirm Carry Forward</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Cancel Modal --}}
    <div x-show="cancelOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4" @click.outside="cancelOpen = false">
            <h3 class="text-base font-bold text-slate-900">Cancel Target</h3>
            <p class="text-xs text-slate-600">Are you sure you want to cancel this target? This action will be recorded in the audit trail.</p>

            <form action="{{ route('sales-targets.cancel', $salesTarget) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cancellation Reason <span class="text-rose-500">*</span></label>
                    <textarea name="reason" required rows="2" placeholder="Reason for cancellation..." class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="cancelOpen = false" class="rounded-lg border px-3 py-1.5 text-xs font-semibold text-slate-600">Back</button>
                    <button type="submit" class="rounded-lg bg-rose-600 px-4 py-1.5 text-xs font-semibold text-white">Confirm Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

