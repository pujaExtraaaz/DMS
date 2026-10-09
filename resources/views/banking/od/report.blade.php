@extends('layouts.dms')
@section('title', 'OD Report')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition">Dashboard</a>
                <span>&rsaquo;</span>
                <span class="text-slate-600">10 · Reports</span>
                <span>&rsaquo;</span>
                <span class="text-indigo-600 font-bold">OD Report</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                Overdraft (OD) Report &amp; Account Statement
            </h1>
        </div>

        <!-- Export Actions & Links -->
        <div class="flex items-center gap-2">
            @if($reportData)
                <div class="inline-flex rounded-lg shadow-sm">
                    <a href="{{ route('reports.od', array_filter(['account_number' => $selectedAccountNo, 'export' => 'csv'])) }}"
                       class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold rounded-l-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition"
                       title="Export CSV">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        CSV
                    </a>
                    <a href="{{ route('reports.od', array_filter(['account_number' => $selectedAccountNo, 'export' => 'excel'])) }}"
                       class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold border-t border-b border-r border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition"
                       title="Export Excel">
                        Excel
                    </a>
                    <a href="{{ route('reports.od', array_filter(['account_number' => $selectedAccountNo, 'export' => 'pdf'])) }}"
                       class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold rounded-r-lg border-t border-b border-r border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition"
                       title="Export PDF">
                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.056-.867-1.809-1.85-2.09a1.992 1.992 0 00-.535-.074H3.75v5.85h.585c1.077 0 1.956-.566 2.385-1.686z" />
                        </svg>
                        PDF
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- FILTER SECTION (SINGLE DROPDOWN: Bank Account No.) -->
    <x-ui.card class="p-4" x-data="{ loading: false }">
        <form method="GET" action="{{ route('reports.od') }}" x-on:submit="loading = true">
            <div class="max-w-md">
                <label for="account_number" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Bank Account No. <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <select id="account_number"
                            name="account_number"
                            x-on:change="loading = true; $el.form.submit()"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white pr-10">
                        <option value="">-- Select Bank Account --</option>
                        @foreach($companyBankAccounts as $b)
                            @php
                                $accNo = $b['account_number'];
                                $bankName = $b['bank_name'] ?? 'Bank Account';
                            @endphp
                            <option value="{{ $accNo }}" @selected($selectedAccountNo === $accNo)>
                                {{ $accNo }} ({{ $bankName }})
                            </option>
                        @endforeach
                        @foreach($odAccounts as $od)
                            @if(! $companyBankAccounts->firstWhere('account_number', $od->account_number))
                                <option value="{{ $od->account_number }}" @selected($selectedAccountNo === $od->account_number)>
                                    {{ $od->account_number }} ({{ $od->bank_name }})
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <!-- Loading Spinner inside select container -->
                    <div x-show="loading" x-cloak class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                        <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Animated loading indicator message -->
                <div x-show="loading" x-cloak class="flex items-center gap-2 text-xs font-semibold text-indigo-600 mt-2">
                    <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Fetching bank transactions and OD statement...</span>
                </div>
            </div>
        </form>
    </x-ui.card>

    @if($odAccount && $selectedAccountNo)
        <!-- DISPLAY ACCOUNT SUMMARY -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
            <!-- Header with Bank Name & Account Number -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 block">Bank Account &amp; Overdraft Summary</span>
                    <h2 class="text-xl font-bold text-slate-900 mt-0.5">
                        {{ $selectedBank['bank_name'] ?? $odAccount->bank_name }}
                    </h2>
                    <p class="text-xs font-mono text-slate-500 mt-0.5">
                        Bank Account Number: <span class="font-bold text-slate-800">{{ $selectedAccountNo }}</span>
                        @if(!empty($selectedBank['ifsc']) || !empty($odAccount->ifsc_code))
                            &bull; IFSC: <span class="font-semibold text-slate-700">{{ $selectedBank['ifsc'] ?? $odAccount->ifsc_code }}</span>
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    @if($hasOdConfig && $reportData)
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $reportData['isExceeded'] ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300' }}">
                            {{ $reportData['isExceeded'] ? 'OD LIMIT EXCEEDED' : 'WITHIN APPROVED LIMIT' }}
                        </span>
                    @elseif(! $hasOdConfig)
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">
                            NO OD CONFIGURED
                        </span>
                    @endif
                </div>
            </div>

            @if($hasOdConfig)
                <!-- Core OD Metrics Summary Panel -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
                    <!-- 1. Configured OD Limit (₹) -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Configured OD Limit</span>
                        <span class="text-base font-bold font-mono text-slate-900 block mt-1">
                            ₹{{ number_format($configuredOdLimit, 2) }}
                        </span>
                    </div>

                    <!-- 2. Annual Interest Rate (%) -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Annual Interest Rate</span>
                        <span class="text-base font-bold font-mono text-indigo-700 block mt-1">
                            {{ number_format($annualInterestRate, 2) }}% p.a.
                        </span>
                    </div>

                    <!-- 3. Estimated Annual Interest (₹) -->
                    <div class="p-3.5 rounded-xl bg-indigo-50/60 border border-indigo-200">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-900 block">Estimated Annual Interest</span>
                        <span class="text-base font-bold font-mono text-indigo-700 block mt-1">
                            ₹{{ number_format($estimatedAnnualInterest, 2) }}
                        </span>
                    </div>

                    <!-- 4. Estimated Monthly Interest (₹) -->
                    <div class="p-3.5 rounded-xl bg-indigo-50/60 border border-indigo-200">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-900 block">Estimated Monthly Interest</span>
                        <span class="text-base font-bold font-mono text-indigo-700 block mt-1">
                            ₹{{ number_format($estimatedMonthlyInterest, 2) }}
                        </span>
                    </div>

                    <!-- 5. Current Running / Utilized Balance (₹) -->
                    <div class="p-3.5 rounded-xl border {{ ($reportData['isExceeded'] ?? false) ? 'bg-rose-50 border-rose-200' : 'bg-slate-50 border-slate-200' }}">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Running Balance</span>
                        <span class="text-base font-bold font-mono block mt-1 {{ ($reportData['isExceeded'] ?? false) ? 'text-rose-700' : 'text-slate-900' }}">
                            ₹{{ number_format($reportData['currentUtilized'] ?? 0, 2) }}
                        </span>
                    </div>

                    <!-- 6. Available OD Limit (₹) -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Available OD Limit</span>
                        <span class="text-base font-bold font-mono text-emerald-700 block mt-1">
                            ₹{{ number_format($reportData['availableOd'] ?? 0, 2) }}
                        </span>
                    </div>
                </div>

                <!-- OD Limit Exceeded Warning Banner if applicable -->
                @if($reportData && $reportData['isExceeded'])
                    <div class="rounded-xl border border-rose-300 bg-rose-50 p-4 text-rose-900 flex items-start gap-3">
                        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <div>
                            <p class="font-bold text-sm text-rose-900">OD LIMIT EXCEEDED</p>
                            <p class="text-xs font-semibold text-rose-800 mt-0.5">
                                Warning: OD LIMIT EXCEEDED BY ₹{{ number_format($reportData['exceededAmount'], 2) }}
                            </p>
                            <p class="text-[11px] text-rose-700 mt-0.5">
                                Transactions exceeding the approved limit remain active for complete accounting accuracy and interest calculation.
                            </p>
                        </div>
                    </div>
                @endif
            @else
                <!-- No OD Configuration Banner -->
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <div>
                        <p class="font-bold text-sm text-amber-900">No OD Configuration Found for this Bank Account</p>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Bank Name: <strong>{{ $selectedBank['bank_name'] ?? $odAccount->bank_name }}</strong> &bull;
                            Bank Account Number: <strong class="font-mono">{{ $selectedAccountNo }}</strong>
                        </p>
                        <p class="text-xs text-amber-700 mt-1">
                            This account does not have an approved OD limit or annual interest rate configured in Company Profile.
                            You can set up an approved OD limit and interest rate under
                            <a href="{{ route('organization.company-profile') }}" class="underline font-semibold hover:text-amber-950">Setup &rarr; Company Profile</a>.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        @if($reportData)
            @php
                $rowsToDisplay = $paginatedRows ?: ($reportData['displayRows']->isNotEmpty() ? $reportData['displayRows'] : $reportData['rows']);
            @endphp

            <!-- TRANSACTION TABLE (ALL REQUIRED COLUMNS, LATEST TRANSACTIONS FIRST) -->
            <x-ui.card class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Account Transaction Statement &amp; Running Balance</h3>
                        <p class="text-xs text-slate-500">
                            Transactions ordered with latest transactions first. Running balances calculated in chronological order.
                        </p>
                    </div>
                    <div class="text-xs font-medium text-slate-500">
                        Total Transactions: <span class="font-bold text-slate-800">{{ $reportData['displayRows']->count() }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs divide-y divide-slate-200">
                        <thead class="bg-slate-50 uppercase font-semibold text-slate-600">
                            <tr>
                                <th scope="col" class="px-3 py-3 text-center w-12">Sr. No.</th>
                                <th scope="col" class="px-3 py-3 text-left">Transaction Date</th>
                                <th scope="col" class="px-3 py-3 text-left">Voucher / Reference Number</th>
                                <th scope="col" class="px-3 py-3 text-left">Transaction Type</th>
                                <th scope="col" class="px-3 py-3 text-left">Particulars / Description</th>
                                <th scope="col" class="px-3 py-3 text-right">Debit (₹)</th>
                                <th scope="col" class="px-3 py-3 text-right">Credit (₹)</th>
                                <th scope="col" class="px-3 py-3 text-right">Running Balance (₹)</th>
                                <th scope="col" class="px-3 py-3 text-right">OD Limit (₹)</th>
                                <th scope="col" class="px-3 py-3 text-right">Available OD Limit (₹)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($rowsToDisplay as $row)
                                <tr class="hover:bg-slate-50/70 transition {{ ($row['is_opening'] ?? false) ? 'bg-amber-50/40 font-medium' : '' }}">
                                    <!-- 1. Sr. No. -->
                                    <td class="px-3 py-2.5 text-center text-slate-500 font-mono">
                                        {{ $row['sr_no'] ?? $loop->iteration }}
                                    </td>

                                    <!-- 2. Transaction Date -->
                                    <td class="px-3 py-2.5 whitespace-nowrap text-slate-700 font-medium">
                                        {{ $row['date'] instanceof \Carbon\Carbon ? $row['date']->format('d/m/Y') : $row['date'] }}
                                    </td>

                                    <!-- 3. Voucher / Reference Number -->
                                    <td class="px-3 py-2.5 whitespace-nowrap font-mono font-semibold text-slate-800">
                                        {{ $row['transaction_no'] }}
                                    </td>

                                    <!-- 4. Transaction Type -->
                                    <td class="px-3 py-2.5 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider {{ in_array($row['transaction_type'], ['debit', 'withdrawal', 'payment', 'bank_charges']) ? 'bg-rose-50 text-rose-700 border border-rose-200' : (($row['is_opening'] ?? false) ? 'bg-amber-100 text-amber-800' : 'bg-emerald-50 text-emerald-700 border border-emerald-200') }}">
                                            {{ str_replace('_', ' ', $row['transaction_type']) }}
                                        </span>
                                    </td>

                                    <!-- 5. Particulars / Description -->
                                    <td class="px-3 py-2.5 max-w-xs truncate text-slate-800" title="{{ $row['description'] }}">
                                        {{ $row['description'] }}
                                    </td>

                                    <!-- 6. Debit (₹) -->
                                    <td class="px-3 py-2.5 text-right font-mono whitespace-nowrap text-rose-600 font-semibold">
                                        {{ $row['debit'] > 0 ? '₹' . number_format($row['debit'], 2) : '-' }}
                                    </td>

                                    <!-- 7. Credit (₹) -->
                                    <td class="px-3 py-2.5 text-right font-mono whitespace-nowrap text-emerald-600 font-semibold">
                                        {{ $row['credit'] > 0 ? '₹' . number_format($row['credit'], 2) : '-' }}
                                    </td>

                                    <!-- 8. Running Balance (₹) -->
                                    <td class="px-3 py-2.5 text-right font-mono whitespace-nowrap font-bold {{ ($row['is_exceeded'] ?? false) ? 'text-rose-700' : 'text-slate-900' }}">
                                        ₹{{ number_format($row['running_balance'] ?? $row['od_utilized'], 2) }}
                                        @if($row['is_exceeded'] ?? false)
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-600 ml-0.5" title="Limit Exceeded"></span>
                                        @endif
                                    </td>

                                    <!-- 9. OD Limit (₹) -->
                                    <td class="px-3 py-2.5 text-right font-mono whitespace-nowrap text-slate-700">
                                        {{ ($row['od_limit'] ?? 0) > 0 ? '₹' . number_format($row['od_limit'], 2) : '-' }}
                                    </td>

                                    <!-- 10. Available OD Limit (₹) -->
                                    <td class="px-3 py-2.5 text-right font-mono whitespace-nowrap font-semibold text-emerald-700">
                                        {{ ($row['od_limit'] ?? 0) > 0 ? '₹' . number_format($row['available_od'], 2) : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-12 text-center text-slate-500">
                                        <div class="max-w-sm mx-auto space-y-2">
                                            <svg class="w-10 h-10 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                            <p class="font-semibold text-slate-700">No transactions found for this account</p>
                                            <p class="text-xs text-slate-400">
                                                No transactions have been recorded for this bank account.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($reportData['displayRows']->isNotEmpty())
                            <tfoot class="bg-slate-100/80 font-bold text-slate-900 border-t-2 border-slate-300">
                                <tr>
                                    <td colspan="5" class="px-3 py-3 text-right uppercase text-[11px] tracking-wider">
                                        Period Totals &amp; Closing Position:
                                    </td>
                                    <td class="px-3 py-3 text-right font-mono text-rose-700">
                                        ₹{{ number_format($reportData['totalDebit'], 2) }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-mono text-emerald-700">
                                        ₹{{ number_format($reportData['totalCredit'], 2) }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-mono {{ ($reportData['isExceeded'] ?? false) ? 'text-rose-700' : 'text-slate-900' }}">
                                        ₹{{ number_format($reportData['currentUtilized'], 2) }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-mono text-slate-700">
                                        {{ $configuredOdLimit > 0 ? '₹' . number_format($configuredOdLimit, 2) : '-' }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-mono text-emerald-700">
                                        {{ $configuredOdLimit > 0 ? '₹' . number_format($reportData['availableOd'], 2) : '-' }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                @if($paginatedRows && $paginatedRows->hasPages())
                    <div class="pt-4 border-t border-slate-100">
                        {{ $paginatedRows->links() }}
                    </div>
                @endif
            </x-ui.card>
        @endif
    @else
        <!-- INITIAL EMPTY STATE (NO ACCOUNT SELECTED) -->
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center text-slate-500 space-y-3">
            <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="font-semibold text-slate-700">Please Select a Bank Account</p>
            <p class="text-xs text-slate-400">Choose a company bank account from the dropdown above to automatically view its Overdraft statement, running balances, and transactions.</p>
        </div>
    @endif
</div>
@endsection
