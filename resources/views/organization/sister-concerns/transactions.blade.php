@extends('layouts.dms')
@section('title', 'Sister Concern Management · Inter-Company Transactions')

@section('content')
<div class="max-w-7xl mx-auto pb-12"
     x-data="interCompanyTransactionsManager()"
     @open-add-transaction-modal.window="openAddModal()">

    {{-- Notification Toast --}}
    <div x-cloak
         x-show="notification.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed top-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl border text-sm max-w-md"
         :class="notification.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-red-50 border-red-200 text-red-900'">
        <div class="shrink-0 w-8 h-8 rounded-xl flex items-center justify-center font-bold"
             :class="notification.type === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
            <span x-text="notification.type === 'success' ? '✓' : '!'"></span>
        </div>
        <div class="flex-1 text-xs font-medium" x-text="notification.message"></div>
        <button type="button" @click="notification.show = false" class="text-slate-400 hover:text-slate-600">✕</button>
    </div>

    {{-- Header Banner & Navigation Tabs --}}
    @include('organization.sister-concerns.partials.header', [
        'activeTab' => 'transactions',
        'tabCounts' => $tabCounts,
    ])

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Card 1: Total Transactions --}}
        <div class="bg-white rounded-2xl p-5 border border-purple-100 shadow-sm relative overflow-hidden group hover:border-purple-300 transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-purple-700">Total Transactions</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-slate-900">{{ number_format($summary['total_transactions'] ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Inter-company trade & funds</div>
        </div>

        {{-- Card 2: Total Value --}}
        <div class="bg-white rounded-2xl p-5 border border-indigo-100 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">Total Transaction Value</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            @php
                $val = (float) ($summary['total_value'] ?? 0);
                $valFormatted = $val >= 100000 ? '₹' . number_format($val / 100000, 2) . ' L' : '₹' . number_format($val, 2);
            @endphp
            <div class="text-2xl font-extrabold text-slate-900">{{ $valFormatted }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Cumulative settled & active net</div>
        </div>

        {{-- Card 3: Pending --}}
        <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-sm relative overflow-hidden group hover:border-amber-300 transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Pending Review</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-amber-700">{{ number_format($summary['pending_count'] ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Awaiting approval or settlement</div>
        </div>

        {{-- Card 4: Settled --}}
        <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Settled Transactions</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-emerald-700">{{ number_format($summary['settled_count'] ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Fully reconciled & settled</div>
        </div>
    </div>

    {{-- Main Transactions Container --}}
    <x-ui.card>
        {{-- Toolbar: Filters & Add Action --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5 pb-5 border-b border-slate-100">
            <form method="GET" action="{{ route('organization.sister-concerns.transactions') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
                {{-- Search --}}
                <div class="relative min-w-[220px] sm:min-w-[260px] flex-1">
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Search Transaction No, Invoice, Company..."
                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                </div>

                {{-- Type Filter --}}
                <div>
                    <select name="transaction_type" class="rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                        <option value="">All Types</option>
                        @foreach($transactionTypes as $tt)
                            <option value="{{ $tt }}" @selected(($selectedType ?? '') === $tt)>{{ $tt }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div>
                    <select name="status" class="rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                        <option value="">All Statuses</option>
                        <option value="Pending" @selected(($selectedStatus ?? '') === 'Pending')>Pending</option>
                        <option value="Approved" @selected(($selectedStatus ?? '') === 'Approved')>Approved</option>
                        <option value="Settled" @selected(($selectedStatus ?? '') === 'Settled')>Settled</option>
                        <option value="Cancelled" @selected(($selectedStatus ?? '') === 'Cancelled')>Cancelled</option>
                    </select>
                </div>

                {{-- Filter & Reset --}}
                <button type="submit" class="px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                    Filter
                </button>
                @if(!empty($search) || !empty($selectedStatus) || !empty($selectedType))
                    <a href="{{ route('organization.sister-concerns.transactions') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-medium transition">
                        Reset
                    </a>
                @endif
            </form>

            {{-- + New Transaction Button --}}
            <div class="shrink-0">
                <button type="button"
                        @click="openAddModal()"
                        class="inline-flex items-center gap-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 text-xs font-semibold shadow-xs transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ New Transaction</span>
                </button>
            </div>
        </div>

        {{-- Table Container --}}
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 uppercase tracking-wider">Transaction</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 uppercase tracking-wider">From</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 uppercase tracking-wider">To</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600 uppercase tracking-wider">Amount</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600 uppercase tracking-wider">Net (₹)</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $tx)
                        @php
                            $isModel = $tx instanceof \App\Domains\Organization\Models\InterCompanyTransaction;
                            $txId = $tx->id;
                            $txNo = $isModel ? $tx->transaction_no : ($tx->reference_no ?? 'ICT-' . $tx->id);
                            $txDate = $isModel ? $tx->transaction_date?->format('d M Y') : ($tx->transaction_date ? \Illuminate\Support\Carbon::parse($tx->transaction_date)->format('d M Y') : '—');
                            $txType = $isModel ? $tx->transaction_type : 'Sale';
                            $fromName = $isModel ? ($tx->fromCompany?->name ?? '—') : ($tx->source_entity ?? '—');
                            $fromEmoji = $isModel ? ($tx->fromCompany?->logo_emoji ?? '🏢') : '🏢';
                            $toName = $isModel ? ($tx->toCompany?->name ?? '—') : ($tx->destination_entity ?? '—');
                            $toEmoji = $isModel ? ($tx->toCompany?->logo_emoji ?? '🏢') : '🏢';
                            $amt = (float) $tx->amount;
                            $curr = $isModel ? ($tx->currency ?? 'INR') : 'INR';
                            $netAmt = $isModel ? (float) $tx->net_amount : (float) $tx->amount;
                            $status = $tx->status;

                            $typeColor = match($txType) {
                                'Sale' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'Purchase' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'Service' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'Loan Given' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'Loan Received' => 'bg-teal-50 text-teal-700 border-teal-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };

                            $statusVariant = match(strtolower($status)) {
                                'settled', 'completed', 'converted', 'paid' => 'emerald',
                                'approved' => 'indigo',
                                'pending', 'issued', 'partial' => 'amber',
                                'cancelled' => 'rose',
                                default => 'gray'
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            {{-- Transaction Ref --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-mono font-bold text-slate-900">{{ $txNo }}</div>
                                @if($isModel && ($tx->reference_invoice || $tx->reference_po))
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        {{ $tx->reference_invoice ? 'Inv: ' . $tx->reference_invoice : '' }}
                                        {{ $tx->reference_po ? ($tx->reference_invoice ? ' • ' : '') . 'PO: ' . $tx->reference_po : '' }}
                                    </div>
                                @endif
                            </td>

                            {{-- Date --}}
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                {{ $txDate }}
                            </td>

                            {{-- Type --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ $typeColor }}">
                                    {{ $txType }}
                                </span>
                            </td>

                            {{-- From --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm">{{ $fromEmoji }}</span>
                                    <span class="font-medium text-slate-900 truncate max-w-[160px]" title="{{ $fromName }}">{{ $fromName }}</span>
                                </div>
                            </td>

                            {{-- To --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm">{{ $toEmoji }}</span>
                                    <span class="font-medium text-slate-900 truncate max-w-[160px]" title="{{ $toName }}">{{ $toName }}</span>
                                </div>
                            </td>

                            {{-- Amount --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap text-slate-600 font-mono">
                                <div>{{ $curr }} {{ number_format($amt, 2) }}</div>
                                @if($isModel && $curr !== 'INR' && $tx->exchange_rate > 1)
                                    <div class="text-[10px] text-slate-400 font-sans">Rate: {{ number_format($tx->exchange_rate, 2) }}</div>
                                @endif
                            </td>

                            {{-- Net Amount (₹) --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="font-bold text-slate-900 font-mono">₹{{ number_format($netAmt, 2) }}</div>
                                @if($isModel && ($tx->is_gst_applicable || $tx->is_tds_applicable))
                                    <div class="text-[10px] text-slate-400 font-sans">
                                        @if($tx->is_gst_applicable) +GST: ₹{{ number_format($tx->igst_amount, 2) }} @endif
                                        @if($tx->is_tds_applicable) -TDS: ₹{{ number_format($tx->tds_amount, 2) }} @endif
                                    </div>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <x-ui.badge :variant="$statusVariant">
                                    {{ ucfirst($status) }}
                                </x-ui.badge>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- View --}}
                                    <button type="button"
                                            @click="viewTransaction('{{ $txId }}')"
                                            class="inline-flex items-center gap-1 font-semibold rounded-lg px-2.5 py-1.5 text-xs text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 shadow-2xs transition"
                                            title="View Transaction Details">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>View</span>
                                    </button>

                                    @if($isModel)
                                        {{-- Edit --}}
                                        <button type="button"
                                                @click="editTransaction('{{ $txId }}')"
                                                class="inline-flex items-center gap-1 font-semibold rounded-lg px-2.5 py-1.5 text-xs text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 shadow-2xs transition"
                                                title="Edit Transaction">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            <span>Edit</span>
                                        </button>

                                        {{-- Delete --}}
                                        <button type="button"
                                                @click="promptDelete('{{ $txId }}', '{{ $txNo }}')"
                                                class="inline-flex items-center gap-1 font-medium rounded-lg text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 px-2 py-1.5 text-xs transition"
                                                title="Delete Transaction">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                <div class="max-w-sm mx-auto text-center">
                                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900">No Transactions Found</h4>
                                    <p class="text-xs text-slate-500 mt-1">Record a new inter-company trade, service, or fund transfer.</p>
                                    <button type="button"
                                            @click="openAddModal()"
                                            class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-3.5 py-1.5 text-xs font-semibold shadow-xs transition">
                                        + Record Inter-Company Transaction
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($items->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $items->links() }}
            </div>
        @endif
    </x-ui.card>

    {{-- ========================================================================= --}}
    {{-- ADD / EDIT TRANSACTION MODAL                                              --}}
    {{-- ========================================================================= --}}
    <div x-cloak
         x-show="showFormModal"
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">

        {{-- Backdrop --}}
        <div x-show="showFormModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeFormModal()"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
            <div x-show="showFormModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl border border-slate-100 flex flex-col">

                {{-- Fixed Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900" id="modal-title" x-text="isEditMode ? 'Edit Inter-Company Transaction' : 'New Inter-Company Transaction'"></h3>
                            <p class="text-xs text-slate-500">Record sales, purchases, services, or loans between sister concerns</p>
                        </div>
                    </div>
                    <button type="button"
                            @click="closeFormModal()"
                            class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Scrollable Form Body --}}
                <form @submit.prevent="submitForm()" class="flex flex-col">
                    <div class="p-6 space-y-6 max-h-[calc(86vh-130px)] overflow-y-auto">

                        {{-- Validation Summary Alert --}}
                        <div x-show="formErrorSummary" class="p-3.5 bg-red-50 border border-red-200 text-red-800 rounded-xl text-xs flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span x-text="formErrorSummary"></span>
                        </div>

                        {{-- ============================================================= --}}
                        {{-- 1. TRANSACTION DETAILS                                         --}}
                        {{-- ============================================================= --}}
                        <div>
                            <div class="flex items-center justify-between pb-2 mb-4 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section 1</span>
                                    <h4 class="text-xs font-bold uppercase tracking-wide text-slate-800">Transaction Details</h4>
                                </div>
                                <span class="text-[11px] text-slate-400 font-normal">* Indicates required fields</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {{-- Transaction Date --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Transaction Date <span class="text-red-500">*</span></label>
                                    <input type="date"
                                           x-model="form.transaction_date"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs"
                                           :class="errors.transaction_date ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.transaction_date" class="text-[11px] text-red-600 mt-0.5" x-text="errors.transaction_date"></p>
                                </div>

                                {{-- Transaction Type --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Transaction Type <span class="text-red-500">*</span></label>
                                    <select x-model="form.transaction_type"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs"
                                            :class="errors.transaction_type ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                        <option value="">-- Select Transaction Type --</option>
                                        <template x-for="t in transactionTypes" :key="t">
                                            <option :value="t" x-text="t"></option>
                                        </template>
                                    </select>
                                    <p x-show="errors.transaction_type" class="text-[11px] text-red-600 mt-0.5" x-text="errors.transaction_type"></p>
                                </div>

                                {{-- Transaction Reference / Number (Readonly or Auto) --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Transaction No.</label>
                                    <input type="text"
                                           x-model="form.transaction_no"
                                           placeholder="Auto-generated (e.g. ICT-GLOBAL-00001)"
                                           class="w-full rounded-xl border-slate-200 text-xs font-mono uppercase bg-slate-50 focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :readonly="isEditMode">
                                </div>

                                {{-- From Company --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">From Company <span class="text-red-500">*</span></label>
                                    <select x-model="form.from_company_id"
                                            @change="validateCounterparties()"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs"
                                            :class="errors.from_company_id ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                        <option value="">-- Select Sender Company --</option>
                                        <template x-for="co in eligibleCompanies" :key="co.id">
                                            <option :value="co.id"
                                                    :disabled="String(co.id) === String(form.to_company_id)"
                                                    x-text="(co.logo_emoji ? co.logo_emoji + ' ' : '') + co.name + ' (' + co.code + ')'"></option>
                                        </template>
                                    </select>
                                    <p x-show="errors.from_company_id" class="text-[11px] text-red-600 mt-0.5" x-text="errors.from_company_id"></p>
                                </div>

                                {{-- To Company --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">To Company <span class="text-red-500">*</span></label>
                                    <select x-model="form.to_company_id"
                                            @change="validateCounterparties()"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs"
                                            :class="errors.to_company_id ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                        <option value="">-- Select Receiver Company --</option>
                                        <template x-for="co in eligibleCompanies" :key="co.id">
                                            <option :value="co.id"
                                                    :disabled="String(co.id) === String(form.from_company_id)"
                                                    x-text="(co.logo_emoji ? co.logo_emoji + ' ' : '') + co.name + ' (' + co.code + ')'"></option>
                                        </template>
                                    </select>
                                    <p x-show="errors.to_company_id" class="text-[11px] text-red-600 mt-0.5" x-text="errors.to_company_id"></p>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================= --}}
                        {{-- 2. AMOUNT DETAILS                                              --}}
                        {{-- ============================================================= --}}
                        <div class="pt-2">
                            <div class="flex items-center justify-between pb-2 mb-4 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section 2</span>
                                    <h4 class="text-xs font-bold uppercase tracking-wide text-slate-800">Amount Details</h4>
                                </div>
                                <span class="text-[11px] text-slate-400 font-normal">Calculates net base amount, taxes, and TDS deductions</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {{-- Amount --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Amount <span class="text-red-500">*</span></label>
                                    <div class="relative rounded-xl shadow-2xs">
                                        <input type="number"
                                               step="0.01"
                                               min="0.01"
                                               x-model="form.amount"
                                               @input="handleAmountChange()"
                                               placeholder="0.00"
                                               class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 font-mono"
                                               :class="errors.amount ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    </div>
                                    <p x-show="errors.amount" class="text-[11px] text-red-600 mt-0.5" x-text="errors.amount"></p>
                                </div>

                                {{-- Currency --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Currency</label>
                                    <select x-model="form.currency"
                                            @change="handleCurrencyChange()"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                        <template x-for="c in currencies" :key="c">
                                            <option :value="c" x-text="c"></option>
                                        </template>
                                    </select>
                                </div>

                                {{-- Exchange Rate --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Exchange Rate</label>
                                    <input type="number"
                                           step="0.0001"
                                           min="0.0001"
                                           x-model="form.exchange_rate"
                                           @input="handleAmountChange()"
                                           :readonly="form.currency === 'INR'"
                                           placeholder="1.0000"
                                           class="w-full rounded-xl border-slate-200 text-xs font-mono focus:border-purple-500 focus:ring-purple-500 shadow-2xs"
                                           :class="form.currency === 'INR' ? 'bg-slate-50 text-slate-500' : ''">
                                </div>

                                {{-- GST Applicable Checkbox & IGST Amount --}}
                                <div class="space-y-2 lg:col-span-3 bg-purple-50/30 p-3.5 rounded-xl border border-purple-100">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox"
                                               id="is_gst_applicable"
                                               x-model="form.is_gst_applicable"
                                               @change="handleGstToggle()"
                                               class="rounded border-slate-300 text-purple-600 focus:ring-purple-500 h-4 w-4">
                                        <label for="is_gst_applicable" class="text-xs font-semibold text-slate-800 cursor-pointer select-none">
                                            GST Applicable on this transaction
                                        </label>
                                    </div>

                                    <div x-show="form.is_gst_applicable" x-transition class="pt-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="space-y-1">
                                            <label class="block text-xs font-semibold text-slate-700">IGST (₹)</label>
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   x-model="form.igst_amount"
                                                   placeholder="0.00"
                                                   class="w-full rounded-xl border-slate-200 text-xs font-mono focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                            <p class="text-[10px] text-slate-400">Integrated Goods & Services Tax amount</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- TDS Applicable Checkbox, Section, Rate & Amount --}}
                                <div class="space-y-2 lg:col-span-3 bg-indigo-50/30 p-3.5 rounded-xl border border-indigo-100">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox"
                                               id="is_tds_applicable"
                                               x-model="form.is_tds_applicable"
                                               @change="handleTdsToggle()"
                                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                        <label for="is_tds_applicable" class="text-xs font-semibold text-slate-800 cursor-pointer select-none">
                                            TDS Applicable (Tax Deducted at Source)
                                        </label>
                                    </div>

                                    <div x-show="form.is_tds_applicable" x-transition class="pt-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="space-y-1">
                                            <label class="block text-xs font-semibold text-slate-700">TDS Section</label>
                                            <select x-model="form.tds_section"
                                                    @change="handleTdsSectionChange()"
                                                    class="w-full rounded-xl border-slate-200 text-xs focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                                <option value="">-- Select TDS Section --</option>
                                                <template x-for="sec in tdsSections" :key="sec.code">
                                                    <option :value="sec.code" x-text="sec.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="space-y-1">
                                            <label class="block text-xs font-semibold text-slate-700">TDS Rate (%)</label>
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   max="100"
                                                   x-model="form.tds_rate"
                                                   @input="handleTdsRateChange()"
                                                   placeholder="e.g. 2.0"
                                                   class="w-full rounded-xl border-slate-200 text-xs font-mono focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                        </div>

                                        <div class="space-y-1">
                                            <label class="block text-xs font-semibold text-slate-700">TDS Amount (₹)</label>
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   x-model="form.tds_amount"
                                                   placeholder="0.00"
                                                   class="w-full rounded-xl border-slate-200 text-xs font-mono focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs">
                                        </div>
                                    </div>
                                </div>

                                {{-- Live Net Amount Preview Card --}}
                                <div class="lg:col-span-3 bg-gradient-to-r from-purple-50 via-indigo-50 to-purple-50 p-4 rounded-xl border border-purple-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700">Net Calculated Amount</span>
                                        <div class="text-xl sm:text-2xl font-extrabold text-slate-900 font-mono">
                                            ₹ <span x-text="computedNetAmount"></span>
                                        </div>
                                    </div>
                                    <div class="text-[11px] text-slate-500 space-y-0.5 text-left sm:text-right font-mono">
                                        <div>Base: ₹ <span x-text="computedBaseAmount"></span></div>
                                        <div x-show="form.is_gst_applicable" class="text-emerald-700">+ GST: ₹ <span x-text="(parseFloat(form.igst_amount) || 0).toFixed(2)"></span></div>
                                        <div x-show="form.is_tds_applicable" class="text-red-600">- TDS: ₹ <span x-text="(parseFloat(form.tds_amount) || 0).toFixed(2)"></span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================= --}}
                        {{-- 3. REFERENCES & SETTLEMENT                                     --}}
                        {{-- ============================================================= --}}
                        <div class="pt-2">
                            <div class="flex items-center gap-2 pb-2 mb-4 border-b border-slate-100">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section 3</span>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-800">References &amp; Settlement</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {{-- Reference Invoice --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Reference Invoice</label>
                                    <input type="text"
                                           x-model="form.reference_invoice"
                                           placeholder="e.g. INV-2026-0891"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- Reference PO --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Reference PO</label>
                                    <input type="text"
                                           x-model="form.reference_po"
                                           placeholder="e.g. PO-SC-0045"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- Due Date --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Due Date</label>
                                    <input type="date"
                                           x-model="form.due_date"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                </div>

                                {{-- Status --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Status <span class="text-red-500">*</span></label>
                                    <select x-model="form.status"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                        <option value="Pending">Pending</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Settled">Settled</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================= --}}
                        {{-- 4. ADDITIONAL INFORMATION                                      --}}
                        {{-- ============================================================= --}}
                        <div class="pt-2">
                            <div class="flex items-center gap-2 pb-2 mb-4 border-b border-slate-100">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section 4</span>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-800">Additional Information</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {{-- Approved By --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Approved By</label>
                                    <select x-model="form.approved_by"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                        <option value="">-- None / Select Approver --</option>
                                        <template x-for="u in eligibleUsers" :key="u.id">
                                            <option :value="u.id" x-text="u.name + ' (' + u.email + ')'"></option>
                                        </template>
                                    </select>
                                </div>

                                {{-- Narration --}}
                                <div class="space-y-1 md:col-span-2 lg:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700">Narration</label>
                                    <textarea x-model="form.narration"
                                              rows="2"
                                              placeholder="General transaction remarks or purpose..."
                                              class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"></textarea>
                                </div>

                                {{-- Notes --}}
                                <div class="space-y-1 lg:col-span-3">
                                    <label class="block text-xs font-semibold text-slate-700">Internal Audit Notes</label>
                                    <textarea x-model="form.notes"
                                              rows="2"
                                              placeholder="Optional internal compliance notes..."
                                              class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"></textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Fixed Modal Footer --}}
                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                        <button type="button"
                                @click="closeFormModal()"
                                :disabled="isSaving"
                                class="px-4 py-2 text-xs font-semibold text-slate-700 hover:text-slate-800 hover:bg-slate-200 rounded-xl transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                :disabled="isSaving"
                                class="inline-flex items-center gap-2 px-5 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded-xl shadow-xs transition disabled:opacity-50 cursor-pointer">
                            <svg x-show="isSaving" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isSaving ? 'Processing...' : (isEditMode ? 'Update Transaction' : 'Create Transaction')"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- VIEW DETAILS MODAL                                                        --}}
    {{-- ========================================================================= --}}
    <div x-cloak
         x-show="showViewModal"
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true">

        <div x-show="showViewModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showViewModal = false"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
            <div x-show="showViewModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl border border-slate-100">

                <template x-if="viewData">
                    <div>
                        {{-- Header Banner --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/80">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-bold text-slate-900" x-text="viewData.transaction_no || viewData.reference_no"></h3>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                              :class="{
                                                  'bg-emerald-50 text-emerald-700 border border-emerald-200': (viewData.status || '').toLowerCase() === 'settled',
                                                  'bg-amber-50 text-amber-700 border border-amber-200': (viewData.status || '').toLowerCase() === 'pending',
                                                  'bg-indigo-50 text-indigo-700 border border-indigo-200': (viewData.status || '').toLowerCase() === 'approved',
                                                  'bg-rose-50 text-rose-700 border border-rose-200': (viewData.status || '').toLowerCase() === 'cancelled'
                                              }"
                                              x-text="viewData.status"></span>
                                    </div>
                                    <p class="text-xs text-slate-500" x-text="'Type: ' + (viewData.transaction_type || 'Sale') + ' • Date: ' + (viewData.transaction_date || '—')"></p>
                                </div>
                            </div>
                            <button type="button" @click="showViewModal = false" class="text-slate-400 hover:text-slate-600 rounded-lg p-1.5 hover:bg-slate-100 transition cursor-pointer">✕</button>
                        </div>

                        {{-- Body --}}
                        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto text-xs">
                            {{-- Counterparties Flow --}}
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div class="text-center sm:text-left flex-1">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">From Company</span>
                                    <div class="font-bold text-slate-900 text-sm mt-0.5" x-text="viewData.from_company ? viewData.from_company.name : (viewData.source_entity || '—')"></div>
                                    <div class="text-[11px] text-slate-500 font-mono" x-text="viewData.from_company ? viewData.from_company.code : ''"></div>
                                </div>
                                <div class="flex flex-col items-center shrink-0">
                                    <span class="text-xs font-bold text-purple-700 bg-purple-100/70 px-2.5 py-1 rounded-full">➔</span>
                                    <span class="text-[10px] text-slate-400 mt-0.5" x-text="viewData.transaction_type || 'Transfer'"></span>
                                </div>
                                <div class="text-center sm:text-right flex-1">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">To Company</span>
                                    <div class="font-bold text-slate-900 text-sm mt-0.5" x-text="viewData.to_company ? viewData.to_company.name : (viewData.destination_entity || '—')"></div>
                                    <div class="text-[11px] text-slate-500 font-mono" x-text="viewData.to_company ? viewData.to_company.code : ''"></div>
                                </div>
                            </div>

                            {{-- Financials Summary --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 rounded-xl bg-purple-50/50 border border-purple-100">
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-semibold">Amount</div>
                                    <div class="text-sm font-bold text-slate-900 font-mono" x-text="(viewData.currency || 'INR') + ' ' + Number(viewData.amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-semibold">Exchange Rate</div>
                                    <div class="text-sm font-bold text-slate-900 font-mono" x-text="viewData.exchange_rate || '1.0000'"></div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-semibold">Taxes & TDS</div>
                                    <div class="text-xs text-slate-700 font-mono">
                                        <span x-text="viewData.is_gst_applicable ? '+GST: ₹' + Number(viewData.igst_amount || 0).toFixed(2) : 'No GST'"></span>
                                        <br>
                                        <span x-text="viewData.is_tds_applicable ? '-TDS: ₹' + Number(viewData.tds_amount || 0).toFixed(2) : 'No TDS'"></span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-purple-700 uppercase font-bold">Net Amount</div>
                                    <div class="text-base font-extrabold text-purple-900 font-mono" x-text="'₹ ' + Number(viewData.net_amount || viewData.amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></div>
                                </div>
                            </div>

                            {{-- References & Approver --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <h5 class="font-bold text-slate-900 uppercase tracking-wide text-[11px] pb-1 border-b border-slate-100">References</h5>
                                    <div><span class="text-slate-400">Invoice:</span> <span class="font-medium text-slate-800" x-text="viewData.reference_invoice || '—'"></span></div>
                                    <div><span class="text-slate-400">PO:</span> <span class="font-medium text-slate-800" x-text="viewData.reference_po || '—'"></span></div>
                                    <div><span class="text-slate-400">Due Date:</span> <span class="font-medium text-slate-800" x-text="viewData.due_date || '—'"></span></div>
                                </div>
                                <div class="space-y-2">
                                    <h5 class="font-bold text-slate-900 uppercase tracking-wide text-[11px] pb-1 border-b border-slate-100">Audit & Approvals</h5>
                                    <div><span class="text-slate-400">Approved By:</span> <span class="font-medium text-slate-800" x-text="viewData.approver ? viewData.approver.name : '—'"></span></div>
                                    <div><span class="text-slate-400">Created:</span> <span class="font-medium text-slate-800" x-text="viewData.created_at || '—'"></span></div>
                                </div>
                            </div>

                            {{-- Remarks --}}
                            <div class="space-y-2" x-show="viewData.narration || viewData.notes">
                                <h5 class="font-bold text-slate-900 uppercase tracking-wide text-[11px] pb-1 border-b border-slate-100">Remarks & Notes</h5>
                                <div x-show="viewData.narration" class="text-slate-600 bg-slate-50 p-2.5 rounded-lg" x-text="viewData.narration"></div>
                                <div x-show="viewData.notes" class="text-slate-500 italic text-[11px]" x-text="'Note: ' + viewData.notes"></div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex justify-end">
                            <button type="button" @click="showViewModal = false" class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                                Close
                            </button>
                        </div>
                    </div>
                </template>

            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- DELETE CONFIRMATION MODAL                                                 --}}
    {{-- ========================================================================= --}}
    <div x-cloak
         x-show="showDeleteModal"
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true">

        <div x-show="showDeleteModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="cancelDelete()"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-100 p-6">

                <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>

                <h3 class="text-base font-bold text-center text-slate-900">Delete Inter-Company Transaction?</h3>
                <p class="text-xs text-center text-slate-500 mt-2">
                    Are you sure you want to delete transaction <strong class="text-slate-800 font-mono" x-text="deletingTransactionNo"></strong>? This action cannot be undone.
                </p>

                <div class="mt-6 flex items-center justify-center gap-3">
                    <button type="button"
                            @click="cancelDelete()"
                            :disabled="isDeleting"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="button"
                            @click="executeDelete()"
                            :disabled="isDeleting"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-xs transition disabled:opacity-50 cursor-pointer">
                        <svg x-show="isDeleting" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isDeleting ? 'Deleting...' : 'Confirm Delete'"></span>
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>

{{-- Alpine.js Management Script --}}
<script>
function interCompanyTransactionsManager() {
    return {
        showFormModal: false,
        showViewModal: false,
        showDeleteModal: false,
        isEditMode: false,
        editingTransactionId: null,
        isSaving: false,
        isDeleting: false,
        deletingTransactionId: null,
        deletingTransactionNo: '',
        viewData: null,

        eligibleCompanies: {!! json_encode($eligibleCompanies ?? []) !!},
        eligibleUsers: {!! json_encode($eligibleUsers ?? []) !!},
        transactionTypes: {!! json_encode($transactionTypes ?? ['Sale', 'Purchase', 'Service', 'Loan Given', 'Loan Received']) !!},
        currencies: {!! json_encode($currencies ?? ['INR', 'USD', 'EUR', 'GBP', 'AED', 'SGD']) !!},
        tdsSections: {!! json_encode($tdsSections ?? []) !!},

        notification: {
            show: false,
            type: 'success',
            message: ''
        },

        form: {
            transaction_date: new Date().toISOString().split('T')[0],
            transaction_type: 'Sale',
            transaction_no: '',
            from_company_id: '',
            to_company_id: '',
            amount: '',
            currency: 'INR',
            exchange_rate: 1.0000,
            is_gst_applicable: false,
            igst_amount: '',
            is_tds_applicable: false,
            tds_section: '',
            tds_rate: '',
            tds_amount: '',
            reference_invoice: '',
            reference_po: '',
            due_date: '',
            status: 'Pending',
            narration: '',
            approved_by: '',
            notes: ''
        },

        errors: {},
        formErrorSummary: '',

        get computedBaseAmount() {
            const amt = parseFloat(this.form.amount) || 0;
            const rate = parseFloat(this.form.exchange_rate) || 1;
            return (amt * rate).toFixed(2);
        },

        get computedNetAmount() {
            const base = (parseFloat(this.form.amount) || 0) * (parseFloat(this.form.exchange_rate) || 1);
            const gst = this.form.is_gst_applicable ? (parseFloat(this.form.igst_amount) || 0) : 0;
            const tds = this.form.is_tds_applicable ? (parseFloat(this.form.tds_amount) || 0) : 0;
            return Math.max(0, base + gst - tds).toFixed(2);
        },

        openAddModal() {
            this.isEditMode = false;
            this.editingTransactionId = null;
            this.resetForm();
            this.errors = {};
            this.formErrorSummary = '';
            this.showFormModal = true;
        },

        openEditModal(tx) {
            this.isEditMode = true;
            this.editingTransactionId = tx.id;
            this.errors = {};
            this.formErrorSummary = '';

            let txDate = tx.transaction_date;
            if (txDate && txDate.includes('T')) {
                txDate = txDate.split('T')[0];
            }
            let dueDate = tx.due_date;
            if (dueDate && dueDate.includes('T')) {
                dueDate = dueDate.split('T')[0];
            }

            this.form = {
                transaction_date: txDate || new Date().toISOString().split('T')[0],
                transaction_type: tx.transaction_type || 'Sale',
                transaction_no: tx.transaction_no || '',
                from_company_id: tx.from_company_id || '',
                to_company_id: tx.to_company_id || '',
                amount: tx.amount !== undefined ? tx.amount : '',
                currency: tx.currency || 'INR',
                exchange_rate: tx.exchange_rate !== undefined ? tx.exchange_rate : 1.0000,
                is_gst_applicable: Boolean(tx.is_gst_applicable),
                igst_amount: tx.igst_amount || '',
                is_tds_applicable: Boolean(tx.is_tds_applicable),
                tds_section: tx.tds_section || '',
                tds_rate: tx.tds_rate !== undefined ? tx.tds_rate : '',
                tds_amount: tx.tds_amount !== undefined ? tx.tds_amount : '',
                reference_invoice: tx.reference_invoice || '',
                reference_po: tx.reference_po || '',
                due_date: dueDate || '',
                status: tx.status || 'Pending',
                narration: tx.narration || '',
                approved_by: tx.approved_by || '',
                notes: tx.notes || ''
            };

            this.showFormModal = true;
        },

        async viewTransaction(id) {
            try {
                const res = await fetch(`{{ url("organization/sister-concerns/transactions") }}/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.viewData = data.transaction;
                    this.showViewModal = true;
                }
            } catch (err) {
                console.error('Error fetching transaction details:', err);
            }
        },

        async editTransaction(id) {
            try {
                const res = await fetch(`{{ url("organization/sister-concerns/transactions") }}/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.openEditModal(data.transaction);
                }
            } catch (err) {
                console.error('Error fetching transaction for edit:', err);
            }
        },

        closeFormModal() {
            if (this.isSaving) return;
            this.showFormModal = false;
            this.resetForm();
        },

        resetForm() {
            this.form = {
                transaction_date: new Date().toISOString().split('T')[0],
                transaction_type: 'Sale',
                transaction_no: '',
                from_company_id: '',
                to_company_id: '',
                amount: '',
                currency: 'INR',
                exchange_rate: 1.0000,
                is_gst_applicable: false,
                igst_amount: '',
                is_tds_applicable: false,
                tds_section: '',
                tds_rate: '',
                tds_amount: '',
                reference_invoice: '',
                reference_po: '',
                due_date: '',
                status: 'Pending',
                narration: '',
                approved_by: '',
                notes: ''
            };
            this.errors = {};
            this.formErrorSummary = '';
        },

        validateCounterparties() {
            if (this.form.from_company_id && this.form.to_company_id) {
                if (String(this.form.from_company_id) === String(this.form.to_company_id)) {
                    this.errors.to_company_id = 'From Company and To Company cannot be the same entity.';
                } else {
                    delete this.errors.to_company_id;
                    delete this.errors.from_company_id;
                }
            }
        },

        handleAmountChange() {
            this.recalculateTds();
        },

        handleCurrencyChange() {
            if (this.form.currency === 'INR') {
                this.form.exchange_rate = 1.0000;
            }
            this.recalculateTds();
        },

        handleGstToggle() {
            if (!this.form.is_gst_applicable) {
                this.form.igst_amount = '';
            }
        },

        handleTdsToggle() {
            if (!this.form.is_tds_applicable) {
                this.form.tds_section = '';
                this.form.tds_rate = '';
                this.form.tds_amount = '';
            }
        },

        handleTdsSectionChange() {
            const sec = this.tdsSections.find(s => s.code === this.form.tds_section);
            if (sec && sec.default_rate !== undefined) {
                this.form.tds_rate = sec.default_rate;
                this.recalculateTds();
            }
        },

        handleTdsRateChange() {
            this.recalculateTds();
        },

        recalculateTds() {
            if (this.form.is_tds_applicable && this.form.tds_rate !== '') {
                const base = (parseFloat(this.form.amount) || 0) * (parseFloat(this.form.exchange_rate) || 1);
                const rate = parseFloat(this.form.tds_rate) || 0;
                this.form.tds_amount = ((base * rate) / 100).toFixed(2);
            }
        },

        async submitForm() {
            this.errors = {};
            this.formErrorSummary = '';

            // Client-side validation
            let hasError = false;
            if (!this.form.transaction_date) {
                this.errors.transaction_date = 'Transaction Date is required.';
                hasError = true;
            }
            if (!this.form.transaction_type) {
                this.errors.transaction_type = 'Transaction Type is required.';
                hasError = true;
            }
            if (!this.form.from_company_id) {
                this.errors.from_company_id = 'From Company is required.';
                hasError = true;
            }
            if (!this.form.to_company_id) {
                this.errors.to_company_id = 'To Company is required.';
                hasError = true;
            }
            if (this.form.from_company_id && this.form.to_company_id && String(this.form.from_company_id) === String(this.form.to_company_id)) {
                this.errors.to_company_id = 'From Company and To Company cannot be the same entity.';
                hasError = true;
            }
            if (!this.form.amount || parseFloat(this.form.amount) <= 0) {
                this.errors.amount = 'A valid positive amount is required.';
                hasError = true;
            }

            if (hasError) {
                this.formErrorSummary = 'Please review and fix the errors highlighted below.';
                return;
            }

            this.isSaving = true;

            const isAdd = !this.isEditMode;
            const url = isAdd
                ? '{{ route("organization.sister-concerns.transactions.store") }}'
                : `{{ url("organization/sister-concerns/transactions") }}/${this.editingTransactionId}`;

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const payload = {
                transaction_date: this.form.transaction_date,
                transaction_type: this.form.transaction_type,
                transaction_no: this.form.transaction_no ? this.form.transaction_no.trim() : null,
                from_company_id: parseInt(this.form.from_company_id, 10),
                to_company_id: parseInt(this.form.to_company_id, 10),
                amount: parseFloat(this.form.amount),
                currency: this.form.currency || 'INR',
                exchange_rate: parseFloat(this.form.exchange_rate || 1),

                is_gst_applicable: Boolean(this.form.is_gst_applicable),
                igst_amount: this.form.is_gst_applicable && this.form.igst_amount !== '' ? parseFloat(this.form.igst_amount) : 0,

                is_tds_applicable: Boolean(this.form.is_tds_applicable),
                tds_section: this.form.is_tds_applicable ? (this.form.tds_section || null) : null,
                tds_rate: this.form.is_tds_applicable && this.form.tds_rate !== '' ? parseFloat(this.form.tds_rate) : 0,
                tds_amount: this.form.is_tds_applicable && this.form.tds_amount !== '' ? parseFloat(this.form.tds_amount) : 0,

                reference_invoice: this.form.reference_invoice ? this.form.reference_invoice.trim() : null,
                reference_po: this.form.reference_po ? this.form.reference_po.trim() : null,
                due_date: this.form.due_date || null,
                status: this.form.status || 'Pending',
                narration: this.form.narration ? this.form.narration.trim() : null,
                approved_by: this.form.approved_by ? parseInt(this.form.approved_by, 10) : null,
                notes: this.form.notes ? this.form.notes.trim() : null,
            };

            try {
                const response = await fetch(url, {
                    method: isAdd ? 'POST' : 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.closeFormModal();
                    this.showNotification('success', data.message || (isAdd ? 'Transaction created successfully.' : 'Transaction updated successfully.'));
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else if (response.status === 422) {
                    if (data.errors) {
                        for (const key in data.errors) {
                            this.errors[key] = data.errors[key][0];
                        }
                    }
                    this.formErrorSummary = data.message || 'Please correct the validation errors in the form.';
                } else {
                    this.formErrorSummary = data.message || 'An error occurred while saving the transaction.';
                }
            } catch (err) {
                console.error('Save transaction error:', err);
                this.formErrorSummary = 'Network error: could not save transaction. Please try again.';
            } finally {
                this.isSaving = false;
            }
        },

        promptDelete(id, txNo) {
            this.deletingTransactionId = id;
            this.deletingTransactionNo = txNo;
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingTransactionId = null;
            this.deletingTransactionNo = '';
        },

        async executeDelete() {
            if (!this.deletingTransactionId || this.isDeleting) return;
            this.isDeleting = true;

            const url = `{{ url("organization/sister-concerns/transactions") }}/${this.deletingTransactionId}`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showDeleteModal = false;
                    this.showNotification('success', data.message || 'Transaction deleted successfully.');
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    this.showNotification('error', data.message || 'Failed to delete transaction.');
                    this.showDeleteModal = false;
                }
            } catch (err) {
                console.error('Delete transaction error:', err);
                this.showNotification('error', 'A network error occurred while deleting the transaction.');
                this.showDeleteModal = false;
            } finally {
                this.isDeleting = false;
                this.deletingTransactionId = null;
            }
        },

        showNotification(type, message) {
            this.notification = { show: true, type, message };
            setTimeout(() => {
                this.notification.show = false;
            }, 5000);
        }
    };
}
</script>
@endsection
