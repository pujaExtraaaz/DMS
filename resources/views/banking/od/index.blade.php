@extends('layouts.dms')
@section('title', 'OD Limit & Interest Calculation')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                OD Limit &amp; Interest Calculation
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Maintain Overdraft (OD) limits, annual interest rates, and monitor real-time utilization for company bank accounts.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.button variant="secondary" :href="route('od.report', ['account_number' => $selectedAccountNo])" class="text-xs">
                <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                View OD Report
            </x-ui.button>
            <button type="button"
                    @click="$dispatch('open-add-transaction-modal')"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Record Transaction
            </button>
        </div>
    </div>

    <!-- Multi-Company Bank Account Notice if none found -->
    @if($companyBankAccounts->isEmpty())
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <div>
                <p class="font-semibold">No Bank Accounts Found in Company Profile</p>
                <p class="text-xs text-amber-700 mt-0.5">
                    To link OD limits, please first add your bank accounts in
                    <a href="{{ route('organization.company-profile') }}" class="underline font-bold text-amber-900 hover:text-amber-950">Setup &rarr; Company Profile &rarr; Banking &amp; Payments</a>.
                </p>
            </div>
        </div>
    @endif

    <div x-data="odConfigComponent({{ Js::from($companyBankAccounts) }}, {{ Js::from($activeConfig) }}, '{{ $selectedAccountNo }}')"
         class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT COLUMN: OD Configuration Form -->
        <div class="lg:col-span-7 space-y-6">
            <x-ui.card>
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">OD Limit &amp; Interest Configuration</h2>
                        <p class="text-xs text-slate-500">Configure overdraft facility and annual interest percentage for the selected bank account.</p>
                    </div>
                    <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {{ $company?->name ?? 'Company' }}
                    </span>
                </div>

                <form method="POST" action="{{ route('od.store') }}" class="space-y-4">
                    @csrf
                    @if($activeConfig)
                        <input type="hidden" name="id" value="{{ $activeConfig->id }}">
                    @endif

                    <!-- Account Selection Dropdown linked to Company Profile -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Bank Account <span class="text-rose-500">*</span>
                        </label>
                        <select name="account_number"
                                x-model="selectedAccountNo"
                                @change="onAccountChange()"
                                required
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                            <option value="">-- Select Company Bank Account --</option>
                            @foreach($companyBankAccounts as $b)
                                <option value="{{ $b['account_number'] }}" @selected($selectedAccountNo === $b['account_number'])>
                                    {{ $b['bank_name'] }} - {{ $b['account_number'] }} {{ $b['ifsc'] ? '('.$b['ifsc'].')' : '' }}
                                </option>
                            @endforeach
                            @foreach($configuredOdAccounts as $od)
                                @if(! $companyBankAccounts->firstWhere('account_number', $od->account_number))
                                    <option value="{{ $od->account_number }}" @selected($selectedAccountNo === $od->account_number)>
                                        {{ $od->bank_name }} - {{ $od->account_number }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Accounts sourced from Company Profile &rarr; Banking &amp; Payments.
                        </p>
                    </div>

                    <!-- Auto-filled Bank Name & Account Number -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Bank Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   name="bank_name"
                                   x-model="bankName"
                                   required
                                   readonly
                                   class="block w-full rounded-lg border-gray-300 bg-slate-100 text-sm font-medium text-slate-800">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Account Number <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   x-model="selectedAccountNo"
                                   readonly
                                   class="block w-full rounded-lg border-gray-300 bg-slate-100 text-sm font-mono font-medium text-slate-800">
                            <input type="hidden" name="ifsc_code" x-model="ifsc">
                        </div>
                    </div>

                    <!-- OD Limit & Interest Rate -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                OD Limit (₹) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative rounded-lg shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-slate-500 sm:text-sm">₹</span>
                                </div>
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       name="od_limit"
                                       x-model="odLimit"
                                       required
                                       placeholder="1000000"
                                       class="block w-full rounded-lg border-gray-300 pl-7 text-sm font-semibold text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <span class="text-[11px] text-slate-500 mt-1 block">Maximum approved overdraft amount.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Interest Rate (% p.a.) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative rounded-lg shadow-sm">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       max="100"
                                       name="interest_rate"
                                       x-model="interestRate"
                                       required
                                       placeholder="12.00"
                                       class="block w-full rounded-lg border-gray-300 pr-8 text-sm font-semibold text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-slate-500 sm:text-sm">%</span>
                                </div>
                            </div>
                            <span class="text-[11px] text-slate-500 mt-1 block">Annual percentage (e.g. 10.5%, 11.25%, 12%).</span>
                        </div>
                    </div>

                    <!-- Calculation Method & Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Calculation Method <span class="text-rose-500">*</span>
                            </label>
                            <select name="interest_calculation_method"
                                    x-model="calculationMethod"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="daily_simple">Daily (Simple Daily: Utilized &times; Rate / 365 / 100)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Status <span class="text-rose-500">*</span>
                            </label>
                            <select name="status"
                                    x-model="status"
                                    required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Effective Dates -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Effective From <span class="text-rose-500">*</span>
                            </label>
                            <input type="date"
                                   name="effective_from"
                                   x-model="effectiveFrom"
                                   required
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Effective To (Optional)
                            </label>
                            <input type="date"
                                   name="effective_to"
                                   x-model="effectiveTo"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Optional Initial Utilized OD Balance -->
                    @if(!$activeConfig)
                        <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Initial OD Utilized Opening Balance (Optional)
                            </label>
                            <div class="relative rounded-lg shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-slate-500 sm:text-sm">₹</span>
                                </div>
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       name="opening_utilized"
                                       placeholder="0.00"
                                       class="block w-full rounded-lg border-gray-300 pl-7 text-sm font-medium text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <span class="text-[11px] text-slate-500 mt-1 block">
                                If the account already has an existing utilized overdraft on the effective date.
                            </span>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Notes / Remarks (Optional)
                        </label>
                        <input type="text"
                               name="notes"
                               x-model="notes"
                               placeholder="e.g. Sanction letter ref #SBI-OD-2026, reviewed annually"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <x-ui.button type="submit" variant="primary">
                            {{ $activeConfig ? 'Update OD Configuration' : 'Save OD Configuration' }}
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>

        <!-- RIGHT COLUMN: CURRENT OD POSITION & METRICS -->
        <div class="lg:col-span-5 space-y-6">
            <!-- CURRENT OD POSITION Card -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 block">Real-Time Status</span>
                        <h3 class="text-base font-bold text-slate-900">CURRENT OD POSITION</h3>
                    </div>
                    <span class="px-2.5 py-1 text-xs font-bold rounded-lg"
                          :class="isExceeded ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300'">
                        <span x-text="isExceeded ? 'LIMIT EXCEEDED' : 'WITHIN LIMIT'"></span>
                    </span>
                </div>

                <!-- OD Limit Exceeded Alert Banner -->
                <div x-show="isExceeded"
                     x-transition
                     class="rounded-xl border border-rose-300 bg-rose-50 p-3.5 text-rose-900 space-y-1">
                    <div class="flex items-center gap-2 font-bold text-xs uppercase tracking-wider text-rose-700">
                        <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <span>OD LIMIT EXCEEDED</span>
                    </div>
                    <p class="text-xs font-semibold text-rose-800">
                        Warning: OD LIMIT EXCEEDED BY ₹<span x-text="formatCurrency(exceededAmount)"></span>
                    </p>
                </div>

                <!-- Position Metrics Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">OD Limit</span>
                        <span class="text-lg font-bold font-mono text-slate-900 block mt-0.5">
                            ₹<span x-text="formatCurrency(odLimit)"></span>
                        </span>
                    </div>

                    <div class="rounded-xl p-3 border"
                         :class="isExceeded ? 'bg-rose-50/70 border-rose-200' : 'bg-slate-50 border-slate-200/80'">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Utilized OD</span>
                        <span class="text-lg font-bold font-mono block mt-0.5"
                              :class="isExceeded ? 'text-rose-700' : 'text-indigo-700'">
                            ₹<span x-text="formatCurrency(currentUtilized)"></span>
                        </span>
                    </div>

                    <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Available OD</span>
                        <span class="text-lg font-bold font-mono text-emerald-700 block mt-0.5">
                            ₹<span x-text="formatCurrency(availableOd)"></span>
                        </span>
                    </div>

                    <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Utilization %</span>
                        <span class="text-lg font-bold font-mono block mt-0.5"
                              :class="isExceeded ? 'text-rose-700' : 'text-slate-900'">
                            <span x-text="utilizationPct.toFixed(1)"></span>%
                        </span>
                    </div>
                </div>

                <!-- Interest & Rate Details -->
                <div class="rounded-xl bg-indigo-50/50 border border-indigo-100 p-3.5 space-y-2 text-xs">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Configured Interest Rate:</span>
                        <span class="font-bold text-indigo-900 font-mono"><span x-text="interestRate"></span>% p.a.</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Estimated Daily Interest:</span>
                        <span class="font-bold text-indigo-900 font-mono">₹<span x-text="formatCurrency(estimatedDailyInterest)"></span> / day</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600 pt-1 border-t border-indigo-100">
                        <span>Formula:</span>
                        <span class="text-[11px] font-mono text-slate-500">Utilized &times; Rate &divide; 365 &divide; 100</span>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div>
                    <div class="flex justify-between text-xs mb-1 font-medium">
                        <span class="text-slate-500">Facility Utilization</span>
                        <span class="font-bold" :class="isExceeded ? 'text-rose-600' : 'text-indigo-600'">
                            <span x-text="utilizationPct.toFixed(1)"></span>%
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-300"
                             :class="isExceeded ? 'bg-rose-600' : 'bg-indigo-600'"
                             :style="`width: ${Math.min(100, Math.max(0, utilizationPct))}%`"></div>
                    </div>
                </div>
            </div>

            <!-- Recent Bank Activity for this account -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Recent Bank Transactions</h4>
                    <a :href="`{{ route('od.report') }}?account_number=${selectedAccountNo}`"
                       class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        Full Statement &rarr;
                    </a>
                </div>

                @if($recentTransactions->isEmpty())
                    <p class="text-xs text-slate-500 py-4 text-center">
                        No transactions recorded yet for this account.<br>
                        Click "Record Transaction" above to log withdrawals or deposits.
                    </p>
                @else
                    <div class="divide-y divide-slate-100 text-xs">
                        @foreach($recentTransactions as $tx)
                            <div class="py-2 flex items-center justify-between">
                                <div>
                                    <span class="font-semibold text-slate-800 block">{{ $tx->description }}</span>
                                    <span class="text-[10px] text-slate-400">
                                        {{ $tx->transaction_date->format('d M Y') }} &bull; {{ $tx->transaction_no }}
                                    </span>
                                </div>
                                <div class="text-right font-mono">
                                    @if($tx->debit > 0)
                                        <span class="text-rose-600 font-bold block">+₹{{ number_format($tx->debit, 2) }}</span>
                                        <span class="text-[9px] uppercase tracking-wider text-slate-400">Debit (OD &uarr;)</span>
                                    @else
                                        <span class="text-emerald-600 font-bold block">-₹{{ number_format($tx->credit, 2) }}</span>
                                        <span class="text-[9px] uppercase tracking-wider text-slate-400">Credit (OD &darr;)</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- LIST OF CONFIGURED OD ACCOUNTS TABLE -->
    <x-ui.card class="space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Company OD Accounts Master</h3>
                <p class="text-xs text-slate-500">Summary of all overdraft facilities configured for {{ $company?->name }}.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-slate-200">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Bank &amp; Account</th>
                        <th class="px-4 py-3 text-right">OD Limit</th>
                        <th class="px-4 py-3 text-right">Interest Rate</th>
                        <th class="px-4 py-3 text-right">Utilized OD</th>
                        <th class="px-4 py-3 text-right">Available OD</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Effective Period</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($configuredOdAccounts as $acc)
                        @php
                            $utilized = $acc->getCurrentUtilization();
                            $available = $acc->getAvailableLimit();
                            $exceeded = $acc->isExceeded();
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-4 py-3">
                                <span class="font-bold text-slate-900 block">{{ $acc->bank_name }}</span>
                                <span class="text-xs font-mono text-slate-500">{{ $acc->account_number }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-800">
                                ₹{{ number_format($acc->od_limit, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-indigo-700 font-bold">
                                {{ number_format($acc->interest_rate, 2) }}%
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold {{ $exceeded ? 'text-rose-600' : 'text-slate-800' }}">
                                ₹{{ number_format($utilized, 2) }}
                                @if($exceeded)
                                    <span class="block text-[10px] text-rose-500 font-semibold">Exceeded</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600">
                                ₹{{ number_format($available, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $acc->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $acc->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-slate-500">
                                {{ $acc->effective_from->format('d/m/Y') }}
                                @if($acc->effective_to)
                                    &rarr; {{ $acc->effective_to->format('d/m/Y') }}
                                @else
                                    &rarr; Ongoing
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <a href="{{ route('od.index', ['account_number' => $acc->account_number]) }}"
                                   class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                    Edit
                                </a>
                                <a href="{{ route('od.report', ['account_number' => $acc->account_number]) }}"
                                   class="text-xs font-semibold text-emerald-600 hover:text-emerald-800">
                                    Report
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-xs text-slate-500">
                                No OD accounts configured yet. Select a company bank account above to setup an OD limit.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <!-- RECORD TRANSACTION MODAL -->
    <div x-data="{ open: false }"
         x-on:open-add-transaction-modal.window="open = true"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="open = false"
             class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4 border border-slate-100">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Record Bank Transaction</h3>
                    <p class="text-xs text-slate-500">Log a withdrawal or deposit affecting the OD utilized balance.</p>
                </div>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('od.transactions.store') }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 uppercase mb-1">Bank Account</label>
                    <select name="account_number" required class="block w-full rounded-lg border-gray-300 text-sm">
                        @foreach($companyBankAccounts as $b)
                            <option value="{{ $b['account_number'] }}" @selected($selectedAccountNo === $b['account_number'])>
                                {{ $b['bank_name'] }} - {{ $b['account_number'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 uppercase mb-1">Date</label>
                        <input type="date" name="transaction_date" value="{{ now()->toDateString() }}" required class="block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 uppercase mb-1">Txn / Cheque No.</label>
                        <input type="text" name="transaction_no" placeholder="TXN-1001" class="block w-full rounded-lg border-gray-300 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 uppercase mb-1">Transaction Type</label>
                        <select name="transaction_type" required class="block w-full rounded-lg border-gray-300 text-sm">
                            <optgroup label="Increases OD (Debit / Withdrawal)">
                                <option value="withdrawal">Withdrawal</option>
                                <option value="payment">Payment to Supplier</option>
                                <option value="bank_charges">Bank Charges</option>
                                <option value="debit">Other Debit</option>
                            </optgroup>
                            <optgroup label="Decreases OD (Credit / Deposit)">
                                <option value="deposit">Deposit / Funds In</option>
                                <option value="receipt">Customer Receipt</option>
                                <option value="credit">Other Credit</option>
                            </optgroup>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 uppercase mb-1">Amount (₹)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" required placeholder="50000.00" class="block w-full rounded-lg border-gray-300 text-sm font-semibold">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase mb-1">Description / Particulars</label>
                    <input type="text" name="description" required placeholder="e.g. Vendor raw material RTGS payout" class="block w-full rounded-lg border-gray-300 text-sm">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 font-medium">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-500 shadow-sm">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function odConfigComponent(companyBankAccounts, activeConfig, initialAccountNo) {
    return {
        companyBankAccounts: companyBankAccounts || [],
        activeConfig: activeConfig || null,
        selectedAccountNo: initialAccountNo || '',
        bankName: '',
        ifsc: '',
        odLimit: 0,
        interestRate: 0,
        calculationMethod: 'daily_simple',
        status: 'active',
        effectiveFrom: '{{ now()->startOfMonth()->toDateString() }}',
        effectiveTo: '',
        notes: '',
        currentUtilized: {{ $activeConfig ? $activeConfig->getCurrentUtilization() : 0 }},

        init() {
            this.syncDetailsFromAccount();
            if (this.activeConfig) {
                this.odLimit = parseFloat(this.activeConfig.od_limit) || 0;
                this.interestRate = parseFloat(this.activeConfig.interest_rate) || 0;
                this.calculationMethod = this.activeConfig.interest_calculation_method || 'daily_simple';
                this.status = this.activeConfig.status || 'active';
                this.effectiveFrom = this.activeConfig.effective_from || this.effectiveFrom;
                this.effectiveTo = this.activeConfig.effective_to || '';
                this.notes = this.activeConfig.notes || '';
            }
        },

        onAccountChange() {
            this.syncDetailsFromAccount();
            if (!this.selectedAccountNo) return;

            // Fetch configured OD details via AJAX
            fetch(`{{ route('od.bank-details') }}?account_number=${encodeURIComponent(this.selectedAccountNo)}`)
                .then(r => r.json())
                .then(data => {
                    this.bankName = data.bank_name || this.bankName;
                    this.ifsc = data.ifsc || this.ifsc;
                    if (data.has_od) {
                        this.odLimit = data.od_limit;
                        this.interestRate = data.interest_rate;
                        this.calculationMethod = data.method;
                        this.effectiveFrom = data.effective_from;
                        this.effectiveTo = data.effective_to || '';
                        this.status = data.status;
                        this.currentUtilized = data.current_utilized;
                    } else {
                        this.odLimit = 0;
                        this.interestRate = 0;
                        this.currentUtilized = 0;
                    }
                })
                .catch(err => console.error(err));
        },

        syncDetailsFromAccount() {
            const found = this.companyBankAccounts.find(b => String(b.account_number) === String(this.selectedAccountNo));
            if (found) {
                this.bankName = found.bank_name || '';
                this.ifsc = found.ifsc || '';
            }
        },

        get availableOd() {
            const lim = parseFloat(this.odLimit) || 0;
            const ut = parseFloat(this.currentUtilized) || 0;
            return Math.max(0, lim - ut);
        },

        get isExceeded() {
            const lim = parseFloat(this.odLimit) || 0;
            const ut = parseFloat(this.currentUtilized) || 0;
            return ut > lim;
        },

        get exceededAmount() {
            const lim = parseFloat(this.odLimit) || 0;
            const ut = parseFloat(this.currentUtilized) || 0;
            return Math.max(0, ut - lim);
        },

        get utilizationPct() {
            const lim = parseFloat(this.odLimit) || 0;
            const ut = parseFloat(this.currentUtilized) || 0;
            if (lim <= 0) return 0;
            return (ut / lim) * 100;
        },

        get estimatedDailyInterest() {
            const ut = parseFloat(this.currentUtilized) || 0;
            const rate = parseFloat(this.interestRate) || 0;
            if (ut <= 0 || rate <= 0) return 0;
            return (ut * rate) / 365 / 100;
        },

        formatCurrency(num) {
            const val = parseFloat(num) || 0;
            return val.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };
}
</script>
@endpush
@endsection

