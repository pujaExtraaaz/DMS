@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Company' : 'Create Company')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Company' : 'Create Company'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('organization.companies.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
    <form method="POST" action="{{ $item->exists ? route('organization.companies.update', $item) : route('organization.companies.store') }}" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
        @csrf @if($item->exists) @method('PUT') @endif

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Identity &amp; Classification</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <x-ui.input name="name" label="Trade Name" :value="old('name', $item->name)" required />
                <x-ui.input name="code" label="Company Code" :value="old('code', $item->code)" placeholder="e.g. CMP-0001" required />
                <x-ui.input name="legal_name" label="Legal Name" :value="old('legal_name', $item->legal_name)" />
                <div>
                    <label for="company_type" class="block text-sm font-medium text-slate-700 mb-1">Company Type <span class="text-red-500">*</span></label>
                    <select id="company_type" name="company_type" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-xs">
                        <option value="">-- Select Company Type --</option>
                        @foreach(['Private Limited Company', 'Public Limited Company', 'Limited Liability Partnership (LLP)', 'Partnership Firm', 'Sole Proprietorship', 'One Person Company (OPC)', 'Joint Venture', 'Section 8 Company / NGO', 'Associate Entity'] as $ct)
                            <option value="{{ $ct }}" @selected(old('company_type', $item->company_type ?? 'Private Limited Company') === $ct)>{{ $ct }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="business_group_id" class="block text-sm font-medium text-slate-700 mb-1">Business Group</label>
                    <select id="business_group_id" name="business_group_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-xs">
                        <option value="">-- None / Standalone Entity --</option>
                        @foreach($businessGroups as $bg)
                            <option value="{{ $bg->id }}" @selected(old('business_group_id', $item->business_group_id) == $bg->id)>{{ $bg->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-ui.input name="incorporation_no" label="Incorporation No." :value="old('incorporation_no', $item->incorporation_no)" />
                <x-ui.input name="incorporation_date" label="Incorporation Date" type="date" :value="old('incorporation_date', $item->incorporation_date?->format('Y-m-d'))" />
                <x-ui.input name="logo_emoji" label="Logo (Emoji)" :value="old('logo_emoji', $item->logo_emoji ?? '🏢')" placeholder="e.g. 🏢" />
                <div class="md:col-span-2 lg:col-span-3" x-data="{
                    savedLogoUrl: '{{ $item->logo_url ?? '' }}',
                    previewUrl: '',
                    removed: false,
                    errorMessage: '',
                    imageLoadFailed: false,

                    get currentSrc() {
                        if (this.removed) return '';
                        if (this.previewUrl) return this.previewUrl;
                        if (!this.imageLoadFailed && this.savedLogoUrl) return this.savedLogoUrl;
                        return '';
                    },

                    get hasLogo() {
                        return !!this.currentSrc;
                    },

                    handleFileChange(event) {
                        this.errorMessage = '';
                        const input = event.target;
                        const file = input.files && input.files[0];

                        if (!file) {
                            return;
                        }

                        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/svg+xml'];
                        const allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
                        const fileExt = (file.name.split('.').pop() || '').toLowerCase();

                        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExt)) {
                            this.errorMessage = 'Please select a valid image file (PNG, JPG, SVG, or WEBP).';
                            input.value = '';
                            return;
                        }

                        if (file.size > 2 * 1024 * 1024) {
                            this.errorMessage = 'File size exceeds 2 MB. Please select a smaller image.';
                            input.value = '';
                            return;
                        }

                        if (this.previewUrl) {
                            URL.revokeObjectURL(this.previewUrl);
                        }

                        this.previewUrl = URL.createObjectURL(file);
                        this.removed = false;
                        this.imageLoadFailed = false;
                    },

                    removeLogo() {
                        if (this.previewUrl) {
                            URL.revokeObjectURL(this.previewUrl);
                            this.previewUrl = '';
                        }
                        if (this.$refs.fileInput) {
                            this.$refs.fileInput.value = '';
                        }
                        this.removed = true;
                        this.errorMessage = '';
                    },

                    handleImageError() {
                        this.imageLoadFailed = true;
                    }
                }">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Company Logo</label>
                    <div class="flex items-start gap-4">
                        <div class="relative w-24 h-24 rounded-xl border border-slate-200 bg-slate-50 overflow-hidden flex items-center justify-center p-1.5 shrink-0 shadow-sm">
                            <template x-if="hasLogo">
                                <div class="w-full h-full flex items-center justify-center bg-white rounded-lg p-1">
                                    <img :src="currentSrc"
                                         alt="Company Logo Preview"
                                         x-on:error="handleImageError()"
                                         class="max-w-full max-h-full object-contain">
                                </div>
                            </template>
                            <template x-if="!hasLogo">
                                <div class="flex flex-col items-center justify-center text-slate-400 text-center p-1">
                                    <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                    <span class="text-[10px] text-slate-400 mt-1 font-medium">No Logo</span>
                                </div>
                            </template>
                        </div>

                        <div class="space-y-1.5 flex-1">
                            <input type="file"
                                   name="logo"
                                   x-ref="fileInput"
                                   accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,.png,.jpg,.jpeg,.webp,.svg"
                                   @change="handleFileChange($event)"
                                   class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer">

                            <input type="hidden" name="remove_logo" :value="removed && savedLogoUrl ? '1' : '0'">

                            <div class="flex items-center gap-3">
                                <template x-if="hasLogo">
                                    <button type="button"
                                            @click="removeLogo()"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-700 hover:underline">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Remove Logo
                                    </button>
                                </template>
                            </div>

                            <p class="text-xs text-slate-400">PNG, JPG, SVG or WEBP up to 2MB. Displayed on sidebar, header, and invoice prints.</p>

                            <template x-if="errorMessage">
                                <p class="text-xs font-medium text-rose-600 flex items-center gap-1" x-text="errorMessage"></p>
                            </template>
                            @error('logo')
                                <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Statutory Registrations</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="gstin" label="GSTIN" :value="old('gstin', $item->gstin)" />
                <x-ui.input name="pan" label="PAN" :value="old('pan', $item->pan)" />
                <x-ui.input name="tan" label="TAN" :value="old('tan', $item->tan)" />
                <x-ui.input name="cin" label="CIN" :value="old('cin', $item->cin)" />
                <x-ui.input name="udyam_registration_no" label="Udyam Registration No" :value="old('udyam_registration_no', $item->udyam_registration_no)" />
                <x-ui.select name="msme_category" label="MSME Category">
                    @foreach(['none' => 'Not applicable', 'micro' => 'Micro', 'small' => 'Small', 'medium' => 'Medium'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('msme_category', $item->msme_category ?? 'none')===$val)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
            </div>
        </div>

        @php
            $contacts = old('contacts');
            if (empty($contacts)) {
                $contacts = $item->exists ? $item->contacts : [[
                    'phone' => '',
                    'email' => '',
                    'website' => '',
                    'state' => '',
                    'pincode' => '',
                    'address' => '',
                ]];
            }
        @endphp

        <div x-data="{ contacts: {{ \Illuminate\Support\Js::from($contacts) }} }" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Contact Details</h3>
                    <p class="text-xs text-slate-500">Primary business contact and any additional department or branch contacts.</p>
                </div>
                <button type="button"
                        @click='contacts.push({ phone: "", email: "", website: "", state: "", pincode: "", address: "" })'
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shadow-sm">
                    + ADD CONTACT
                </button>
            </div>

            @if ($errors->has('contacts.*'))
                <div class="rounded-lg bg-rose-50 border border-rose-200 p-3 text-xs text-rose-700">
                    <p class="font-medium">Please correct the following contact information errors:</p>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        @foreach ($errors->get('contacts.*') as $messages)
                            @foreach ($messages as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-4">
                <template x-for="(contact, index) in contacts" :key="index">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3 relative shadow-xs">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700" x-text="`Contact #${index + 1}`"></span>
                                <template x-if="index === 0">
                                    <span class="inline-flex items-center rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">Primary Contact</span>
                                </template>
                                <template x-if="index > 0">
                                    <span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-800">Additional Contact</span>
                                </template>
                            </div>
                            <button type="button"
                                    x-show="contacts.length > 1"
                                    @click="contacts.splice(index, 1)"
                                    class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline transition">
                                Remove
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Phone</label>
                                <input type="text" :name="`contacts[${index}][phone]`" x-model="contact.phone" placeholder="e.g. +91 9876543210" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Email</label>
                                <input type="email" :name="`contacts[${index}][email]`" x-model="contact.email" placeholder="e.g. contact@example.com" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Website</label>
                                <input type="text" :name="`contacts[${index}][website]`" x-model="contact.website" placeholder="e.g. https://example.com" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">State</label>
                                <input type="text" :name="`contacts[${index}][state]`" x-model="contact.state" placeholder="e.g. Maharashtra" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Pincode</label>
                                <input type="text" :name="`contacts[${index}][pincode]`" x-model="contact.pincode" placeholder="e.g. 400001" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Address</label>
                            <textarea :name="`contacts[${index}][address]`" x-model="contact.address" rows="3" placeholder="Full postal address" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white"></textarea>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        @php
            $bankAccounts = old('bank_accounts');
            if (empty($bankAccounts)) {
                if ($item->exists) {
                    $bankAccounts = $item->bank_accounts;
                } else {
                    $bankAccounts = [[
                        'bank_name' => '',
                        'bank_account_no' => '',
                        'bank_ifsc' => '',
                        'upi_id' => '',
                        'swift_code' => '',
                        'branch' => '',
                        'bsr_code' => '',
                        'client_code' => '',
                    ]];
                }
            }
        @endphp

        <div x-data='{ bankAccounts: @json($bankAccounts) }' class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">Banking &amp; Payments</h3>
                <button type="button"
                        @click='bankAccounts.push({ bank_name: "", bank_account_no: "", bank_ifsc: "", upi_id: "", swift_code: "", branch: "", bsr_code: "", client_code: "" })'
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                    + ADD
                </button>
            </div>

            <div class="space-y-4">
                <template x-for="(account, index) in bankAccounts" :key="index">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500" x-text="`Bank Account #${index + 1}`"></span>
                            <button type="button"
                                    x-show="bankAccounts.length > 1"
                                    @click="bankAccounts.splice(index, 1)"
                                    class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">
                                Remove
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Bank Name</label>
                                <input type="text" :name="`bank_accounts[${index}][bank_name]`" x-model="account.bank_name" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Bank Account No</label>
                                <input type="text" :name="`bank_accounts[${index}][bank_account_no]`" x-model="account.bank_account_no" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">IFSC</label>
                                <input type="text" :name="`bank_accounts[${index}][bank_ifsc]`" x-model="account.bank_ifsc" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">UPI ID (for invoice QR)</label>
                                <input type="text" :name="`bank_accounts[${index}][upi_id]`" x-model="account.upi_id" placeholder="business@bank" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">SWIFT Code</label>
                                <input type="text" :name="`bank_accounts[${index}][swift_code]`" x-model="account.swift_code" placeholder="e.g. HDFCINBBXXX" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Branch</label>
                                <input type="text" :name="`bank_accounts[${index}][branch]`" x-model="account.branch" placeholder="Branch Name / Location" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">BSR Code</label>
                                <input type="text" :name="`bank_accounts[${index}][bsr_code]`" x-model="account.bsr_code" placeholder="7-digit BSR Code" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Client Code</label>
                                <input type="text" :name="`bank_accounts[${index}][client_code]`" x-model="account.client_code" placeholder="Client / Corporate ID" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Preferences</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">
                Invoice Terms &amp; Conditions
            </h3>

            <p class="text-xs text-slate-500 mb-4">
                These terms will automatically appear on new Purchase and Sales Invoices.
                You can still edit them for an individual invoice before posting.
            </p>

            <div class="grid grid-cols-1 gap-4">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Purchase Terms &amp; Conditions
                    </label>

                    <textarea
                        name="purchase_terms_and_conditions"
                        rows="5"
                        class="block w-full rounded-lg border-gray-300 text-sm"
                        placeholder="Enter Default Terms And Conditions For Purchase Invoices..."
                    >{{ old('purchase_terms_and_conditions', $item->purchase_terms_and_conditions) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Selling Terms &amp; Conditions
                    </label>

                    <textarea
                        name="selling_terms_and_conditions"
                        rows="5"
                        class="block w-full rounded-lg border-gray-300 text-sm"
                        placeholder="Enter Default Terms And Conditions For Sales Invoices..."
                    >{{ old('selling_terms_and_conditions', $item->selling_terms_and_conditions) }}</textarea>
                </div>

            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Business &amp; Financial</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <x-ui.input name="nature_of_business" label="Nature of Business" :value="old('nature_of_business', $item->nature_of_business)" placeholder="e.g. Consumer Electronics Distribution" />
                <x-ui.input name="authorized_capital" label="Authorized Capital (₹)" type="number" step="0.01" min="0" :value="old('authorized_capital', $item->authorized_capital)" />
                <x-ui.input name="paid_up_capital" label="Paid-up Capital (₹)" type="number" step="0.01" min="0" :value="old('paid_up_capital', $item->paid_up_capital)" />
                <x-ui.input name="annual_turnover" label="Annual Turnover (₹)" type="number" step="0.01" min="0" :value="old('annual_turnover', $item->annual_turnover)" />
                <x-ui.input name="financial_year" label="Financial Year" :value="old('financial_year', $item->financial_year ?? '2026-2027')" />
                <x-ui.input name="accounting_software" label="Accounting Software" :value="old('accounting_software', $item->accounting_software ?? 'TallyPrime')" />
                <x-ui.input name="branches_count_manual" label="Branches" type="number" min="0" :value="old('branches_count_manual', $item->branches_count_manual ?? 0)" />
                <x-ui.input name="employees_count_manual" label="Employees" type="number" min="0" :value="old('employees_count_manual', $item->employees_count_manual ?? 0)" />
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Relationship &amp; Compliance</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label for="relationship_type" class="block text-sm font-medium text-slate-700 mb-1">Relationship Type</label>
                    <select id="relationship_type" name="relationship_type" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-xs">
                        @foreach(['Sister Concern', 'Subsidiary', 'Holding', 'Associate', 'Joint Venture'] as $rel)
                            <option value="{{ $rel }}" @selected(old('relationship_type', $item->relationship_type ?? 'Sister Concern') === $rel)>{{ $rel }}</option>
                        @endforeach
                    </select>
                </div>
                <x-ui.input name="shareholding_percentage" label="Shareholding (%)" type="number" step="0.01" min="0" max="100" :value="old('shareholding_percentage', $item->shareholding_percentage ?? 0)" />
                <div>
                    <label for="compliance_status" class="block text-sm font-medium text-slate-700 mb-1">Compliance Status</label>
                    <select id="compliance_status" name="compliance_status" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-xs">
                        @foreach(['Compliant', 'Pending Review', 'Non-Compliant'] as $cs)
                            <option value="{{ $cs }}" @selected(old('compliance_status', $item->compliance_status ?? 'Compliant') === $cs)>{{ $cs }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Status</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="company_status" class="block text-sm font-medium text-slate-700 mb-1">Company Status <span class="text-rose-500">*</span></label>
                    <select id="company_status" name="is_active" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-xs">
                        <option value="1" @selected((string) old('is_active', $item->exists ? ($item->is_active ? '1' : '0') : '1') === '1')>Active</option>
                        <option value="0" @selected((string) old('is_active', $item->exists ? ($item->is_active ? '1' : '0') : '1') === '0')>Inactive</option>
                    </select>
                    <p class="text-xs text-slate-400 mt-1">Inactive companies cannot be set as default and allow their branches to be deleted if no dependencies exist.</p>
                    @error('is_active')
                        <p class="text-xs font-medium text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="pt-2">
            <x-ui.button type="submit" variant="primary">Save</x-ui.button>
        </div>
    </form>
</x-ui.card>
@endsection