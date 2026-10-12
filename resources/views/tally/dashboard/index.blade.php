<x-tally::layouts.app title="Books Dashboard">
    @php
        $company = $workspace->company();
        $branch = $workspace->branch();
        $year = $workspace->financialYear();
        $money = function (?string $value): string {
            $value = trim((string) ($value ?: '0.00'));
            if ($value === '') {
                $value = '0.00';
            }

            return str_starts_with($value, '₹') ? $value : '₹'.$value;
        };
    @endphp

    @if ($companyTotal === 0)
        <x-ui.card>
            <x-ui.empty-state
                title="No company yet"
                description="Create the first company to open the books, then add branches and financial years."
            >
                <x-slot:action>
                    <x-ui.button variant="primary" :href="tally_route('books.tally.companies.create')">Create company</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        </x-ui.card>
    @elseif (! $company)
        <x-ui.card>
            <x-ui.empty-state
                :title="$activeCompanyTotal === 0 ? 'No active company' : 'Select a company'"
                :description="$activeCompanyTotal === 0 ? 'Every company is inactive. Activate one before working in the books.' : 'Choose the working company from the top bar. Branch and financial year follow that company.'"
            >
                <x-slot:action>
                    <x-ui.button variant="primary" :href="tally_route('books.tally.companies.index')">Open companies</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        {{-- Hero --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 p-6 sm:p-8 text-white shadow-xl">
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-indigo-300 text-sm font-medium">{{ now()->format('l, F j, Y') }}</p>
                    <h2 class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight truncate">{{ $company->name }}</h2>
                    <p class="mt-2 text-slate-300 text-sm">
                        {{ $branch?->name ?? 'All branches' }}
                        · {{ $year?->name ?? 'No FY' }}
                        @if ($lastEntry)
                            · Last entry {{ \Illuminate\Support\Carbon::parse($lastEntry)->format('d M Y') }}
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button variant="primary" :href="tally_route('books.tally.vouchers.create')" class="!bg-white !text-indigo-700 hover:!bg-indigo-50 shadow-lg">+ New voucher</x-ui.button>
                    <x-ui.button variant="secondary" :href="tally_route('books.tally.vouchers.receipt.create')" class="!bg-white/10 !text-white !border-white/20 hover:!bg-white/20">Receipt</x-ui.button>
                    <x-ui.button variant="secondary" :href="tally_route('books.tally.vouchers.payment.create')" class="!bg-white/10 !text-white !border-white/20 hover:!bg-white/20">Payment</x-ui.button>
                    <x-ui.button variant="ghost" :href="tally_route('books.tally.reports.day-book')" class="!text-white hover:!bg-white/10">Day book</x-ui.button>
                </div>
            </div>
        </div>

        {{-- As-on filter --}}
        <x-ui.card>
            <form method="GET" action="{{ tally_route('books.tally.dashboard') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="as_on" class="block text-xs font-medium text-slate-500 mb-1">As on</label>
                    <input
                        id="as_on"
                        type="date"
                        name="as_on"
                        value="{{ $asOn }}"
                        class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
                <x-ui.button type="submit" variant="secondary" size="sm">Apply</x-ui.button>
                <p class="text-xs text-slate-500 pb-2">Figures refresh for the selected date within {{ $year?->name }}.</p>
            </form>
        </x-ui.card>

        @if ($cards)
            {{-- KPIs --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card
                    label="Sales / Income"
                    :value="$money($cards['revenue'] ?? '0.00')"
                    :change="'As on '.$cards['as_on']"
                    change-type="neutral"
                    accent="indigo"
                />
                <x-ui.stat-card
                    label="Net {{ ($cards['net_side'] ?? 'profit') === 'profit' ? 'Profit' : 'Loss' }}"
                    :value="$money($cards['net_profit'] ?? '0.00')"
                    :change="'Gross '.(($cards['gross_side'] ?? 'profit') === 'profit' ? 'profit' : 'loss').' '.$money($cards['gross_profit'] ?? '0.00')"
                    :change-type="($cards['net_side'] ?? 'profit') === 'profit' ? 'positive' : 'negative'"
                    accent="emerald"
                />
                <x-ui.stat-card
                    label="Receivables"
                    :value="$money($cards['receivables'] ?? '0.00')"
                    :change="'Overdue '.$money($cards['receivables_overdue'] ?? '0.00')"
                    :change-type="(($cards['receivables_overdue'] ?? '0.00') !== '0.00') ? 'warning' : 'neutral'"
                    accent="amber"
                />
                <x-ui.stat-card
                    label="Payables"
                    :value="$money($cards['payables'] ?? '0.00')"
                    :change="'Overdue '.$money($cards['payables_overdue'] ?? '0.00')"
                    :change-type="(($cards['payables_overdue'] ?? '0.00') !== '0.00') ? 'warning' : 'neutral'"
                    accent="rose"
                />
                <x-ui.stat-card
                    label="Cash"
                    :value="$money($cards['cash'] ?? '0.00')"
                    change-type="neutral"
                    accent="sky"
                />
                <x-ui.stat-card
                    label="Bank"
                    :value="$money($cards['bank'] ?? '0.00')"
                    change-type="neutral"
                    accent="violet"
                />
                <x-ui.stat-card
                    label="Assets"
                    :value="$money($cards['assets'] ?? '0.00')"
                    change-type="neutral"
                    accent="indigo"
                />
                <x-ui.stat-card
                    label="Stock value"
                    :value="$money($cards['stock_value'] ?? '0.00')"
                    :change="'Qty '.($cards['stock_quantity'] ?? '0')"
                    change-type="neutral"
                    accent="emerald"
                />
            </div>
        @endif

        {{-- Quick voucher actions --}}
        <x-ui.card title="Quick vouchers" description="Create day-to-day accounting entries.">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                @foreach ([
                    ['Payment', 'vouchers.payment.create'],
                    ['Receipt', 'vouchers.receipt.create'],
                    ['Journal', 'vouchers.journal.create'],
                    ['Contra', 'vouchers.contra.create'],
                    ['Transfer', 'vouchers.transfer.create'],
                    ['Adjustment', 'vouchers.adjustment.create'],
                ] as [$label, $route])
                    <a
                        href="{{ tally_route('books.tally.'.$route) }}"
                        class="flex flex-col items-center justify-center gap-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-4 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 transition"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </x-ui.card>

        {{-- Feature modules --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <x-ui.card title="Masters" description="Chart of accounts and dimensions.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ([
                        ['Ledgers', 'ledgers.index'],
                        ['Account groups', 'account-groups.index'],
                        ['Parties', 'parties.index'],
                        ['Business units', 'business-units.index'],
                        ['Departments', 'departments.index'],
                        ['Company features', 'companies.features'],
                    ] as [$label, $route])
                        @if (tally_route_has($route))
                            <x-ui.button variant="secondary" size="sm" class="!justify-start" :href="tally_route($route)">{{ $label }}</x-ui.button>
                        @endif
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card title="Transactions" description="Vouchers, invoices, and recurring entries.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ([
                        ['All vouchers', 'vouchers.index'],
                        ['Voucher types', 'vouchers.other'],
                        ['Sales invoices', 'invoices.sales.index'],
                        ['Purchase invoices', 'invoices.purchase.index'],
                        ['Day book', 'reports.day-book'],
                        ['Recurring journals', 'recurring.index'],
                        ['Intercompany', 'intercompany.index'],
                    ] as [$label, $route])
                        @if (tally_route_has($route))
                            <x-ui.button variant="secondary" size="sm" class="!justify-start" :href="tally_route($route)">{{ $label }}</x-ui.button>
                        @endif
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card title="Reports" description="Financial statements and registers.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ([
                        ['Trial balance', 'reports.trial-balance'],
                        ['Profit & loss', 'reports.profit-and-loss'],
                        ['Balance sheet', 'reports.balance-sheet'],
                        ['Business units', 'reports.business-units'],
                        ['Departments', 'reports.departments'],
                        ['Outstanding', 'reports.outstanding'],
                        ['Sub-ledgers', 'reports.sub-ledgers'],
                        ['Stock summary', 'reports.stock-summary'],
                    ] as [$label, $route])
                        @if (tally_route_has($route))
                            <x-ui.button variant="secondary" size="sm" class="!justify-start" :href="tally_route($route)">{{ $label }}</x-ui.button>
                        @endif
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card title="Utilities" description="Period control, year-end, and sync.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ([
                        ['Period locking', 'periods.index'],
                        ['Year-end close', 'year-end.show'],
                        ['FX revaluation', 'fx-revaluation.show'],
                        ['IC ledger mirrors', 'ledger-mirrors.index'],
                        ['Locations', 'locations.index'],
                        ['Exchange rates', 'exchange-rates.index'],
                        ['Import', 'utilities.import'],
                        ['Export', 'utilities.export'],
                        ['Audit trail', 'audit.index'],
                        ['Tally connector', 'connector.show'],
                    ] as [$label, $route])
                        @if (tally_route_has($route))
                            <x-ui.button variant="secondary" size="sm" class="!justify-start" :href="tally_route($route)">{{ $label }}</x-ui.button>
                        @endif
                    @endforeach
                </div>
            </x-ui.card>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            @if ($cards)
                <x-ui.card title="Cash flow" description="Cash and bank movement for the period.">
                    <dl class="grid grid-cols-3 gap-4">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Inflow</dt>
                            <dd class="mt-1 text-lg font-semibold text-emerald-700">{{ $money($cards['cash_flow']['inflow'] ?? '0.00') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Outflow</dt>
                            <dd class="mt-1 text-lg font-semibold text-rose-700">{{ $money($cards['cash_flow']['outflow'] ?? '0.00') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">Net</dt>
                            <dd class="mt-1 text-lg font-semibold text-slate-900">{{ $cards['cash_flow']['net'] ?? '0.00' }}</dd>
                        </div>
                    </dl>
                    @if (! empty($cards['accounts']))
                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Cash / bank ledgers</p>
                            <ul class="divide-y divide-slate-100">
                                @foreach (collect($cards['accounts'])->take(6) as $row)
                                    <li class="flex items-center justify-between py-2 text-sm">
                                        <a href="{{ $row['url'] }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $row['name'] }}</a>
                                        <span class="tabular-nums text-slate-700">{{ $row['amount'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </x-ui.card>
            @endif

            <x-ui.card title="Recent vouchers" description="Latest entries in this company." :padding="false">
                @if ($recentVouchers->isEmpty())
                    <div class="px-6 py-8">
                        <x-ui.empty-state title="No vouchers yet" description="Create a payment, receipt, or journal to get started.">
                            <x-slot:action>
                                <x-ui.button variant="primary" size="sm" :href="tally_route('books.tally.vouchers.create')">New voucher</x-ui.button>
                            </x-slot:action>
                        </x-ui.empty-state>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($recentVouchers as $voucher)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-3 text-sm text-slate-700 whitespace-nowrap">
                                            {{ \Illuminate\Support\Carbon::parse($voucher->voucher_date)->format('d M Y') }}
                                        </td>
                                        <td class="px-6 py-3 text-sm font-medium text-slate-900">{{ $voucher->voucher_number ?: '—' }}</td>
                                        <td class="px-6 py-3 text-sm text-slate-600">
                                            {{ is_object($voucher->voucher_type) ? ($voucher->voucher_type->label() ?? $voucher->voucher_type->value ?? $voucher->voucher_type->name) : $voucher->voucher_type }}
                                        </td>
                                        <td class="px-6 py-3 text-sm">
                                            <x-ui.badge variant="default">
                                                {{ is_object($voucher->status) ? ($voucher->status->value ?? $voucher->status->name) : $voucher->status }}
                                            </x-ui.badge>
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <x-ui.button variant="ghost" size="sm" :href="tally_route('books.tally.vouchers.show', $voucher)">Open</x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 rounded-b-xl">
                        <x-ui.button variant="secondary" size="sm" :href="tally_route('books.tally.vouchers.index')">View all vouchers</x-ui.button>
                    </div>
                @endif
            </x-ui.card>
        </div>
    @endif
</x-tally::layouts.app>
