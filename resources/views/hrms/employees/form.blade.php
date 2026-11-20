@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Employee' : 'Create Employee')

@php
    $effectiveReturnUrl = old('return_url', $returnUrl ?? request('return_url'));
    $effectiveReturnTo = $returnTo ?? request('return_to', old('return_to'));
    $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
@endphp

@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Employee' : 'Create Employee'">
<x-slot name="actions">
    @if(filled($effectiveReturnUrl))
        <x-ui.button variant="secondary" :href="$effectiveReturnUrl">Cancel &amp; Go Back</x-ui.button>
    @elseif($effectiveReturnTo === 'invoices.create' || $effectiveReturnTo === 'sales.invoices.create')
        <x-ui.button variant="secondary" :href="route('invoices.create')">Back to Tax Invoice</x-ui.button>
    @else
        <x-ui.button variant="secondary" :href="route('hrms.employees.index')">Back</x-ui.button>
    @endif
</x-slot>
</x-ui.page-header>

@if($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <div class="font-semibold">Please correct the errors below.</div>
        <ul class="mt-1 list-disc list-inside text-xs">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST"
      action="{{ $item->exists ? route('hrms.employees.update', $item) : route('hrms.employees.store') }}"
      enctype="multipart/form-data"
      class="space-y-5"
      x-data="employeeForm({
          sameAsPresent: {{ old('same_as_present_address') ? 'true' : 'false' }},
          bank_name: @js(old('bank_name', $item->bank_name)),
          bank_branch: @js(old('bank_branch', $item->bank_branch)),
          bank_ifsc: @js(old('bank_ifsc', $item->bank_ifsc)),
          ifscLookupUrl: @js(route('hrms.employees.ifsc-lookup')),
      })">
@csrf
@if($item->exists) @method('PUT') @endif
@if(! $item->exists && filled($effectiveReturnUrl))
    <input type="hidden" name="return_url" value="{{ $effectiveReturnUrl }}">
@endif
@if($effectiveReturnTo === 'invoices.create' || $effectiveReturnTo === 'sales.invoices.create')
    <input type="hidden" name="return_to" value="{{ $effectiveReturnTo }}">
@endif

@if(! $item->exists && filled($effectiveReturnUrl))
    <div class="bg-indigo-50/90 border border-indigo-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-indigo-900 shadow-2xs">
        <div>
            <span class="font-bold">Adding Salesperson for Tax Invoice</span>
            <p class="text-indigo-700 text-[11px]">Save this employee, then you will be returned to the Tax Invoice with the salesperson selected.</p>
        </div>
        <a href="{{ $effectiveReturnUrl }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white text-indigo-700 border border-indigo-200 font-semibold rounded-xl hover:bg-indigo-100 transition shrink-0">&larr; Cancel &amp; Go Back</a>
    </div>
@endif

{{-- Official --}}
<x-ui.card title="Official Information">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <x-ui.input name="employee_code" label="Employee Code" :value="old('employee_code', $item->employee_code)" />
        <x-ui.input name="biometric_code" label="Biometric / eSSL Code" :value="old('biometric_code', $item->biometric_code)" />
        <x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
        <x-ui.input name="email" label="Official Email" type="email" :value="old('email', $item->email)" />
        <x-ui.input name="phone" label="Official Phone" :value="old('phone', $item->phone)" />
        <x-ui.select name="company_id" label="Company"><option value="">—</option>@foreach($companies as $c)<option value="{{ $c->id }}" @selected(old('company_id', $item->company_id)==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
        <x-ui.select name="branch_id" label="Branch"><option value="">—</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $item->branch_id)==$b->id)>{{ $b->name }}</option>@endforeach</x-ui.select>
        <x-ui.select name="department_id" label="Department"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $item->department_id)==$d->id)>{{ $d->name }}</option>@endforeach</x-ui.select>
        <x-ui.select name="designation_id" label="Designation"><option value="">—</option>@foreach($designations as $d)<option value="{{ $d->id }}" @selected(old('designation_id', $item->designation_id)==$d->id)>{{ $d->name }}</option>@endforeach</x-ui.select>
        <x-ui.select name="manager_id" label="Manager"><option value="">—</option>@foreach($managers as $m)<option value="{{ $m->id }}" @selected(old('manager_id', $item->manager_id)==$m->id)>{{ $m->name }}</option>@endforeach</x-ui.select>
        <x-ui.select name="user_id" label="Linked User"><option value="">—</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('user_id', $item->user_id)==$u->id)>{{ $u->name }}</option>@endforeach</x-ui.select>
        <x-ui.input name="joining_date" label="Joining Date" type="date" :value="old('joining_date', optional($item->joining_date)->format('Y-m-d'))" />
        <x-ui.select name="status" label="Status">@foreach(['active','on_leave','resigned','terminated','inactive'] as $s)<option value="{{ $s }}" @selected(old('status', $item->status)==$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</x-ui.select>
    </div>
    <label class="mt-4 flex items-center gap-2 text-sm"><input type="checkbox" name="is_salesperson" value="1" @checked(old('is_salesperson', $item->is_salesperson)) class="rounded border-gray-300 text-indigo-600"> Salesperson</label>
</x-ui.card>

{{-- Personal --}}
<x-ui.card title="Personal Information">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <x-ui.input name="date_of_birth" label="Date of Birth" type="date" :value="old('date_of_birth', optional($item->date_of_birth)->format('Y-m-d'))" />
        <x-ui.input name="father_name" label="Father's Name" :value="old('father_name', $item->father_name)" />
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
            <select name="blood_group" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select</option>
                @foreach($bloodGroups as $bg)
                    <option value="{{ $bg }}" @selected(old('blood_group', $item->blood_group) === $bg)>{{ $bg }}</option>
                @endforeach
            </select>
        </div>
        <x-ui.input name="aadhaar_number" label="Aadhaar Number" :value="old('aadhaar_number', $item->aadhaar_number)" placeholder="12-digit Aadhaar" />
        <x-ui.input name="pan_number" label="PAN Number" :value="old('pan_number', $item->pan_number)" placeholder="ABCDE1234F" />
        <x-ui.input name="personal_email" label="Personal Email ID" type="email" :value="old('personal_email', $item->personal_email)" />
        <x-ui.input name="contact_number" label="Contact Number" :value="old('contact_number', $item->contact_number ?: $item->phone)" />
        <x-ui.input name="alternate_contact_number" label="Alternate Contact Number" :value="old('alternate_contact_number', $item->alternate_contact_number)" />
    </div>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Present Address</label>
            <textarea name="present_address" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('present_address', $item->present_address ?: $item->address) }}</textarea>
        </div>
        <div class="lg:col-span-1">
            <div class="mb-1 flex items-center justify-between gap-2">
                <label class="block text-sm font-medium text-gray-700">Permanent Address</label>
                <label class="flex items-center gap-1.5 text-xs text-slate-600">
                    <input type="checkbox" name="same_as_present_address" value="1" x-model="sameAsPresent" class="rounded border-gray-300 text-indigo-600">
                    Same as present
                </label>
            </div>
            <textarea name="permanent_address" rows="3" class="block w-full rounded-lg border-gray-300 text-sm" :disabled="sameAsPresent" :class="sameAsPresent && 'bg-slate-50 text-slate-500'">{{ old('permanent_address', $item->permanent_address) }}</textarea>
        </div>
    </div>
</x-ui.card>

{{-- Emergency --}}
<x-ui.card title="Emergency Contact">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <x-ui.input name="emergency_contact_number" label="Emergency Contact Number" :value="old('emergency_contact_number', $item->emergency_contact_number)" />
        <x-ui.input name="emergency_contact_relationship" label="Relationship" :value="old('emergency_contact_relationship', $item->emergency_contact_relationship)" placeholder="e.g. Spouse, Father, Friend" />
    </div>
</x-ui.card>

{{-- Bank --}}
<x-ui.card title="Bank Account Details">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label for="bank_ifsc" class="block text-sm font-medium text-gray-700 mb-1">IFSC Code</label>
            <input id="bank_ifsc" name="bank_ifsc" type="text" maxlength="11"
                   x-model="bank_ifsc"
                   @input="bank_ifsc = (bank_ifsc || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 11); onIfscInput()"
                   @blur="lookupIfsc()"
                   class="block w-full rounded-lg border-gray-300 shadow-sm text-sm uppercase tracking-wide focus:border-indigo-500 focus:ring-indigo-500"
                   placeholder="e.g. HDFC0001234"
                   autocomplete="off">
            <p class="mt-1 text-xs" :class="{
                'text-slate-400': !ifscStatus,
                'text-indigo-600': ifscStatus === 'loading',
                'text-emerald-600': ifscStatus === 'ok',
                'text-red-600': ifscStatus === 'error'
            }" x-text="ifscMessage || 'Enter IFSC to auto-fill bank name and branch.'" x-show="true"></p>
        </div>
        <x-ui.input name="bank_account_holder" label="Account Holder Name" :value="old('bank_account_holder', $item->bank_account_holder)" />
        <div>
            <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
            <input id="bank_name" name="bank_name" type="text" x-model="bank_name"
                   class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                   data-is-name="true">
        </div>
        <div>
            <label for="bank_branch" class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
            <input id="bank_branch" name="bank_branch" type="text" x-model="bank_branch"
                   class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <x-ui.input name="bank_account_number" label="Account Number" :value="old('bank_account_number', $item->bank_account_number)" />
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
            <select name="bank_account_type" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select</option>
                @foreach(['Savings', 'Current', 'Salary'] as $type)
                    <option value="{{ $type }}" @selected(old('bank_account_type', $item->bank_account_type) === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cancelled Cheque</label>
            <input type="file" name="cancelled_cheque" accept=".jpg,.jpeg,.png,.webp,.pdf" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700">
            @if($item->cancelled_cheque_path)
                <p class="mt-1 text-xs text-slate-500">
                    Current file on record —
                    <a href="{{ route('hrms.employees.cancelled-cheque', $item) }}" class="font-medium text-indigo-600 hover:underline">Download</a>
                </p>
            @endif
            <p class="mt-1 text-xs text-slate-400">JPG, PNG or PDF up to 8MB.</p>
        </div>
    </div>
</x-ui.card>

{{-- Documents / Photo / Education --}}
<x-ui.card title="Photograph & Education Certificates">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Employee Photograph</label>
            <div class="flex items-start gap-3">
                @if($item->exists && $item->photo_path)
                    <img src="{{ route('hrms.employees.photo', $item) }}" alt="Photo" class="h-16 w-16 rounded-lg border border-slate-200 object-cover bg-slate-50">
                @endif
                <div class="min-w-0 flex-1">
                    <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700">
                    <p class="mt-1 text-xs text-slate-400">JPG/PNG/WEBP up to 4MB.</p>
                </div>
            </div>
        </div>
        <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Education Certificates</label>
            <input type="file" name="education_certificates[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700">
            <p class="mt-1 text-xs text-slate-400">Upload one or more certificates (PDF/DOC/JPG). Stored in the secure document vault.</p>
        </div>
    </div>
    @if($item->exists)
        <p class="mt-3 text-xs text-slate-500">
            Manage all uploaded documents anytime from the
            <a href="{{ route('hrms.employees.show', $item) }}" class="font-medium text-indigo-600 hover:underline">employee profile vault</a>.
        </p>
    @endif
</x-ui.card>

<div class="flex items-center gap-3">
    <x-ui.button type="submit" variant="primary">Save Employee</x-ui.button>
    @php
        $cancelUrl = filled($effectiveReturnUrl)
            ? $effectiveReturnUrl
            : (($effectiveReturnTo === 'invoices.create' || $effectiveReturnTo === 'sales.invoices.create')
                ? route('invoices.create')
                : route('hrms.employees.index'));
    @endphp
    <x-ui.button variant="secondary" :href="$cancelUrl">Cancel</x-ui.button>
</div>
</form>

@push('scripts')
<script>
function employeeForm(config) {
    return {
        sameAsPresent: !!config.sameAsPresent,
        bank_name: config.bank_name || '',
        bank_branch: config.bank_branch || '',
        bank_ifsc: config.bank_ifsc || '',
        ifscLookupUrl: config.ifscLookupUrl,
        ifscStatus: '',
        ifscMessage: '',
        _ifscTimer: null,
        _ifscLastFetched: '',
        _ifscLoading: false,
        onIfscInput() {
            this.ifscStatus = '';
            this.ifscMessage = '';
            if (this._ifscTimer) clearTimeout(this._ifscTimer);
            const ifsc = (this.bank_ifsc || '').toUpperCase().trim();
            if (ifsc.length === 11) {
                this._ifscTimer = setTimeout(() => this.lookupIfsc(), 500);
            }
        },
        async lookupIfsc() {
            const ifsc = (this.bank_ifsc || '').toUpperCase().trim();
            if (!ifsc) {
                this.ifscStatus = '';
                this.ifscMessage = '';
                return;
            }
            if (ifsc.length !== 11) {
                this.ifscStatus = 'error';
                this.ifscMessage = 'Please enter a valid 11-character IFSC code.';
                return;
            }
            if (this._ifscLastFetched === ifsc && this.bank_name) return;
            if (this._ifscLoading) return;

            this._ifscLoading = true;
            this.ifscStatus = 'loading';
            this.ifscMessage = 'Fetching bank details...';

            try {
                const response = await fetch(`${this.ifscLookupUrl}?ifsc=${encodeURIComponent(ifsc)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json();
                if (data.success && data.bank) {
                    this.bank_name = data.bank.bank_name || this.bank_name;
                    this.bank_branch = data.bank.branch_name || this.bank_branch;
                    this._ifscLastFetched = ifsc;
                    this.ifscStatus = 'ok';
                    this.ifscMessage = data.message || 'Bank and branch details fetched successfully.';
                } else {
                    this.ifscStatus = 'error';
                    this.ifscMessage = data.message || 'Unable to fetch bank details. Please enter details manually.';
                }
            } catch (e) {
                this.ifscStatus = 'error';
                this.ifscMessage = 'Unable to fetch bank details. Please enter details manually.';
            } finally {
                this._ifscLoading = false;
            }
        },
    };
}
</script>
@endpush
@endsection
