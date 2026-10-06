@extends('layouts.dms')
@section('title', 'Create CRM Lead')
@section('content')
<x-ui.page-header title="Create CRM Lead">
<x-slot name="actions">
    <x-ui.button variant="secondary" :href="route('crm.leads.index')">Back to Leads</x-ui.button>
</x-slot>
</x-ui.page-header>

<x-ui.card>
<form method="POST" action="{{ route('crm.leads.store') }}" class="space-y-6 max-w-4xl">
    @csrf

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

    <div>
        <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider mb-3">Lead Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-ui.input name="name" label="Full Name" :value="old('name', $lead->name)" required placeholder="e.g. John Doe" />
            <x-ui.input name="organization" label="Company / Organization" :value="old('organization', $lead->organization)" placeholder="e.g. Acme Industries" />
            <x-ui.input name="mobile" label="Mobile Number" :value="old('mobile', $lead->mobile)" placeholder="e.g. 9876543210" />
            <x-ui.input name="email" label="Email Address" type="email" :value="old('email', $lead->email)" placeholder="e.g. contact@example.com" />
            <x-ui.input name="city" label="City" :value="old('city', $lead->city)" placeholder="e.g. Mumbai" />
            <x-ui.select name="state" label="State">
                <option value="">Select State</option>
                @foreach($states as $st)
                    <option value="{{ $st }}" @selected(old('state', $lead->state) == $st)>{{ $st }}</option>
                @endforeach
            </x-ui.select>
        </div>
    </div>

    <div class="pt-4 border-t border-slate-100">
        <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider mb-3">Classification & Assignment</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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

            <x-ui.select name="status" label="Initial Status" required>
                @foreach($statuses as $stat)
                    <option value="{{ $stat }}" @selected(old('status', $lead->status ?? 'new') == $stat)>{{ ucfirst($stat) }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="assigned_to" label="Assign Salesperson">
                <option value="">Unassigned</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(old('assigned_to', $lead->assigned_to) == $user->id)>{{ $user->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input name="interested_product" label="Interested Product" :value="old('interested_product', $lead->interested_product)" placeholder="e.g. Solar Inverter" />
        </div>
    </div>

    <div class="pt-4 border-t border-slate-100">
        <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider mb-3">Additional Notes</h3>
        <div>
            <textarea name="notes" rows="3" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Initial discussion notes or requirements...">{{ old('notes', $lead->notes) }}</textarea>
        </div>
    </div>

    <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
        <x-ui.button type="submit" variant="primary">Create Lead</x-ui.button>
        <x-ui.button variant="secondary" :href="route('crm.leads.index')">Cancel</x-ui.button>
    </div>
</form>
</x-ui.card>
@endsection

