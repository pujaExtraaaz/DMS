@props([
    'targetSelect' => 'customer_id',
    'customerTypes' => null,
])

@php
    $customerTypes = $customerTypes ?? \App\Domains\Master\Models\CustomerType::where('is_active', true)->orderBy('name')->get();
    $stateOptions = \App\Support\IndianStates::options();
    $stateCities = \App\Support\IndianCities::all();
@endphp

<div x-data="quickAddCustomerModal('{{ $targetSelect }}', @js($stateOptions), @js($stateCities))"
     x-on:open-quick-add-customer.window="openModal()"
     x-cloak>

    <!-- Modal Dialog -->
    <div x-show="open"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-4 border border-slate-200 my-8 max-h-[90vh] flex flex-col"
             @click.outside="closeModal()">

            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b pb-3 shrink-0">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Quick Add Customer</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Register a new customer without leaving the order form.</p>
                </div>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <!-- Error Notification -->
            <template x-if="errorMessage">
                <div class="p-3 bg-red-50 text-red-700 text-xs rounded-lg border border-red-200 shrink-0" x-text="errorMessage"></div>
            </template>

            <!-- Scrollable Form Fields -->
            <div class="space-y-3 overflow-y-auto pr-1 flex-1">
                <!-- GSTIN & Fetch -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">GSTIN</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="form.gstin" @input="onGstinInput()" maxlength="15" placeholder="e.g. 27AAAAA0000A1Z5"
                               class="block w-full uppercase rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="button" @click="fetchGstDetails()" :disabled="searchingGst"
                                class="inline-flex items-center px-3 py-2 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-lg hover:bg-indigo-100 disabled:opacity-50 shrink-0">
                            <span x-show="!searchingGst">Fetch</span>
                            <span x-show="searchingGst">...</span>
                        </button>
                    </div>
                </div>

                <!-- Customer Name & Customer Code -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Customer Name *</label>
                        <input type="text" x-model="form.name" required placeholder="Business or Customer Name"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Customer Code</label>
                        <input type="text" x-model="form.code" placeholder="Auto-generated if blank"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-xs">
                        <p class="text-[11px] text-slate-500 mt-0.5">Leave blank to auto-generate (PTY-...)</p>
                    </div>
                </div>

                <!-- PAN & Classification -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">PAN</label>
                        <input type="text" x-model="form.pan" maxlength="10" placeholder="e.g. AAAAA0000A"
                               class="block w-full uppercase rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Classification</label>
                        <select x-model="form.customer_type_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select classification</option>
                            @foreach($customerTypes as $ct)
                                <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Phone & Email -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Phone</label>
                        <input type="text" x-model="form.phone" placeholder="Contact number"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email</label>
                        <input type="email" x-model="form.email" placeholder="customer@example.com"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- State, City & Pincode -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">State</label>
                        <select x-model="form.state" @change="onStateChanged()" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select State / UT</option>
                            @foreach($stateOptions as $st)
                                <option value="{{ $st['name'] }}">{{ $st['name'] }} ({{ $st['code'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">City</label>
                        <input type="text" list="customer-quick-add-cities" x-model="form.city"
                               :placeholder="form.state ? 'Select or type city' : 'Select state first'"
                               :disabled="!form.state"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100 disabled:text-slate-400">
                        <datalist id="customer-quick-add-cities">
                            <template x-for="c in currentCities" :key="c">
                                <option :value="c"></option>
                            </template>
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Pincode</label>
                        <input type="text" x-model="form.pincode" maxlength="12" placeholder="Pincode"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Address -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Address</label>
                    <textarea x-model="form.address" rows="2" placeholder="Full address / Delivery address"
                              class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-2 pt-3 border-t shrink-0">
                <button type="button" @click="closeModal()"
                        class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg text-xs font-medium hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" @click="saveCustomer()" :disabled="saving"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-medium hover:bg-indigo-700 disabled:opacity-50 inline-flex items-center gap-1.5">
                    <svg x-show="saving" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-show="!saving">Save Customer</span>
                    <span x-show="saving">Saving...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Success Toast Alert -->
    <div x-show="toastMessage"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 flex items-center gap-2 bg-emerald-600 text-white px-4 py-3 rounded-lg shadow-xl text-sm font-medium"
         x-cloak>
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span x-text="toastMessage"></span>
    </div>
</div>

<script>
function quickAddCustomerModal(targetSelectName, statesList, stateCitiesMap) {
    return {
        open: false,
        saving: false,
        searchingGst: false,
        errorMessage: '',
        toastMessage: '',
        states: statesList || [],
        stateCities: stateCitiesMap || {},
        currentCities: [],
        panDerivedFromGst: false,
        form: {
            name: '',
            code: '',
            gstin: '',
            pan: '',
            customer_type_id: '',
            phone: '',
            email: '',
            state: '',
            city: '',
            pincode: '',
            address: '',
            party_type: 'sundry_debtors'
        },
        openModal() {
            this.reset();
            this.open = true;
        },
        closeModal() {
            this.open = false;
        },
        reset() {
            this.form = {
                name: '',
                code: '',
                gstin: '',
                pan: '',
                customer_type_id: '',
                phone: '',
                email: '',
                state: '',
                city: '',
                pincode: '',
                address: '',
                party_type: 'sundry_debtors'
            };
            this.errorMessage = '';
            this.saving = false;
            this.searchingGst = false;
            this.currentCities = [];
            this.panDerivedFromGst = false;
        },
        onGstinInput() {
            this.form.gstin = (this.form.gstin || '').toUpperCase();
            if (this.form.gstin.length >= 12 && (!this.form.pan || this.panDerivedFromGst)) {
                this.form.pan = this.form.gstin.substring(2, 12);
                this.panDerivedFromGst = true;
            }
        },
        onStateChanged() {
            const st = this.form.state;
            if (!st) {
                this.currentCities = [];
                this.form.city = '';
                return;
            }
            let list = this.stateCities[st] || [];
            if (!list || !list.length) {
                const lower = st.toLowerCase();
                for (const [key, cities] of Object.entries(this.stateCities)) {
                    if (key.toLowerCase() === lower) {
                        list = cities;
                        break;
                    }
                }
            }
            this.currentCities = Array.isArray(list) ? list : [];
        },
        async fetchGstDetails() {
            const gstin = (this.form.gstin || '').trim().toUpperCase();
            if (gstin.length !== 15) {
                this.errorMessage = 'Please enter a valid 15-character GSTIN.';
                return;
            }
            this.searchingGst = true;
            this.errorMessage = '';
            try {
                const response = await fetch(`{{ route('masters.customers.gst-lookup') }}?gstin=${encodeURIComponent(gstin)}`);
                const data = await response.json();
                if (data.success && data.party) {
                    this.form.name = data.party.name || this.form.name;
                    if (data.party.pan) {
                        this.form.pan = data.party.pan;
                    } else if (gstin.length >= 12) {
                        this.form.pan = gstin.substring(2, 12);
                    }
                    if (data.party.state) {
                        this.form.state = data.party.state;
                        this.onStateChanged();
                    }
                    this.form.pincode = data.party.pincode || this.form.pincode;
                    this.form.address = data.party.address || this.form.address;
                } else {
                    this.errorMessage = data.message || 'GST details not found.';
                }
            } catch (err) {
                this.errorMessage = 'Failed to connect to GST lookup service.';
            } finally {
                this.searchingGst = false;
            }
        },
        showToast(msg) {
            this.toastMessage = msg;
            setTimeout(() => {
                this.toastMessage = '';
            }, 4000);
        },
        async saveCustomer() {
            if (!this.form.name.trim()) {
                this.errorMessage = 'Customer name is required.';
                return;
            }
            this.saving = true;
            this.errorMessage = '';
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                                  document.querySelector('input[name="_token"]')?.value;
                const response = await fetch('{{ route("masters.customers.quick-add") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(this.form)
                });
                const res = await response.json();
                if (response.ok && res.success && res.party) {
                    const select = document.querySelector(`select[name="${targetSelectName}"]`);
                    if (select) {
                        const opt = document.createElement('option');
                        opt.value = res.party.id;
                        opt.textContent = `${res.party.name}${res.party.code ? ' (' + res.party.code + ')' : ''}`;
                        opt.selected = true;
                        select.appendChild(opt);
                        select.value = res.party.id;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    window.dispatchEvent(new CustomEvent('customer-quick-added', { detail: res.party }));
                    this.showToast(`Customer "${res.party.name}" added successfully.`);
                    this.closeModal();
                } else {
                    let msg = res.message || 'Failed to save customer.';
                    if (res.errors) {
                        const errList = Object.values(res.errors).flat().join(' ');
                        if (errList) msg = errList;
                    }
                    this.errorMessage = msg;
                }
            } catch (err) {
                this.errorMessage = 'An error occurred while saving customer.';
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>

