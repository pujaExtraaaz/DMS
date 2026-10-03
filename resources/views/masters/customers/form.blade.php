@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Party / Customer' : 'Create Party / Customer')
@section('content')
@php
    $existingContacts = old('contacts', $item->exists ? $item->contacts->map->only(['name','role','phone','alternate_phone','email','level','location','is_primary'])->values()->toArray() : []);
    $existingAddresses = old('addresses', $item->exists ? $item->addresses->map->only(['type','label','name','address','state','pincode','gstin','is_default'])->values()->toArray() : []);
    if (empty($existingContacts)) {
        $existingContacts = [[ 'name' => '', 'role' => '', 'phone' => '', 'alternate_phone' => '', 'email' => '', 'level' => 'primary', 'location' => '', 'is_primary' => true ]];
    }
    if (empty($existingAddresses)) {
        $existingAddresses = [[ 'type' => 'billing', 'label' => 'Head Office', 'name' => '', 'address' => '', 'state' => '', 'pincode' => '', 'gstin' => '', 'is_default' => true ]];
    }
@endphp
<x-ui.page-header :title="$item->exists ? 'Edit Party / Customer' : 'Create Party / Customer'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.customers.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="POST" action="{{ $item->exists ? route('masters.customers.update', $item) : route('masters.customers.store') }}" class="space-y-6 max-w-5xl">
        @csrf @if($item->exists) @method('PUT') @endif

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Party</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="name" label="Party Name" :value="old('name', $item->name)" required />
                <x-ui.input name="code" label="Code" :value="old('code', $item->code)" required />
                <x-ui.select name="party_type" label="Party Type" required>
                    @foreach(['dealer'=>'Dealer','customer'=>'Customer','supplier'=>'Supplier','both'=>'Both'] as $val=>$label)
                        <option value="{{ $val }}" @selected(old('party_type', $item->party_type ?? 'customer')==$val)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="customer_type_id" label="Classification" required>
                    @foreach($customerTypes as $ct)
                        <option value="{{ $ct->id }}" @selected(old('customer_type_id', $item->customer_type_id)==$ct->id)>{{ $ct->name }}</option>
                    @endforeach
                </x-ui.select>
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
                <x-ui.select name="salesperson_id" label="Salesperson" placeholder="Select">
                    <option value=""></option>
                    @foreach($salespersons as $s)
                        <option value="{{ $s->id }}" @selected(old('salesperson_id', $item->salesperson_id)==$s->id)>{{ $s->name }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="gstin" label="GSTIN" :value="old('gstin', $item->gstin)" />
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Default contact snapshot</h3>
            <p class="text-xs text-slate-500 mb-3">Kept for backward compatibility; the multi-contact list below is the source of truth going forward.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="phone" label="Primary Phone" :value="old('phone', $item->phone)" />
                <x-ui.input name="email" label="Primary Email" type="email" :value="old('email', $item->email)" />
                <x-ui.input name="state" label="State" :value="old('state', $item->state)" />
                <x-ui.input name="pincode" label="Pincode" :value="old('pincode', $item->pincode)" />
            </div>
            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea name="address" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('address', $item->address) }}</textarea>
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

        <div x-data='@json(["rows" => $existingAddresses])'>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-700">Addresses / Locations</h3>
                <button type="button"
                        @click='rows.push({ type:"shipping", label:"", name:"", address:"", state:"", pincode:"", gstin:"", is_default:false })'
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                    + Add address
                </button>
            </div>
            <div class="space-y-3">
                <template x-for="(row, idx) in rows" :key="idx">
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
                                <input type="text" :name="`addresses[${idx}][state]`" x-model="row.state" class="block w-full rounded-lg border-gray-300 text-sm">
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
                            <button type="button" x-show="rows.length > 1" @click="rows.splice(idx, 1)" class="text-xs font-semibold text-red-600 hover:text-red-800">Remove</button>
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
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active
        </label>

        <x-ui.button type="submit" variant="primary">Save</x-ui.button>
    </form>
</x-ui.card>
@endsection
