@extends('layouts.dms')
@section('title', 'Company Profile')
@section('content')
<x-ui.page-header title="Company Profile" description="Manage identity, statutory registrations, bank accounts, and default invoice terms for your active company.">
</x-ui.page-header>

<x-ui.card>
    <form method="POST" action="{{ route('organization.company-profile.update') }}" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
        @csrf
        @method('PUT')

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Identity</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="name" label="Trade Name" :value="old('name', $item->name)" required />
                <x-ui.input name="legal_name" label="Legal Name" :value="old('legal_name', $item->legal_name)" />
                <div class="md:col-span-2" x-data="{
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
                if ($item->exists) {
                    $primary = [
                        'phone' => $item->phone ?? '',
                        'email' => $item->email ?? '',
                        'website' => $item->website ?? '',
                        'state' => $item->state ?? '',
                        'pincode' => $item->pincode ?? '',
                        'address' => $item->address ?? '',
                    ];
                    $additional = $item->additional_details['contacts'] ?? [];
                    $contacts = array_merge([$primary], $additional);
                } else {
                    $contacts = [[
                        'phone' => '',
                        'email' => '',
                        'website' => '',
                        'state' => '',
                        'pincode' => '',
                        'address' => '',
                    ]];
                }
            }
        @endphp

        <div x-data='{ contacts: @json($contacts) }' class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">Contact</h3>
                <button type="button"
                        @click='contacts.push({ phone: "", email: "", website: "", state: "", pincode: "", address: "" })'
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                    + ADD
                </button>
            </div>

            <div class="space-y-4">
                <template x-for="(contact, index) in contacts" :key="index">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500" x-text="`Contact #${index + 1}`"></span>
                            <button type="button"
                                    x-show="contacts.length > 1"
                                    @click="contacts.splice(index, 1)"
                                    class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">
                                Remove
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Phone</label>
                                <input type="text" :name="`contacts[${index}][phone]`" x-model="contact.phone" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Email</label>
                                <input type="email" :name="`contacts[${index}][email]`" x-model="contact.email" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Website</label>
                                <input type="text" :name="`contacts[${index}][website]`" x-model="contact.website" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">State</label>
                                <input type="text" :name="`contacts[${index}][state]`" x-model="contact.state" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Pincode</label>
                                <input type="text" :name="`contacts[${index}][pincode]`" x-model="contact.pincode" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Address</label>
                            <textarea :name="`contacts[${index}][address]`" x-model="contact.address" rows="3" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
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
                        'od_limit' => 0,
                        'interest_rate' => 0,
                    ]];
                }
            } else {
                $bankAccounts = array_map(function ($b) {
                    $b['od_limit'] = isset($b['od_limit']) ? (float) $b['od_limit'] : 0.0;
                    $b['interest_rate'] = isset($b['interest_rate']) ? (float) $b['interest_rate'] : 0.0;
                    return $b;
                }, $bankAccounts);
            }
        @endphp

        <script>
            function companyBankAndOdProfile(initialAccounts) {
                return {
                    bankAccounts: Array.isArray(initialAccounts) && initialAccounts.length ? initialAccounts : [{
                        bank_name: '',
                        bank_account_no: '',
                        bank_ifsc: '',
                        upi_id: '',
                        od_limit: 0,
                        interest_rate: 0
                    }],
                    annualInterest(acc) {
                        const limit = parseFloat(acc.od_limit) || 0;
                        const rate = parseFloat(acc.interest_rate) || 0;
                        return (limit * rate) / 100;
                    },
                    monthlyInterest(acc) {
                        return this.annualInterest(acc) / 12;
                    },
                    formatInr(val) {
                        const num = parseFloat(val) || 0;
                        return '₹' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    },
                    maskAccount(accNo) {
                        if (!accNo) return '—';
                        const str = String(accNo).trim();
                        if (str.length <= 4) return str;
                        return '•••• •••• ' + str.slice(-4);
                    },
                    addAccount() {
                        this.bankAccounts.push({
                            bank_name: '',
                            bank_account_no: '',
                            bank_ifsc: '',
                            upi_id: '',
                            od_limit: 0,
                            interest_rate: 0
                        });
                    },
                    removeAccount(index) {
                        if (this.bankAccounts.length > 1) {
                            this.bankAccounts.splice(index, 1);
                        }
                    }
                };
            }
            if (window.Alpine) {
                window.Alpine.data('companyBankAndOdProfile', companyBankAndOdProfile);
            } else {
                document.addEventListener('alpine:init', () => {
                    window.Alpine.data('companyBankAndOdProfile', companyBankAndOdProfile);
                });
            }
        </script>

        <div x-data="companyBankAndOdProfile({{ Illuminate\Support\Js::from($bankAccounts) }})" class="space-y-6">
            {{-- Banking & Payments Section --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">Banking &amp; Payments</h3>
                    <button type="button"
                            @click="addAccount()"
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
                                        @click="removeAccount(index)"
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">
                                    Remove
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">Bank Name</label>
                                    <input type="text" :name="`bank_accounts[${index}][bank_name]`" x-model="account.bank_name" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">Bank Account No</label>
                                    <input type="text" :name="`bank_accounts[${index}][bank_account_no]`" x-model="account.bank_account_no" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">IFSC</label>
                                    <input type="text" :name="`bank_accounts[${index}][bank_ifsc]`" x-model="account.bank_ifsc" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">UPI ID (for invoice QR)</label>
                                    <input type="text" :name="`bank_accounts[${index}][upi_id]`" x-model="account.upi_id" placeholder="business@bank" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- OD Limit & Interest Section --}}
            <div class="space-y-4 pt-2 border-t border-slate-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700">OD Limit &amp; Interest</h3>
                        <p class="text-xs text-slate-500">Configure overdraft limits and annual interest rates directly for each of your bank accounts.</p>
                    </div>
                </div>

                {{-- Message when no bank accounts are present --}}
                <template x-if="bankAccounts.length === 0">
                    <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50/70 p-6 text-center space-y-2">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-700 font-bold text-lg">
                            ₹
                        </div>
                        <h4 class="text-sm font-semibold text-amber-900">No Bank Account Configured</h4>
                        <p class="text-xs text-amber-800 max-w-md mx-auto">
                            Please add at least one bank account under the <strong>Banking &amp; Payments</strong> section above before configuring OD settings.
                        </p>
                    </div>
                </template>

                {{-- Direct per-bank-account OD configuration cards --}}
                <template x-for="(account, index) in bankAccounts" :key="index">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-4">
                        {{-- Read-only Bank Header --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/80 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600 text-white text-xs font-bold shrink-0" x-text="index + 1"></span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Bank Name:</span>
                                        <span class="text-sm font-bold text-slate-800" x-text="account.bank_name ? account.bank_name : 'Bank Account #' + (index + 1)"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600 mt-0.5">
                                        <span>A/C No: <span class="font-mono font-medium text-slate-900" x-text="maskAccount(account.bank_account_no)"></span></span>
                                        <template x-if="account.bank_ifsc">
                                            <span class="text-slate-400">&bull; <span class="text-slate-600">IFSC: <span class="font-mono text-slate-900" x-text="account.bank_ifsc"></span></span></span>
                                        </template>
                                        <template x-if="account.upi_id">
                                            <span class="text-slate-400">&bull; <span class="text-slate-600">UPI: <span class="font-mono text-slate-900" x-text="account.upi_id"></span></span></span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <template x-if="parseFloat(account.od_limit) > 0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 self-start sm:self-auto">
                                    OD Facility Active
                                </span>
                            </template>
                        </div>

                        {{-- Editable OD Limit & Interest Rate Inputs --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">
                                    OD Limit (₹)
                                </label>
                                <div class="relative rounded-lg shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-slate-500 sm:text-sm">₹</span>
                                    </div>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           :name="`bank_accounts[${index}][od_limit]`"
                                           x-model.number="account.od_limit"
                                           placeholder="0.00"
                                           class="block w-full rounded-lg border-gray-300 pl-7 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500">Approved overdraft limit for this account (no negative values).</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">
                                    Interest Rate (% per annum)
                                </label>
                                <div class="relative rounded-lg shadow-sm">
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           max="100"
                                           :name="`bank_accounts[${index}][interest_rate]`"
                                           x-model.number="account.interest_rate"
                                           placeholder="e.g. 10.50"
                                           class="block w-full rounded-lg border-gray-300 pr-8 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-slate-500 sm:text-sm">%</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500">Annual interest rate applicable (0.00% to 100.00%).</p>
                            </div>
                        </div>

                        {{-- Interest Calculation Card for this account --}}
                        <div class="rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/70 via-white to-slate-50 p-4 space-y-3">
                            <div class="flex items-center justify-between border-b border-indigo-100/70 pb-2">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded bg-indigo-600 text-white text-xs font-bold">
                                        ₹
                                    </div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-900">Interest Calculation</span>
                                </div>
                                <span class="text-xs text-slate-600 font-medium" x-text="`${account.bank_name || 'Bank'} (${maskAccount(account.bank_account_no)})`"></span>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div class="rounded-lg bg-white p-3 border border-slate-200 shadow-xs">
                                    <p class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">OD Limit</p>
                                    <p class="text-base font-bold text-slate-900 mt-0.5" x-text="formatInr(account.od_limit)"></p>
                                </div>

                                <div class="rounded-lg bg-white p-3 border border-slate-200 shadow-xs">
                                    <p class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Interest Rate</p>
                                    <p class="text-base font-bold text-indigo-600 mt-0.5" x-text="`${(parseFloat(account.interest_rate) || 0).toFixed(2)}% p.a.`"></p>
                                </div>

                                <div class="rounded-lg bg-white p-3 border border-indigo-200 shadow-xs">
                                    <p class="text-[11px] font-medium text-indigo-600 uppercase tracking-wider">Estimated Annual Interest</p>
                                    <p class="text-base font-bold text-indigo-700 mt-0.5" x-text="formatInr(annualInterest(account))"></p>
                                </div>

                                <div class="rounded-lg bg-white p-3 border border-indigo-200 shadow-xs">
                                    <p class="text-[11px] font-medium text-indigo-600 uppercase tracking-wider">Estimated Monthly Interest</p>
                                    <p class="text-base font-bold text-indigo-700 mt-0.5" x-text="formatInr(monthlyInterest(account))"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Important note once at the bottom of the section --}}
                <div class="rounded-lg bg-amber-50 border border-amber-200/80 p-3 text-[11px] text-amber-800 flex items-start gap-2">
                    <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="font-semibold text-amber-900">Important Note on Estimates</p>
                        <p class="mt-0.5 text-amber-800/90 leading-relaxed">
                            These figures are estimates based on the full OD limit being used throughout the period. Actual interest must be calculated using the outstanding OD balance and the bank's applicable interest calculation rules.
                        </p>
                    </div>
                </div>
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
                        placeholder="Enter default terms and conditions for Purchase Invoices..."
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
                        placeholder="Enter default terms and conditions for Sales Invoices..."
                    >{{ old('selling_terms_and_conditions', $item->selling_terms_and_conditions) }}</textarea>
                </div>
            </div>
        </div>

        <x-ui.button type="submit" variant="primary">Save Changes</x-ui.button>
    </form>
</x-ui.card>
@endsection