@extends('layouts.dms')
@section('title', 'Cheque Register')

@section('content')
<div class="space-y-6" x-data="{
    // Modal states
    showDepositModal: false,
    showClearModal: false,
    showBounceModal: false,
    showCancelModal: false,
    showStaleModal: false,
    selectedCheque: null,

    // Form fields
    depositDate: '{{ now()->toDateString() }}',
    clearanceDate: '{{ now()->toDateString() }}',
    bounceReason: '',
    bounceCharges: '0.00',
    cancelReason: '',
    staleReason: 'Cheque expired validity period (> 90 days).',

    openDeposit(cheque) {
        this.selectedCheque = cheque;
        this.depositDate = '{{ now()->toDateString() }}';
        this.showDepositModal = true;
    },
    openClear(cheque) {
        this.selectedCheque = cheque;
        this.clearanceDate = '{{ now()->toDateString() }}';
        this.showClearModal = true;
    },
    openBounce(cheque) {
        this.selectedCheque = cheque;
        this.bounceReason = '';
        this.bounceCharges = '0.00';
        this.showBounceModal = true;
    },
    openCancel(cheque) {
        this.selectedCheque = cheque;
        this.cancelReason = '';
        this.showCancelModal = true;
    },
    openStale(cheque) {
        this.selectedCheque = cheque;
        this.staleReason = 'Cheque expired validity period (> 90 days).';
        this.showStaleModal = true;
    }
}">
    {{-- Header --}}
    <x-ui.page-header
        title="Cheque Register"
        description="Comprehensive register for incoming and outgoing cheques, clearance tracking, deposit dates, and bounce management."
    >
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('cheque-books.index')">
                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                Manage Cheque Books
            </x-ui.button>
            <x-ui.button variant="secondary" :href="route('cheques.export', request()->all())">
                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Export CSV
            </x-ui.button>
            <x-ui.button variant="primary" :href="route('cheques.create')">
                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Record / Issue Cheque
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    {{-- 6 Summary Cards (Section 7) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        {{-- Total Issued --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Cheques Issued</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-blue-900">{{ number_format($stats['total_issued_count']) }}</span>
                <span class="text-xs font-semibold text-blue-700">₹{{ number_format($stats['total_issued_amount'], 2) }}</span>
            </div>
        </div>

        {{-- Total Received --}}
        <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-700">Cheques Received</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-indigo-900">{{ number_format($stats['total_received_count']) }}</span>
                <span class="text-xs font-semibold text-indigo-700">₹{{ number_format($stats['total_received_amount'], 2) }}</span>
            </div>
        </div>

        {{-- Pending --}}
        <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Pending</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-amber-900">{{ number_format($stats['pending_count']) }}</span>
                <span class="text-xs font-medium text-amber-600">Awaiting Deposit</span>
            </div>
        </div>

        {{-- Deposited --}}
        <div class="rounded-xl border border-cyan-200 bg-cyan-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-cyan-700">Deposited</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-cyan-900">{{ number_format($stats['deposited_count']) }}</span>
                <span class="text-xs font-medium text-cyan-600">In Clearing</span>
            </div>
        </div>

        {{-- Cleared --}}
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Cleared</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-emerald-900">{{ number_format($stats['cleared_count']) }}</span>
                <span class="text-xs font-medium text-emerald-600">Realized</span>
            </div>
        </div>

        {{-- Bounced --}}
        <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Bounced</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-rose-900">{{ number_format($stats['bounced_count']) }}</span>
                <span class="text-xs font-medium text-rose-600">Dishonored</span>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar (Section 7) --}}
    <x-ui.card>
        <form method="GET" action="{{ route('cheques.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            {{-- Bank Account Filter --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Company Bank A/C</label>
                <select name="bank_account_no" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Bank Accounts</option>
                    @foreach($bankAccounts as $acc)
                        @php $no = $acc['bank_account_no'] ?? ''; @endphp
                        @if(filled($no))
                            <option value="{{ $no }}" @selected(($filters['bank_account_no'] ?? '') === $no)>
                                {{ $acc['bank_name'] ?: 'Bank' }} ({{ substr($no, -4) }})
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            {{-- Cheque Type --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Cheque Type</label>
                <select name="cheque_type" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Types</option>
                    <option value="issued" @selected(($filters['cheque_type'] ?? '') === 'issued')>Issued (Outgoing)</option>
                    <option value="received" @selected(($filters['cheque_type'] ?? '') === 'received')>Received (Incoming)</option>
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Statuses</option>
                    <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending (Issued/Received)</option>
                    <option value="deposited" @selected(($filters['status'] ?? '') === 'deposited')>Deposited</option>
                    <option value="cleared" @selected(($filters['status'] ?? '') === 'cleared')>Cleared</option>
                    <option value="bounced" @selected(($filters['status'] ?? '') === 'bounced')>Bounced</option>
                    <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelled</option>
                    <option value="stale" @selected(($filters['status'] ?? '') === 'stale')>Stale / Expired</option>
                </select>
            </div>

            {{-- Party Filter --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Party Name</label>
                <select name="customer_id" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Parties</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(($filters['customer_id'] ?? '') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- From Date --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
            </div>

            {{-- To Date --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
            </div>

            {{-- Search & Reset row --}}
            <div class="sm:col-span-2 md:col-span-3 lg:col-span-6 flex items-center gap-2 pt-1 border-t border-slate-100">
                <div class="flex-1">
                    <input
                        type="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search by cheque number, party name, voucher, bank, remarks..."
                        class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                    >
                </div>
                <x-ui.button type="submit" variant="secondary" size="sm">Search</x-ui.button>
                @if(array_filter($filters ?? []))
                    <a href="{{ route('cheques.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline ml-2">Clear Filters</a>
                @endif
            </div>
        </form>

        {{-- 11-Column Register Table (Section 3) --}}
        <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50/80 text-slate-700 font-semibold">
                    <tr>
                        <x-ui.sortable-th column="cheque_no" label="Cheque Number" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="bank_account_no" label="Bank Account" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="cheque_date" label="Cheque Date" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="customer" label="Party Name" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-3 py-2 text-center">Cheque Type</th>
                        <x-ui.sortable-th column="amount" label="Amount" align="right" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-3 py-2 text-left">Purpose</th>
                        <th class="px-3 py-2 text-left">Reference Voucher</th>
                        <x-ui.sortable-th column="deposit_date" label="Deposit Date" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="status" label="Status" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-3 py-2 text-left">Remarks</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        @php
                            $isIssued = $item->isIssued();
                            $badge = $item->statusBadgeClass();
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            {{-- 1. Cheque Number --}}
                            <td class="px-3 py-2 font-mono font-bold text-slate-900 whitespace-nowrap">
                                <a href="{{ route('cheques.show', $item) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                    {{ $item->cheque_no }}
                                </a>
                                @if($item->chequeBook)
                                    <span class="text-[10px] text-slate-400 block font-sans font-normal" title="{{ $item->chequeBook->cheque_book_name }}">
                                        {{ $item->chequeBook->cheque_book_name ?: 'Book' }}
                                    </span>
                                @endif
                            </td>

                            {{-- 2. Bank Account --}}
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="font-medium text-slate-800">{{ $item->bank_name ?: 'Bank' }}</span>
                                @if($item->bank_account_no)
                                    <span class="font-mono text-[11px] text-slate-500 block">{{ $item->bank_account_no }}</span>
                                @endif
                            </td>

                            {{-- 3. Cheque Date --}}
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-slate-700">
                                {{ $item->cheque_date ? $item->cheque_date->format('d M Y') : '—' }}
                            </td>

                            {{-- 4. Party Name --}}
                            <td class="px-3 py-2 font-medium text-slate-900">
                                @if($item->customer)
                                    <a href="{{ route('masters.customers.show', $item->customer) }}" class="hover:underline">
                                        {{ $item->customer->name }}
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- 5. Cheque Type --}}
                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                @if($isIssued)
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 border border-blue-200">
                                        Issued
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 border border-indigo-200">
                                        Received
                                    </span>
                                @endif
                            </td>

                            {{-- 6. Amount (₹) --}}
                            <td class="px-3 py-2 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                ₹{{ number_format($item->amount, 2) }}
                            </td>

                            {{-- 7. Purpose --}}
                            <td class="px-3 py-2 text-slate-700 uppercase font-mono text-[11px] whitespace-nowrap">
                                {{ $item->purpose ?: '—' }}
                            </td>

                            {{-- 8. Reference Voucher --}}
                            <td class="px-3 py-2 font-mono text-[11px] text-slate-700 whitespace-nowrap">
                                @if($item->payment)
                                    <a href="{{ route('payments.show', $item->payment) }}" class="text-indigo-600 hover:underline">
                                        {{ $item->payment->payment_no }}
                                    </a>
                                @elseif($item->reference_voucher)
                                    <span>{{ $item->reference_voucher }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- 9. Deposit Date --}}
                            <td class="px-3 py-2 font-mono text-[11px] text-slate-700 whitespace-nowrap">
                                {{ $item->deposit_date ? $item->deposit_date->format('d M Y') : '—' }}
                            </td>

                            {{-- 10. Status --}}
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border {{ $badge }}">
                                    {{ $item->status_label }}
                                </span>
                            </td>

                            {{-- 11. Remarks --}}
                            <td class="px-3 py-2 text-slate-500 max-w-xs truncate" title="{{ $item->notes ?: ($item->cancelled_reason ?: '') }}">
                                {{ $item->notes ?: ($item->cancelled_reason ?: '—') }}
                            </td>

                            {{-- Actions --}}
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    {{-- Deposit Action --}}
                                    @if(in_array($item->status, ['pending', \App\Domains\Payment\Models\Cheque::STATUS_ISSUED, \App\Domains\Payment\Models\Cheque::STATUS_RECEIVED]))
                                        <button
                                            type="button"
                                            @click="openDeposit({
                                                id: {{ $item->id }},
                                                cheque_no: '{{ $item->cheque_no }}',
                                                amount: '{{ number_format($item->amount, 2) }}'
                                            })"
                                            class="rounded bg-cyan-50 border border-cyan-200 px-2 py-0.5 text-[11px] font-semibold text-cyan-700 hover:bg-cyan-100 transition"
                                            title="Mark as deposited in bank"
                                        >
                                            Deposit
                                        </button>
                                    @endif

                                    {{-- Clear Action --}}
                                    @if(in_array($item->status, ['pending', \App\Domains\Payment\Models\Cheque::STATUS_DEPOSITED, \App\Domains\Payment\Models\Cheque::STATUS_ISSUED, \App\Domains\Payment\Models\Cheque::STATUS_RECEIVED]))
                                        <button
                                            type="button"
                                            @click="openClear({
                                                id: {{ $item->id }},
                                                cheque_no: '{{ $item->cheque_no }}',
                                                amount: '{{ number_format($item->amount, 2) }}'
                                            })"
                                            class="rounded bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100 transition"
                                            title="Mark as cleared"
                                        >
                                            Clear
                                        </button>
                                    @endif

                                    {{-- Bounce Action --}}
                                    @if(in_array($item->status, ['pending', \App\Domains\Payment\Models\Cheque::STATUS_DEPOSITED, \App\Domains\Payment\Models\Cheque::STATUS_ISSUED, \App\Domains\Payment\Models\Cheque::STATUS_RECEIVED]))
                                        <button
                                            type="button"
                                            @click="openBounce({
                                                id: {{ $item->id }},
                                                cheque_no: '{{ $item->cheque_no }}',
                                                party: '{{ addslashes($item->customer?->name ?: 'Party') }}',
                                                amount: '{{ number_format($item->amount, 2) }}'
                                            })"
                                            class="rounded bg-rose-50 border border-rose-200 px-2 py-0.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-100 transition"
                                            title="Record cheque bounce"
                                        >
                                            Bounce
                                        </button>
                                    @endif

                                    {{-- Cancel Action --}}
                                    @if(!in_array($item->status, [\App\Domains\Payment\Models\Cheque::STATUS_CLEARED, \App\Domains\Payment\Models\Cheque::STATUS_BOUNCED, \App\Domains\Payment\Models\Cheque::STATUS_CANCELLED]))
                                        <button
                                            type="button"
                                            @click="openCancel({
                                                id: {{ $item->id }},
                                                cheque_no: '{{ $item->cheque_no }}'
                                            })"
                                            class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-200 transition"
                                            title="Cancel cheque"
                                        >
                                            Cancel
                                        </button>
                                    @endif

                                    {{-- Stale Action --}}
                                    @if(!in_array($item->status, [\App\Domains\Payment\Models\Cheque::STATUS_CLEARED, \App\Domains\Payment\Models\Cheque::STATUS_BOUNCED, \App\Domains\Payment\Models\Cheque::STATUS_CANCELLED, \App\Domains\Payment\Models\Cheque::STATUS_STALE]))
                                        <button
                                            type="button"
                                            @click="openStale({
                                                id: {{ $item->id }},
                                                cheque_no: '{{ $item->cheque_no }}'
                                            })"
                                            class="rounded bg-amber-50 border border-amber-200 px-2 py-0.5 text-[11px] font-semibold text-amber-700 hover:bg-amber-100 transition"
                                            title="Mark as stale / expired"
                                        >
                                            Stale
                                        </button>
                                    @endif

                                    {{-- View Details --}}
                                    <a
                                        href="{{ route('cheques.show', $item) }}"
                                        class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-200 transition"
                                    >
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-slate-500">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    <p class="font-medium text-slate-700">No cheques found in register.</p>
                                    <p class="text-[11px] text-slate-500">Record incoming client cheques or issue cheques against your configured cheque books.</p>
                                    <a href="{{ route('cheques.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                                        + Record / Issue Cheque
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="mt-4">{{ $items->links() }}</div>
        @endif
    </x-ui.card>

    {{-- Deposit Modal --}}
    <div
        x-show="showDepositModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div @click.outside="showDepositModal = false" class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h3 class="text-sm font-bold text-slate-900" x-text="`Deposit Cheque #${selectedCheque?.cheque_no}`"></h3>
                <button type="button" @click="showDepositModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>
            <form method="POST" :action="`{{ url('cheques') }}/${selectedCheque?.id}/deposit`" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Deposit Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="deposit_date" x-model="depositDate" required class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-cyan-500 focus:border-cyan-500">
                </div>
                <p class="text-[11px] text-slate-500">Cheque status will transition to <strong>Deposited</strong> and track clearing date.</p>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showDepositModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-cyan-600 px-3 py-1.5 font-semibold text-white hover:bg-cyan-700 shadow-2xs">Confirm Deposit</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Clear Modal --}}
    <div
        x-show="showClearModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div @click.outside="showClearModal = false" class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h3 class="text-sm font-bold text-emerald-900" x-text="`Clear Cheque #${selectedCheque?.cheque_no}`"></h3>
                <button type="button" @click="showClearModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>
            <form method="POST" :action="`{{ url('cheques') }}/${selectedCheque?.id}/clear`" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Clearance Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="clearance_date" x-model="clearanceDate" required class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <p class="text-[11px] text-slate-500">The cheque amount (₹<span x-text="selectedCheque?.amount"></span>) will be recognized as cleared in your bank accounts.</p>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showClearModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 font-semibold text-white hover:bg-emerald-700 shadow-2xs">Confirm Clearance</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bounce Modal --}}
    <div
        x-show="showBounceModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div @click.outside="showBounceModal = false" class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <div>
                    <h3 class="text-sm font-bold text-rose-900" x-text="`Cheque Bounce #${selectedCheque?.cheque_no}`"></h3>
                    <p class="text-[11px] text-slate-500" x-text="`Party: ${selectedCheque?.party}`"></p>
                </div>
                <button type="button" @click="showBounceModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>
            <form method="POST" :action="`{{ url('cheques') }}/${selectedCheque?.id}/bounce`" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Bounce Reason</label>
                    <input type="text" name="reason" x-model="bounceReason" placeholder="e.g. Insufficient funds, signature mismatch" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-rose-500 focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Bank Bounce Charges (₹)</label>
                    <input type="number" step="0.01" name="charges" x-model="bounceCharges" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-rose-500 focus:border-rose-500">
                </div>
                <div class="rounded-lg bg-rose-50 p-2.5 text-[11px] text-rose-800 space-y-1">
                    <p class="font-semibold">⚠️ Party Risk & Freeze Logic:</p>
                    <p>Increments the party's cheque bounce counter. If the party exceeds 3 bounces, their credit status will be automatically frozen.</p>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showBounceModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-rose-600 px-3 py-1.5 font-semibold text-white hover:bg-rose-700 shadow-2xs">Confirm Bounce</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Cancel Modal --}}
    <div
        x-show="showCancelModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div @click.outside="showCancelModal = false" class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h3 class="text-sm font-bold text-slate-900" x-text="`Cancel Cheque #${selectedCheque?.cheque_no}`"></h3>
                <button type="button" @click="showCancelModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>
            <form method="POST" :action="`{{ url('cheques') }}/${selectedCheque?.id}/cancel`" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Cancellation Reason</label>
                    <textarea name="reason" x-model="cancelReason" rows="2" placeholder="e.g. Stopped payment, replacement cheque issued..." class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showCancelModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 font-semibold text-white hover:bg-slate-900 shadow-2xs">Confirm Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stale Modal --}}
    <div
        x-show="showStaleModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div @click.outside="showStaleModal = false" class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h3 class="text-sm font-bold text-amber-900" x-text="`Mark Cheque #${selectedCheque?.cheque_no} as Stale`"></h3>
                <button type="button" @click="showStaleModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>
            <form method="POST" :action="`{{ url('cheques') }}/${selectedCheque?.id}/mark-stale`" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Remarks</label>
                    <textarea name="reason" x-model="staleReason" rows="2" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-amber-500 focus:border-amber-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showStaleModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-amber-600 px-3 py-1.5 font-semibold text-white hover:bg-amber-700 shadow-2xs">Mark as Stale</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
