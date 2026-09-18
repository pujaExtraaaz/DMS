@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Company' : 'Create Company')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Company' : 'Create Company'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('organization.companies.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
    <form method="POST" action="{{ $item->exists ? route('organization.companies.update', $item) : route('organization.companies.store') }}" class="space-y-6 max-w-4xl">
        @csrf @if($item->exists) @method('PUT') @endif

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Identity</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="name" label="Trade Name" :value="old('name', $item->name)" required />
                <x-ui.input name="code" label="Short Code" :value="old('code', $item->code)" required />
                <x-ui.input name="legal_name" label="Legal Name" :value="old('legal_name', $item->legal_name)" />
                <x-ui.select name="business_group_id" label="Business Group" placeholder="Select">
                    <option value=""></option>
                    @foreach($businessGroups as $g)
                        <option value="{{ $g->id }}" @selected(old('business_group_id', $item->business_group_id)==$g->id)>{{ $g->name }}</option>
                    @endforeach
                </x-ui.select>
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
                <x-ui.input name="msme_registration_no" label="MSME Registration No" :value="old('msme_registration_no', $item->msme_registration_no)" />
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Contact</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="phone" label="Phone" :value="old('phone', $item->phone)" />
                <x-ui.input name="email" label="Email" type="email" :value="old('email', $item->email)" />
                <x-ui.input name="website" label="Website" :value="old('website', $item->website)" />
                <x-ui.input name="state" label="State" :value="old('state', $item->state)" />
                <x-ui.input name="pincode" label="Pincode" :value="old('pincode', $item->pincode)" />
            </div>
            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea name="address" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('address', $item->address) }}</textarea>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Banking &amp; Payments</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="bank_name" label="Bank Name" :value="old('bank_name', $item->bank_name)" />
                <x-ui.input name="bank_account_no" label="Bank Account No" :value="old('bank_account_no', $item->bank_account_no)" />
                <x-ui.input name="bank_ifsc" label="IFSC" :value="old('bank_ifsc', $item->bank_ifsc)" />
                <x-ui.input name="upi_id" label="UPI ID (for invoice QR)" :value="old('upi_id', $item->upi_id)" placeholder="business@bank" />
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Preferences</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.select name="due_date_basis" label="Due Date Basis" required>
                    <option value="invoice_date" @selected(old('due_date_basis', $item->due_date_basis ?? 'invoice_date')==='invoice_date')>Invoice Date</option>
                    <option value="inward_date" @selected(old('due_date_basis', $item->due_date_basis ?? 'invoice_date')==='inward_date')>Receive / Inward Date</option>
                </x-ui.select>
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

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active
        </label>

        <x-ui.button type="submit" variant="primary">Save</x-ui.button>
    </form>
</x-ui.card>
@endsection
