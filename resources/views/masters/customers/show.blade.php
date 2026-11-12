@extends('layouts.dms')
@section('title', 'Party Details · ' . $customer->name)

@php
    $can = fn (...$perms) => auth()->user()?->hasAnyRole(['super-admin', 'client-admin']) || auth()->user()?->hasAnyPermission($perms);
@endphp

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    {{-- Header --}}
    <x-ui.page-header :title="$customer->name">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('masters.customers.index')">
                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Parties
            </x-ui.button>
            @if($can('customers.edit', 'masters.edit', 'masters.manage'))
                <x-ui.button variant="primary" :href="route('masters.customers.edit', $customer)">
                    <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Edit Party
                </x-ui.button>
            @endif
        </x-slot>
    </x-ui.page-header>

    {{-- Party Summary Card --}}
    <x-ui.card>
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white flex items-center justify-center font-bold text-2xl shadow-md shrink-0">
                {{ strtoupper(substr($customer->name, 0, 2)) }}
            </div>
            <div class="space-y-1.5 flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-xl font-bold text-slate-900 truncate">{{ $customer->name }}</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                        {{ $customer->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                        {{ $customer->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $customer->party_type_key === 'sundry_creditors' ? 'bg-amber-100 text-amber-800' : ($customer->party_type_key === 'both' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800') }}">
                        {{ $customer->party_type_label }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                    <span class="font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-700">Code: {{ $customer->code ?: '—' }}</span>
                    @if($customer->trade_name && $customer->trade_name !== $customer->name)
                        <span>Trade Name: <strong class="text-slate-700 font-medium">{{ $customer->trade_name }}</strong></span>
                    @endif
                    @if($customer->businessGroup)
                        <span class="inline-flex items-center gap-1 text-purple-700 font-medium">
                            <span class="font-emoji">{{ $customer->businessGroup->logo_emoji ?: '🏢' }}</span>
                            {{ $customer->businessGroup->name }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- 1. Basic & Identity Details --}}
        <x-ui.card title="Basic Details">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Party Name</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Party Code</dt>
                    <dd class="mt-0.5 font-mono font-medium text-slate-900">{{ $customer->code ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Party Type</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->party_type_label }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Category / Customer Type</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->customerType?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Business Group</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        @if($customer->businessGroup)
                            <span class="inline-flex items-center gap-1 text-purple-700">
                                <span class="font-emoji">{{ $customer->businessGroup->logo_emoji ?: '🏢' }}</span>
                                {{ $customer->businessGroup->name }}
                            </span>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Status</dt>
                    <dd class="mt-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $customer->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $customer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
                @if($customer->shipping_name)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold text-slate-500 tracking-wider">Shipping Name</dt>
                        <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->shipping_name }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>

        {{-- 2. Contact Details --}}
        <x-ui.card title="Contact Details">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Phone / Mobile</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        @if($customer->phone)
                            <a href="tel:{{ $customer->phone }}" class="text-indigo-600 hover:underline">{{ $customer->phone }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Email Address</dt>
                    <dd class="mt-0.5 font-medium text-slate-900 truncate">
                        @if($customer->email)
                            <a href="mailto:{{ $customer->email }}" class="text-indigo-600 hover:underline">{{ $customer->email }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Date of Opening</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->opened_on?->format('d M Y') ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Reference By</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->reference_by ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Udyam Number</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->udyam_number ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">GEM Authorization Details</dt>
                    <dd class="mt-2 space-y-3">
                        @forelse($customer->gemAuthorizations as $authorization)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="text-sm font-semibold text-slate-900">{{ $authorization->brand?->name ?: 'OEM' }}</p>
                                <dl class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <dt class="text-slate-500">Product registered on GEM</dt>
                                        <dd class="font-medium text-slate-800">{{ $authorization->gem_registered ? 'Yes' : 'No' }}</dd>
                                    </div>
                                    @if($authorization->gem_registered)
                                        <div>
                                            <dt class="text-slate-500">Party authorized on GEM</dt>
                                            <dd class="font-medium text-slate-800">{{ $authorization->gem_authorized ? 'Yes' : 'No' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-slate-500">GEM Authorization Number</dt>
                                            <dd class="font-medium text-slate-800">{{ $authorization->authorization_number ?: '—' }}</dd>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <dt class="text-slate-500">GEM Star Rating Photo/Document</dt>
                                            <dd class="mt-1 space-y-1">
                                                @forelse($authorization->documents as $document)
                                                    <div class="flex flex-wrap items-center gap-3">
                                                        <span class="font-medium text-slate-800">{{ $document->original_name }}</span>
                                                        <a href="{{ route('masters.customers.gem-documents.show', [$customer, $document]) }}" target="_blank" rel="noopener" class="text-indigo-700 hover:underline">View</a>
                                                        <a href="{{ route('masters.customers.gem-documents.show', [$customer, $document]) }}?download=1" class="text-indigo-700 hover:underline">Download</a>
                                                    </div>
                                                @empty
                                                    <span>—</span>
                                                @endforelse
                                            </dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @empty
                            <span class="text-sm font-medium text-slate-900">—</span>
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Website</dt>
                    <dd class="mt-0.5 font-medium text-slate-900 truncate">
                        @if($customer->website)
                            <a href="{{ $customer->website }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">{{ $customer->website }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Location Link</dt>
                    <dd class="mt-0.5 font-medium text-slate-900 truncate">
                        @if($customer->location_link)
                            <a href="{{ $customer->location_link }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">{{ $customer->location_link }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Contact Person</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        {{ $customer->defaultAddress()?->contact_person ?: ($customer->contacts->first()?->name ?: '—') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Contact Phone</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        {{ $customer->defaultAddress()?->contact_phone ?: ($customer->contacts->first()?->phone ?: '—') }}
                    </dd>
                </div>
            </dl>

            {{-- Multiple contacts if any --}}
            @if($customer->contacts->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Registered Contacts</h4>
                    <div class="space-y-2">
                        @foreach($customer->contacts as $contact)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 text-xs">
                                <div>
                                    <span class="font-semibold text-slate-800">{{ $contact->name }}</span>
                                    @if($contact->role)<span class="text-slate-500">({{ $contact->role }})</span>@endif
                                    @if($contact->is_primary)<span class="ml-1 text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-semibold">Primary</span>@endif
                                </div>
                                <div class="text-slate-600 space-x-2">
                                    @if($contact->phone)<span>{{ $contact->phone }}</span>@endif
                                    @if($contact->email)<span>{{ $contact->email }}</span>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </x-ui.card>

        {{-- 3. Tax & Regulatory Details --}}
        <x-ui.card title="Tax &amp; Compliance Details">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">GSTIN</dt>
                    <dd class="mt-0.5 font-mono font-medium text-slate-900">{{ $customer->gstin ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">PAN</dt>
                    <dd class="mt-0.5 font-mono font-medium text-slate-900">{{ $customer->pan ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">State</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->state ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">PIN Code</dt>
                    <dd class="mt-0.5 font-mono font-medium text-slate-900">{{ $customer->pincode ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Area</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->area?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Route</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->route?->name ?: '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        {{-- 4. Business & Credit Policy --}}
        <x-ui.card title="Business &amp; Credit Details">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Credit Limit</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        {{ $customer->credit_limit ? '₹ ' . number_format($customer->credit_limit, 2) : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Credit Days</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        {{ $customer->credit_days ? $customer->credit_days . ' Days' : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Credit Period Basis</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        {{ $customer->creditPeriodBasisLabel() }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Interest Rate</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        {{ $customer->interest_rate ? $customer->interest_rate . '%' : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Credit Status</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        @php
                            $creditTone = match ($customer->credit_status) {
                                'hold' => 'bg-amber-100 text-amber-800',
                                'blocked', 'frozen', 'restricted' => 'bg-red-100 text-red-800',
                                default => 'bg-emerald-100 text-emerald-800',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $creditTone }}">
                            {{ $customer->creditStatusLabel() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Payment Terms</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->payment_terms ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Sales Representative</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->salesperson?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Sales Manager</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $customer->salesManager?->name ?: '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>
    </div>

    {{-- Target Performance & Status Tracking Section --}}
    @php
        $target = $customer->activeSalesTarget() ?? $customer->latestSalesTarget();
        $targetAmount = (float) ($target?->target_amount ?? ($customer->target_amount ?? 0));
        $achievement = $target?->achievement;
        $achievedAmount = (float) ($achievement?->achieved_amount ?? 0);
        $shortfall = $targetAmount > 0 ? max(0, $targetAmount - $achievedAmount) : 0;
        $excess = $targetAmount > 0 ? max(0, $achievedAmount - $targetAmount) : 0;
        $percent = $targetAmount > 0 ? round(($achievedAmount / $targetAmount) * 100, 1) : 0;
        $status = $target ? $target->progress_status : ($targetAmount > 0 ? 'Not Started' : 'No Target');
        $remDays = $target ? $target->remaining_days_label : null;

        $contributingInvoices = collect();
        $contributingCreditNotes = collect();
        if ($target) {
            $startDate = $target->starts_on->toDateString();
            $endDate = $target->ends_on->toDateString();
            $contributingInvoices = \App\Domains\Sales\Models\Invoice::where('customer_id', $customer->id)
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->whereNotIn('status', ['cancelled', 'draft', 'void'])
                ->latest('invoice_date')
                ->get();
            $contributingCreditNotes = \App\Domains\Payment\Models\CreditNote::where('customer_id', $customer->id)
                ->whereBetween('credit_note_date', [$startDate, $endDate])
                ->where('status', 'posted')
                ->latest('credit_note_date')
                ->get();
        }
    @endphp

    <x-ui.card title="Target Performance &amp; Status Tracking">
        <div class="space-y-6">
            @if($targetAmount > 0)
                {{-- Top Stat Widgets --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned Target</span>
                        <div class="mt-1 text-xl font-bold text-slate-900">₹ {{ number_format($targetAmount, 2) }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            {{ $target?->starts_on?->format('d M Y') ?? '—' }} to {{ $target?->ends_on?->format('d M Y') ?? '—' }}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/60">
                        <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Total Achieved Sales</span>
                        <div class="mt-1 text-xl font-bold text-emerald-800">₹ {{ number_format($achievedAmount, 2) }}</div>
                        <div class="text-[11px] text-emerald-600 mt-0.5">
                            {{ $contributingInvoices->count() }} invoice(s) net of credit notes
                        </div>
                    </div>

                    <div class="p-4 rounded-xl border border-slate-200 {{ $shortfall > 0 ? 'bg-amber-50/60 border-amber-200' : 'bg-slate-50' }}">
                        <span class="text-xs font-semibold {{ $shortfall > 0 ? 'text-amber-800' : 'text-slate-500' }} uppercase tracking-wider">
                            Remaining Shortfall
                        </span>
                        <div class="mt-1 text-xl font-bold {{ $shortfall > 0 ? 'text-amber-900' : 'text-emerald-700' }}">
                            ₹ {{ number_format($shortfall, 2) }}
                        </div>
                        <div class="text-[11px] {{ $excess > 0 ? 'text-emerald-700 font-semibold' : 'text-slate-500' }} mt-0.5">
                            @if($excess > 0)
                                Excess: +₹ {{ number_format($excess, 2) }}
                            @else
                                Formula: Target &minus; Achieved
                            @endif
                        </div>
                    </div>

                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Target Status</span>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold
                                @if($status === 'Achieved') bg-emerald-100 text-emerald-800 border border-emerald-300
                                @elseif($status === 'In Progress') bg-blue-100 text-blue-800 border border-blue-300
                                @elseif($status === 'Period Expired') bg-rose-100 text-rose-800 border border-rose-300
                                @else bg-slate-100 text-slate-700 border border-slate-300 @endif">
                                <span class="w-2 h-2 rounded-full
                                    @if($status === 'Achieved') bg-emerald-500
                                    @elseif($status === 'In Progress') bg-blue-500
                                    @elseif($status === 'Period Expired') bg-rose-500
                                    @else bg-slate-400 @endif"></span>
                                {{ $status }}
                            </span>
                        </div>
                        <div class="text-[11px] text-indigo-700 font-medium mt-1">
                            {{ $remDays ?: 'Period Active' }}
                        </div>
                    </div>
                </div>

                {{-- Visual Progress Bar --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">Progress against assigned target</span>
                        <span class="font-bold text-slate-900">{{ $percent }}% achieved</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden border border-slate-200">
                        <div class="h-3 rounded-full transition-all duration-500
                            @if($percent >= 100) bg-emerald-500
                            @elseif($percent > 0) bg-indigo-600
                            @else bg-slate-300 @endif"
                            style="width: {{ min(100, $percent) }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-0.5">
                        <span>Target Period: <strong class="text-slate-700">{{ $target?->starts_on?->format('d-m-Y') }}</strong> to <strong class="text-slate-700">{{ $target?->ends_on?->format('d-m-Y') }}</strong></span>
                        <span>₹ {{ number_format($achievedAmount, 2) }} of ₹ {{ number_format($targetAmount, 2) }}</span>
                    </div>
                </div>

                {{-- Invoice-wise Contributing Breakdown Table --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Contributing Sales Invoices (Invoice-Wise Breakdown)</h4>
                        <span class="text-xs text-slate-500">{{ $contributingInvoices->count() }} invoice(s)</span>
                    </div>

                    @if($contributingInvoices->isNotEmpty())
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                                <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                                    <tr>
                                        <th class="px-4 py-2.5">Invoice #</th>
                                        <th class="px-4 py-2.5">Invoice Date</th>
                                        <th class="px-4 py-2.5 text-right">Taxable Amount (₹)</th>
                                        <th class="px-4 py-2.5 text-right">Tax (₹)</th>
                                        <th class="px-4 py-2.5 text-right">Total Amount (₹)</th>
                                        <th class="px-4 py-2.5 text-center">Status</th>
                                        <th class="px-4 py-2.5 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach($contributingInvoices as $inv)
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-4 py-2 font-mono font-medium text-indigo-600">
                                                <a href="{{ route('sales.invoices.show', $inv) }}" class="hover:underline">
                                                    {{ $inv->invoice_no }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-2 text-slate-600">{{ $inv->invoice_date?->format('d-m-Y') }}</td>
                                            <td class="px-4 py-2 text-right text-slate-700">₹ {{ number_format($inv->subtotal, 2) }}</td>
                                            <td class="px-4 py-2 text-right text-slate-500">₹ {{ number_format(($inv->cgst_amount ?? 0) + ($inv->sgst_amount ?? 0) + ($inv->igst_amount ?? 0), 2) }}</td>
                                            <td class="px-4 py-2 text-right font-bold text-slate-900">₹ {{ number_format($inv->grand_total, 2) }}</td>
                                            <td class="px-4 py-2 text-center">
                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    {{ ucfirst($inv->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-right">
                                                <a href="{{ route('sales.invoices.show', $inv) }}" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-slate-300 p-4 text-center text-xs text-slate-500">
                            No posted sales invoices recorded for this party within the active target period ({{ $target?->starts_on?->format('d-m-Y') }} to {{ $target?->ends_on?->format('d-m-Y') }}).
                        </div>
                    @endif

                    @if($contributingCreditNotes->isNotEmpty())
                        <div class="mt-3 pt-3 border-t border-slate-100 space-y-2">
                            <h5 class="text-xs font-semibold text-rose-700 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                                Deducted Credit Notes &amp; Sales Returns
                            </h5>
                            <div class="overflow-x-auto rounded-lg border border-rose-200 bg-rose-50/30">
                                <table class="min-w-full divide-y divide-rose-100 text-left text-xs">
                                    <thead class="bg-rose-50 text-rose-800 uppercase font-semibold">
                                        <tr>
                                            <th class="px-4 py-2">Credit Note #</th>
                                            <th class="px-4 py-2">Date</th>
                                            <th class="px-4 py-2 text-right">Deducted Amount (₹)</th>
                                            <th class="px-4 py-2 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-rose-100 bg-white">
                                        @foreach($contributingCreditNotes as $cn)
                                            <tr>
                                                <td class="px-4 py-2 font-mono text-rose-700">{{ $cn->credit_note_no }}</td>
                                                <td class="px-4 py-2 text-slate-600">{{ $cn->credit_note_date?->format('d-m-Y') }}</td>
                                                <td class="px-4 py-2 text-right font-bold text-rose-700">&minus; ₹ {{ number_format($cn->grand_total, 2) }}</td>
                                                <td class="px-4 py-2 text-center text-[10px] text-slate-600">{{ ucfirst($cn->status) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Historical Targets (if more than 1) --}}
                @if($customer->salesTargets->count() > 1)
                    <div class="pt-4 border-t border-slate-200 space-y-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Historical Target Periods</h4>
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                                <thead class="bg-slate-50 text-slate-600 font-semibold">
                                    <tr>
                                        <th class="px-4 py-2">Period</th>
                                        <th class="px-4 py-2 text-right">Target Amount (₹)</th>
                                        <th class="px-4 py-2 text-right">Achieved (₹)</th>
                                        <th class="px-4 py-2 text-right">Shortfall (₹)</th>
                                        <th class="px-4 py-2 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach($customer->salesTargets as $histTarget)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-2 text-slate-700 font-medium">
                                                {{ $histTarget->starts_on?->format('d-m-Y') }} to {{ $histTarget->ends_on?->format('d-m-Y') }}
                                            </td>
                                            <td class="px-4 py-2 text-right font-semibold text-slate-900">
                                                ₹ {{ number_format($histTarget->target_amount, 2) }}
                                            </td>
                                            <td class="px-4 py-2 text-right font-semibold text-emerald-700">
                                                ₹ {{ number_format($histTarget->achievement?->achieved_amount ?? 0, 2) }}
                                            </td>
                                            <td class="px-4 py-2 text-right text-slate-600">
                                                ₹ {{ number_format($histTarget->achievement?->pending_amount ?? 0, 2) }}
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold
                                                    @if($histTarget->progress_status === 'Achieved') bg-emerald-100 text-emerald-800
                                                    @elseif($histTarget->progress_status === 'In Progress') bg-blue-100 text-blue-800
                                                    @elseif($histTarget->progress_status === 'Period Expired') bg-rose-100 text-rose-800
                                                    @else bg-slate-100 text-slate-700 @endif">
                                                    {{ $histTarget->progress_status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @else
                <div class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-xs text-slate-500">
                    No sales target is currently assigned to this party.
                    @if($can('customers.edit', 'masters.edit', 'masters.manage'))
                        <a href="{{ route('masters.customers.edit', $customer) }}" class="text-indigo-600 font-semibold hover:underline ml-1">
                            Assign Target in Edit Party &rarr;
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </x-ui.card>

    {{-- 5. Addresses & Locations Section --}}
    <x-ui.card title="Addresses &amp; Locations">
        @if($customer->addresses->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($customer->addresses as $addr)
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-sm text-slate-900">{{ $addr->label ?: 'Address #' . $addr->id }}</span>
                            <div class="flex items-center gap-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-200 text-slate-700">
                                    {{ ucfirst($addr->type ?: 'both') }}
                                </span>
                                @if($addr->is_default_billing)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-blue-800">
                                        Default Billing
                                    </span>
                                @endif
                                @if($addr->is_default_delivery)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">
                                        Default Delivery
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-xs text-slate-600 space-y-0.5">
                            @if($addr->contact_person)
                                <p><strong class="text-slate-700">Contact:</strong> {{ $addr->contact_person }} @if($addr->contact_phone)({{ $addr->contact_phone }})@endif</p>
                            @endif
                            <p class="text-slate-800">{{ $addr->full_address }}</p>
                            @if($addr->gstin)
                                <p class="font-mono text-slate-500">GSTIN: {{ $addr->gstin }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-sm text-slate-500">
                <p><strong class="text-slate-700">Address:</strong> {{ $customer->address ?: '—' }}</p>
                <p><strong class="text-slate-700">State / PIN:</strong> {{ $customer->state ?: '—' }} {{ $customer->pincode ? '(' . $customer->pincode . ')' : '' }}</p>
            </div>
        @endif
    </x-ui.card>

    {{-- 6. Bank Accounts Section --}}
    <x-ui.card title="Bank Accounts">
        @if($customer->bankAccounts->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($customer->bankAccounts as $acc)
                    @php
                        $num = (string) $acc->account_number;
                        $masked = strlen($num) > 4
                            ? str_repeat('•', max(4, strlen($num) - 4)) . ' ' . substr($num, -4)
                            : ($num ?: '—');
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-sm text-slate-900">{{ $acc->bank_name ?: 'Bank Account' }}</span>
                            @if($acc->is_primary)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">
                                    Primary Account
                                </span>
                            @endif
                        </div>

                        <dl class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <dt class="text-slate-500">Holder Name</dt>
                                <dd class="font-medium text-slate-800">{{ $acc->account_holder_name ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Account Number</dt>
                                <dd class="font-mono font-medium text-slate-800">{{ $masked }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">IFSC Code</dt>
                                <dd class="font-mono font-medium text-slate-800">{{ $acc->ifsc_code ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Branch</dt>
                                <dd class="font-medium text-slate-800">{{ $acc->branch_name ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-400 py-2">— No bank accounts registered for this party —</p>
        @endif
    </x-ui.card>

    {{-- 7. Additional Information & System Audit --}}
    <x-ui.card title="Additional Information">
        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div class="sm:col-span-3">
                <dt class="text-xs font-semibold text-slate-500 tracking-wider">Notes / Remarks</dt>
                <dd class="mt-0.5 font-medium text-slate-800 whitespace-pre-line">{{ $customer->credit_notes ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500 tracking-wider">Created Date</dt>
                <dd class="mt-0.5 text-xs text-slate-700">{{ $customer->created_at?->format('d M Y, h:i A') ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500 tracking-wider">Last Updated</dt>
                <dd class="mt-0.5 text-xs text-slate-700">{{ $customer->updated_at?->format('d M Y, h:i A') ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500 tracking-wider">Company / Branch</dt>
                <dd class="mt-0.5 text-xs text-slate-700">
                    {{ $customer->company?->name ?: '—' }}
                    @if($customer->branch) / {{ $customer->branch->name }} @endif
                </dd>
            </div>
        </dl>
    </x-ui.card>
</div>
@endsection

