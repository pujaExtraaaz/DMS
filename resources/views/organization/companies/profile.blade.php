@extends('layouts.dms')
@section('title', 'Company Profile - '.$company->name)

@section('content')
<x-ui.page-header title="Company Profile" description="Maintain company identity, branding logo, tax details, and contact information.">
    <x-slot name="actions">
        @if($isSuperAdmin && $allCompanies->count() > 1)
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500 font-medium">Switch Company:</span>
                <select
                    onchange="window.location.href='{{ route('organization.company-profile.edit') }}?company_id=' + this.value"
                    class="rounded-lg border-gray-300 text-xs font-semibold py-1.5 focus:border-indigo-500 focus:ring-indigo-500 bg-white"
                >
                    @foreach($allCompanies as $c)
                        <option value="{{ $c->id }}" @selected($company->id === $c->id)>{{ $c->name }} ({{ $c->code }})</option>
                    @endforeach
                </select>
            </div>
        @endif
    </x-slot>
</x-ui.page-header>

<form
    method="POST"
    action="{{ route('organization.company-profile.update') }}"
    enctype="multipart/form-data"
    class="space-y-6 max-w-5xl"
    x-data="companyProfileForm(@js($company->logo_url))"
>
    @csrf
    @method('PUT')

    @if($isSuperAdmin)
        <input type="hidden" name="company_id" value="{{ $company->id }}">
    @endif

    <input type="hidden" name="remove_logo" :value="removeLogoFlag ? 1 : 0">

    <!-- Section A: Company Branding -->
    <x-ui.card>
        <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
            Company Branding
        </h3>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
            <div class="relative flex h-32 w-32 shrink-0 items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-white p-2 shadow-sm overflow-hidden">
                <template x-if="logoPreview">
                    <img :src="logoPreview"
                         alt="{{ $company->name }} Logo"
                         class="h-full w-full object-contain rounded-xl">
                </template>
                <template x-if="!logoPreview">
                    <div class="text-center text-slate-400 p-2">
                        <svg class="mx-auto h-8 w-8 opacity-60" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        <span class="text-[10px] font-medium block mt-1">No Logo</span>
                    </div>
                </template>
            </div>

            <div class="space-y-2 flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shadow-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        <span x-text="logoPreview ? 'Change Logo' : 'Upload / Change Logo'"></span>
                        <input
                            type="file"
                            x-ref="logoInput"
                            name="logo"
                            accept="image/png,image/jpeg,image/jpg,image/webp"
                            x-on:change="previewFile"
                            class="hidden"
                        >
                    </label>

                    <button
                        type="button"
                        x-show="logoPreview || fileName"
                        x-on:click="removeLogo()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100 transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        <span x-text="fileName ? 'Cancel Selection' : 'Remove Logo'"></span>
                    </button>
                </div>

                <div x-show="fileName" class="text-xs text-indigo-600 font-medium">
                    Selected file: <span x-text="fileName" class="font-mono"></span>
                </div>

                <div x-show="errorMessage" class="text-xs text-red-600 font-semibold" x-text="errorMessage"></div>

                <p class="text-xs text-slate-500">Allowed formats: PNG, JPG, JPEG, WEBP • Maximum 2MB. Logo appears on headers, reports, and print documents.</p>
                @error('logo')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </x-ui.card>

    <!-- Section B: Company Identity -->
    <x-ui.card>
        <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
            Company Identity
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="name" class="block text-xs font-medium text-gray-700 mb-1">
                    Company Trade Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $company->name) }}"
                    required
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="legal_name" class="block text-xs font-medium text-gray-700 mb-1">Legal Name</label>
                <input
                    type="text"
                    id="legal_name"
                    name="legal_name"
                    value="{{ old('legal_name', $company->legal_name) }}"
                    placeholder="e.g. Acme Enterprises Private Limited"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                @error('legal_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="code" class="block text-xs font-medium text-gray-700 mb-1">Company Code</label>
                <input
                    type="text"
                    id="code"
                    value="{{ $company->code }}"
                    readonly
                    class="block w-full rounded-lg border-gray-200 bg-slate-100 text-sm text-slate-500 cursor-not-allowed font-mono shadow-sm"
                >
                <p class="text-[11px] text-slate-400 mt-1">Auto-assigned internal company code</p>
            </div>

            <div>
                <label for="gstin" class="block text-xs font-medium text-gray-700 mb-1">GSTIN</label>
                <input
                    type="text"
                    id="gstin"
                    name="gstin"
                    maxlength="15"
                    value="{{ old('gstin', $company->gstin) }}"
                    placeholder="27AAPFU0939F1ZV"
                    class="block w-full rounded-lg border-gray-300 text-sm uppercase tracking-wider font-mono focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                @error('gstin') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="pan" class="block text-xs font-medium text-gray-700 mb-1">PAN</label>
                <input
                    type="text"
                    id="pan"
                    name="pan"
                    maxlength="10"
                    value="{{ old('pan', $company->pan) }}"
                    placeholder="AAPFU0939F"
                    class="block w-full rounded-lg border-gray-300 text-sm uppercase tracking-wider font-mono focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                @error('pan') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cin" class="block text-xs font-medium text-gray-700 mb-1">CIN</label>
                <input
                    type="text"
                    id="cin"
                    name="cin"
                    value="{{ old('cin', $company->cin) }}"
                    placeholder="e.g. U72200MH2023PTC123456"
                    class="block w-full rounded-lg border-gray-300 text-sm uppercase font-mono focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                @error('cin') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="tan" class="block text-xs font-medium text-gray-700 mb-1">TAN</label>
                <input
                    type="text"
                    id="tan"
                    name="tan"
                    value="{{ old('tan', $company->tan) }}"
                    placeholder="e.g. PNEA12345B"
                    class="block w-full rounded-lg border-gray-300 text-sm uppercase font-mono focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                @error('tan') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="udyam_registration_no" class="block text-xs font-medium text-gray-700 mb-1">Udyam Registration No.</label>
                <input
                    type="text"
                    id="udyam_registration_no"
                    name="udyam_registration_no"
                    value="{{ old('udyam_registration_no', $company->udyam_registration_no) }}"
                    placeholder="UDYAM-MH-00-1234567"
                    class="block w-full rounded-lg border-gray-300 text-sm uppercase font-mono focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                @error('udyam_registration_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="msme_category" class="block text-xs font-medium text-gray-700 mb-1">MSME Category</label>
                <select
                    id="msme_category"
                    name="msme_category"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="none" @selected(old('msme_category', $company->msme_category) === 'none')>None</option>
                    <option value="micro" @selected(old('msme_category', $company->msme_category) === 'micro')>Micro</option>
                    <option value="small" @selected(old('msme_category', $company->msme_category) === 'small')>Small</option>
                    <option value="medium" @selected(old('msme_category', $company->msme_category) === 'medium')>Medium</option>
                </select>
                @error('msme_category') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-ui.card>

    <!-- Section C: Contact Information -->
    <x-ui.card>
        <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
            </svg>
            Contact Details
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="phone" class="block text-xs font-medium text-gray-700 mb-1">Primary Phone</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="{{ old('phone', $company->phone) }}"
                    placeholder="e.g. +91 98765 43210"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="alternate_phone" class="block text-xs font-medium text-gray-700 mb-1">Alternate Phone</label>
                <input
                    type="text"
                    id="alternate_phone"
                    name="alternate_phone"
                    value="{{ old('alternate_phone', $company->alternate_phone) }}"
                    placeholder="e.g. 022 12345678"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                @error('alternate_phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-medium text-gray-700 mb-1">Primary Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $company->email) }}"
                    placeholder="e.g. contact@company.com"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="website" class="block text-xs font-medium text-gray-700 mb-1">Website URL</label>
                <input
                    type="text"
                    id="website"
                    name="website"
                    value="{{ old('website', $company->website) }}"
                    placeholder="https://www.company.com"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                @error('website') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-ui.card>

    <!-- Section D: Address -->
    <x-ui.card>
        <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
            </svg>
            Registered &amp; Operating Address
        </h3>

        <div class="space-y-4">
            <div>
                <label for="address" class="block text-xs font-medium text-gray-700 mb-1">Street Address</label>
                <textarea
                    id="address"
                    name="address"
                    rows="2"
                    placeholder="Building, Plot No., Industrial Area, Street"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >{{ old('address', $company->address) }}</textarea>
                @error('address') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="city" class="block text-xs font-medium text-gray-700 mb-1">City</label>
                    <input
                        type="text"
                        id="city"
                        name="city"
                        value="{{ old('city', $company->city) }}"
                        placeholder="e.g. Mumbai"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                    @error('city') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="state" class="block text-xs font-medium text-gray-700 mb-1">State</label>
                    <select
                        id="state"
                        name="state"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Select State</option>
                        @foreach($states as $st)
                            <option value="{{ $st }}" @selected(old('state', $company->state) === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                    @error('state') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="pincode" class="block text-xs font-medium text-gray-700 mb-1">Pincode</label>
                    <input
                        type="text"
                        id="pincode"
                        name="pincode"
                        maxlength="10"
                        value="{{ old('pincode', $company->pincode) }}"
                        placeholder="e.g. 400001"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm font-mono"
                    >
                    @error('pincode') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="country" class="block text-xs font-medium text-gray-700 mb-1">Country</label>
                    <input
                        type="text"
                        id="country"
                        name="country"
                        value="{{ old('country', $company->country ?? 'India') }}"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                    @error('country') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- Section E: Banking & Commercial -->
    <x-ui.card>
        <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
            </svg>
            Primary Banking &amp; UPI
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="bank_name" class="block text-xs font-medium text-gray-700 mb-1">Bank Name</label>
                <input
                    type="text"
                    id="bank_name"
                    name="bank_name"
                    value="{{ old('bank_name', $company->bank_name) }}"
                    placeholder="e.g. HDFC Bank"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
            </div>

            <div>
                <label for="bank_account_no" class="block text-xs font-medium text-gray-700 mb-1">Account Number</label>
                <input
                    type="text"
                    id="bank_account_no"
                    name="bank_account_no"
                    value="{{ old('bank_account_no', $company->bank_account_no) }}"
                    placeholder="e.g. 50200012345678"
                    class="block w-full rounded-lg border-gray-300 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
            </div>

            <div>
                <label for="bank_ifsc" class="block text-xs font-medium text-gray-700 mb-1">IFSC Code</label>
                <input
                    type="text"
                    id="bank_ifsc"
                    name="bank_ifsc"
                    value="{{ old('bank_ifsc', $company->bank_ifsc) }}"
                    placeholder="HDFC0001234"
                    class="block w-full rounded-lg border-gray-300 text-sm uppercase font-mono focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
            </div>

            <div>
                <label for="upi_id" class="block text-xs font-medium text-gray-700 mb-1">UPI ID</label>
                <input
                    type="text"
                    id="upi_id"
                    name="upi_id"
                    value="{{ old('upi_id', $company->upi_id) }}"
                    placeholder="e.g. company@okhdfcbank"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
            </div>
        </div>
    </x-ui.card>

    <div class="flex items-center justify-end gap-3 pt-2">
        <x-ui.button type="submit" variant="primary" class="px-6 py-2.5">Save Changes</x-ui.button>
    </div>
</form>
@endsection

@push('scripts')
<script>
function companyProfileForm(initialLogo) {
    return {
        initialLogo: initialLogo || null,
        logoPreview: initialLogo || null,
        removeLogoFlag: false,
        fileName: '',
        errorMessage: '',
        previewFile(e) {
            this.errorMessage = '';
            const file = e.target.files && e.target.files[0];
            if (!file) return;

            const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
            if (!allowedTypes.includes(file.type.toLowerCase())) {
                this.errorMessage = 'Please upload a PNG, JPG, JPEG, or WEBP image.';
                if (this.$refs.logoInput) this.$refs.logoInput.value = '';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                this.errorMessage = 'Logo size must not exceed 2MB.';
                if (this.$refs.logoInput) this.$refs.logoInput.value = '';
                return;
            }

            this.removeLogoFlag = false;
            this.fileName = file.name;
            if (this.logoPreview && this.logoPreview.startsWith('blob:')) {
                URL.revokeObjectURL(this.logoPreview);
            }
            this.logoPreview = URL.createObjectURL(file);
        },
        removeLogo() {
            if (this.logoPreview && this.logoPreview.startsWith('blob:')) {
                URL.revokeObjectURL(this.logoPreview);
            }
            if (this.fileName) {
                // If a new file was chosen, cancelling reverts back to the saved company logo
                this.fileName = '';
                if (this.$refs.logoInput) {
                    this.$refs.logoInput.value = '';
                }
                this.logoPreview = this.initialLogo;
                this.removeLogoFlag = !this.initialLogo;
            } else {
                // Remove saved logo
                this.logoPreview = null;
                this.removeLogoFlag = true;
                if (this.$refs.logoInput) {
                    this.$refs.logoInput.value = '';
                }
            }
            this.errorMessage = '';
        }
    };
}
</script>
@endpush