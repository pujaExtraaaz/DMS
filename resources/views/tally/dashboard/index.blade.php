<x-tally::layouts.app title="Dashboard">
    <x-tally::shell.page title="Dashboard" section="Workspace">
        @php
            $company = $workspace->company();
            $branch = $workspace->branch();
            $year = $workspace->financialYear();
        @endphp

        @if ($companyTotal === 0)
            <x-tally::ui.empty-state title="No company yet" message="Create the first company to open the books, then add branches and financial years.">
                <a class="btn btn-primary" href="{{ tally_route('books.tally.companies.create') }}">Create the first company</a>
            </x-ui.empty-state>
        @elseif (! $company)
            <x-tally::ui.empty-state
                title="{{ $activeCompanyTotal === 0 ? 'No active company' : 'Select a company' }}"
                message="{{ $activeCompanyTotal === 0 ? 'Every company is inactive. Activate one before working in the books.' : 'Choose the working company from the top bar. Branch and financial year follow that company.' }}"
            >
                <a class="btn btn-primary" href="{{ tally_route('books.tally.companies.index') }}">Open companies</a>
            </x-ui.empty-state>
        @elseif (($screen ?? 'gateway') !== 'tiles')
            @include('tally::dashboard.gateway')
        @else
            @if ($cards)
                <a class="esc-back" data-esc href="{{ tally_route('books.tally.dashboard') }}">Quit</a>
                <form class="filters" method="GET">
                    <input type="hidden" name="screen" value="tiles">
                    <x-tally::form.field name="as_on" label="As on"><x-tally::form.input name="as_on" type="date" value="{{ $asOn }}" /></x-form.field>
                    <button class="btn btn-primary" type="submit">Apply</button>
                </form>
                <div class="tally-dash">
                    <section>
                        <h2>Sales Trend <span>{{ $year->rangeLabel() }}</span></h2>
                        @include('tally::dashboard._trend', ['rows' => $cards['sales_trend']])
                    </section>
                    <section>
                        <h2>Purchase Trend <span>{{ $year->rangeLabel() }}</span></h2>
                        @include('tally::dashboard._trend', ['rows' => $cards['purchase_trend']])
                    </section>
                    <section>
                        <h2>Cash In/Out Flow <span>for {{ $branch?->name ?? 'all branches' }}</span></h2>
                        @if ($cards['cash_flow']['inflow'] !== '0.00' || $cards['cash_flow']['outflow'] !== '0.00')
                            <table class="data">
                                <tbody>
                                    <tr><td>Net Flow</td><td class="money">{{ $cards['cash_flow']['net'] }}</td></tr>
                                    <tr><td>Inflow</td><td class="money">{{ $cards['cash_flow']['inflow'] }}</td></tr>
                                    <tr><td>Outflow</td><td class="money">{{ $cards['cash_flow']['outflow'] }}</td></tr>
                                </tbody>
                            </table>
                        @endif
                    </section>
                    <section>
                        <h2>Trading Details <span>{{ $year->rangeLabel() }}</span></h2>
                        <table class="data">
                            <tbody>
                                @if ($cards['gross_profit'] !== '0.00')
                                    <tr><td>Gross {{ $cards['gross_side'] === 'profit' ? 'Profit' : 'Loss' }}</td><td class="money">{{ $cards['gross_profit'] }}</td></tr>
                                @endif
                                @if ($cards['net_profit'] !== '0.00')
                                    <tr><td>Net {{ $cards['net_side'] === 'profit' ? 'Profit' : 'Loss' }}</td><td class="money">{{ $cards['net_profit'] }}</td></tr>
                                @endif
                                @if ($cards['revenue'] !== '0.00')
                                    <tr><td><a href="{{ tally_route('books.tally.reports.profit-and-loss') }}">Sales Accounts</a></td><td class="money">{{ $cards['revenue'] }}</td></tr>
                                @endif
                                @if ($cards['purchases'] !== '0.00')
                                    <tr><td>Purchase Accounts</td><td class="money">{{ $cards['purchases'] }}</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </section>
                    <section>
                        <h2>Assets/Liabilities <span>as at {{ $cards['as_on'] }}</span></h2>
                        <table class="data">
                            <tbody>
                                @if ($cards['assets'] !== '0.00')
                                    <tr><td><a href="{{ tally_route('books.tally.reports.balance-sheet') }}">Current Assets</a></td><td class="money">{{ $cards['assets'] }}</td></tr>
                                @endif
                                @if ($cards['liabilities'] !== '0.00')
                                    <tr><td>Current Liabilities</td><td class="money">{{ $cards['liabilities'] }}</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </section>
                    <section>
                        <h2>Receivables/Payables <span>as at {{ $cards['as_on'] }}</span></h2>
                        <table class="data">
                            <tbody>
                                @if ($cards['receivables'] !== '0.00')
                                    <tr><td><a href="{{ tally_route('books.tally.reports.outstanding') }}">Receivables</a></td><td class="money">{{ $cards['receivables'] }}</td></tr>
                                @endif
                                @if ($cards['receivables_overdue'] !== '0.00')
                                    <tr><td>Overdue Receivables</td><td class="money">{{ $cards['receivables_overdue'] }}</td></tr>
                                @endif
                                @if ($cards['payables'] !== '0.00')
                                    <tr><td>Payables</td><td class="money">{{ $cards['payables'] }}</td></tr>
                                @endif
                                @if ($cards['payables_overdue'] !== '0.00')
                                    <tr><td>Overdue Payables</td><td class="money">{{ $cards['payables_overdue'] }}</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </section>
                    <section>
                        <h2>Cash/Bank Accounts <span>{{ $year->rangeLabel() }}</span></h2>
                        <table class="data">
                            <tbody>
                                @if ($cards['cash'] !== '0.00')
                                    <tr><td>Cash-in-Hand</td><td class="money">{{ $cards['cash'] }}</td></tr>
                                @endif
                                @foreach ($cards['accounts'] as $row)
                                    <tr><td><a href="{{ $row['url'] }}">{{ $row['name'] }}</a></td><td class="money">{{ $row['amount'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                    <section>
                        <h2>Top Ledgers <span>Bank and cash</span></h2>
                        <table class="data">
                            <tbody>
                                @foreach ($cards['accounts'] as $row)
                                    <tr><td><a href="{{ $row['url'] }}">{{ $row['name'] }}</a></td><td class="money">{{ $row['amount'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                    <section>
                        <h2>Inventory Details <span>{{ $year->rangeLabel() }}</span></h2>
                        <table class="data">
                            <tbody>
                                @if ($cards['stock_quantity'] !== '0' && $cards['stock_quantity'] !== '0.00')
                                    <tr><td>Closing Stock</td><td class="money">{{ $cards['stock_quantity'] }}</td><td class="money">{{ $cards['stock_value'] }}</td></tr>
                                @endif
                                @if ($cards['manufacturing']['quantity'] !== '0' && $cards['manufacturing']['quantity'] !== '0.00')
                                    <tr><td>Production</td><td class="money">{{ $cards['manufacturing']['quantity'] }}</td><td class="money">{{ $cards['manufacturing']['cost'] }}</td></tr>
                                @endif
                                @foreach ($cards['low_stock'] as $row)
                                    <tr><td>{{ $row['name'] }}</td><td class="money">{{ $row['quantity'] }}</td><td></td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                </div>
            @endif
        @endif
    </x-shell.page>
</x-layouts.app>
