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
                    logoPreview: '{{ $item->logo_path ? asset('storage/' . $item->logo_path) : '' }}',
                    previewImage(e) {
                        const file = e.target.files[0];
                        if (file) {
                            this.logoPreview = URL.createObjectURL(file);
                        }
                    }
                }">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Company Logo</label>
                    <div class="flex items-center gap-4">
                        <template x-if="logoPreview">
                            <div class="relative w-24 h-24 rounded-lg border border-slate-200 overflow-hidden bg-slate-50 flex items-center justify-center p-1">
                                <img :src="logoPreview" class="max-w-full max-h-full object-contain">
                            </div>
                        </template>
                        <div class="space-y-1">
                            <input type="file" name="logo" accept="image/*" @change="previewImage($event)"
                                   class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p class="text-xs text-slate-400">PNG, JPG, SVG or WEBP up to 2MB. Displayed on sidebar, header, and invoice prints.</p>
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
                    $primaryBank = [
                        'bank_name' => $item->bank_name ?? '',
                        'bank_account_no' => $item->bank_account_no ?? '',
                        'bank_ifsc' => $item->bank_ifsc ?? '',
                        'upi_id' => $item->upi_id ?? '',
                    ];
                    $additionalBanks = $item->additional_details['bank_accounts'] ?? [];
                    $bankAccounts = array_merge([$primaryBank], $additionalBanks);
                } else {
                    $bankAccounts = [[
                        'bank_name' => '',
                        'bank_account_no' => '',
                        'bank_ifsc' => '',
                        'upi_id' => '',
                    ]];
                }
            }
        @endphp

        <div x-data='{ bankAccounts: @json($bankAccounts) }' class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">Banking &amp; Payments</h3>
                <button type="button"
                        @click='bankAccounts.push({ bank_name: "", bank_account_no: "", bank_ifsc: "", upi_id: "" })'
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
                        </div>
                    </div>
                </template>
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