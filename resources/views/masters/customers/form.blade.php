@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Party' : 'Create Party')

@php
    $defaultBankAccount = [
        'account_holder_name' => '',
        'bank_name' => '',
        'branch_name' => '',
        'account_number' => '',
        'account_type' => 'current',
        'ifsc_code' => '',
    ];
    $initialBankAccounts = old('bank_accounts', ($item->exists && $item->relationLoaded('bankAccounts') && $item->bankAccounts->isNotEmpty()) ? $item->bankAccounts->toArray() : [$defaultBankAccount]);

    $defaultCreditCheque = [
        'cheque_number' => '',
        'bank_name' => '',
        'branch_name' => '',
        'cheque_date' => '',
        'amount' => '',
        'cheque_type' => 'regular',
    ];
    $initialCreditCheques = old('credit_cheques', ($item->exists && $item->relationLoaded('creditCheques') && $item->creditCheques->isNotEmpty()) ? $item->creditCheques->toArray() : [$defaultCreditCheque]);
@endphp

@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Party' : 'Create Party'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.customers.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
    <form method="POST"
          action="{{ $item->exists ? route('masters.customers.update', $item) : route('masters.customers.store') }}"
          class="space-y-6 max-w-5xl"
          x-data="partyFormController()">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Identity</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Party Code</label>
                    <input type="text" name="code" value="{{ old('code', $item->code ?? '') }}" readonly
                           class="block w-full rounded-lg border-gray-300 bg-gray-100 text-gray-700 text-sm font-mono cursor-not-allowed focus:ring-0 focus:border-gray-300"
                           placeholder="Auto-generated on save">
                    <p class="mt-1 text-xs text-gray-500">Auto-generated party code</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">GSTIN</label>
                    <div class="flex gap-2">
                        <input type="text" name="gstin" x-model="form.gstin" maxlength="15"
                               placeholder="e.g. 27AAAAA0000A1Z5"
                               class="block w-full uppercase rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="button" @click="fetchGstDetails()" :disabled="searchingGst"
                                class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-lg hover:bg-indigo-100 transition disabled:opacity-50">
                            <span x-show="!searchingGst">Fetch GST</span>
                            <span x-show="searchingGst" class="flex items-center gap-1">
                                <svg class="animate-spin h-3.5 w-3.5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Searching...</span>
                            </span>
                        </button>
                    </div>

                    <div x-show="gstFeedback.message"
                         x-transition
                         :class="{
                             'bg-emerald-50 text-emerald-800 border-emerald-200': gstFeedback.type === 'success',
                             'bg-amber-50 text-amber-800 border-amber-200': gstFeedback.type === 'warning',
                             'bg-rose-50 text-rose-800 border-rose-200': gstFeedback.type === 'error'
                         }"
                         class="mt-2 p-2.5 rounded-lg border text-xs flex items-start gap-2">
                        <span class="font-bold shrink-0 mt-0.5" x-text="gstFeedback.type === 'success' ? '✓' : (gstFeedback.type === 'warning' ? 'ℹ' : '⚠')"></span>
                        <div class="space-y-0.5">
                            <p x-text="gstFeedback.message" class="font-medium"></p>
                            <p x-show="gstFeedback.note" x-text="gstFeedback.note" class="text-[11px] opacity-90"></p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Party Name *</label>
                    <input type="text" name="name" x-model="form.name" required
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Party Type *</label>
                    <select name="party_type" x-model="form.party_type" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach(['customer' => 'Customer', 'supplier' => 'Supplier', 'dealer' => 'Dealer', 'both' => 'Both (Customer & Supplier)'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('party_type', $item->party_type ?? 'customer')===$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Category / Type</label>
                    <select name="customer_type_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select category</option>
                        @foreach($customerTypes as $t)
                            <option value="{{ $t->id }}" @selected(old('customer_type_id', $item->customer_type_id)==$t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">PAN</label>
                    <input type="text" name="pan" x-model="form.pan" maxlength="10"
                           class="block w-full uppercase rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Phone</label>
                    <input type="text" name="phone" x-model="form.phone"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email</label>
                    <input type="email" name="email" x-model="form.email"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Registered Address</label>
                    <textarea name="address" x-model="form.address" rows="2" placeholder="Full street address, building, locality"
                              class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', $item->address ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">State</label>
                    <select name="state" x-model="form.state" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select State / UT</option>
                        @foreach($states as $code => $name)
                            <option value="{{ $name }}" @selected(old('state', $item->state)==$name)>{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Pincode</label>
                    <input type="text" name="pincode" x-model="form.pincode"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Sales &amp; Logistics Assignment</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.select name="salesperson_id" label="Assigned Salesperson">
                    <option value="">Unassigned</option>
                    @foreach($salespersons as $s)
                        <option value="{{ $s->id }}" @selected(old('salesperson_id', $item->salesperson_id)==$s->id)>{{ $s->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="sales_manager_id" label="Assigned Sales Manager">
                    <option value="">Unassigned</option>
                    @foreach($salesManagers as $m)
                        <option value="{{ $m->id }}" @selected(old('sales_manager_id', $item->sales_manager_id)==$m->id)>{{ $m->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="area_id" label="Area">
                    <option value="">Unassigned</option>
                    @foreach($areas as $a)
                        <option value="{{ $a->id }}" @selected(old('area_id', $item->area_id)==$a->id)>{{ $a->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="route_id" label="Delivery Route">
                    <option value="">Unassigned</option>
                    @foreach($routes as $r)
                        <option value="{{ $r->id }}" @selected(old('route_id', $item->route_id)==$r->id)>{{ $r->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Credit Terms &amp; Policies</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="credit_limit" label="Credit Limit (₹)" type="number" step="0.01" :value="old('credit_limit', $item->credit_limit ?? 0)" />
                <x-ui.input name="credit_days" label="Credit Period (Days)" type="number" :value="old('credit_days', $item->credit_days ?? 0)" />
                <x-ui.select name="credit_status" label="Credit Status">
                    @foreach(['open' => 'Open', 'hold' => 'On Hold', 'blocked' => 'Blocked'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('credit_status', $item->credit_status ?? 'open')===$val)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="credit_period_basis" label="Credit Period Basis">
                    @foreach(['cumulative' => 'Cumulative / Balance', 'invoice_date' => 'Invoice Date', 'inward_date' => 'Inward Date', 'receipt_date' => 'Receipt Date'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('credit_period_basis', $item->credit_period_basis ?? 'cumulative')===$val)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="interest_rate" label="Overdue Interest Rate (% per annum)" type="number" step="0.01" :value="old('interest_rate', $item->interest_rate ?? 18)" />
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">Bank Accounts</h3>
                <button type="button" @click="addBankAccount()"
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                    + Add Bank Account
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="(acc, idx) in bankAccounts" :key="idx">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500" x-text="`Bank Account #${idx + 1}`"></span>
                            <button type="button" x-show="bankAccounts.length > 1" @click="bankAccounts.splice(idx, 1)"
                                    class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">Remove</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Account Holder Name</label>
                                <input type="text" :name="`bank_accounts[${idx}][account_holder_name]`" x-model="acc.account_holder_name"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Bank Name</label>
                                <input type="text" :name="`bank_accounts[${idx}][bank_name]`" x-model="acc.bank_name"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Branch Name</label>
                                <input type="text" :name="`bank_accounts[${idx}][branch_name]`" x-model="acc.branch_name"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Account Number</label>
                                <input type="text" :name="`bank_accounts[${idx}][account_number]`" x-model="acc.account_number"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Account Type</label>
                                <select :name="`bank_accounts[${idx}][account_type]`" x-model="acc.account_type"
                                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="current">Current Account</option>
                                    <option value="savings">Savings Account</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">IFSC Code</label>
                                <input type="text" :name="`bank_accounts[${idx}][ifsc_code]`" x-model="acc.ifsc_code" maxlength="20"
                                       class="block w-full uppercase rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">Security / Credit Cheques (PDC)</h3>
                <button type="button" @click="addCreditCheque()"
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                    + Add Cheque
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="(chq, idx) in creditCheques" :key="idx">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500" x-text="`Cheque #${idx + 1}`"></span>
                            <button type="button" x-show="creditCheques.length > 1" @click="creditCheques.splice(idx, 1)"
                                    class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">Remove</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Cheque Number</label>
                                <input type="text" :name="`credit_cheques[${idx}][cheque_number]`" x-model="chq.cheque_number"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Bank Name</label>
                                <input type="text" :name="`credit_cheques[${idx}][bank_name]`" x-model="chq.bank_name"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Branch</label>
                                <input type="text" :name="`credit_cheques[${idx}][branch_name]`" x-model="chq.branch_name"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Cheque Date</label>
                                <input type="date" :name="`credit_cheques[${idx}][cheque_date]`" x-model="chq.cheque_date"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Amount (₹)</label>
                                <input type="number" step="0.01" :name="`credit_cheques[${idx}][amount]`" x-model="chq.amount"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Cheque Type</label>
                                <select :name="`credit_cheques[${idx}][cheque_type]`" x-model="chq.cheque_type"
                                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="regular">Regular</option>
                                    <option value="security">Security Cheque</option>
                                    <option value="pdc">Post-Dated Cheque (PDC)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active
        </label>

        <x-ui.button type="submit" variant="primary">Save Party</x-ui.button>
    </form>
</x-ui.card>

@push('scripts')
<script>
function partyFormController() {
    return {
        searchingGst: false,
        gstFeedback: {
            type: '',
            message: '',
            note: ''
        },
        form: {
            name: {!! json_encode(old('name', $item->name ?? '')) !!},
            gstin: {!! json_encode(old('gstin', $item->gstin ?? '')) !!},
            party_type: {!! json_encode(old('party_type', $item->party_type ?? 'customer')) !!},
            pan: {!! json_encode(old('pan', $item->pan ?? '')) !!},
            phone: {!! json_encode(old('phone', $item->phone ?? '')) !!},
            email: {!! json_encode(old('email', $item->email ?? '')) !!},
            address: {!! json_encode(old('address', $item->address ?? '')) !!},
            state: {!! json_encode(old('state', $item->state ?? '')) !!},
            pincode: {!! json_encode(old('pincode', $item->pincode ?? '')) !!},
        },
        bankAccounts: {!! json_encode($initialBankAccounts) !!},
        creditCheques: {!! json_encode($initialCreditCheques) !!},

        addBankAccount() {
            this.bankAccounts.push({
                account_holder_name: '',
                bank_name: '',
                branch_name: '',
                account_number: '',
                account_type: 'current',
                ifsc_code: ''
            });
        },
        addCreditCheque() {
            this.creditCheques.push({
                cheque_number: '',
                bank_name: '',
                branch_name: '',
                cheque_date: '',
                amount: '',
                cheque_type: 'regular'
            });
        },
        async fetchGstDetails() {
            const gstin = (this.form.gstin || '').trim().toUpperCase();
            this.form.gstin = gstin;

            if (gstin.length !== 15) {
                this.gstFeedback = {
                    type: 'error',
                    message: 'Please enter a valid 15-character GSTIN (e.g. 27AAAAA0000A1Z5).',
                    note: ''
                };
                return;
            }

            this.searchingGst = true;
            this.gstFeedback = { type: '', message: '', note: '' };

            try {
                const response = await fetch(`{{ route('masters.customers.gst-lookup') }}?gstin=${encodeURIComponent(gstin)}`);
                const data = await response.json();

                if (data.success && data.party) {
                    // Safe field mapping - only update if data is provided and non-empty
                    if (data.party.name && data.party.name.trim() !== '') {
                        this.form.name = data.party.name;
                    }
                    if (data.party.pan && data.party.pan.trim() !== '') {
                        this.form.pan = data.party.pan;
                    }
                    if (data.party.state && data.party.state.trim() !== '') {
                        this.form.state = data.party.state;
                    }
                    if (data.party.pincode && data.party.pincode.trim() !== '') {
                        this.form.pincode = data.party.pincode;
                    }
                    if (data.party.address && data.party.address.trim() !== '') {
                        this.form.address = data.party.address;
                    }

                    if (data.is_live) {
                        this.gstFeedback = {
                            type: 'success',
                            message: data.message || 'GSTIN verified successfully from official records.',
                            note: 'Contact Phone and Email are not published in public GST records and should be entered manually.'
                        };
                    } else {
                        this.gstFeedback = {
                            type: 'warning',
                            message: data.message || 'PAN and State deduced from GSTIN format.',
                            note: 'Party Name, Contact and Address must be entered manually.'
                        };
                    }
                } else {
                    this.gstFeedback = {
                        type: 'error',
                        message: data.message || 'GSTIN details not found.',
                        note: ''
                    };
                }
            } catch (err) {
                this.gstFeedback = {
                    type: 'error',
                    message: 'An error occurred while connecting to GST lookup service.',
                    note: 'Please verify the GSTIN and try again.'
                };
            } finally {
                this.searchingGst = false;
            }
        }
    };
}
</script>
@endpush
@endsection