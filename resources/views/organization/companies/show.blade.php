@extends('layouts.dms')
@section('title', $company->name)

@section('content')
<div class="space-y-6">
    <x-ui.page-header :title="$company->name">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('organization.companies.index')">
                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back
            </x-ui.button>
            <x-ui.button variant="secondary" :href="route('organization.companies.edit', $company)">
                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Edit
            </x-ui.button>
            @if($company->is_default)
                <button type="button" disabled class="inline-flex items-center gap-1 font-medium rounded-lg opacity-80 cursor-default bg-indigo-50 text-indigo-700 border border-indigo-200 px-3 py-2 text-xs shadow-xs" title="Currently selected default company">
                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Default Company
                </button>
            @elseif($company->is_active)
                <form method="POST" action="{{ route('organization.companies.set-default', $company) }}" class="inline" onsubmit="return confirm('Set {{ addslashes($company->name) }} as the default company?')">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">
                        Set as Default
                    </x-ui.button>
                </form>
            @endif
        </x-slot>
    </x-ui.page-header>

    {{-- Company Overview Card --}}
    <x-ui.card>
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 pb-6 border-b border-slate-100">
            <div class="w-24 h-24 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-center p-2 shrink-0 shadow-xs overflow-hidden">
                @if($company->hasLogo() && $company->logo_url)
                    <img src="{{ $company->logo_url }}" alt="{{ $company->name }} Logo" class="max-w-full max-h-full object-contain">
                @else
                    <div class="w-full h-full rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-2xl tracking-wider">
                        {{ strtoupper(substr($company->name, 0, 2)) }}
                    </div>
                @endif
            </div>

            <div class="space-y-2 flex-1">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-900">{{ $company->name }}</h2>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $company->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                        {{ $company->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($company->is_default)
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-xs">
                            Default Company
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-slate-600">
                    <div>
                        <span class="text-xs uppercase font-semibold text-slate-400">Code:</span>
                        <span class="font-mono font-medium text-slate-800 ml-1">{{ $company->code }}</span>
                    </div>
                    <div>
                        <span class="text-xs uppercase font-semibold text-slate-400">Legal Name:</span>
                        <span class="font-medium text-slate-800 ml-1">{{ $company->legal_name ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-xs uppercase font-semibold text-slate-400">Business Group:</span>
                        <span class="font-medium text-slate-800 ml-1">{{ $company->businessGroup?->name ?: '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Linked Records Metric Counters --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-6">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase text-slate-500">Branches</span>
                    <p class="text-lg font-bold text-slate-800">{{ $company->branches->count() }}</p>
                </div>
                <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase text-slate-500">Warehouses</span>
                    <p class="text-lg font-bold text-slate-800">{{ $company->warehouses->count() }}</p>
                </div>
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase text-slate-500">Financial Years</span>
                    <p class="text-lg font-bold text-slate-800">{{ $company->financialYears->count() }}</p>
                </div>
                <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Statutory Registrations Card --}}
    <x-ui.card>
        <h3 class="text-sm font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-100">Statutory Registrations</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">GSTIN</span>
                <span class="font-mono font-medium text-slate-800">{{ $company->gstin ?: '—' }}</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">PAN</span>
                <span class="font-mono font-medium text-slate-800">{{ $company->pan ?: '—' }}</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">TAN</span>
                <span class="font-mono font-medium text-slate-800">{{ $company->tan ?: '—' }}</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">CIN</span>
                <span class="font-mono font-medium text-slate-800">{{ $company->cin ?: '—' }}</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">Udyam Registration No</span>
                <span class="font-mono font-medium text-slate-800">{{ $company->udyam_registration_no ?: '—' }}</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">MSME Category</span>
                <span class="font-medium text-slate-800">{{ filled($company->msme_category) && $company->msme_category !== 'none' ? ucfirst($company->msme_category) : '—' }}</span>
            </div>
            @if(filled($company->msme_registration_no))
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">MSME Registration No</span>
                <span class="font-mono font-medium text-slate-800">{{ $company->msme_registration_no }}</span>
            </div>
            @endif
        </div>
    </x-ui.card>

    {{-- Contact Details Card --}}
    <x-ui.card>
        <h3 class="text-sm font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-100">Contact Details</h3>
        @php
            $contacts = $company->contacts;
            $hasAnyContact = collect($contacts)->contains(function($c) {
                return filled($c['phone'] ?? null) || filled($c['email'] ?? null) || filled($c['website'] ?? null) || filled($c['address'] ?? null) || filled($c['state'] ?? null) || filled($c['pincode'] ?? null);
            });
        @endphp

        @if($hasAnyContact)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($contacts as $index => $contact)
                    @php
                        $contactFilled = filled($contact['phone'] ?? null) || filled($contact['email'] ?? null) || filled($contact['website'] ?? null) || filled($contact['address'] ?? null) || filled($contact['state'] ?? null) || filled($contact['pincode'] ?? null);
                    @endphp
                    @if($contactFilled)
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">Contact #{{ $index + 1 }}</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Phone</span>
                                    <span class="font-medium text-slate-800">{{ $contact['phone'] ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Email</span>
                                    @if(filled($contact['email'] ?? null))
                                        <a href="mailto:{{ $contact['email'] }}" class="font-medium text-indigo-600 hover:underline">{{ $contact['email'] }}</a>
                                    @else
                                        <span class="font-medium text-slate-800">—</span>
                                    @endif
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Website</span>
                                    @if(filled($contact['website'] ?? null))
                                        <a href="{{ str_starts_with($contact['website'], 'http') ? $contact['website'] : 'https://' . $contact['website'] }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:underline">{{ $contact['website'] }}</a>
                                    @else
                                        <span class="font-medium text-slate-800">—</span>
                                    @endif
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">State &amp; Pincode</span>
                                    <span class="font-medium text-slate-800">{{ ($contact['state'] ?: '') . ($contact['state'] && $contact['pincode'] ? ' — ' : '') . ($contact['pincode'] ?: '') ?: '—' }}</span>
                                </div>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-slate-500 block">Address</span>
                                <p class="font-medium text-slate-800 whitespace-pre-line text-sm mt-0.5">{{ $contact['address'] ?: '—' }}</p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-500 italic">No contact details provided.</p>
        @endif
    </x-ui.card>

    {{-- Banking & Payments Card --}}
    <x-ui.card>
        <h3 class="text-sm font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-100">Banking &amp; Payment Accounts</h3>
        @php
            $bankAccounts = $company->bank_accounts;
            $hasAnyBank = collect($bankAccounts)->contains(function($b) {
                return filled($b['bank_name'] ?? null)
                    || filled($b['bank_account_no'] ?? null)
                    || filled($b['bank_ifsc'] ?? null)
                    || filled($b['upi_id'] ?? null)
                    || filled($b['swift_code'] ?? null)
                    || filled($b['branch'] ?? null)
                    || filled($b['bank_branch'] ?? null)
                    || filled($b['bsr_code'] ?? null)
                    || filled($b['client_code'] ?? null);
            });
        @endphp

        @if($hasAnyBank)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($bankAccounts as $index => $bank)
                    @php
                        $bankFilled = filled($bank['bank_name'] ?? null)
                            || filled($bank['bank_account_no'] ?? null)
                            || filled($bank['bank_ifsc'] ?? null)
                            || filled($bank['upi_id'] ?? null)
                            || filled($bank['swift_code'] ?? null)
                            || filled($bank['branch'] ?? null)
                            || filled($bank['bank_branch'] ?? null)
                            || filled($bank['bsr_code'] ?? null)
                            || filled($bank['client_code'] ?? null);
                    @endphp
                    @if($bankFilled)
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Account #{{ $index + 1 }}</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Bank Name</span>
                                    <span class="font-medium text-slate-800">{{ $bank['bank_name'] ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Account Number</span>
                                    <span class="font-mono font-medium text-slate-800">{{ $bank['bank_account_no'] ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">IFSC Code</span>
                                    <span class="font-mono font-medium text-slate-800">{{ $bank['bank_ifsc'] ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">UPI ID</span>
                                    <span class="font-mono font-medium text-slate-800">{{ $bank['upi_id'] ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">SWIFT Code</span>
                                    <span class="font-mono font-medium text-slate-800">{{ $bank['swift_code'] ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Branch</span>
                                    <span class="font-medium text-slate-800">{{ ($bank['branch'] ?? $bank['bank_branch'] ?? null) ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">BSR Code</span>
                                    <span class="font-mono font-medium text-slate-800">{{ $bank['bsr_code'] ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Client Code</span>
                                    <span class="font-mono font-medium text-slate-800">{{ $bank['client_code'] ?: '—' }}</span>
                                </div>
                                @if(isset($bank['od_limit']) && (float)$bank['od_limit'] > 0)
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">OD Limit</span>
                                    <span class="font-medium text-slate-800">₹{{ number_format((float)$bank['od_limit'], 2) }}</span>
                                </div>
                                @endif
                                @if(isset($bank['interest_rate']) && (float)$bank['interest_rate'] > 0)
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Interest Rate</span>
                                    <span class="font-medium text-slate-800">{{ number_format((float)$bank['interest_rate'], 2) }}%</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-500 italic">No bank accounts configured.</p>
        @endif
    </x-ui.card>

    {{-- Terms & Conditions Card --}}
    <x-ui.card>
        <h3 class="text-sm font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-100">Terms &amp; Preferences</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block mb-1">Purchase Terms &amp; Conditions</span>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 whitespace-pre-line leading-relaxed min-h-[5rem]">
                    {{ $company->purchase_terms_and_conditions ?: '—' }}
                </div>
            </div>
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block mb-1">Selling Terms &amp; Conditions</span>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 whitespace-pre-line leading-relaxed min-h-[5rem]">
                    {{ $company->selling_terms_and_conditions ?: '—' }}
                </div>
            </div>
        </div>
    </x-ui.card>
</div>
@endsection

