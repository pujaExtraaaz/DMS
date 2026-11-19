@extends('layouts.dms')
@section('title', 'Record / Issue Cheque')

@section('content')
<div class="space-y-6" x-data="{
    chequeType: '{{ old('cheque_type', $item->cheque_type ?: 'issued') }}',
    bankAccountNo: '{{ old('bank_account_no', $item->bank_account_no ?: '') }}',
    selectedBookId: '{{ old('cheque_book_id', $item->cheque_book_id ?: '') }}',
    chequeNo: '{{ old('cheque_no', $item->cheque_no ?: '') }}',
    books: [],
    availableNumbers: [],
    loadingBooks: false,
    errorMessage: '',

    init() {
        if (this.chequeType === 'issued' && this.bankAccountNo) {
            this.fetchAvailableNumbers(this.bankAccountNo, false);
        }
    },

    setChequeType(type) {
        this.chequeType = type;
        if (type === 'issued' && this.bankAccountNo) {
            this.fetchAvailableNumbers(this.bankAccountNo, false);
        }
    },

    async fetchAvailableNumbers(accountNo, autoSuggest = true) {
        if (!accountNo) {
            this.books = [];
            this.availableNumbers = [];
            return;
        }

        this.loadingBooks = true;
        this.errorMessage = '';

        try {
            const url = `{{ route('cheque-books.available-numbers') }}?bank_account_no=${encodeURIComponent(accountNo)}`;
            const res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
            const data = await res.json();

            if (data.success) {
                this.books = data.books || [];
                if (autoSuggest && data.suggested_cheque_no && !this.chequeNo) {
                    this.chequeNo = data.suggested_cheque_no;
                }
                this.updateAvailableList();
            } else {
                this.books = [];
                this.availableNumbers = [];
            }
        } catch (err) {
            this.errorMessage = 'Unable to fetch available cheque numbers.';
        } finally {
            this.loadingBooks = false;
        }
    },

    onAccountChange() {
        this.chequeNo = '';
        this.selectedBookId = '';
        this.fetchAvailableNumbers(this.bankAccountNo, true);
    },

    onBookChange() {
        this.updateAvailableList();
        if (this.availableNumbers.length > 0) {
            this.chequeNo = this.availableNumbers[0];
        }
    },

    updateAvailableList() {
        if (this.selectedBookId) {
            const book = this.books.find(b => String(b.id) === String(this.selectedBookId));
            this.availableNumbers = book ? book.available_numbers : [];
        } else {
            // Aggregate all available numbers from active books
            let all = [];
            for (const b of this.books) {
                all = all.concat(b.available_numbers || []);
            }
            this.availableNumbers = all;
        }
    }
}">
    <x-ui.page-header
        title="Record / Issue Cheque"
        description="Issue an outgoing company cheque against registered cheque books or record an incoming cheque from a client."
    >
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('cheques.index')">
                Back to Register
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        {{-- Type Selector Tabs (Section 4 & 5) --}}
        <div class="flex items-center gap-2 border-b border-slate-200 pb-4 mb-6">
            <button
                type="button"
                @click="setChequeType('issued')"
                :class="chequeType === 'issued' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                class="rounded-lg px-4 py-2 text-xs font-bold transition flex items-center gap-2"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                Issue Outgoing Cheque
            </button>
            <button
                type="button"
                @click="setChequeType('received')"
                :class="chequeType === 'received' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                class="rounded-lg px-4 py-2 text-xs font-bold transition flex items-center gap-2"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Record Incoming Cheque
            </button>
        </div>

        <form method="POST" action="{{ route('cheques.store') }}" class="space-y-6 max-w-4xl">
            @csrf
            <input type="hidden" name="cheque_type" x-model="chequeType">

            {{-- SECTION 4: ISSUANCE WORKFLOW FIELDS --}}
            <template x-if="chequeType === 'issued'">
                <div class="space-y-4">
                    <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 space-y-2">
                        <div class="flex items-center gap-2 text-blue-900 font-bold text-xs uppercase tracking-wider">
                            <span>🏦 Company Bank Account &amp; Cheque Leaf Selection</span>
                        </div>
                        <p class="text-xs text-blue-800">
                            Select the company bank account you are issuing the cheque from. Available numbers from your active cheque books will be suggested automatically.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- 1. Company Bank Account --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Company Bank Account <span class="text-rose-500">*</span></label>
                            <select
                                name="bank_account_no"
                                x-model="bankAccountNo"
                                @change="onAccountChange()"
                                required
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                            >
                                <option value="">-- Select Bank Account --</option>
                                @foreach($bankAccounts as $acc)
                                    @php $no = $acc['bank_account_no'] ?? ''; @endphp
                                    @if(filled($no))
                                        <option value="{{ $no }}">
                                            {{ $acc['bank_name'] ?: 'Bank' }} — {{ $no }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        {{-- Active Cheque Book Selection --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque Book Range</label>
                            <select
                                name="cheque_book_id"
                                x-model="selectedBookId"
                                @change="onBookChange()"
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                            >
                                <option value="">-- All Active Cheque Books --</option>
                                <template x-for="book in books" :key="book.id">
                                    <option :value="book.id" x-text="`${book.name} (${book.from_number} - ${book.to_number}) — ${book.remaining_count} left`"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Cheque Number with Suggestion --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-slate-700">Cheque Number <span class="text-rose-500">*</span></label>
                                <template x-if="availableNumbers.length > 0">
                                    <span class="text-[11px] text-emerald-600 font-medium" x-text="`${availableNumbers.length} available leaves`"></span>
                                </template>
                            </div>
                            <div class="relative">
                                <input
                                    type="text"
                                    name="cheque_no"
                                    x-model="chequeNo"
                                    required
                                    placeholder="e.g. 000101"
                                    class="w-full font-mono font-bold rounded-lg border-gray-300 text-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"
                                >
                            </div>
                            {{-- Quick pick pills for available numbers --}}
                            <template x-if="availableNumbers.length > 0">
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <span class="text-[10px] uppercase font-semibold text-slate-400">Next available:</span>
                                    <template x-for="num in availableNumbers.slice(0, 6)" :key="num">
                                        <button
                                            type="button"
                                            @click="chequeNo = num"
                                            :class="chequeNo === num ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            class="font-mono rounded px-1.5 py-0.5 text-xs font-bold transition"
                                            x-text="num"
                                        ></button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Cheque Date --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque Date <span class="text-rose-500">*</span></label>
                            <input
                                type="date"
                                name="cheque_date"
                                value="{{ old('cheque_date', now()->toDateString()) }}"
                                required
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>

                        {{-- Payee / Party --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Favouring / Party Name</label>
                            <select
                                name="customer_id"
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                            >
                                <option value="">-- Select Party (Supplier / Vendor / Other) --</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>
                                        {{ $c->name }} ({{ ucfirst($c->type ?? 'Party') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Amount --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque Amount (₹) <span class="text-rose-500">*</span></label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                name="amount"
                                value="{{ old('amount') }}"
                                required
                                placeholder="0.00"
                                class="w-full font-mono font-bold rounded-lg border-gray-300 text-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>

                        {{-- Purpose --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Purpose</label>
                            <select
                                name="purpose"
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                            >
                                <option value="payment">Vendor Payment</option>
                                <option value="advance">Advance Payment</option>
                                <option value="security">Security Cheque</option>
                                <option value="pdc">Post-Dated Cheque (PDC)</option>
                                <option value="credit">Credit Guarantee</option>
                            </select>
                        </div>

                        {{-- Reference Voucher --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Reference Voucher / PO</label>
                            <input
                                type="text"
                                name="reference_voucher"
                                value="{{ old('reference_voucher') }}"
                                placeholder="e.g. PV-2026-0012, PO-1092"
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>
                    </div>
                </div>
            </template>

            {{-- SECTION 5: RECEIPT WORKFLOW FIELDS --}}
            <template x-if="chequeType === 'received'">
                <div class="space-y-4">
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50/50 p-4 space-y-2">
                        <div class="flex items-center gap-2 text-indigo-900 font-bold text-xs uppercase tracking-wider">
                            <span>📥 Client Cheque Receipt Details</span>
                        </div>
                        <p class="text-xs text-indigo-800">
                            Record a cheque received from a customer or client. This cheque will enter the register under <strong>Received</strong> status and can later be deposited and cleared.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- 1. Party / Customer --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Received From (Party) <span class="text-rose-500">*</span></label>
                            <select
                                name="customer_id"
                                required
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                            >
                                <option value="">-- Select Customer / Client --</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>
                                        {{ $c->name }} ({{ ucfirst($c->type ?? 'Customer') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Receiving Company Bank A/C --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Receiving Bank Account (Optional)</label>
                            <select
                                name="bank_account_no"
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                            >
                                <option value="">-- Select Company Deposit Bank Account --</option>
                                @foreach($bankAccounts as $acc)
                                    @php $no = $acc['bank_account_no'] ?? ''; @endphp
                                    @if(filled($no))
                                        <option value="{{ $no }}">
                                            {{ $acc['bank_name'] ?: 'Bank' }} — {{ $no }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        {{-- Cheque Number --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque Number <span class="text-rose-500">*</span></label>
                            <input
                                type="text"
                                name="cheque_no"
                                value="{{ old('cheque_no') }}"
                                required
                                placeholder="e.g. 849201"
                                class="w-full font-mono font-bold rounded-lg border-gray-300 text-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>

                        {{-- Cheque Date --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque Date <span class="text-rose-500">*</span></label>
                            <input
                                type="date"
                                name="cheque_date"
                                value="{{ old('cheque_date', now()->toDateString()) }}"
                                required
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>

                        {{-- Drawer Bank Name --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Drawee Bank Name</label>
                            <input
                                type="text"
                                name="bank_name"
                                value="{{ old('bank_name') }}"
                                placeholder="e.g. State Bank of India, Axis Bank"
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>

                        {{-- Drawer Branch --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Branch Name</label>
                            <input
                                type="text"
                                name="branch_name"
                                value="{{ old('branch_name') }}"
                                placeholder="e.g. Connaught Place Branch"
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>

                        {{-- Amount --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (₹) <span class="text-rose-500">*</span></label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                name="amount"
                                value="{{ old('amount') }}"
                                required
                                placeholder="0.00"
                                class="w-full font-mono font-bold rounded-lg border-gray-300 text-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>

                        {{-- Purpose --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Purpose</label>
                            <select
                                name="purpose"
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                            >
                                <option value="collection">Invoice Collection</option>
                                <option value="pdc">Post-Dated Cheque (PDC)</option>
                                <option value="security">Security Cheque</option>
                                <option value="credit">Credit / Collateral</option>
                            </select>
                        </div>

                        {{-- Reference Voucher --}}
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Reference Voucher / Invoice</label>
                            <input
                                type="text"
                                name="reference_voucher"
                                value="{{ old('reference_voucher') }}"
                                placeholder="e.g. INV-2026-0812, Receipt #REC-001"
                                class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>
                    </div>
                </div>
            </template>

            {{-- Notes & Remarks --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks / Notes</label>
                <textarea
                    name="notes"
                    rows="2"
                    placeholder="Optional notes or instructions..."
                    class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"
                >{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <x-ui.button variant="secondary" :href="route('cheques.index')">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary">
                    <span x-text="chequeType === 'issued' ? 'Issue Cheque & Record' : 'Record Received Cheque'"></span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
