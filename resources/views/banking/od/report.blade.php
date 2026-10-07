@extends('layouts.dms')
@section('title', 'OD Report')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                <a href="{{ route('od.index') }}" class="hover:text-indigo-600 transition">OD Limit &amp; Interest</a>
                <span>&rsaquo;</span>
                <span class="text-indigo-600">Reports</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                Overdraft (OD) Report &amp; Interest Statement
            </h1>
        </div>

        <!-- Export Actions & Links -->
        <div class="flex items-center gap-2">
            <x-ui.button variant="secondary" :href="route('od.index', ['account_number' => $selectedAccountNo])" class="text-xs">
                OD Configuration &rarr;
            </x-ui.button>

            @if($reportData)
                <div class="inline-flex rounded-lg shadow-sm">
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                       class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold rounded-l-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        CSV
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}"
                       class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold border-t border-b border-r border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">
                        Excel
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}"
                       class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold rounded-r-lg border-t border-b border-r border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">
                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.056-.867-1.809-1.85-2.09a1.992 1.992 0 00-.535-.074H3.75v5.85h.585c1.077 0 1.956-.566 2.385-1.686z" />
                        </svg>
                        PDF
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- FILTER TOOLBAR (Account Dropdown, Date Range, Presets, Search) -->
    <x-ui.card class="p-4">
        <form method="GET" action="{{ route('od.report') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <!-- 1. ACCOUNT NO. DROPDOWN (Displays ALL company bank accounts) -->
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Account No. <span class="text-rose-500">*</span>
                    </label>
                    <select name="account_number"
                            onchange="this.form.submit()"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                        <option value="">-- Select Bank Account --</option>
                        @foreach($companyBankAccounts as $b)
                            <option value="{{ $b['account_number'] }}" @selected($selectedAccountNo === $b['account_number'])>
                                {{ $b['bank_name'] }} - {{ $b['account_number'] }}
                            </option>
                        @endforeach
                        @foreach($odAccounts as $od)
                            @if(! $companyBankAccounts->firstWhere('account_number', $od->account_number))
                                <option value="{{ $od->account_number }}" @selected($selectedAccountNo === $od->account_number)>
                                    {{ $od->bank_name }} - {{ $od->account_number }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <!-- 2. FROM DATE -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">From Date</label>
                    <input type="date"
                           name="date_from"
                           value="{{ $dateFrom }}"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- 3. TO DATE -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">To Date</label>
                    <input type="date"
                           name="date_to"
                           value="{{ $dateTo }}"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- 4. SEARCH -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Search</label>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Txn / Particulars..."
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- 5. FILTER BUTTONS -->
                <div class="md:col-span-2 flex items-center gap-2">
                    <button type="submit"
                            class="flex-1 inline-flex justify-center items-center rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                        Generate
                    </button>
                    <a href="{{ route('od.report', ['account_number' => $selectedAccountNo]) }}"
                       class="inline-flex justify-center items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition"
                       title="Reset Filters">
                        Reset
                    </a>
                </div>
            </div>

            <!-- PRESET QUICK BUTTONS (Current Month, Previous Month, Financial Year) -->
            <div class="flex items-center gap-2 pt-2 border-t border-slate-100 text-xs">
                <span class="text-slate-400 font-medium">Quick Periods:</span>
                <a href="{{ route('od.report', ['account_number' => $selectedAccountNo, 'preset' => 'fy']) }}"
                   class="px-2.5 py-1 rounded-md border font-semibold transition {{ $datePreset === 'fy' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                    Financial Year
                </a>
                <a href="{{ route('od.report', ['account_number' => $selectedAccountNo, 'preset' => 'current_month']) }}"
                   class="px-2.5 py-1 rounded-md border font-semibold transition {{ $datePreset === 'current_month' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                    Current Month
                </a>
                <a href="{{ route('od.report', ['account_number' => $selectedAccountNo, 'preset' => 'previous_month']) }}"
                   class="px-2.5 py-1 rounded-md border font-semibold transition {{ $datePreset === 'previous_month' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                    Previous Month
                </a>
            </div>
        </form>
    </x-ui.card>

    @if($reportData)
        <!-- OD REPORT HEADER / SUMMARY -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
            <!-- Account Info Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 block">Statement of Overdraft Account</span>
                    <h2 class="text-xl font-bold text-slate-900 mt-0.5">
                        {{ $reportData['odAccount']->bank_name }}
                    </h2>
                    <p class="text-xs font-mono text-slate-500 mt-0.5">
                        Account No: <span class="font-bold text-slate-800">{{ $reportData['odAccount']->account_number }}</span>
                        @if($reportData['odAccount']->ifsc_code)
                            &bull; IFSC: {{ $reportData['odAccount']->ifsc_code }}
                        @endif
                        &bull; Period: {{ \Carbon\Carbon::parse($reportData['fromDate'])->format('d/m/Y') }} &rarr; {{ \Carbon\Carbon::parse($reportData['toDate'])->format('d/m/Y') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $reportData['isExceeded'] ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300' }}">
                        {{ $reportData['isExceeded'] ? 'OD LIMIT EXCEEDED' : 'WITHIN APPROVED LIMIT' }}
                    </span>
                </div>
            </div>

            <!-- OD LIMIT EXCEEDED WARNING BANNER -->
            @if($reportData['isExceeded'])
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
                            Transactions exceeding the OD limit remain active for complete accounting accuracy and interest calculation.
                        </p>
                    </div>
                </div>
            @endif

            <!-- Core OD Metrics Summary -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">OD Limit</span>
                    <span class="text-base font-bold font-mono text-slate-900 block mt-1">
                        ₹{{ number_format($reportData['odLimit'], 2) }}
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Interest Rate</span>
                    <span class="text-base font-bold font-mono text-indigo-700 block mt-1">
                        {{ number_format($reportData['interestRate'], 2) }}% p.a.
                    </span>
                </div>

                <div class="p-3 rounded-xl border {{ $reportData['isExceeded'] ? 'bg-rose-50 border-rose-200' : 'bg-slate-50 border-slate-200' }}">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Current OD Utilized</span>
                    <span class="text-base font-bold font-mono block mt-1 {{ $reportData['isExceeded'] ? 'text-rose-700' : 'text-indigo-700' }}">
                        ₹{{ number_format($reportData['currentUtilized'], 2) }}
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Available OD</span>
                    <span class="text-base font-bold font-mono text-emerald-700 block mt-1">
                        ₹{{ number_format($reportData['availableOd'], 2) }}
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Utilization %</span>
                    <span class="text-base font-bold font-mono block mt-1 {{ $reportData['isExceeded'] ? 'text-rose-700' : 'text-slate-900' }}">
                        {{ number_format($reportData['utilizationPct'], 1) }}%
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Period Cashflow</span>
                    <span class="text-[11px] font-mono text-slate-700 block mt-1">
                        +₹{{ number_format($reportData['totalDebit'], 2) }} <span class="text-[9px] text-rose-500">(Dr)</span><br>
                        -₹{{ number_format($reportData['totalCredit'], 2) }} <span class="text-[9px] text-emerald-500">(Cr)</span>
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-200 col-span-2 md:col-span-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-900 block">Total Period Interest</span>
                    <span class="text-base font-bold font-mono text-indigo-700 block mt-1">
                        ₹{{ number_format($reportData['totalPeriodInterest'], 2) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- OD TRANSACTION TABLE (12 REQUIRED COLUMNS) -->
        <x-ui.card class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">OD Transaction Statement &amp; Daily Interest</h3>
                    <p class="text-xs text-slate-500">
                        Daily Interest = OD Utilized Amount &times; Annual Rate / 365 / 100 based on actual utilized balance.
                    </p>
                </div>
                <div class="text-xs font-medium text-slate-500">
                    Total Transactions: <span class="font-bold text-slate-800">{{ $reportData['rows']->count() }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs divide-y divide-slate-200">
                    <thead class="bg-slate-50 uppercase font-semibold text-slate-600">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left">Date</th>
                            <th scope="col" class="px-3 py-3 text-left">Transaction No.</th>
                            <th scope="col" class="px-3 py-3 text-left">Particulars / Description</th>
                            <th scope="col" class="px-3 py-3 text-left">Type</th>
                            <th scope="col" class="px-3 py-3 text-right">Debit (Withdrawal)</th>
                            <th scope="col" class="px-3 py-3 text-right">Credit (Deposit)</th>
                            <th scope="col" class="px-3 py-3 text-right">OD Utilized</th>
                            <th scope="col" class="px-3 py-3 text-right">Available OD</th>
                            <th scope="col" class="px-3 py-3 text-center">Rate (%)</th>
                            <th scope="col" class="px-3 py-3 text-center">Days</th>
                            <th scope="col" class="px-3 py-3 text-right">Daily Interest</th>
                            <th scope="col" class="px-3 py-3 text-right">Cumulative Interest</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($reportData['rows'] as $row)
                            <tr class="hover:bg-slate-50/70 transition {{ $row['is_opening'] ? 'bg-amber-50/40 font-medium' : '' }}">
                                <!-- 1. Date -->
                                <td class="px-3 py-2.5 whitespace-nowrap text-slate-700">
                                    {{ $row['date'] instanceof \Carbon\Carbon ? $row['date']->format('d/m/Y') : $row['date'] }}
                                </td>

                                <!-- 2. Transaction No. -->
                                <td class="px-3 py-2.5 whitespace-nowrap font-mono font-semibold text-slate-800">
                                    {{ $row['transaction_no'] }}
                                </td>

                                <!-- 3. Description / Particulars -->
                                <td class="px-3 py-2.5 max-w-xs truncate text-slate-800" title="{{ $row['description'] }}">
                                    {{ $row['description'] }}
                                </td>

                                <!-- 4. Transaction Type -->
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider {{ in_array($row['transaction_type'], ['debit', 'withdrawal', 'payment', 'bank_charges']) ? 'bg-rose-50 text-rose-700 border border-rose-200' : ($row['is_opening'] ? 'bg-amber-100 text-amber-800' : 'bg-emerald-50 text-emerald-700 border border-emerald-200') }}">
                                        {{ str_replace('_', ' ', $row['transaction_type']) }}
                                    </span>
                                </td>

                                <!-- 5. Debit -->
                                <td class="px-3 py-2.5 text-right font-mono whitespace-nowrap text-rose-600 font-semibold">
                                    {{ $row['debit'] > 0 ? '₹' . number_format($row['debit'], 2) : '-' }}
                                </td>

                                <!-- 6. Credit -->
                                <td class="px-3 py-2.5 text-right font-mono whitespace-nowrap text-emerald-600 font-semibold">
                                    {{ $row['credit'] > 0 ? '₹' . number_format($row['credit'], 2) : '-' }}
                                </td>

                                <!-- 7. OD Utilized -->
                                <td class="px-3 py-2.5 text-right font-mono whitespace-nowrap font-bold {{ $row['is_exceeded'] ? 'text-rose-700' : 'text-slate-900' }}">
                                    ₹{{ number_format($row['od_utilized'], 2) }}
                                    @if($row['is_exceeded'])
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-600 ml-0.5" title="Exceeded"></span>
                                    @endif
                                </td>

                                <!-- 8. Available OD -->
                                <td class="px-3 py-2.5 text-right font-mono whitespace-nowrap font-semibold text-emerald-700">
                                    ₹{{ number_format($row['available_od'], 2) }}
                                </td>

                                <!-- 9. Interest Rate -->
                                <td class="px-3 py-2.5 text-center font-mono whitespace-nowrap text-slate-600">
                                    {{ number_format($row['interest_rate'], 2) }}%
                                </td>

                                <!-- 10. Days -->
                                <td class="px-3 py-2.5 text-center font-mono whitespace-nowrap font-bold text-slate-700">
                                    {{ $row['days'] }}
                                </td>

                                <!-- 11. Daily Interest -->
                                <td class="px-3 py-2.5 text-right font-mono whitespace-nowrap text-slate-800">
                                    ₹{{ number_format($row['daily_interest'], 2) }}
                                </td>

                                <!-- 12. Cumulative Interest -->
                                <td class="px-3 py-2.5 text-right font-mono whitespace-nowrap font-bold text-indigo-700">
                                    ₹{{ number_format($row['cumulative_interest'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-12 text-center text-slate-500">
                                    <div class="max-w-sm mx-auto space-y-2">
                                        <svg class="w-10 h-10 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                        <p class="font-semibold text-slate-700">No bank transactions found in this period</p>
                                        <p class="text-xs text-slate-400">
                                            Record withdrawals or deposits under the OD Limit screen to generate interest statements.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($reportData['rows']->isNotEmpty())
                        <tfoot class="bg-slate-100/80 font-bold text-slate-900 border-t-2 border-slate-300">
                            <tr>
                                <td colspan="4" class="px-3 py-3 text-right uppercase text-[11px] tracking-wider">
                                    Period Totals / Position:
                                </td>
                                <td class="px-3 py-3 text-right font-mono text-rose-700">
                                    ₹{{ number_format($reportData['totalDebit'], 2) }}
                                </td>
                                <td class="px-3 py-3 text-right font-mono text-emerald-700">
                                    ₹{{ number_format($reportData['totalCredit'], 2) }}
                                </td>
                                <td class="px-3 py-3 text-right font-mono {{ $reportData['isExceeded'] ? 'text-rose-700' : 'text-slate-900' }}">
                                    ₹{{ number_format($reportData['currentUtilized'], 2) }}
                                </td>
                                <td class="px-3 py-3 text-right font-mono text-emerald-700">
                                    ₹{{ number_format($reportData['availableOd'], 2) }}
                                </td>
                                <td colspan="3" class="px-3 py-3 text-right uppercase text-[11px] text-slate-500">
                                    Total Interest:
                                </td>
                                <td class="px-3 py-3 text-right font-mono text-indigo-700 text-sm">
                                    ₹{{ number_format($reportData['totalPeriodInterest'], 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-ui.card>
    @else
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center text-slate-500 space-y-3">
            <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="font-semibold text-slate-700">Please Select a Bank Account</p>
            <p class="text-xs text-slate-400">Choose a company bank account from the dropdown above to view its Overdraft statement and interest calculations.</p>
        </div>
    @endif
</div>
@endsection

