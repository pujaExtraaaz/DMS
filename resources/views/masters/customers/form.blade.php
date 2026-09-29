@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Party / Customer' : 'Create Party / Customer')
@section('content')
@php
    $existingContacts = old('contacts', $item->exists ? $item->contacts->map->only(['name','role','phone','alternate_phone','email','level','location','is_primary'])->values()->toArray() : []);
    $existingAddresses = old('addresses', $item->exists ? $item->addresses->map->only(['type','label','name','address','state','pincode','gstin','is_default'])->values()->toArray() : []);
    $existingBankAccounts = old('bank_accounts', $item->exists && $item->relationLoaded('bankAccounts') ? $item->bankAccounts->map->only(['bank_name','account_holder_name','account_number','account_type','ifsc_code','branch_name','branch_address','upi_id','is_primary'])->values()->toArray() : []);
    $existingCreditCheques = old('credit_cheques', $item->exists && $item->relationLoaded('creditCheques') ? $item->creditCheques->map->only(['cheque_number','bank_name','account_holder_name','cheque_date','amount','cheque_type','status','remarks'])->values()->toArray() : []);

    if (empty($existingContacts)) {
        $existingContacts = [[ 'name' => '', 'role' => '', 'phone' => '', 'alternate_phone' => '', 'email' => '', 'level' => 'primary', 'location' => '', 'is_primary' => true ]];
    }
    if (empty($existingAddresses)) {
        $existingAddresses = [[ 'type' => 'billing', 'label' => 'Head Office', 'name' => '', 'address' => '', 'state' => '', 'pincode' => '', 'gstin' => '', 'is_default' => true ]];
    }
    if (empty($existingBankAccounts)) {
        $existingBankAccounts = [[ 'bank_name' => '', 'account_holder_name' => '', 'account_number' => '', 'account_type' => 'current', 'ifsc_code' => '', 'branch_name' => '', 'branch_address' => '', 'upi_id' => '', 'is_primary' => true ]];
    }
    if (empty($existingCreditCheques)) {
        $existingCreditCheques = [];
    }
@endphp

<script>
    function partyForm() {
        return {
            partyName: @json(old('name', $item->name ?? '')),
            partyState: @json(old('state', $item->state ?? '')),
            partyPincode: @json(old('pincode', $item->pincode ?? '')),
            partyAddress: @json(old('address', $item->address ?? '')),
            gstin: @json(old('gstin', $item->gstin ?? '')),
            fetchingGst: false,
            gstMessage: '',
            gstStatusType: '',
            addresses: @json($existingAddresses),
            bankAccounts: @json($existingBankAccounts),
            creditCheques: @json($existingCreditCheques),
            setPrimaryBankAccount(index) {
                this.bankAccounts.forEach((acc, i) => {
                    acc.is_primary = (i === index);
                });
            },
            addBankAccount() {
                const isFirst = this.bankAccounts.length === 0;
                this.bankAccounts.push({
                    bank_name: '',
                    account_holder_name: this.partyName || '',
                    account_number: '',
                    account_type: 'current',
                    ifsc_code: '',
                    branch_name: '',
                    branch_address: '',
                    upi_id: '',
                    is_primary: isFirst,
                });
            },
            removeBankAccount(index) {
                const wasPrimary = this.bankAccounts[index]?.is_primary;
                this.bankAccounts.splice(index, 1);
                if (wasPrimary && this.bankAccounts.length > 0) {
                    this.bankAccounts[0].is_primary = true;
                }
            },
            addCreditCheque() {
                this.creditCheques.push({
                    cheque_number: '',
                    bank_name: '',
                    account_holder_name: this.partyName || '',
                    cheque_date: '',
                    amount: '',
                    cheque_type: 'credit_cheque',
                    status: 'pending',
                    remarks: '',
                });
            },
            removeCreditCheque(index) {
                this.creditCheques.splice(index, 1);
            },
            async fetchGstDetails() {
                const cleanedGstin = (this.gstin || '').trim().toUpperCase();
                this.gstin = cleanedGstin;

                if (!cleanedGstin) {
                    this.gstStatusType = 'error';
                    this.gstMessage = 'Please enter a GSTIN first.';
                    return;
                }

                if (!/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i.test(cleanedGstin)) {
                    this.gstStatusType = 'error';
                    this.gstMessage = 'Please enter a valid 15-digit GSTIN format.';
                    return;
                }

                this.fetchingGst = true;
                this.gstMessage = '';
                this.gstStatusType = '';

                try {
                    const response = await fetch('{{ route('masters.customers.gst-lookup') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            gstin: cleanedGstin,
                            exclude_id: '{{ $item->id ?? '' }}',
                        }),
                    });

                    const res = await response.json();

                    if (!response.ok || !res.ok) {
                        this.gstStatusType = 'error';
                        this.gstMessage = res.message || 'Failed to fetch GST details.';
                        return;
                    }

                    const d = res.data;
                    if (d) {
                        if (d.name) {
                            this.partyName = d.name;
                            if (this.bankAccounts.length > 0 && !this.bankAccounts[0].account_holder_name) {
                                this.bankAccounts[0].account_holder_name = d.name;
                            }
                        }
                        if (d.state) {
                            this.partyState = d.state;
                        }
                        if (d.pincode) {
                            this.partyPincode = d.pincode;
                        }
                        if (d.address) {
                            this.partyAddress = d.address;
                        }

                        if (Array.isArray(this.addresses) && this.addresses.length > 0) {
                            if (d.address) this.addresses[0].address = d.address;
                            if (d.state) this.addresses[0].state = d.state;
                            if (d.pincode) this.addresses[0].pincode = d.pincode;
                            if (cleanedGstin) this.addresses[0].gstin = cleanedGstin;
                            if (d.name) this.addresses[0].name = d.name;
                        }

                        const statusLabel = d.status || 'Active';
                        this.gstStatusType = statusLabel.toLowerCase() === 'active' ? 'success' : 'warning';
                        this.gstMessage = `GST Verified: ${d.trade_name || d.legal_name || d.name} (Status: ${statusLabel})`;
                    }
                } catch (err) {
                    this.gstStatusType = 'error';
                    this.gstMessage = 'Network error while fetching GST details.';
                } finally {
                    this.fetchingGst = false;
                }
            }
        };
    }
</script>

<x-ui.page-header :title="$item->exists ? 'Edit Party / Customer' : 'Create Party / Customer'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.customers.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>

<x-ui.card>
    <form
        method="POST"
        action="{{ $item->exists ? route('masters.customers.update', $item) : route('masters.customers.store') }}"
        class="space-y-6 max-w-5xl"
        x-data="partyForm()"
    >
        @csrf @if($item->exists) @method('PUT') @endif

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Party</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Party Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        x-model="partyName"
                        required
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    @error('name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.input name="code" label="Code" :value="old('code', $item->code)" readonly help="Auto-generated by system (read-only)" />

                <x-ui.select name="party_type" label="Party Type" placeholder="Select" required>
                    <option value="sundry_debtors" @selected(in_array(old('party_type', $item->party_type), ['sundry_debtors', 'customer', 'dealer'], true))>Sundry Debtors</option>
                    <option value="sundry_creditor" @selected(in_array(old('party_type', $item->party_type), ['sundry_creditor', 'supplier'], true))>Sundry Creditor</option>
                    <option value="both" @selected(old('party_type', $item->party_type) === 'both')>Both</option>
                </x-ui.select>

                <div x-data="{
                    types: {{ Js::from($customerTypes->map(fn($ct) => ['id' => (string) $ct->id, 'name' => $ct->name])) }},
                    selectedId: '{{ (string) old('customer_type_id', $item->customer_type_id) }}',
                    showModal: false,
                    newName: '',
                    saving: false,
                    error: null,
                    openModal() {
                        this.showModal = true;
                        this.newName = '';
                        this.error = null;
                        this.$nextTick(() => {
                            this.$refs.classificationInput?.focus();
                        });
                    },
                    closeModal() {
                        this.showModal = false;
                        this.newName = '';
                        this.error = null;
                    },
                    async save() {
                        const trimmedName = this.newName.trim();
                        if (!trimmedName) {
                            this.error = 'Classification name is required.';
                            return;
                        }

                        const duplicate = this.types.some(t => (t.name || '').trim().toLowerCase() === trimmedName.toLowerCase());
                        if (duplicate) {
                            this.error = 'Classification already exists.';
                            return;
                        }

                        this.saving = true;
                        this.error = null;

                        try {
                            const response = await fetch('{{ route('masters.customer-types.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({
                                    name: trimmedName,
                                    is_active: true,
                                }),
                            });

                            const data = await response.json();

                            if (!response.ok) {
                                if (data.errors && data.errors.name) {
                                    this.error = Array.isArray(data.errors.name) ? data.errors.name[0] : data.errors.name;
                                } else if (data.message) {
                                    this.error = data.message;
                                } else {
                                    this.error = 'Failed to create classification.';
                                }
                                this.saving = false;
                                return;
                            }

                            if (data.customer_type) {
                                const created = {
                                    id: String(data.customer_type.id),
                                    name: data.customer_type.name
                                };
                                this.types.push(created);
                                this.types.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
                                this.selectedId = created.id;
                                this.closeModal();
                            }
                        } catch (err) {
                            this.error = 'Network error while creating classification.';
                        } finally {
                            this.saving = false;
                        }
                    }
                }" class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label for="customer_type_id" class="block text-sm font-medium text-gray-700">
                            Classification <span class="text-red-500">*</span>
                        </label>
                        <button type="button" @click="openModal()" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            + Add Classification
                        </button>
                    </div>

                    <select id="customer_type_id" name="customer_type_id" x-model="selectedId" required class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Classification</option>
                        <template x-for="ct in types" :key="ct.id">
                            <option :value="ct.id" x-text="ct.name" :selected="ct.id == selectedId"></option>
                        </template>
                    </select>

                    @error('customer_type_id')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    <!-- Quick Add Classification Modal -->
                    <template x-teleport="body">
                        <div x-show="showModal" x-cloak style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                            <div class="w-full max-w-md rounded-xl bg-white shadow-2xl overflow-hidden" @click.outside="closeModal()" @keydown.escape.window="if (showModal) closeModal()">
                                <div class="flex items-center justify-between px-5 py-3 border-b bg-slate-50">
                                    <h3 class="text-sm font-semibold text-slate-800">Add Classification</h3>
                                    <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
                                </div>

                                <div class="p-5 space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Classification Name <span class="text-red-500">*</span></label>
                                        <input type="text" x-ref="classificationInput" x-model="newName" @keydown.enter.prevent="save()" placeholder="e.g. Distributor" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <template x-if="error">
                                        <div class="rounded-lg bg-red-50 border border-red-200 p-2.5 text-xs text-red-600" x-text="error"></div>
                                    </template>

                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" @click="closeModal()" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
                                        <button type="button" @click="save()" :disabled="saving || !newName.trim()" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                                            <span x-show="!saving">Save</span>
                                            <span x-show="saving">Saving...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <x-ui.select name="area_id" label="Area" placeholder="Select">
                    <option value=""></option>
                    @foreach($areas as $a)
                        <option value="{{ $a->id }}" @selected(old('area_id', $item->area_id)==$a->id)>{{ $a->name }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="route_id" label="Route" placeholder="Select">
                    <option value=""></option>
                    @foreach($routes as $r)
                        <option value="{{ $r->id }}" @selected(old('route_id', $item->route_id)==$r->id)>{{ $r->name }}</option>
                    @endforeach
                </x-ui.select>

                <div x-data="{
                    selectedManager: '{{ (string) old('sales_manager_id', $item->sales_manager_id) }}',
                    selectedSalesperson: '{{ (string) old('salesperson_id', $item->salesperson_id) }}',
                    salespersons: {{ Js::from($salespersonsData) }},
                    get filteredSalespersons() {
                        if (!this.selectedManager) {
                            return this.salespersons;
                        }
                        const filtered = this.salespersons.filter(s => !s.manager_user_id || String(s.manager_user_id) === String(this.selectedManager));
                        return filtered.length > 0 ? filtered : this.salespersons;
                    }
                }" class="contents">
                    <div>
                        <label for="sales_manager_id" class="block text-sm font-medium text-gray-700 mb-1">Sales Manager</label>
                        <select id="sales_manager_id" name="sales_manager_id" x-model="selectedManager" class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Sales Manager</option>
                            @foreach($salesManagers as $sm)
                                <option value="{{ $sm->id }}" @selected(old('sales_manager_id', $item->sales_manager_id) == $sm->id)>{{ $sm->name }}</option>
                            @endforeach
                        </select>
                        @error('sales_manager_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="salesperson_id" class="block text-sm font-medium text-gray-700 mb-1">Sales Person</label>
                        <select id="salesperson_id" name="salesperson_id" x-model="selectedSalesperson" class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Sales Person</option>
                            <template x-for="sp in filteredSalespersons" :key="sp.id">
                                <option :value="String(sp.id)" x-text="sp.name" :selected="String(sp.id) === String(selectedSalesperson)"></option>
                            </template>
                        </select>
                        @error('salesperson_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-1">
                    <label for="gstin" class="block text-sm font-medium text-gray-700">GSTIN</label>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            id="gstin"
                            name="gstin"
                            x-model="gstin"
                            @input="gstin = gstin.toUpperCase()"
                            maxlength="15"
                            placeholder="e.g. 27AAPFU0939F1ZV"
                            class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase tracking-wider"
                        >
                        <button
                            type="button"
                            @click="fetchGstDetails()"
                            :disabled="fetchingGst || !gstin.trim()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 whitespace-nowrap shadow-sm"
                        >
                            <svg x-show="fetchingGst" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="fetchingGst ? 'Fetching GST...' : 'Fetch GST Details'"></span>
                        </button>
                    </div>

                    <template x-if="gstMessage">
                        <div
                            class="mt-1.5 flex items-center gap-1.5 text-xs"
                            :class="{
                                'text-emerald-700': gstStatusType === 'success',
                                'text-amber-700': gstStatusType === 'warning',
                                'text-red-600': gstStatusType === 'error'
                            }"
                        >
                            <span
                                class="inline-block w-2 h-2 rounded-full shrink-0"
                                :class="{
                                    'bg-emerald-500': gstStatusType === 'success',
                                    'bg-amber-500': gstStatusType === 'warning',
                                    'bg-red-500': gstStatusType === 'error'
                                }"
                            ></span>
                            <span x-text="gstMessage"></span>
                        </div>
                    </template>

                    @error('gstin')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Default contact snapshot</h3>
            <p class="text-xs text-slate-500 mb-3">Kept for backward compatibility; the multi-contact list below is the source of truth going forward.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="phone" label="Primary Phone" :value="old('phone', $item->phone)" />
                <x-ui.input name="email" label="Primary Email" type="email" :value="old('email', $item->email)" />

                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <select
                        id="state"
                        name="state"
                        x-model="partyState"
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Select State</option>
                        @if($item->state && !in_array($item->state, $states, true))
                            <option value="{{ $item->state }}" selected>{{ $item->state }}</option>
                        @endif
                        @foreach($states as $st)
                            <option value="{{ $st }}" :selected="partyState === '{{ $st }}'">{{ $st }}</option>
                        @endforeach
                    </select>
                    @error('state')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="pincode" class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                    <input
                        type="text"
                        id="pincode"
                        name="pincode"
                        x-model="partyPincode"
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    @error('pincode')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="mt-3">
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea
                    id="address"
                    name="address"
                    x-model="partyAddress"
                    rows="3"
                    class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>
                @error('address')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div x-data='@json(["rows" => $existingContacts])'>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-700">Contacts (multi-level)</h3>
                <button type="button"
                        @click='rows.push({ name:"", role:"", phone:"", alternate_phone:"", email:"", level:"secondary", location:"", is_primary:false })'
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                    + Add contact
                </button>
            </div>
            <div class="space-y-3">
                <template x-for="(row, idx) in rows" :key="idx">
                    <div class="grid grid-cols-1 md:grid-cols-8 gap-3 items-end rounded-lg border border-slate-200 p-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" :name="`contacts[${idx}][name]`" x-model="row.name" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Level</label>
                            <select :name="`contacts[${idx}][level]`" x-model="row.level" class="block w-full rounded-lg border-gray-300 text-sm">
                                <option value="primary">Primary</option>
                                <option value="secondary">Secondary</option>
                                <option value="finance">Finance</option>
                                <option value="operations">Operations</option>
                                <option value="site">Site</option>
                            </select>
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Role</label>
                            <input type="text" :name="`contacts[${idx}][role]`" x-model="row.role" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Phone</label>
                            <input type="text" :name="`contacts[${idx}][phone]`" x-model="row.phone" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Alt Phone</label>
                            <input type="text" :name="`contacts[${idx}][alternate_phone]`" x-model="row.alternate_phone" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" :name="`contacts[${idx}][email]`" x-model="row.email" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Location</label>
                            <input type="text" :name="`contacts[${idx}][location]`" x-model="row.location" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-4 flex items-center justify-between">
                            <label class="flex items-center gap-2 text-xs">
                                <input type="hidden" :name="`contacts[${idx}][is_primary]`" value="0">
                                <input type="checkbox" :name="`contacts[${idx}][is_primary]`" value="1" x-model="row.is_primary" class="rounded border-gray-300 text-indigo-600">
                                Primary contact
                            </label>
                            <button type="button" x-show="rows.length > 1" @click="rows.splice(idx, 1)" class="text-xs font-semibold text-red-600 hover:text-red-800">Remove</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-700">Addresses / Locations</h3>
                <button type="button"
                        @click='addresses.push({ type:"shipping", label:"", name:"", address:"", state:"", pincode:"", gstin:"", is_default:false })'
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                    + Add address
                </button>
            </div>
            <div class="space-y-3">
                <template x-for="(row, idx) in addresses" :key="idx">
                    <div class="rounded-lg border border-slate-200 p-3 space-y-2">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                            <div class="md:col-span-1">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                                <select :name="`addresses[${idx}][type]`" x-model="row.type" class="block w-full rounded-lg border-gray-300 text-sm">
                                    <option value="billing">Billing</option>
                                    <option value="shipping">Shipping</option>
                                    <option value="site">Site</option>
                                    <option value="warehouse">Warehouse</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Label</label>
                                <input type="text" :name="`addresses[${idx}][label]`" x-model="row.label" class="block w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Consignee / Name</label>
                                <input type="text" :name="`addresses[${idx}][name]`" x-model="row.name" class="block w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-xs font-medium text-gray-700 mb-1">GSTIN</label>
                                <input type="text" :name="`addresses[${idx}][gstin]`" x-model="row.gstin" class="block w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div class="md:col-span-4">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Address</label>
                                <textarea :name="`addresses[${idx}][address]`" x-model="row.address" rows="2" class="block w-full rounded-lg border-gray-300 text-sm"></textarea>
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-xs font-medium text-gray-700 mb-1">State</label>
                                <select :name="`addresses[${idx}][state]`" x-model="row.state" class="block w-full rounded-lg border-gray-300 text-sm">
                                    <option value="">Select State</option>
                                    @foreach($states as $st)
                                        <option value="{{ $st }}">{{ $st }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Pincode</label>
                                <input type="text" :name="`addresses[${idx}][pincode]`" x-model="row.pincode" class="block w-full rounded-lg border-gray-300 text-sm">
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-xs">
                                <input type="hidden" :name="`addresses[${idx}][is_default]`" value="0">
                                <input type="checkbox" :name="`addresses[${idx}][is_default]`" value="1" x-model="row.is_default" class="rounded border-gray-300 text-indigo-600">
                                Default for this type
                            </label>
                            <button type="button" x-show="addresses.length > 1" @click="addresses.splice(idx, 1)" class="text-xs font-semibold text-red-600 hover:text-red-800">Remove</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-700">Bank Details</h3>
                <button
                    type="button"
                    @click="addBankAccount()"
                    class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                >
                    + Add Bank Account
                </button>
            </div>
            <div class="space-y-4">
                <template x-for="(acc, bIdx) in bankAccounts" :key="bIdx">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500" x-text="`Bank Account #${bIdx + 1}`"></span>
                                <template x-if="acc.is_primary">
                                    <span class="rounded bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-700">Primary</span>
                                </template>
                            </div>
                            <button
                                type="button"
                                x-show="bankAccounts.length > 1"
                                @click="removeBankAccount(bIdx)"
                                class="text-xs font-semibold text-red-600 hover:text-red-800"
                            >
                                Remove Bank Account
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Bank Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    :name="`bank_accounts[${bIdx}][bank_name]`"
                                    x-model="acc.bank_name"
                                    placeholder="e.g. HDFC Bank"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Account Holder Name</label>
                                <input
                                    type="text"
                                    :name="`bank_accounts[${bIdx}][account_holder_name]`"
                                    x-model="acc.account_holder_name"
                                    placeholder="e.g. Enterprise Pvt Ltd"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Account Number <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    :name="`bank_accounts[${bIdx}][account_number]`"
                                    x-model="acc.account_number"
                                    placeholder="e.g. 50100234567890"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Account Type</label>
                                <select
                                    :name="`bank_accounts[${bIdx}][account_type]`"
                                    x-model="acc.account_type"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="current">Current</option>
                                    <option value="savings">Savings</option>
                                    <option value="od">OD (Overdraft)</option>
                                    <option value="cc">CC (Cash Credit)</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">IFSC Code</label>
                                <input
                                    type="text"
                                    :name="`bank_accounts[${bIdx}][ifsc_code]`"
                                    x-model="acc.ifsc_code"
                                    @input="acc.ifsc_code = (acc.ifsc_code || '').toUpperCase()"
                                    maxlength="11"
                                    placeholder="e.g. HDFC0001234"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase tracking-wider font-mono"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Branch Name</label>
                                <input
                                    type="text"
                                    :name="`bank_accounts[${bIdx}][branch_name]`"
                                    x-model="acc.branch_name"
                                    placeholder="e.g. Fort Branch, Mumbai"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Branch Address</label>
                                <input
                                    type="text"
                                    :name="`bank_accounts[${bIdx}][branch_address]`"
                                    x-model="acc.branch_address"
                                    placeholder="e.g. MG Road, Fort"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">UPI ID</label>
                                <input
                                    type="text"
                                    :name="`bank_accounts[${bIdx}][upi_id]`"
                                    x-model="acc.upi_id"
                                    placeholder="e.g. company@okhdfcbank"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>
                        </div>

                        <div class="pt-1">
                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                                <input
                                    type="radio"
                                    name="primary_bank_selector"
                                    :checked="acc.is_primary"
                                    @change="setPrimaryBankAccount(bIdx)"
                                    class="text-indigo-600 focus:ring-indigo-500"
                                >
                                <input type="hidden" :name="`bank_accounts[${bIdx}][is_primary]`" :value="acc.is_primary ? 1 : 0">
                                <span>Primary Bank Account</span>
                            </label>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Credit &amp; risk</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="credit_limit" label="Credit Limit" type="number" step="0.01" :value="old('credit_limit', $item->credit_limit ?? 0)" />
                <x-ui.input name="credit_days" label="Credit Days" type="number" :value="old('credit_days', $item->credit_days ?? 0)" />
                <x-ui.input name="interest_rate" label="Interest Rate % p.a." type="number" step="0.01" :value="old('interest_rate', $item->interest_rate ?? 18)" />
                <x-ui.select name="credit_period_basis" label="Credit Period Basis">
                    @foreach(['cumulative'=>'Cumulative','monthly'=>'Monthly','quarterly'=>'Quarterly','yearly'=>'Yearly'] as $val=>$label)
                        <option value="{{ $val }}" @selected(old('credit_period_basis', $item->credit_period_basis ?? 'cumulative')==$val)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="credit_status" label="Credit Status">
                    @foreach(['open'=>'Open','restricted'=>'Restricted','frozen'=>'Frozen'] as $val=>$label)
                        <option value="{{ $val }}" @selected(old('credit_status', $item->credit_status ?? 'open')==$val)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Credit Notes / Collection Rules</label>
                <textarea name="credit_notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('credit_notes', $item->credit_notes) }}</textarea>
            </div>

            <!-- Credit Cheque Details Subsection -->
            <div class="mt-6 pt-5 border-t border-slate-200">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-700">Credit Cheque Details</h4>
                        <p class="text-xs text-slate-500">Record security, credit, or post-dated cheques received or issued for this party.</p>
                    </div>
                    <button
                        type="button"
                        @click="addCreditCheque()"
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                    >
                        + Add Cheque
                    </button>
                </div>

                <div class="space-y-4">
                    <template x-for="(chk, cIdx) in creditCheques" :key="cIdx">
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-600" x-text="`Cheque #${cIdx + 1}`"></span>
                                    <span
                                        class="rounded px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
                                        :class="{
                                            'bg-amber-100 text-amber-800': chk.status === 'pending' || !chk.status,
                                            'bg-blue-100 text-blue-800': chk.status === 'received' || chk.status === 'deposited',
                                            'bg-emerald-100 text-emerald-800': chk.status === 'cleared',
                                            'bg-red-100 text-red-800': chk.status === 'bounced' || chk.status === 'cancelled'
                                        }"
                                        x-text="chk.status ? chk.status.replace('_', ' ') : 'pending'"
                                    ></span>
                                </div>
                                <button
                                    type="button"
                                    @click="removeCreditCheque(cIdx)"
                                    class="text-xs font-semibold text-red-600 hover:text-red-800"
                                >
                                    Remove Cheque
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        Cheque Number <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        :name="`credit_cheques[${cIdx}][cheque_number]`"
                                        x-model="chk.cheque_number"
                                        placeholder="e.g. 000124"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                                    >
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Bank Name</label>
                                    <input
                                        type="text"
                                        :name="`credit_cheques[${cIdx}][bank_name]`"
                                        x-model="chk.bank_name"
                                        placeholder="e.g. State Bank of India"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Account Holder Name</label>
                                    <input
                                        type="text"
                                        :name="`credit_cheques[${cIdx}][account_holder_name]`"
                                        x-model="chk.account_holder_name"
                                        placeholder="e.g. Creative Systems"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Cheque Date</label>
                                    <input
                                        type="date"
                                        :name="`credit_cheques[${cIdx}][cheque_date]`"
                                        x-model="chk.cheque_date"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Cheque Amount (₹)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :name="`credit_cheques[${cIdx}][amount]`"
                                        x-model="chk.amount"
                                        placeholder="0.00"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                                    >
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Cheque Type</label>
                                    <select
                                        :name="`credit_cheques[${cIdx}][cheque_type]`"
                                        x-model="chk.cheque_type"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="security_cheque">Security Cheque</option>
                                        <option value="credit_cheque">Credit Cheque</option>
                                        <option value="post_dated_cheque">Post-Dated Cheque (PDC)</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                                    <select
                                        :name="`credit_cheques[${cIdx}][status]`"
                                        x-model="chk.status"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="pending">Pending</option>
                                        <option value="received">Received</option>
                                        <option value="deposited">Deposited</option>
                                        <option value="cleared">Cleared</option>
                                        <option value="bounced">Bounced</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Remarks / Cheque Notes</label>
                                    <input
                                        type="text"
                                        :name="`credit_cheques[${cIdx}][remarks]`"
                                        x-model="chk.remarks"
                                        placeholder="e.g. Held as collateral security against credit limit"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="creditCheques.length === 0">
                        <div class="rounded-lg border border-dashed border-slate-300 p-4 text-center text-xs text-slate-500">
                            No credit or security cheques recorded yet. Click <strong class="text-indigo-600 font-semibold cursor-pointer" @click="addCreditCheque()">+ Add Cheque</strong> to record one.
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active
        </label>

        <x-ui.button type="submit" variant="primary">Save</x-ui.button>
    </form>
</x-ui.card>
@endsection