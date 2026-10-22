@extends('layouts.dms')
@section('title', isset($lead) ? 'Convert Lead to Customer' : ($item->exists ? 'Edit Party' : 'Create Party'))

@php
    $defaultBankAccount = [
        'id' => null,
        'account_holder_name' => '',
        'bank_name' => '',
        'branch_name' => '',
        'account_number' => '',
        'account_type' => 'current',
        'ifsc_code' => '',
        'is_primary' => true,
    ];
    $initialBankAccounts = old('bank_accounts', ($item->exists && $item->relationLoaded('bankAccounts') && $item->bankAccounts->isNotEmpty()) 
        ? $item->bankAccounts->map(fn ($b) => [
            'id' => $b->id,
            'account_holder_name' => $b->account_holder_name ?? '',
            'bank_name' => $b->bank_name ?? '',
            'branch_name' => $b->branch_name ?? '',
            'account_number' => $b->account_number ?? '',
            'account_type' => $b->account_type ?? 'current',
            'ifsc_code' => $b->ifsc_code ?? '',
            'is_primary' => (bool) ($b->is_primary),
        ])->toArray() 
        : [$defaultBankAccount]);

    $defaultAddress = [
        'id' => null,
        'label' => 'Head Office / Billing',
        'contact_person' => '',
        'contact_phone' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'state' => '',
        'pincode' => '',
        'type' => 'both',
        'is_default_billing' => true,
        'is_default_delivery' => true,
    ];
    $initialAddresses = old('addresses', $initialAddresses ?? (
        ($item->exists && $item->relationLoaded('addresses') && $item->addresses->isNotEmpty()) 
            ? $item->addresses->map(function ($a) {
                return [
                    'id' => $a->id,
                    'label' => $a->label ?? 'Office',
                    'contact_person' => $a->contact_person ?? '',
                    'contact_phone' => $a->contact_phone ?? '',
                    'address_line_1' => $a->address_line_1 ?? $a->address ?? '',
                    'address_line_2' => $a->address_line_2 ?? '',
                    'city' => $a->city ?? '',
                    'state' => $a->state ?? '',
                    'pincode' => $a->pincode ?? '',
                    'type' => $a->type ?? 'both',
                    'is_default_billing' => (bool) ($a->is_default_billing || $a->is_default),
                    'is_default_delivery' => (bool) ($a->is_default_delivery),
                ];
            })->toArray()
            : [$defaultAddress]
    ));

    $initialBusinessGroupId = old('business_group_id', $item->business_group_id ?? request('selected_group_id', request('business_group_id')));

    $initialContacts = old('contacts', ($item->exists && $item->relationLoaded('contacts'))
        ? $item->contacts->map(fn ($contact) => [
            'name' => $contact->name ?? '',
            'role' => $contact->role ?? '',
            'phone' => $contact->phone ?? '',
            'alternate_phone' => $contact->alternate_phone ?? '',
            'email' => $contact->email ?? '',
            'is_primary' => (bool) $contact->is_primary,
        ])->values()->all()
        : []);
@endphp

@section('content')
<x-ui.page-header :title="isset($lead) ? 'Convert Lead to Customer' : ($item->exists ? 'Edit Party' : 'Create Party')">
    <x-slot name="actions">
        @if(isset($lead))
            <x-ui.button variant="secondary" :href="route('crm.leads.index')">Back to Leads</x-ui.button>
        @else
            <x-ui.button variant="secondary" :href="route('masters.customers.index')">Back</x-ui.button>
        @endif
    </x-slot>
</x-ui.page-header>
<x-ui.card>
    @if(isset($lead))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 shadow-xs">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-emerald-950">
                            Converting Lead: {{ $lead->name }}{{ $lead->company_name && $lead->company_name !== $lead->name ? ' (' . $lead->company_name . ')' : '' }}
                        </h3>
                        <p class="text-xs text-emerald-700">
                            Lead contact, address, and assignment details have been pre-filled. Complete party details below and save to convert this lead into a Customer.
                        </p>
                    </div>
                </div>
                <a href="{{ route('crm.leads.index') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 shadow-xs hover:bg-emerald-50 transition">
                    &larr; Back to Leads
                </a>
            </div>
        </div>
    @endif

    <form method="POST"
          action="{{ $item->exists ? route('masters.customers.update', $item) : route('masters.customers.store') }}"
          class="space-y-6 max-w-5xl"
          x-data="partyFormController()">
        @csrf
        @if($item->exists) @method('PUT') @endif
        @if(isset($lead))
            <input type="hidden" name="lead_id" value="{{ $lead->id }}">
        @elseif(old('lead_id'))
            <input type="hidden" name="lead_id" value="{{ old('lead_id') }}">
        @endif

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Identity</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">GSTIN</label>
                    <div class="flex gap-2">
                        <input type="text" name="gstin" x-model="form.gstin" maxlength="15"
                               placeholder="E.G. 27AAAAA0000A1Z5" @input.debounce.400ms="autoFetchGst()"
                               class="block w-full uppercase rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="button" @click="fetchGstDetails()" :disabled="searchingGst"
                                class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-lg hover:bg-indigo-100 transition disabled:opacity-50">
                            <span x-show="!searchingGst">Fetch GST</span>
                            <span x-show="searchingGst" class="flex items-center gap-1">
                                <svg class="animate-spin h-3.5 w-3.5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Searching...</span>
                            </span>
                        </button>
                    </div>

                    <div x-show="gstFeedback.message"
                         x-transition
                         :class="{
                             'bg-emerald-50 text-emerald-800 border-emerald-200': gstFeedback.type === 'success',
                             'bg-amber-50 text-amber-800 border-amber-200': gstFeedback.type === 'warning',
                             'bg-rose-50 text-rose-800 border-rose-200': gstFeedback.type === 'error'
                         }"
                         class="mt-2 p-2.5 rounded-lg border text-xs flex items-start gap-2">
                        <span class="font-bold shrink-0 mt-0.5" x-text="gstFeedback.type === 'success' ? '✓' : (gstFeedback.type === 'warning' ? 'ℹ' : '⚠')"></span>
                        <div class="space-y-0.5">
                            <p x-text="gstFeedback.message" class="font-medium"></p>
                            <p x-show="gstFeedback.note" x-text="gstFeedback.note" class="text-[11px] opacity-90"></p>
                        </div>
                    </div>

                    <div x-show="gstLookupInfo && Object.keys(gstLookupInfo).length"
                         x-cloak
                         class="mt-2 p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-[11px] text-slate-700 space-y-1">
                        <p class="font-semibold text-slate-800 uppercase tracking-wider text-[10px]">GST registration details (for review)</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-0.5">
                            <p x-show="gstLookupInfo.legal_name"><span class="text-slate-500">Legal name:</span> <span x-text="gstLookupInfo.legal_name"></span></p>
                            <p x-show="gstLookupInfo.trade_name"><span class="text-slate-500">Trade name:</span> <span x-text="gstLookupInfo.trade_name"></span></p>
                            <p x-show="gstLookupInfo.status"><span class="text-slate-500">Status:</span> <span x-text="gstLookupInfo.status"></span></p>
                            <p x-show="gstLookupInfo.taxpayer_type"><span class="text-slate-500">Taxpayer type:</span> <span x-text="gstLookupInfo.taxpayer_type"></span></p>
                            <p x-show="gstLookupInfo.constitution_of_business"><span class="text-slate-500">Constitution:</span> <span x-text="gstLookupInfo.constitution_of_business"></span></p>
                            <p x-show="gstLookupInfo.registration_date"><span class="text-slate-500">Registration date:</span> <span x-text="gstLookupInfo.registration_date"></span></p>
                            <p x-show="gstLookupInfo.cancellation_date"><span class="text-slate-500">Cancellation date:</span> <span x-text="gstLookupInfo.cancellation_date"></span></p>
                            <p x-show="gstLookupInfo.state_jurisdiction"><span class="text-slate-500">State jurisdiction:</span> <span x-text="gstLookupInfo.state_jurisdiction"></span></p>
                            <p x-show="gstLookupInfo.center_jurisdiction"><span class="text-slate-500">Center jurisdiction:</span> <span x-text="gstLookupInfo.center_jurisdiction"></span></p>
                            <p x-show="gstLookupInfo.nature_of_business" class="sm:col-span-2"><span class="text-slate-500">Nature of business:</span> <span x-text="gstLookupInfo.nature_of_business"></span></p>
                        </div>
                        <p class="text-slate-500">Trade name is saved with the party. The other GST registration details are shown for review.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Party Name *</label>
                    <input type="text" name="name" x-model="form.name" required
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider">Trade Name</label>
                        <button type="button" x-show="form.trade_name" @click="form.name = form.trade_name" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Use as party name</button>
                    </div>
                    <input type="text" name="trade_name" x-model="form.trade_name"
                           placeholder="Filled automatically from the GSTIN"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Party Type *</label>
                    <select name="party_type" x-model="form.party_type" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach([\App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_DEBTORS => 'Sundry Debtors', \App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_CREDITORS => 'Sundry Creditors', \App\Domains\Master\Models\Customer::PARTY_TYPE_BOTH => 'Both'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('party_type', $item->party_type_key ?? 'sundry_debtors')===$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Business Group (Linked from Sister Concern) --}}
                <div class="relative">
                    <div class="flex items-center justify-between mb-1">
                        <label for="business_group_id_display" class="block text-xs font-semibold text-slate-700 tracking-wider">
                            Business Group
                        </label>
                        <a href="{{ route('organization.business-groups.index', ['return_url' => url()->current()]) }}"
                           class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1 hover:underline focus:outline-none"
                           title="Create new business group in Sister Concern">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add
                        </a>
                    </div>

                    {{-- Hidden input for Form submission --}}
                    <input type="hidden" name="business_group_id" :value="selectedBusinessGroupId || ''">

                    {{-- Dropdown Container --}}
                    <div class="relative w-full"
                         x-ref="businessGroupContainer"
                         @click.outside="closeBusinessGroupDropdown()">
                        
                        <!-- Trigger Input / Button -->
                        <button type="button"
                                id="business_group_id_display"
                                x-ref="bgTriggerButton"
                                @click="toggleBusinessGroupDropdown()"
                                :class="businessGroupDropdownOpen ? 'ring-2 ring-indigo-500 border-indigo-500' : 'border-gray-300 hover:border-gray-400'"
                                class="relative w-full rounded-lg border bg-white py-2 pl-3 pr-2 text-left text-sm shadow-xs focus:outline-none transition flex items-center justify-between">
                            <div class="flex items-center gap-2 truncate flex-1 mr-2">
                                <template x-if="selectedBusinessGroup">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span class="text-sm" x-text="selectedBusinessGroup.logo_emoji || '🏢'"></span>
                                        <span class="font-medium text-slate-800 truncate" x-text="selectedBusinessGroup.name"></span>
                                        <span class="text-[11px] text-slate-500 font-mono bg-slate-100 px-1 py-0.5 rounded border border-slate-200" x-text="selectedBusinessGroup.code"></span>
                                    </div>
                                </template>
                                <template x-if="!selectedBusinessGroup">
                                    <span class="text-slate-400">Select Business Group (optional)</span>
                                </template>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <template x-if="selectedBusinessGroupId">
                                    <span @click.stop="clearBusinessGroup()" title="Clear selection"
                                          class="p-0.5 text-slate-400 hover:text-rose-500 rounded cursor-pointer transition">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </span>
                                </template>
                                <svg class="h-4 w-4 text-slate-400 transition-transform duration-150"
                                     :class="businessGroupDropdownOpen ? 'rotate-180 text-indigo-600' : ''"
                                     viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>

                        <!-- Dropdown Menu Popover -->
                        <div x-show="businessGroupDropdownOpen"
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute left-0 right-0 z-50 rounded-lg bg-white shadow-lg ring-1 ring-black/5 border border-slate-200 overflow-hidden flex flex-col focus:outline-none"
                             :class="dropdownPlacement === 'top' ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                             style="max-height: 280px; display: none;">
                            
                            <!-- Search Field -->
                            <div class="p-2 border-b border-slate-100 bg-slate-50/80 sticky top-0 z-10">
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input type="text"
                                           x-ref="businessGroupSearchInput"
                                           x-model="businessGroupSearch"
                                           @keydown="onBusinessGroupKeyDown($event)"
                                           placeholder="Search Business Group by name or code..."
                                           class="block w-full rounded-md border border-slate-200 bg-white py-1.5 pl-8 pr-2 text-xs text-slate-700 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                </div>
                            </div>

                            <!-- Options List -->
                            <div class="overflow-y-auto py-1 divide-y divide-slate-50 max-h-48 text-xs">
                                <div @click="clearBusinessGroup()"
                                     class="p-2 hover:bg-slate-50 cursor-pointer flex items-center justify-between text-slate-500 italic text-[11px] transition">
                                    <span>-- None / No Business Group --</span>
                                    <span x-show="!selectedBusinessGroupId" class="text-indigo-600 font-bold">✓</span>
                                </div>

                                <template x-for="(g, idx) in filteredBusinessGroups" :key="g.id">
                                    <div @click="selectBusinessGroup(g)"
                                         @mouseenter="highlightedGroupIndex = idx"
                                         class="p-2.5 cursor-pointer flex items-center justify-between transition-colors"
                                         :class="{
                                             'bg-indigo-50 text-indigo-900 font-semibold': String(selectedBusinessGroupId) === String(g.id),
                                             'bg-slate-100/70': highlightedGroupIndex === idx && String(selectedBusinessGroupId) !== String(g.id),
                                             'text-slate-800 hover:bg-slate-50': String(selectedBusinessGroupId) !== String(g.id)
                                         }">
                                        <div class="flex items-center gap-2 truncate">
                                            <span class="text-sm font-emoji" x-text="g.logo_emoji || '🏢'"></span>
                                            <span class="truncate" x-text="g.name"></span>
                                            <span class="font-mono text-[10px] text-slate-500 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded" x-text="g.code"></span>
                                        </div>
                                        <span x-show="String(selectedBusinessGroupId) === String(g.id)" class="text-indigo-600 font-bold ml-2">✓</span>
                                    </div>
                                </template>

                                <div x-show="!loadingBusinessGroups && filteredBusinessGroups.length === 0" class="p-4 text-center text-slate-400 space-y-2">
                                    <p class="text-[11px]">No business groups match "<span class="font-medium text-slate-600" x-text="businessGroupSearch"></span>"</p>
                                    <a href="{{ route('organization.business-groups.index', ['return_url' => url()->current()]) }}"
                                       class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-700 hover:text-indigo-900 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-200">
                                        + Add New Business Group
                                    </a>
                                </div>

                                <div x-show="loadingBusinessGroups" class="p-3 text-center text-slate-400 flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>Loading business groups...</span>
                                </div>

                                <div x-show="businessGroupError" class="p-2.5 bg-rose-50 text-rose-700 text-[11px] flex items-center justify-between">
                                    <span x-text="businessGroupError"></span>
                                    <button type="button" @click="refreshBusinessGroups(true)" class="underline font-semibold ml-2">Retry</button>
                                </div>
                            </div>

                            <!-- Footer with quick link -->
                            <div class="px-3 py-2 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                                <span x-text="`${filteredBusinessGroups.length} group${filteredBusinessGroups.length === 1 ? '' : 's'} available`"></span>
                                <a href="{{ route('organization.business-groups.index', ['return_url' => url()->current()]) }}"
                                   class="text-indigo-600 hover:text-indigo-900 font-semibold hover:underline inline-flex items-center gap-0.5">
                                    Manage Groups &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                    @error('business_group_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="customer_type_id" class="block text-xs font-semibold text-slate-700 tracking-wider">Classification</label>
                        <button type="button"
                                @click="openClassificationModal()"
                                class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1 hover:underline focus:outline-none">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Manually
                        </button>
                    </div>
                    <select name="customer_type_id"
                            id="customer_type_id"
                            x-ref="classificationSelect"
                            x-model="selectedCustomerTypeId"
                            @change="onClassificationChange($event)"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Classification</option>
                        @foreach($customerTypes as $t)
                            <option value="{{ $t->id }}" @selected(old('customer_type_id', $item->customer_type_id)==$t->id)>{{ $t->name }}</option>
                        @endforeach
                        <template x-for="t in customClassifications" :key="t.id">
                            <option :value="t.id" x-text="t.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">PAN</label>
                    <input type="text" name="pan" x-model="form.pan" maxlength="10"
                           class="block w-full uppercase rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Phone</label>
                    <input type="text" name="phone" x-model="form.phone"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Email</label>
                    <input type="email" name="email" x-model="form.email"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <input type="hidden" name="address" :value="form.address">
                <input type="hidden" name="state" :value="form.state">
                <input type="hidden" name="pincode" :value="form.pincode">
            </div>
        </div>

        <!-- Addresses (Multiple Delivery & Billing Locations) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700">Addresses &amp; Locations</h3>
                    <p class="text-xs text-slate-500">Multiple addresses allowed per party. Set default billing and delivery addresses.</p>
                </div>
                <button type="button" @click="addAddress()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Address
                </button>
            </div>

            <div class="space-y-4">
                <template x-for="(addr, idx) in addresses" :key="idx">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-4 transition shadow-xs">
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-200/80">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-600" x-text="`Address #${idx + 1}`"></span>
                                <span x-show="addr.label" class="text-xs font-medium text-slate-500" x-text="`(${addr.label})`"></span>
                            </div>
                            <div class="flex items-center gap-4">
                                <label class="inline-flex items-center gap-1.5 text-xs font-semibold cursor-pointer text-slate-700">
                                    <input type="radio" name="default_billing_radio" :checked="addr.is_default_billing" @change="setDefaultBilling(idx)"
                                           class="text-indigo-600 focus:ring-indigo-500">
                                    <span>Default Billing</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 text-xs font-semibold cursor-pointer text-slate-700">
                                    <input type="radio" name="default_delivery_radio" :checked="addr.is_default_delivery" @change="setDefaultDelivery(idx)"
                                           class="text-emerald-600 focus:ring-emerald-500">
                                    <span>Default Delivery</span>
                                </label>
                                <button type="button" x-show="addresses.length > 1" @click="removeAddress(idx)"
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">
                                    Remove
                                </button>
                            </div>
                        </div>

                        <!-- Hidden fields to submit to backend -->
                        <input type="hidden" :name="`addresses[${idx}][id]`" :value="addr.id || ''">
                        <input type="hidden" :name="`addresses[${idx}][is_default_billing]`" :value="addr.is_default_billing ? 1 : 0">
                        <input type="hidden" :name="`addresses[${idx}][is_default_delivery]`" :value="addr.is_default_delivery ? 1 : 0">

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Address Label *</label>
                                <input type="text" :name="`addresses[${idx}][label]`" x-model="addr.label" placeholder="E.G. Head Office, Warehouse"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Address Type</label>
                                <select :name="`addresses[${idx}][type]`" x-model="addr.type"
                                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="both">Both (Billing &amp; Delivery)</option>
                                    <option value="billing">Billing Only</option>
                                    <option value="delivery">Delivery Only</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Contact Person (optional)</label>
                                <input type="text" :name="`addresses[${idx}][contact_person]`" x-model="addr.contact_person" placeholder="Name"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Contact Phone (optional)</label>
                                <input type="text" :name="`addresses[${idx}][contact_phone]`" x-model="addr.contact_phone" placeholder="Phone"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Address Line 1 *</label>
                                <input type="text" :name="`addresses[${idx}][address_line_1]`" x-model="addr.address_line_1" @input="syncPrimaryAddress()"
                                       placeholder="Flat / Door / Block No., Premises, Street"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Address Line 2 (optional)</label>
                                <input type="text" :name="`addresses[${idx}][address_line_2]`" x-model="addr.address_line_2" @input="syncPrimaryAddress()"
                                       placeholder="Area, Landmark, Locality"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">State *</label>
                                <div class="relative" x-data="stateDropdown(addr, statesList, () => syncPrimaryAddress())" @click.outside="close()">
                                    <!-- Hidden input for standard form submission -->
                                    <input type="hidden" :name="`addresses[${idx}][state]`" :value="addr.state">

                                    <!-- Trigger Button -->
                                    <button type="button"
                                            x-ref="triggerButton"
                                            @click="toggle()"
                                            @keydown="onKeyDown($event)"
                                            :class="open ? 'ring-2 ring-indigo-500 border-indigo-500' : 'border-gray-300 hover:border-gray-400'"
                                            class="relative w-full rounded-lg border bg-white py-2 pl-3 pr-2 text-left text-sm shadow-xs focus:outline-none transition flex items-center justify-between">
                                        <span class="block truncate" :class="addr.state ? 'text-slate-800 font-medium' : 'text-slate-400'"
                                              x-text="selectedLabel || 'Select State / UT'"></span>
                                        <div class="flex items-center gap-1 shrink-0 ml-2">
                                            <template x-if="addr.state">
                                                <span @click.stop="clearState()" title="Clear State"
                                                      class="p-0.5 text-slate-400 hover:text-rose-500 rounded cursor-pointer transition">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </span>
                                            </template>
                                            <svg class="h-4 w-4 text-slate-400 transition-transform duration-150" :class="open ? 'rotate-180 text-indigo-600' : ''" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>

                                    <!-- Dropdown Menu -->
                                    <div x-show="open"
                                         x-cloak
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="opacity-0 translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="opacity-100 translate-y-0"
                                         x-transition:leave-end="opacity-0 translate-y-1"
                                         class="absolute left-0 right-0 top-full z-50 mt-1 max-h-60 rounded-lg bg-white shadow-lg ring-1 ring-black/5 border border-slate-200 overflow-hidden flex flex-col focus:outline-none"
                                         style="display: none;">
                                        <div class="p-2 border-b border-slate-100 bg-slate-50/70 sticky top-0 z-10">
                                            <div class="relative">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                    </svg>
                                                </div>
                                                <input type="text"
                                                       x-ref="searchInput"
                                                       x-model="search"
                                                       @input="highlightedIndex = 0"
                                                       @keydown="onKeyDown($event)"
                                                       placeholder="Search State..."
                                                       class="block w-full rounded-md border border-slate-200 bg-white py-1.5 pl-8 pr-2 text-xs text-slate-700 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                            </div>
                                        </div>
                                        <ul x-ref="optionsList" class="max-h-48 overflow-y-auto py-1 text-sm divide-y divide-slate-50">
                                            <template x-for="(st, sIdx) in filteredStates" :key="st.code">
                                                <li :data-index="sIdx"
                                                    @click="selectState(st)"
                                                    @mouseenter="highlightedIndex = sIdx"
                                                    :class="{
                                                        'bg-indigo-50 text-indigo-900 font-semibold': addr.state === st.name,
                                                        'bg-slate-100 text-slate-900': highlightedIndex === sIdx && addr.state !== st.name,
                                                        'text-slate-700': addr.state !== st.name && highlightedIndex !== sIdx
                                                    }"
                                                    class="cursor-pointer select-none px-3 py-2 text-xs flex items-center justify-between hover:bg-slate-100 transition-colors">
                                                    <div class="flex items-center gap-2">
                                                        <span x-text="st.name"></span>
                                                        <span class="text-[10px] text-slate-400 font-mono" x-text="`(${st.code})`"></span>
                                                    </div>
                                                    <span x-show="addr.state === st.name" class="text-indigo-600 font-bold">✓</span>
                                                </li>
                                            </template>
                                            <template x-if="filteredStates.length === 0">
                                                <li class="p-3 text-xs text-slate-400 text-center italic">
                                                    No states found
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">City *</label>
                                <div class="relative" x-data="cityDropdown(addr, stateCities)" @click.outside="close()">
                                    <!-- Hidden input for standard form submission -->
                                    <input type="hidden" :name="`addresses[${idx}][city]`" :value="addr.city">

                                    <!-- Trigger Button -->
                                    <button type="button"
                                            x-ref="triggerButton"
                                            @click="toggle()"
                                            @keydown="onKeyDown($event)"
                                            :disabled="isDisabled"
                                            :class="{
                                                'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200 select-none': isDisabled,
                                                'ring-2 ring-indigo-500 border-indigo-500 bg-white': !isDisabled && open,
                                                'border-gray-300 hover:border-gray-400 bg-white': !isDisabled && !open
                                            }"
                                            class="relative w-full rounded-lg border py-2 pl-3 pr-2 text-left text-sm shadow-xs focus:outline-none transition flex items-center justify-between">
                                        <span class="block truncate"
                                              :class="(!isDisabled && addr.city) ? 'text-slate-800 font-medium' : 'text-slate-400'"
                                              x-text="isDisabled ? 'Select State First' : (addr.city || 'Select City')"></span>
                                        <div class="flex items-center gap-1 shrink-0 ml-2">
                                            <template x-if="!isDisabled && addr.city">
                                                <span @click.stop="clearCity()" title="Clear City"
                                                      class="p-0.5 text-slate-400 hover:text-rose-500 rounded cursor-pointer transition">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </span>
                                            </template>
                                            <svg class="h-4 w-4 text-slate-400 transition-transform duration-150" :class="(!isDisabled && open) ? 'rotate-180 text-indigo-600' : ''" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>

                                    <!-- Dropdown Menu -->
                                    <div x-show="!isDisabled && open"
                                         x-cloak
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="opacity-0 translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="opacity-100 translate-y-0"
                                         x-transition:leave-end="opacity-0 translate-y-1"
                                         class="absolute left-0 right-0 top-full z-50 mt-1 max-h-60 rounded-lg bg-white shadow-lg ring-1 ring-black/5 border border-slate-200 overflow-hidden flex flex-col focus:outline-none"
                                         style="display: none;">
                                        <div class="p-2 border-b border-slate-100 bg-slate-50/70 sticky top-0 z-10">
                                            <div class="relative">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                    </svg>
                                                </div>
                                                <input type="text"
                                                       x-ref="searchInput"
                                                       x-model="search"
                                                       @input="highlightedIndex = 0"
                                                       @keydown="onKeyDown($event)"
                                                       placeholder="Search City..."
                                                       class="block w-full rounded-md border border-slate-200 bg-white py-1.5 pl-8 pr-2 text-xs text-slate-700 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                            </div>
                                        </div>
                                        <ul x-ref="optionsList" class="max-h-48 overflow-y-auto py-1 text-sm divide-y divide-slate-50">
                                            <template x-for="(city, cIdx) in filteredCities" :key="city">
                                                <li :data-index="cIdx"
                                                    @click="selectCity(city)"
                                                    @mouseenter="highlightedIndex = cIdx"
                                                    :class="{
                                                        'bg-indigo-50 text-indigo-900 font-semibold': addr.city === city,
                                                        'bg-slate-100 text-slate-900': highlightedIndex === cIdx && addr.city !== city,
                                                        'text-slate-700': addr.city !== city && highlightedIndex !== cIdx
                                                    }"
                                                    class="cursor-pointer select-none px-3 py-2 text-xs flex items-center justify-between hover:bg-slate-100 transition-colors">
                                                    <span x-text="city"></span>
                                                    <span x-show="addr.city === city" class="text-indigo-600 font-bold">✓</span>
                                                </li>
                                            </template>
                                            <template x-if="filteredCities.length === 0">
                                                <li class="p-3 text-xs text-slate-400 text-center italic">
                                                    No cities found
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                    <template x-if="addr.city && addr.state && !isCityValidForState(addr.city, addr.state)">
                                        <p class="text-[11px] text-rose-600 mt-1 font-medium">⚠️ Note: Selected city does not belong to <span x-text="addr.state"></span></p>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">PIN Code *</label>
                                <input type="text" :name="`addresses[${idx}][pincode]`" x-model="addr.pincode" maxlength="12" @input="syncPrimaryAddress()"
                                       placeholder="6-Digit Pin Code"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Sales &amp; Logistics Assignment</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.select name="salesperson_id" label="Assigned Salesperson">
                    <option value="">Unassigned</option>
                    @foreach($salespersons as $s)
                        <option value="{{ $s->id }}" @selected(old('salesperson_id', $item->salesperson_id)==$s->id)>{{ $s->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="sales_manager_id" label="Assigned Sales Manager">
                    <option value="">Unassigned</option>
                    @foreach($salesManagers as $m)
                        <option value="{{ $m->id }}" @selected(old('sales_manager_id', $item->sales_manager_id)==$m->id)>{{ $m->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="area_id" label="Area">
                    <option value="">Unassigned</option>
                    @foreach($areas as $a)
                        <option value="{{ $a->id }}" @selected(old('area_id', $item->area_id)==$a->id)>{{ $a->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="route_id" label="Delivery Route">
                    <option value="">Unassigned</option>
                    @foreach($routes as $r)
                        <option value="{{ $r->id }}" @selected(old('route_id', $item->route_id)==$r->id)>{{ $r->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Credit Terms &amp; Policies</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="credit_limit" label="Credit Limit (₹)" type="number" step="0.01" :value="old('credit_limit', $item->credit_limit ?? 0)" />
                <x-ui.input name="credit_days" label="Credit Period (Days)" type="number" :value="old('credit_days', $item->credit_days ?? 0)" />
                <x-ui.select name="credit_status" label="Credit Status">
                    @foreach(['open' => 'Open', 'hold' => 'On Hold', 'blocked' => 'Blocked'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('credit_status', $item->credit_status ?? 'open')===$val)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="credit_period_basis" label="Credit Period Basis">
                    @foreach(['cumulative' => 'Cumulative / Balance', 'invoice_date' => 'Invoice Date', 'inward_date' => 'Inward Date', 'receipt_date' => 'Receipt Date'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('credit_period_basis', $item->credit_period_basis ?? 'cumulative')===$val)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="interest_rate" label="Overdue Interest Rate (% per annum)" type="number" step="0.01" :value="old('interest_rate', $item->interest_rate ?? 18)" />
            </div>

            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50/70 p-3.5 text-xs text-slate-600">
                <div class="flex items-center gap-2 font-semibold text-slate-700 mb-1">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Commercial Setup &amp; Price Schedules
                </div>
                <p>Specific pricing schedules and custom rates are managed centrally in <a href="{{ route('masters.price-masters.index') }}" target="_blank" class="text-indigo-600 underline font-medium hover:text-indigo-800">Master &rarr; Price Master</a> based on party classification.</p>
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700">Contact Persons</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Add the people you deal with at this party. One can be marked primary.</p>
                </div>
                <button type="button" @click="addContact()"
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shrink-0">
                    + Add Contact
                </button>
            </div>
            <div x-show="contacts.length === 0" class="rounded-lg border border-dashed border-slate-300 px-3 py-4 text-xs text-slate-500">
                No contact persons yet.
            </div>
            <template x-for="(contact, idx) in contacts" :key="idx">
                <div class="rounded-lg border border-slate-200 p-3 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                            <input type="radio" name="contact_primary" :checked="contact.is_primary" @change="setPrimaryContact(idx)" class="text-indigo-600 focus:ring-indigo-500">
                            Primary contact
                        </label>
                        <button type="button" @click="removeContact(idx)" class="text-xs font-semibold text-rose-600 hover:text-rose-800">Remove</button>
                    </div>
                    <input type="hidden" :name="`contacts[${idx}][level]`" value="party">
                    <input type="hidden" :name="`contacts[${idx}][is_primary]`" :value="contact.is_primary ? 1 : 0">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Name *</label>
                            <input type="text" :name="`contacts[${idx}][name]`" x-model="contact.name" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Designation</label>
                            <input type="text" :name="`contacts[${idx}][role]`" x-model="contact.role" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Mobile 1</label>
                            <input type="text" :name="`contacts[${idx}][phone]`" x-model="contact.phone" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Mobile 2</label>
                            <input type="text" :name="`contacts[${idx}][alternate_phone]`" x-model="contact.alternate_phone" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Email</label>
                            <input type="email" :name="`contacts[${idx}][email]`" x-model="contact.email" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700">Bank Accounts</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Enter IFSC to auto-fill bank and branch. Account number and type are entered manually.</p>
                </div>
                <button type="button" @click="addBankAccount()"
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shrink-0">
                    + Add Bank Account
                </button>
            </div>

            <div class="rounded-lg border border-sky-100 bg-sky-50/70 px-3 py-2 text-xs text-sky-800">
                Security / credit / PDC cheques are recorded under
                <a href="{{ route('cheques.create') }}" target="_blank" class="font-semibold underline hover:text-sky-950">Receivables → Cheque Register → Record Cheque</a>.
            </div>

            <div class="space-y-3">
                <template x-for="(acc, idx) in bankAccounts" :key="idx">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500" x-text="`Bank Account #${idx + 1}`"></span>
                            <div class="flex items-center gap-4">
                                <label class="inline-flex items-center gap-1.5 text-xs font-semibold cursor-pointer text-slate-700">
                                    <input type="radio" name="default_bank_account_radio" :checked="acc.is_primary" @change="setDefaultBankAccount(idx)"
                                           class="text-indigo-600 focus:ring-indigo-500">
                                    <span>Default Account</span>
                                </label>
                                <button type="button" x-show="bankAccounts.length > 1" @click="removeBankAccount(idx)"
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">Remove</button>
                            </div>
                        </div>

                        <!-- Hidden fields to submit to backend -->
                        <input type="hidden" :name="`bank_accounts[${idx}][id]`" :value="acc.id || ''">
                        <input type="hidden" :name="`bank_accounts[${idx}][is_primary]`" :value="acc.is_primary ? 1 : 0">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">IFSC Code</label>
                                <div class="relative">
                                    <input type="text" :name="`bank_accounts[${idx}][ifsc_code]`" x-model="acc.ifsc_code" maxlength="11"
                                           @input="acc.ifsc_code = (acc.ifsc_code || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 11); onIfscInput(idx)"
                                           @blur="lookupIfsc(idx)"
                                           placeholder="e.g. HDFC0001234"
                                           class="block w-full uppercase rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-10">
                                    <span x-show="acc._ifsc_loading" class="absolute inset-y-0 right-3 flex items-center text-slate-400 text-xs" x-cloak>…</span>
                                </div>
                                <p x-show="acc._ifsc_message" x-cloak
                                   class="mt-1 text-[11px]"
                                   :class="acc._ifsc_status === 'ok' ? 'text-emerald-600' : (acc._ifsc_status === 'error' ? 'text-rose-600' : 'text-slate-500')"
                                   x-text="acc._ifsc_message"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Bank Name</label>
                                <input type="text" :name="`bank_accounts[${idx}][bank_name]`" x-model="acc.bank_name"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Branch Name</label>
                                <input type="text" :name="`bank_accounts[${idx}][branch_name]`" x-model="acc.branch_name"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Account Holder Name</label>
                                <input type="text" :name="`bank_accounts[${idx}][account_holder_name]`" x-model="acc.account_holder_name"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Account Number</label>
                                <input type="text" :name="`bank_accounts[${idx}][account_number]`" x-model="acc.account_number"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Account Type</label>
                                <select :name="`bank_accounts[${idx}][account_type]`" x-model="acc.account_type"
                                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="current">Current Account</option>
                                    <option value="savings">Savings Account</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">IFSC Code</label>
                                <input type="text" :name="`bank_accounts[${idx}][ifsc_code]`" x-model="acc.ifsc_code" maxlength="20"
                                       class="block w-full uppercase rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm pt-2">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active
        </label>

        <div class="flex items-center gap-3 pt-2">
            <x-ui.button type="submit" variant="primary">
                {{ isset($lead) ? 'Save Customer & Complete Conversion' : 'Save Party' }}
            </x-ui.button>
            <x-ui.button variant="secondary" :href="isset($lead) ? route('crm.leads.index') : route('masters.customers.index')">
                Cancel
            </x-ui.button>
        </div>

        <!-- Add Classification Modal -->
        <div x-show="classificationModalOpen"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title"
             role="dialog"
             aria-modal="true"
             @keydown.escape.window="closeClassificationModal()">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="classificationModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 transition-opacity bg-slate-900/50 backdrop-blur-sm"
                     @click="closeClassificationModal()"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="classificationModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-2xl shadow-xl sm:my-8"
                     @click.stop>

                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-semibold text-slate-800" id="modal-title">Add Classification</h3>
                        <button type="button" @click="closeClassificationModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Error Feedback -->
                    <div x-show="classificationError" x-cloak class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs font-medium text-red-700 flex items-start gap-2">
                        <svg class="w-4 h-4 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="classificationError"></span>
                    </div>

                    <!-- Success Feedback -->
                    <div x-show="classificationSuccess" x-cloak class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs font-medium text-emerald-700 flex items-start gap-2">
                        <svg class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="classificationSuccess"></span>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Classification Name *</label>
                            <input type="text"
                                   x-ref="classificationInput"
                                   x-model="newClassificationName"
                                   :disabled="classificationSaving"
                                   @keydown.enter.prevent="saveClassification()"
                                   placeholder="E.G. Retailer, Super Stockist, Corporate"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100 disabled:cursor-not-allowed">
                            <p class="text-[11px] text-slate-500 mt-1">This classification will be saved to the master database and selected automatically.</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button"
                                    @click="closeClassificationModal()"
                                    :disabled="classificationSaving"
                                    class="px-4 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition">
                                Cancel
                            </button>
                            <button type="button"
                                    @click="saveClassification()"
                                    :disabled="classificationSaving"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 shadow-sm transition">
                                <svg x-show="classificationSaving" class="w-3.5 h-3.5 animate-spin -ml-0.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-text="classificationSaving ? 'Saving...' : 'Save'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-ui.card>

@push('scripts')
<script>
function partyFormController() {
    return {
        // Business Group state
        businessGroups: {!! json_encode($businessGroups ?? []) !!},
        selectedBusinessGroupId: {!! json_encode((string) ($initialBusinessGroupId ?? '')) !!},
        selectedBusinessGroup: null,
        businessGroupSearch: '',
        businessGroupDropdownOpen: false,
        loadingBusinessGroups: false,
        businessGroupError: '',
        highlightedGroupIndex: 0,
        dropdownPlacement: 'bottom',

        init() {
            if (this.selectedBusinessGroupId) {
                this.selectBusinessGroupById(this.selectedBusinessGroupId);
            }
            window.addEventListener('resize', () => {
                if (this.businessGroupDropdownOpen) {
                    this.updateDropdownPlacement();
                }
            });
            window.addEventListener('scroll', () => {
                if (this.businessGroupDropdownOpen) {
                    this.updateDropdownPlacement();
                }
            }, true);
        },

        toggleBusinessGroupDropdown() {
            this.businessGroupDropdownOpen = !this.businessGroupDropdownOpen;
            if (this.businessGroupDropdownOpen) {
                this.businessGroupSearch = '';
                this.highlightedGroupIndex = 0;
                this.updateDropdownPlacement();
                this.$nextTick(() => {
                    this.$refs.businessGroupSearchInput?.focus();
                });
                if (!this.businessGroups || this.businessGroups.length === 0) {
                    this.refreshBusinessGroups();
                }
            }
        },

        closeBusinessGroupDropdown() {
            this.businessGroupDropdownOpen = false;
        },

        updateDropdownPlacement() {
            this.$nextTick(() => {
                const container = this.$refs.businessGroupContainer;
                if (!container) return;
                const rect = container.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const spaceBelow = viewportHeight - rect.bottom;
                const minSpaceNeeded = 260;

                if (spaceBelow < minSpaceNeeded && rect.top > minSpaceNeeded) {
                    this.dropdownPlacement = 'top';
                } else {
                    this.dropdownPlacement = 'bottom';
                }
            });
        },

        get filteredBusinessGroups() {
            if (!Array.isArray(this.businessGroups)) {
                return [];
            }
            const q = (this.businessGroupSearch || '').trim().toLowerCase();
            if (!q) {
                return this.businessGroups;
            }
            return this.businessGroups.filter(g => {
                const name = (g.name || '').toLowerCase();
                const code = (g.code || '').toLowerCase();
                return name.includes(q) || code.includes(q);
            });
        },

        selectBusinessGroup(group) {
            if (!group) {
                this.clearBusinessGroup();
                return;
            }
            this.selectedBusinessGroup = group;
            this.selectedBusinessGroupId = String(group.id);
            this.businessGroupSearch = '';
            this.businessGroupError = '';
            this.closeBusinessGroupDropdown();
        },

        selectBusinessGroupById(id) {
            if (!id) {
                this.clearBusinessGroup();
                return;
            }
            const found = (this.businessGroups || []).find(g => String(g.id) === String(id));
            if (found) {
                this.selectedBusinessGroup = found;
                this.selectedBusinessGroupId = String(found.id);
            } else {
                this.fetchBusinessGroupById(id);
            }
        },

        clearBusinessGroup() {
            this.selectedBusinessGroup = null;
            this.selectedBusinessGroupId = '';
            this.businessGroupSearch = '';
            this.closeBusinessGroupDropdown();
        },

        async refreshBusinessGroups(showIndicator = true) {
            if (showIndicator) {
                this.loadingBusinessGroups = true;
            }
            this.businessGroupError = '';

            try {
                let url = "{{ route('masters.customers.business-groups') }}";
                if (this.selectedBusinessGroupId) {
                    url += `?include_id=${encodeURIComponent(this.selectedBusinessGroupId)}`;
                }
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json();
                if (response.ok && result.success && Array.isArray(result.data)) {
                    this.businessGroups = result.data;
                    if (this.selectedBusinessGroupId) {
                        const found = this.businessGroups.find(g => String(g.id) === String(this.selectedBusinessGroupId));
                        if (found) {
                            this.selectedBusinessGroup = found;
                        }
                    }
                } else {
                    this.businessGroupError = result.message || 'Failed to load business groups.';
                }
            } catch (e) {
                this.businessGroupError = 'Network error fetching business groups.';
            } finally {
                this.loadingBusinessGroups = false;
            }
        },

        async fetchBusinessGroupById(id) {
            try {
                const url = `{{ route('masters.customers.business-groups') }}?include_id=${encodeURIComponent(id)}`;
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json();
                if (response.ok && result.success && Array.isArray(result.data)) {
                    this.businessGroups = result.data;
                    const found = this.businessGroups.find(g => String(g.id) === String(id));
                    if (found) {
                        this.selectedBusinessGroup = found;
                        this.selectedBusinessGroupId = String(found.id);
                    }
                }
            } catch (e) {
                // Silently ignore background fetch failure
            }
        },

        onBusinessGroupKeyDown(event) {
            const list = this.filteredBusinessGroups;
            if (!this.businessGroupDropdownOpen) {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Enter') {
                    event.preventDefault();
                    this.toggleBusinessGroupDropdown();
                }
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (list.length > 0) {
                    this.highlightedGroupIndex = (this.highlightedGroupIndex + 1) % list.length;
                    this.scrollActiveGroupIntoView();
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (list.length > 0) {
                    this.highlightedGroupIndex = (this.highlightedGroupIndex - 1 + list.length) % list.length;
                    this.scrollActiveGroupIntoView();
                }
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (list.length > 0 && list[this.highlightedGroupIndex]) {
                    this.selectBusinessGroup(list[this.highlightedGroupIndex]);
                }
            } else if (event.key === 'Escape') {
                event.preventDefault();
                this.closeBusinessGroupDropdown();
            }
        },

        scrollActiveGroupIntoView() {
            this.$nextTick(() => {
                const el = this.$refs.businessGroupContainer?.querySelector(`[data-index="${this.highlightedGroupIndex}"]`);
                if (el) {
                    el.scrollIntoView({ block: 'nearest' });
                }
            });
        },

        searchingGst: false,
        gstFeedback: {
            type: '',
            message: '',
            note: ''
        },
        gstLookupInfo: null,
        gstLookupAppliedKey: '',
        selectedCustomerTypeId: {!! json_encode((string) old('customer_type_id', $item->customer_type_id ?? '')) !!},
        previousCustomerTypeId: {!! json_encode((string) old('customer_type_id', $item->customer_type_id ?? '')) !!},
        customClassifications: [],
        knownClassificationNames: {!! json_encode($customerTypes->pluck('name')->map(fn($n) => mb_strtolower(trim($n)))->values()) !!},
        classificationModalOpen: false,
        newClassificationName: '',
        classificationSaving: false,
        classificationError: '',
        classificationSuccess: '',

        openClassificationModal() {
            this.classificationModalOpen = true;
            this.newClassificationName = '';
            this.classificationError = '';
            this.classificationSuccess = '';
            this.$nextTick(() => {
                this.$refs.classificationInput?.focus();
            });
        },
        closeClassificationModal() {
            this.classificationModalOpen = false;
            this.classificationError = '';
            this.classificationSuccess = '';
            if (this.selectedCustomerTypeId === '__add_manually__') {
                this.selectedCustomerTypeId = this.previousCustomerTypeId;
            }
        },
        onClassificationChange(event) {
            if (this.selectedCustomerTypeId === '__add_manually__') {
                this.openClassificationModal();
            } else {
                this.previousCustomerTypeId = this.selectedCustomerTypeId;
            }
        },
        async saveClassification() {
            const rawName = this.newClassificationName || '';
            const name = rawName.trim();

            if (!name) {
                this.classificationError = 'Classification Name is required.';
                return;
            }

            // Client-side case-insensitive duplicate check
            const lowerName = name.toLowerCase();
            if (this.knownClassificationNames.includes(lowerName)) {
                this.classificationError = 'A classification with this name already exists.';
                return;
            }

            this.classificationSaving = true;
            this.classificationError = '';
            this.classificationSuccess = '';

            try {
                const response = await fetch("{{ route('masters.customer-types.quick-store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name: name })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    this.classificationError = data.message || 'Failed to save classification.';
                    this.classificationSaving = false;
                    return;
                }

                const newItem = data.item;
                const newIdStr = String(newItem.id);

                const alreadyCustom = this.customClassifications.some(c => String(c.id) === newIdStr);
                if (!alreadyCustom) {
                    this.customClassifications.push({
                        id: newItem.id,
                        name: newItem.name
                    });
                }
                this.knownClassificationNames.push(newItem.name.trim().toLowerCase());

                const selectEl = this.$refs.classificationSelect;
                if (selectEl && !selectEl.querySelector(`option[value="${newItem.id}"]`)) {
                    const opt = new Option(newItem.name, newItem.id);
                    const addManuallyOpt = selectEl.querySelector('option[value="__add_manually__"]');
                    selectEl.insertBefore(opt, addManuallyOpt);
                }

                this.selectedCustomerTypeId = newIdStr;
                this.previousCustomerTypeId = newIdStr;
                this.classificationSuccess = data.message || 'Classification created successfully!';

                setTimeout(() => {
                    this.closeClassificationModal();
                }, 700);
            } catch (err) {
                this.classificationError = 'An error occurred while saving the classification. Please try again.';
            } finally {
                this.classificationSaving = false;
            }
        },

        form: {
            name: {!! json_encode(old('name', $item->name ?? '')) !!},
            trade_name: {!! json_encode(old('trade_name', $item->trade_name ?? '')) !!},
            gstin: {!! json_encode(old('gstin', $item->gstin ?? '')) !!},
            party_type: {!! json_encode(old('party_type', $item->party_type_key ?? 'sundry_debtors')) !!},
            pan: {!! json_encode(old('pan', $item->pan ?? '')) !!},
            phone: {!! json_encode(old('phone', $item->phone ?? '')) !!},
            email: {!! json_encode(old('email', $item->email ?? '')) !!},
            address: {!! json_encode(old('address', $item->address ?? '')) !!},
            state: {!! json_encode(old('state', $item->state ?? '')) !!},
            pincode: {!! json_encode(old('pincode', $item->pincode ?? '')) !!},
        },
        lastAutoGstin: '',
        bankAccounts: {!! json_encode($initialBankAccounts) !!},
        contacts: {!! json_encode($initialContacts) !!},
        addresses: {!! json_encode($initialAddresses) !!},
        _ifscTimers: {},
        _ifscLastFetched: {},
        stateCities: {!! json_encode($stateCities ?? \App\Support\IndianCities::all()) !!},
        statesList: {!! json_encode($stateOptions ?? \App\Support\IndianStates::options()) !!},

        getCities(stateName) {
            if (!stateName) return [];
            return this.stateCities[stateName] || [];
        },

        addContact() {
            this.contacts.push({
                name: '',
                role: '',
                phone: '',
                alternate_phone: '',
                email: '',
                is_primary: this.contacts.length === 0,
            });
        },

        removeContact(idx) {
            const wasPrimary = this.contacts[idx]?.is_primary;
            this.contacts.splice(idx, 1);
            if (wasPrimary && this.contacts.length) {
                this.contacts[0].is_primary = true;
            }
        },

        setPrimaryContact(idx) {
            this.contacts.forEach((contact, index) => {
                contact.is_primary = index === idx;
            });
        },

        addAddress() {
            const hasBilling = this.addresses.some(a => a.is_default_billing);
            const hasDelivery = this.addresses.some(a => a.is_default_delivery);
            this.addresses.push({
                id: null,
                label: 'Branch / Warehouse',
                contact_person: '',
                contact_phone: '',
                address_line_1: '',
                address_line_2: '',
                city: '',
                state: this.form.state || '',
                pincode: '',
                type: 'both',
                is_default_billing: !hasBilling,
                is_default_delivery: !hasDelivery
            });
        },

        removeAddress(idx) {
            if (this.addresses.length <= 1) return;
            const wasBilling = this.addresses[idx].is_default_billing;
            const wasDelivery = this.addresses[idx].is_default_delivery;
            this.addresses.splice(idx, 1);
            if (wasBilling && this.addresses.length > 0) {
                this.setDefaultBilling(0);
            }
            if (wasDelivery && this.addresses.length > 0) {
                this.setDefaultDelivery(0);
            }
            this.syncPrimaryAddress();
        },

        setDefaultBilling(index) {
            this.addresses.forEach((addr, i) => {
                addr.is_default_billing = (i === index);
                if (i === index && addr.type === 'delivery') {
                    addr.type = 'both';
                }
            });
            this.syncPrimaryAddress();
        },

        setDefaultDelivery(index) {
            this.addresses.forEach((addr, i) => {
                addr.is_default_delivery = (i === index);
                if (i === index && addr.type === 'billing') {
                    addr.type = 'both';
                }
            });
        },

        syncPrimaryAddress() {
            const primary = this.addresses.find(a => a.is_default_billing) || this.addresses[0];
            if (primary) {
                const line1 = primary.address_line_1 || '';
                const line2 = primary.address_line_2 || '';
                this.form.address = (line1 + (line2 ? ', ' + line2 : '')).trim();
                if (primary.state) this.form.state = primary.state;
                if (primary.pincode) this.form.pincode = primary.pincode;
            }
        },

        isCityValidForState(city, state) {
            if (!city || !state) return true;
            const cleanCity = city.trim().toLowerCase();
            const citiesInState = (this.stateCities[state] || []).map(c => c.toLowerCase());
            if (citiesInState.includes(cleanCity)) return true;
            for (const [st, cities] of Object.entries(this.stateCities)) {
                if (st === state) continue;
                if (cities.map(c => c.toLowerCase()).includes(cleanCity)) {
                    return false;
                }
            }
            return true;
        },

        addBankAccount() {
            const hasPrimary = this.bankAccounts.some(b => b.is_primary);
            this.bankAccounts.push({
                id: null,
                account_holder_name: this.form.name || '',
                bank_name: '',
                branch_name: '',
                account_number: '',
                account_type: 'current',
                ifsc_code: '',
                is_primary: !hasPrimary
            });
        },

        removeBankAccount(idx) {
            if (this.bankAccounts.length <= 1) return;
            const wasPrimary = this.bankAccounts[idx].is_primary;
            this.bankAccounts.splice(idx, 1);
            if (wasPrimary && this.bankAccounts.length > 0) {
                this.setDefaultBankAccount(0);
            }
        },

        setDefaultBankAccount(index) {
            this.bankAccounts.forEach((acc, i) => {
                acc.is_primary = (i === index);
            });
        },

        applyExtractedGstinFields(extracted = null, gstin = '') {
            const source = extracted || {};
            const derivedPan = (source.pan || (gstin.length >= 12 ? gstin.substring(2, 12) : '') || '').trim();
            const stateCode = (source.state_code || (gstin.length >= 2 ? gstin.substring(0, 2) : '') || '').toString();
            const stateMatch = (this.statesList || []).find(s => String(s.code) === stateCode);
            const derivedState = (source.state || (stateMatch ? stateMatch.name : '') || '').trim();

            if (derivedPan && (!this.form.pan || !this.form.pan.trim())) {
                this.form.pan = derivedPan;
            }
            if (derivedState && (!this.form.state || !this.form.state.trim())) {
                this.form.state = derivedState;
            }
            if (this.addresses && this.addresses.length > 0) {
                const primary = this.addresses.find(a => a.is_default_billing) || this.addresses[0];
                if (primary && derivedState && (!primary.state || !primary.state.trim())) {
                    primary.state = derivedState;
                }
            }
        },

        addressFingerprint(addr) {
            const norm = (v) => String(v || '').trim().toLowerCase().replace(/\s+/g, ' ');
            return [norm(addr.address_line_1), norm(addr.city), norm(addr.pincode)].join('|');
        },

        isAddressEmpty(addr) {
            if (!addr) return true;
            return !String(addr.address_line_1 || '').trim()
                && !String(addr.address_line_2 || '').trim()
                && !String(addr.city || '').trim()
                && !String(addr.pincode || '').trim();
        },

        applyGstAddresses(party) {
            if (!this.addresses) this.addresses = [];
            const returned = Array.isArray(party.addresses) && party.addresses.length
                ? party.addresses
                : [{
                    label: party.address_label || 'Principal Place of Business',
                    address_line_1: party.address_line_1 || party.address || '',
                    address_line_2: party.address_line_2 || '',
                    city: party.city || '',
                    state: party.state || '',
                    pincode: party.pincode || '',
                }];

            const take = (value) => {
                const clean = String(value || '').trim();
                return clean || null;
            };

            // Always refresh the principal / default billing address from the latest GST fetch.
            const principal = returned[0] || null;
            if (principal) {
                let primary = this.addresses.find(a => a.is_default_billing) || this.addresses[0];
                if (!primary) {
                    this.addAddress();
                    primary = this.addresses[0];
                }

                const label = take(principal.label) || 'Principal Place of Business';
                if (!String(primary.label || '').trim()
                    || ['Head Office', 'Branch / Warehouse', 'Principal Place of Business'].includes(primary.label)
                    || String(primary.label || '').startsWith('Additional Place of Business')) {
                    primary.label = label;
                }

                if (take(principal.address_line_1)) primary.address_line_1 = take(principal.address_line_1);
                if (take(principal.address_line_2) !== null) primary.address_line_2 = take(principal.address_line_2) || '';
                if (take(principal.city)) primary.city = take(principal.city);
                if (take(principal.state) || take(party.state)) primary.state = take(principal.state) || take(party.state);
                if (take(principal.pincode)) primary.pincode = take(principal.pincode);
            }

            // Add only new additional places of business (never duplicate).
            const existingKeys = new Set(this.addresses.map(a => this.addressFingerprint(a)));
            returned.slice(1).forEach((src, index) => {
                const mapped = {
                    label: take(src.label) || `Additional Place of Business ${index + 1}`,
                    address_line_1: take(src.address_line_1) || '',
                    address_line_2: take(src.address_line_2) || '',
                    city: take(src.city) || '',
                    state: take(src.state) || take(party.state) || '',
                    pincode: take(src.pincode) || '',
                };
                const key = this.addressFingerprint(mapped);
                if (!mapped.address_line_1 && !mapped.city && !mapped.pincode) return;
                if (existingKeys.has(key)) return;

                this.addresses.push({
                    id: null,
                    label: mapped.label,
                    contact_person: '',
                    contact_phone: '',
                    address_line_1: mapped.address_line_1,
                    address_line_2: mapped.address_line_2,
                    city: mapped.city,
                    state: mapped.state,
                    pincode: mapped.pincode,
                    type: 'both',
                    is_default_billing: false,
                    is_default_delivery: false,
                });
                existingKeys.add(key);
            });

            this.syncPrimaryAddress();
        },

        autoFetchGst() {
            const gstin = (this.form.gstin || '').trim().toUpperCase();
            if (gstin.length !== 15 || gstin === this.lastAutoGstin || this.searchingGst) return;
            const gstinPattern = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[0-9A-Z]{1}[0-9A-Z]{1}$/;
            if (!gstinPattern.test(gstin)) return;
            this.fetchGstDetails();
        },

        async fetchGstDetails() {
            const gstin = (this.form.gstin || '').trim().toUpperCase();
            this.form.gstin = gstin;
            this.gstLookupInfo = null;

            if (gstin.length !== 15) {
                this.gstFeedback = {
                    type: 'error',
                    message: 'Please enter a valid 15-character GSTIN (e.g. 27AACCN0053F1ZW).',
                    note: ''
                };
                return;
            }

            const gstinPattern = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[0-9A-Z]{1}[0-9A-Z]{1}$/;
            if (!gstinPattern.test(gstin)) {
                this.gstFeedback = {
                    type: 'error',
                    message: 'Invalid GSTIN format. Expected 2-digit state code, 10-character PAN, entity code, and checksum.',
                    note: ''
                };
                this.applyExtractedGstinFields(null, gstin);
                return;
            }

            this.lastAutoGstin = gstin;
            this.searchingGst = true;
            this.gstFeedback = { type: '', message: '', note: '' };
            this.applyExtractedGstinFields(null, gstin);

            try {
                const response = await fetch(`{{ route('masters.customers.gst-lookup') }}?gstin=${encodeURIComponent(gstin)}`);
                const data = await response.json();

                if (data.extracted) {
                    this.applyExtractedGstinFields(data.extracted, gstin);
                }

                if (data.success && data.party) {
                    if (data.party.trade_name) this.form.trade_name = String(data.party.trade_name).trim();
                    if (data.party.name) this.form.name = String(data.party.name).trim();
                    this.lastAutoGstin = gstin;
                    if (data.party.pan) this.form.pan = String(data.party.pan).trim();
                    if (data.party.state) this.form.state = String(data.party.state).trim();
                    if (data.party.pincode) this.form.pincode = String(data.party.pincode).trim();
                    if (data.party.address || data.party.address_line_1) {
                        this.form.address = String(data.party.address || data.party.address_line_1).trim();
                    }

                    this.applyGstAddresses(data.party);
                    this.gstLookupAppliedKey = [
                        gstin,
                        data.party.address_line_1 || '',
                        data.party.pincode || '',
                        (data.party.addresses || []).length
                    ].join('::');

                    this.gstLookupInfo = {
                        legal_name: data.party.legal_name || '',
                        trade_name: data.party.trade_name || '',
                        status: data.party.status || '',
                        taxpayer_type: data.party.taxpayer_type || '',
                        constitution_of_business: data.party.constitution_of_business || '',
                        registration_date: data.party.registration_date || '',
                        cancellation_date: data.party.cancellation_date || '',
                        state_jurisdiction: data.party.state_jurisdiction || '',
                        center_jurisdiction: data.party.center_jurisdiction || '',
                        nature_of_business: Array.isArray(data.party.nature_of_business)
                            ? data.party.nature_of_business.join(', ')
                            : (data.party.nature_of_business || ''),
                    };

                    const statusText = data.party.status ? ` (Status: ${data.party.status})` : '';
                    const isInactive = !!data.party.is_inactive
                        || (data.party.status && !['active', 'provisional'].includes(String(data.party.status).toLowerCase()));
                    this.gstFeedback = {
                        type: isInactive ? 'warning' : 'success',
                        message: (data.message || 'GSTIN verified successfully from official records.') + statusText,
                        note: isInactive
                            ? `Warning: GST registration status is reported as ${data.party.status}. Please review carefully before saving.`
                            : 'Details updated from GST records. Phone, email, party type, credit, and bank details remain manual.'
                    };
                } else {
                    this.gstLookupInfo = null;
                    const note = data.is_configured === false
                        ? 'Add GST_API_KEY and GST_API_SECRET to .env, then run php artisan config:clear. PAN and State were filled from the GSTIN where possible.'
                        : 'PAN and State were filled from the GSTIN where possible (structural extraction, not provider-verified). You can enter remaining details manually.';
                    this.gstFeedback = {
                        type: 'error',
                        message: data.message || 'GSTIN details not found or verification failed.',
                        note: note
                    };
                }
            } catch (err) {
                this.gstLookupInfo = null;
                this.applyExtractedGstinFields(null, gstin);
                this.gstFeedback = {
                    type: 'error',
                    message: 'An error occurred while connecting to GST lookup service.',
                    note: 'PAN and State were filled from the GSTIN where possible. Please verify network connection and try again.'
                };
            } finally {
                this.searchingGst = false;
            }
        }
    };
}

function stateDropdown(addr, statesList, onStateSelected) {
    return {
        open: false,
        search: '',
        highlightedIndex: 0,
        get states() {
            return Array.isArray(statesList) ? statesList : [];
        },
        get filteredStates() {
            const list = this.states;
            if (!this.search || !this.search.trim()) {
                return list;
            }
            const q = this.search.toLowerCase().trim();
            return list.filter(s =>
                (s.name && s.name.toLowerCase().includes(q)) ||
                (s.code && String(s.code).toLowerCase().includes(q))
            );
        },
        get selectedLabel() {
            if (!addr.state) return '';
            const raw = String(addr.state).trim().toLowerCase();
            const match = this.states.find(s =>
                (s.name && s.name.toLowerCase() === raw) ||
                (s.code && String(s.code).toLowerCase() === raw)
            );
            if (match) {
                if (String(addr.state).trim() === String(match.code)) {
                    addr.state = match.name;
                }
                return `${match.name} (${match.code})`;
            }
            return addr.state;
        },
        toggle() {
            if (this.open) {
                this.close();
            } else {
                this.open = true;
                this.search = '';
                this.highlightedIndex = 0;
                this.$nextTick(() => {
                    this.$refs.searchInput?.focus();
                });
            }
        },
        close() {
            this.open = false;
            this.search = '';
        },
        clearState() {
            addr.state = '';
            addr.city = '';
            if (typeof onStateSelected === 'function') {
                onStateSelected();
            }
            this.close();
            this.$nextTick(() => {
                this.$refs.triggerButton?.focus();
            });
        },
        selectState(st) {
            const isChanged = (addr.state !== st.name);
            addr.state = st.name;
            if (isChanged) {
                addr.city = '';
            }
            if (typeof onStateSelected === 'function') {
                onStateSelected();
            }
            this.close();
            this.$nextTick(() => {
                this.$refs.triggerButton?.focus();
            });
        },
        onKeyDown(event) {
            const list = this.filteredStates;
            if (!this.open) {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Enter') {
                    event.preventDefault();
                    this.toggle();
                }
                return;
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (list.length > 0) {
                    this.highlightedIndex = (this.highlightedIndex + 1) % list.length;
                    this.scrollActiveIntoView();
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (list.length > 0) {
                    this.highlightedIndex = (this.highlightedIndex - 1 + list.length) % list.length;
                    this.scrollActiveIntoView();
                }
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (list.length > 0 && list[this.highlightedIndex]) {
                    this.selectState(list[this.highlightedIndex]);
                }
            } else if (event.key === 'Escape') {
                event.preventDefault();
                this.close();
                this.$nextTick(() => {
                    this.$refs.triggerButton?.focus();
                });
            }
        },
        scrollActiveIntoView() {
            this.$nextTick(() => {
                const el = this.$refs.optionsList?.querySelector(`[data-index="${this.highlightedIndex}"]`);
                if (el) {
                    el.scrollIntoView({ block: 'nearest' });
                }
            });
        }
    };
}

function cityDropdown(addr, stateCities) {
    return {
        open: false,
        search: '',
        highlightedIndex: 0,
        get isDisabled() {
            return !addr.state;
        },
        get cities() {
            if (!addr.state || !stateCities) return [];
            return stateCities[addr.state] || [];
        },
        get filteredCities() {
            const list = this.cities;
            if (!this.search || !this.search.trim()) {
                return list;
            }
            const q = this.search.toLowerCase().trim();
            return list.filter(c => c.toLowerCase().includes(q));
        },
        toggle() {
            if (this.isDisabled) return;
            if (this.open) {
                this.close();
            } else {
                this.open = true;
                this.search = '';
                this.highlightedIndex = 0;
                this.$nextTick(() => {
                    this.$refs.searchInput?.focus();
                });
            }
        },
        close() {
            this.open = false;
            this.search = '';
        },
        clearCity() {
            addr.city = '';
            this.close();
            this.$nextTick(() => {
                this.$refs.triggerButton?.focus();
            });
        },
        selectCity(cityName) {
            addr.city = cityName;
            this.close();
            this.$nextTick(() => {
                this.$refs.triggerButton?.focus();
            });
        },
        onKeyDown(event) {
            const list = this.filteredCities;
            if (!this.open) {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Enter') {
                    event.preventDefault();
                    this.toggle();
                }
                return;
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (list.length > 0) {
                    this.highlightedIndex = (this.highlightedIndex + 1) % list.length;
                    this.scrollActiveIntoView();
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (list.length > 0) {
                    this.highlightedIndex = (this.highlightedIndex - 1 + list.length) % list.length;
                    this.scrollActiveIntoView();
                }
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (list.length > 0 && list[this.highlightedIndex]) {
                    this.selectCity(list[this.highlightedIndex]);
                }
            } else if (event.key === 'Escape') {
                event.preventDefault();
                this.close();
                this.$nextTick(() => {
                    this.$refs.triggerButton?.focus();
                });
            }
        },
        scrollActiveIntoView() {
            this.$nextTick(() => {
                const el = this.$refs.optionsList?.querySelector(`[data-index="${this.highlightedIndex}"]`);
                if (el) {
                    el.scrollIntoView({ block: 'nearest' });
                }
            });
        }
    };
}
</script>
@endpush
@endsection
