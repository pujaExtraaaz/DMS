@extends('layouts.dms')
@section('title', $lead->exists ? 'Edit CRM Lead' : 'Create CRM Lead')
@section('content')
<x-ui.page-header :title="$lead->exists ? 'Edit CRM Lead: ' . $lead->name : 'Create CRM Lead'">
<x-slot name="actions">
    <x-ui.button variant="secondary" :href="$lead->exists ? route('crm.leads.show', $lead) : route('crm.leads.index')">
        {{ $lead->exists ? 'Back to Lead' : 'Back to Leads' }}
    </x-ui.button>
</x-slot>
</x-ui.page-header>

<x-ui.card>
<form method="POST" action="{{ $lead->exists ? route('crm.leads.update', $lead) : route('crm.leads.store') }}" 
      class="space-y-6 max-w-4xl"
      x-data="leadFormDuplicateChecker({{ $lead->exists ? $lead->id : 'null' }})">
    @csrf
    @if($lead->exists)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 p-4 border border-red-200">
            <h4 class="text-sm font-semibold text-red-800">Please correct the following errors:</h4>
            <ul class="mt-2 list-disc list-inside text-xs text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Section 1: Contact Information -->
    <div>
        <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider">Contact Information</h3>
            <span class="text-xs text-slate-400">* Required field</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-ui.input 
                name="name" 
                label="Contact Name" 
                :value="old('name', $lead->name)" 
                required 
                placeholder="Full Name (e.g. John Doe)" 
            />

            <x-ui.input 
                name="company_name" 
                label="Company Name" 
                :value="old('company_name', $lead->company_name ?: $lead->organization)" 
                placeholder="e.g. Acme Industries Ltd." 
            />

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Title</label>
                <input 
                    type="text" 
                    name="title" 
                    list="lead-title-presets" 
                    value="{{ old('title', $lead->title) }}" 
                    placeholder="e.g. Mr., Ms., Director, Purchase Manager"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                <datalist id="lead-title-presets">
                    <option value="Mr.">
                    <option value="Ms.">
                    <option value="Mrs.">
                    <option value="Dr.">
                    <option value="Proprietor">
                    <option value="Partner">
                    <option value="Director">
                    <option value="Managing Director">
                    <option value="Purchase Manager">
                    <option value="General Manager">
                </datalist>
            </div>

            <div>
                <x-ui.input 
                    name="email" 
                    label="Email" 
                    type="email" 
                    :value="old('email', $lead->email)" 
                    placeholder="e.g. contact@example.com" 
                    @blur="checkEmail($event)"
                />
                <p x-show="emailError" x-text="emailError" class="text-xs text-red-600 mt-1 font-medium" x-cloak></p>
            </div>

            <x-ui.input 
                name="secondary_email" 
                label="Secondary Email" 
                type="email" 
                :value="old('secondary_email', $lead->secondary_email)" 
                placeholder="e.g. accounts@example.com" 
            />

            <div>
                <x-ui.input 
                    name="mobile" 
                    label="Mobile" 
                    :value="old('mobile', $lead->mobile)" 
                    placeholder="Mobile Number (e.g. 9876543210)" 
                    @blur="checkMobile($event)"
                />
                <p x-show="mobileError" x-text="mobileError" class="text-xs text-red-600 mt-1 font-medium" x-cloak></p>
            </div>

            <x-ui.input 
                name="secondary_mobile" 
                label="Second Mobile Number (SECND MOB)" 
                :value="old('secondary_mobile', $lead->secondary_mobile)" 
                placeholder="e.g. 9876500000" 
            />

            <x-ui.input 
                name="phone" 
                label="Phone" 
                :value="old('phone', $lead->phone)" 
                placeholder="e.g. 022-28001122" 
            />

            <x-ui.input 
                name="landline" 
                label="Landline" 
                :value="old('landline', $lead->landline)" 
                placeholder="e.g. 022-28001133" 
            />
        </div>
    </div>

    <!-- Section 2: Assignment and Classification -->
    <div class="pt-4 border-t border-slate-100">
        <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider mb-3">Assignment and Classification</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-ui.select name="assigned_to" label="Sales Person">
                <option value="">Unassigned</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(old('assigned_to', $lead->assigned_to) == $user->id)>{{ $user->name }}</option>
                @endforeach
            </x-ui.select>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tag</label>
                <input 
                    type="text" 
                    name="tag" 
                    list="lead-tag-presets"
                    value="{{ old('tag', $lead->tag) }}" 
                    placeholder="e.g. VIP, Wholesale, Priority"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                <datalist id="lead-tag-presets">
                    <option value="VIP">
                    <option value="High Priority">
                    <option value="Wholesale">
                    <option value="Retailer">
                    <option value="Contractor">
                    <option value="Government">
                    <option value="Hot Lead">
                </datalist>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Sub Category</label>
                <select 
                    name="sub_category_id" 
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Select Sub Category</option>
                    @foreach($subCategories as $sc)
                        <option value="{{ $sc->id }}" @selected(old('sub_category_id', $lead->sub_category_id) == $sc->id)>
                            {{ $sc->name }} ({{ $sc->code ?? 'CAT' }})
                        </option>
                    @endforeach
                </select>
                @if($lead->sub_category && !$lead->sub_category_id)
                    <p class="mt-1 text-xs text-slate-500">Current text: <span class="font-medium text-slate-700">{{ $lead->sub_category }}</span></p>
                @endif
            </div>

            <x-ui.select name="lead_source_id" label="Lead Source">
                <option value="">Direct / Walk-in / Other</option>
                @foreach($sources as $src)
                    <option value="{{ $src->id }}" @selected(old('lead_source_id', $lead->lead_source_id) == $src->id)>{{ $src->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="lead_campaign_id" label="Campaign">
                <option value="">None / Direct</option>
                @foreach($campaigns as $camp)
                    <option value="{{ $camp->id }}" @selected(old('lead_campaign_id', $lead->lead_campaign_id) == $camp->id)>{{ $camp->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="priority" label="Priority" required>
                @foreach($priorities as $pri)
                    <option value="{{ $pri }}" @selected(old('priority', $lead->priority ?? 'normal') == $pri)>{{ ucfirst($pri) }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="status" label="Status" required>
                @foreach($statuses as $stat)
                    <option value="{{ $stat }}" @selected(old('status', $lead->status ?? 'new') == $stat)>{{ ucfirst($stat) }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input 
                name="interested_product" 
                label="Interested Product" 
                :value="old('interested_product', $lead->interested_product)" 
                placeholder="e.g. Solar Inverter 5kW" 
            />
        </div>
    </div>

    <!-- Section 3: Mailing Address -->
    <div class="pt-4 border-t border-slate-100">
        <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider mb-3">Mailing Address</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Mailing Street</label>
                <textarea 
                    name="street" 
                    rows="2" 
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" 
                    placeholder="Premises, Street, Building, Area..."
                >{{ old('street', $lead->street) }}</textarea>
            </div>

            <x-ui.input 
                name="city" 
                label="Mailing City" 
                :value="old('city', $lead->city)" 
                placeholder="e.g. Mumbai" 
            />

            <x-ui.select name="state" label="Mailing State">
                <option value="">Select State</option>
                @foreach($states as $st)
                    <option value="{{ $st }}" @selected(old('state', $lead->state) == $st)>{{ $st }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input 
                name="zip" 
                label="Mailing Zip" 
                :value="old('zip', $lead->zip)" 
                placeholder="e.g. 400001" 
            />
        </div>
    </div>

    <!-- Section 4: Additional Notes -->
    <div class="pt-4 border-t border-slate-100">
        <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider mb-3">Additional Notes</h3>
        <div>
            <textarea 
                name="notes" 
                rows="3" 
                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" 
                placeholder="Initial discussion notes or requirements..."
            >{{ old('notes', $lead->notes) }}</textarea>
        </div>
    </div>

    <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
        <x-ui.button type="submit" variant="primary">
            {{ $lead->exists ? 'Update Lead' : 'Create Lead' }}
        </x-ui.button>
        <x-ui.button variant="secondary" :href="$lead->exists ? route('crm.leads.show', $lead) : route('crm.leads.index')">
            Cancel
        </x-ui.button>
    </div>
</form>
</x-ui.card>

<script>
function leadFormDuplicateChecker(excludeLeadId) {
    return {
        emailError: null,
        mobileError: null,
        async checkEmail(e) {
            const val = (e.target.value || '').trim();
            if (!val) {
                this.emailError = null;
                return;
            }
            try {
                const res = await fetch('{{ route('crm.leads.check-duplicate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ email: val, exclude_id: excludeLeadId })
                });
                if (res.ok) {
                    const data = await res.json();
                    this.emailError = data.email_duplicate ? data.email_message : null;
                }
            } catch (err) {
                // Silently ignore network failures
            }
        },
        async checkMobile(e) {
            const val = (e.target.value || '').trim();
            if (!val) {
                this.mobileError = null;
                return;
            }
            try {
                const res = await fetch('{{ route('crm.leads.check-duplicate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ mobile: val, exclude_id: excludeLeadId })
                });
                if (res.ok) {
                    const data = await res.json();
                    this.mobileError = data.mobile_duplicate ? data.mobile_message : null;
                }
            } catch (err) {
                // Silently ignore network failures
            }
        }
    };
}
</script>
@endsection
