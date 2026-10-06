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

    $defaultAddress = [
        'id' => null,
        'label' => 'Head Office / Billing',
        'contact_person' => '',
        'contact_phone' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'state' => '',
        'pincode' => '',
        'type' => 'both',
        'is_default_billing' => true,
        'is_default_delivery' => true,
    ];
    $initialAddresses = old('addresses', $initialAddresses ?? (
        ($item->exists && $item->relationLoaded('addresses') && $item->addresses->isNotEmpty()) 
            ? $item->addresses->map(function ($a) {
                return [
                    'id' => $a->id,
                    'label' => $a->label ?? 'Office',
                    'contact_person' => $a->contact_person ?? '',
                    'contact_phone' => $a->contact_phone ?? '',
                    'address_line_1' => $a->address_line_1 ?? $a->address ?? '',
                    'address_line_2' => $a->address_line_2 ?? '',
                    'city' => $a->city ?? '',
                    'state' => $a->state ?? '',
                    'pincode' => $a->pincode ?? '',
                    'type' => $a->type ?? 'both',
                    'is_default_billing' => (bool) ($a->is_default_billing || $a->is_default),
                    'is_default_delivery' => (bool) ($a->is_default_delivery),
                ];
            })->toArray()
            : [$defaultAddress]
    ));
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
                        @foreach([\App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_DEBTORS => 'Sundry Debtors', \App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_CREDITORS => 'Sundry Creditors', \App\Domains\Master\Models\Customer::PARTY_TYPE_BOTH => 'Both'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('party_type', $item->party_type_key ?? 'sundry_debtors')===$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="customer_type_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Classification</label>
                        <button type="button"
                                @click="openClassificationModal()"
                                class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1 hover:underline focus:outline-none">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Manually
                        </button>
                    </div>
                    <select name="customer_type_id"
                            id="customer_type_id"
                            x-ref="classificationSelect"
                            x-model="selectedCustomerTypeId"
                            @change="onClassificationChange($event)"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Classification</option>
                        @foreach($customerTypes as $t)
                            <option value="{{ $t->id }}" @selected(old('customer_type_id', $item->customer_type_id)==$t->id)>{{ $t->name }}</option>
                        @endforeach
                        <template x-for="t in customClassifications" :key="t.id">
                            <option :value="t.id" x-text="t.name"></option>
                        </template>
                        <option value="__add_manually__" class="font-semibold text-indigo-600 bg-indigo-50">+ Add Manually</option>
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

                <input type="hidden" name="address" :value="form.address">
                <input type="hidden" name="state" :value="form.state">
                <input type="hidden" name="pincode" :value="form.pincode">
            </div>
        </div>

        <!-- Addresses (Multiple Delivery & Billing Locations) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700">Addresses &amp; Locations</h3>
                    <p class="text-xs text-slate-500">Multiple addresses allowed per party. Set default billing and delivery addresses.</p>
                </div>
                <button type="button" @click="addAddress()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    + Add Address
                </button>
            </div>

            <div class="space-y-4">
                <template x-for="(addr, idx) in addresses" :key="idx">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-4 transition shadow-xs">
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-200/80">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-600" x-text="`Address #${idx + 1}`"></span>
                                <span x-show="addr.label" class="text-xs font-medium text-slate-500" x-text="`(${addr.label})`"></span>
                            </div>
                            <div class="flex items-center gap-4">
                                <label class="inline-flex items-center gap-1.5 text-xs font-semibold cursor-pointer text-slate-700">
                                    <input type="radio" name="default_billing_radio" :checked="addr.is_default_billing" @change="setDefaultBilling(idx)"
                                           class="text-indigo-600 focus:ring-indigo-500">
                                    <span>Default Billing</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 text-xs font-semibold cursor-pointer text-slate-700">
                                    <input type="radio" name="default_delivery_radio" :checked="addr.is_default_delivery" @change="setDefaultDelivery(idx)"
                                           class="text-emerald-600 focus:ring-emerald-500">
                                    <span>Default Delivery</span>
                                </label>
                                <button type="button" x-show="addresses.length > 1" @click="removeAddress(idx)"
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">
                                    Remove
                                </button>
                            </div>
                        </div>

                        <!-- Hidden fields to submit to backend -->
                        <input type="hidden" :name="`addresses[${idx}][id]`" :value="addr.id || ''">
                        <input type="hidden" :name="`addresses[${idx}][is_default_billing]`" :value="addr.is_default_billing ? 1 : 0">
                        <input type="hidden" :name="`addresses[${idx}][is_default_delivery]`" :value="addr.is_default_delivery ? 1 : 0">

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Address Label *</label>
                                <input type="text" :name="`addresses[${idx}][label]`" x-model="addr.label" placeholder="e.g. Head Office, Warehouse"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Address Type</label>
                                <select :name="`addresses[${idx}][type]`" x-model="addr.type"
                                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="both">Both (Billing &amp; Delivery)</option>
                                    <option value="billing">Billing Only</option>
                                    <option value="delivery">Delivery Only</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Contact Person (optional)</label>
                                <input type="text" :name="`addresses[${idx}][contact_person]`" x-model="addr.contact_person" placeholder="Name"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Contact Phone (optional)</label>
                                <input type="text" :name="`addresses[${idx}][contact_phone]`" x-model="addr.contact_phone" placeholder="Phone"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Address Line 1 *</label>
                                <input type="text" :name="`addresses[${idx}][address_line_1]`" x-model="addr.address_line_1" @input="syncPrimaryAddress()"
                                       placeholder="Flat / Door / Block No., Premises, Street"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Address Line 2 (optional)</label>
                                <input type="text" :name="`addresses[${idx}][address_line_2]`" x-model="addr.address_line_2" @input="syncPrimaryAddress()"
                                       placeholder="Area, Landmark, Locality"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">State *</label>
                                <select :name="`addresses[${idx}][state]`" x-model="addr.state" @change="syncPrimaryAddress()"
                                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">Select State / UT</option>
                                    @foreach($states as $code => $name)
                                        <option value="{{ $name }}">{{ $name }} ({{ $code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">City *</label>
                                <input type="text" :name="`addresses[${idx}][city]`" x-model="addr.city" :list="`city-list-${idx}`"
                                       placeholder="Enter or select city"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <datalist :id="`city-list-${idx}`">
                                    <template x-for="c in getCities(addr.state)" :key="c">
                                        <option :value="c"></option>
                                    </template>
                                </datalist>
                                <template x-if="addr.city && addr.state && !isCityValidForState(addr.city, addr.state)">
                                    <p class="text-[11px] text-rose-600 mt-1 font-medium">⚠️ Note: Selected city does not belong to <span x-text="addr.state"></span></p>
                                </template>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">PIN Code *</label>
                                <input type="text" :name="`addresses[${idx}][pincode]`" x-model="addr.pincode" maxlength="12" @input="syncPrimaryAddress()"
                                       placeholder="6-digit PIN code"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                        </div>
                    </div>
                </template>
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

        <!-- Add Classification Modal -->
        <div x-show="classificationModalOpen"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title"
             role="dialog"
             aria-modal="true"
             @keydown.escape.window="closeClassificationModal()">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="classificationModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 transition-opacity bg-slate-900/50 backdrop-blur-sm"
                     @click="closeClassificationModal()"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="classificationModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-2xl shadow-xl sm:my-8"
                     @click.stop>

                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-semibold text-slate-800" id="modal-title">Add Classification</h3>
                        <button type="button" @click="closeClassificationModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Error Feedback -->
                    <div x-show="classificationError" x-cloak class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs font-medium text-red-700 flex items-start gap-2">
                        <svg class="w-4 h-4 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="classificationError"></span>
                    </div>

                    <!-- Success Feedback -->
                    <div x-show="classificationSuccess" x-cloak class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs font-medium text-emerald-700 flex items-start gap-2">
                        <svg class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="classificationSuccess"></span>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Classification Name *</label>
                            <input type="text"
                                   x-ref="classificationInput"
                                   x-model="newClassificationName"
                                   :disabled="classificationSaving"
                                   @keydown.enter.prevent="saveClassification()"
                                   placeholder="e.g. Retailer, Super Stockist, Corporate"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100 disabled:cursor-not-allowed">
                            <p class="text-[11px] text-slate-500 mt-1">This classification will be saved to the master database and selected automatically.</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button"
                                    @click="closeClassificationModal()"
                                    :disabled="classificationSaving"
                                    class="px-4 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition">
                                Cancel
                            </button>
                            <button type="button"
                                    @click="saveClassification()"
                                    :disabled="classificationSaving"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 shadow-sm transition">
                                <svg x-show="classificationSaving" class="w-3.5 h-3.5 animate-spin -ml-0.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-text="classificationSaving ? 'Saving...' : 'Save'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
        selectedCustomerTypeId: {!! json_encode((string) old('customer_type_id', $item->customer_type_id ?? '')) !!},
        previousCustomerTypeId: {!! json_encode((string) old('customer_type_id', $item->customer_type_id ?? '')) !!},
        customClassifications: [],
        knownClassificationNames: {!! json_encode($customerTypes->pluck('name')->map(fn($n) => mb_strtolower(trim($n)))->values()) !!},
        classificationModalOpen: false,
        newClassificationName: '',
        classificationSaving: false,
        classificationError: '',
        classificationSuccess: '',

        openClassificationModal() {
            this.classificationModalOpen = true;
            this.newClassificationName = '';
            this.classificationError = '';
            this.classificationSuccess = '';
            this.$nextTick(() => {
                this.$refs.classificationInput?.focus();
            });
        },
        closeClassificationModal() {
            this.classificationModalOpen = false;
            this.classificationError = '';
            this.classificationSuccess = '';
            if (this.selectedCustomerTypeId === '__add_manually__') {
                this.selectedCustomerTypeId = this.previousCustomerTypeId;
            }
        },
        onClassificationChange(event) {
            if (this.selectedCustomerTypeId === '__add_manually__') {
                this.openClassificationModal();
            } else {
                this.previousCustomerTypeId = this.selectedCustomerTypeId;
            }
        },
        async saveClassification() {
            const rawName = this.newClassificationName || '';
            const name = rawName.trim();

            if (!name) {
                this.classificationError = 'Classification Name is required.';
                return;
            }

            // Client-side case-insensitive duplicate check
            const lowerName = name.toLowerCase();
            if (this.knownClassificationNames.includes(lowerName)) {
                this.classificationError = 'A classification with this name already exists.';
                return;
            }

            this.classificationSaving = true;
            this.classificationError = '';
            this.classificationSuccess = '';

            try {
                const response = await fetch("{{ route('masters.customer-types.quick-store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name: name })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    this.classificationError = data.message || 'Failed to save classification.';
                    this.classificationSaving = false;
                    return;
                }

                const newItem = data.item;
                const newIdStr = String(newItem.id);

                const alreadyCustom = this.customClassifications.some(c => String(c.id) === newIdStr);
                if (!alreadyCustom) {
                    this.customClassifications.push({
                        id: newItem.id,
                        name: newItem.name
                    });
                }
                this.knownClassificationNames.push(newItem.name.trim().toLowerCase());

                const selectEl = this.$refs.classificationSelect;
                if (selectEl && !selectEl.querySelector(`option[value="${newItem.id}"]`)) {
                    const opt = new Option(newItem.name, newItem.id);
                    const addManuallyOpt = selectEl.querySelector('option[value="__add_manually__"]');
                    selectEl.insertBefore(opt, addManuallyOpt);
                }

                this.selectedCustomerTypeId = newIdStr;
                this.previousCustomerTypeId = newIdStr;
                this.classificationSuccess = data.message || 'Classification created successfully!';

                setTimeout(() => {
                    this.closeClassificationModal();
                }, 700);
            } catch (err) {
                this.classificationError = 'An error occurred while saving the classification. Please try again.';
            } finally {
                this.classificationSaving = false;
            }
        },

        form: {
            name: {!! json_encode(old('name', $item->name ?? '')) !!},
            gstin: {!! json_encode(old('gstin', $item->gstin ?? '')) !!},
            party_type: {!! json_encode(old('party_type', $item->party_type_key ?? 'sundry_debtors')) !!},
            pan: {!! json_encode(old('pan', $item->pan ?? '')) !!},
            phone: {!! json_encode(old('phone', $item->phone ?? '')) !!},
            email: {!! json_encode(old('email', $item->email ?? '')) !!},
            address: {!! json_encode(old('address', $item->address ?? '')) !!},
            state: {!! json_encode(old('state', $item->state ?? '')) !!},
            pincode: {!! json_encode(old('pincode', $item->pincode ?? '')) !!},
        },
        bankAccounts: {!! json_encode($initialBankAccounts) !!},
        creditCheques: {!! json_encode($initialCreditCheques) !!},
        addresses: {!! json_encode($initialAddresses) !!},
        stateCities: {!! json_encode($stateCities ?? \App\Support\IndianCities::all()) !!},

        getCities(stateName) {
            if (!stateName) return [];
            return this.stateCities[stateName] || [];
        },

        addAddress() {
            const hasBilling = this.addresses.some(a => a.is_default_billing);
            const hasDelivery = this.addresses.some(a => a.is_default_delivery);
            this.addresses.push({
                id: null,
                label: 'Branch / Warehouse',
                contact_person: '',
                contact_phone: '',
                address_line_1: '',
                address_line_2: '',
                city: '',
                state: this.form.state || '',
                pincode: '',
                type: 'delivery',
                is_default_billing: !hasBilling,
                is_default_delivery: !hasDelivery
            });
        },

        removeAddress(idx) {
            if (this.addresses.length <= 1) return;
            const wasBilling = this.addresses[idx].is_default_billing;
            const wasDelivery = this.addresses[idx].is_default_delivery;
            this.addresses.splice(idx, 1);
            if (wasBilling && this.addresses.length > 0) {
                this.setDefaultBilling(0);
            }
            if (wasDelivery && this.addresses.length > 0) {
                this.setDefaultDelivery(0);
            }
            this.syncPrimaryAddress();
        },

        setDefaultBilling(index) {
            this.addresses.forEach((addr, i) => {
                addr.is_default_billing = (i === index);
            });
            this.syncPrimaryAddress();
        },

        setDefaultDelivery(index) {
            this.addresses.forEach((addr, i) => {
                addr.is_default_delivery = (i === index);
            });
        },

        syncPrimaryAddress() {
            const primary = this.addresses.find(a => a.is_default_billing) || this.addresses[0];
            if (primary) {
                const line1 = primary.address_line_1 || '';
                const line2 = primary.address_line_2 || '';
                this.form.address = (line1 + (line2 ? ', ' + line2 : '')).trim();
                if (primary.state) this.form.state = primary.state;
                if (primary.pincode) this.form.pincode = primary.pincode;
            }
        },

        isCityValidForState(city, state) {
            if (!city || !state) return true;
            const cleanCity = city.trim().toLowerCase();
            const citiesInState = (this.stateCities[state] || []).map(c => c.toLowerCase());
            if (citiesInState.includes(cleanCity)) return true;
            for (const [st, cities] of Object.entries(this.stateCities)) {
                if (st === state) continue;
                if (cities.map(c => c.toLowerCase()).includes(cleanCity)) {
                    return false;
                }
            }
            return true;
        },

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
                        if (this.addresses.length > 0) {
                            this.addresses[0].address_line_1 = data.party.address;
                            if (data.party.state) {
                                this.addresses[0].state = data.party.state;
                            }
                            if (data.party.pincode) {
                                this.addresses[0].pincode = data.party.pincode;
                            }
                            if (data.party.city) {
                                this.addresses[0].city = data.party.city;
                            }
                        }
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