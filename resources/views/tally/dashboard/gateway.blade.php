@php
    $company = $workspace->company();
    $year = $workspace->financialYear();
    $working = app(\Tally\Context\WorkingCalendar::class)->present($company, auth()->user(), $year);
    $lastLabel = $working['saved']
        ? \Illuminate\Support\Carbon::parse($working['date'])->format('j-M-y')
        : ($lastEntry ? \Illuminate\Support\Carbon::parse($lastEntry)->format('j-M-y') : '—');
@endphp
<div class="gateway">
    <div class="gateway-top">
        <p>
            <span>Current period</span>
            @if ($year)
                <button class="gateway-link" type="button" data-open-working-period>{{ $working['periodLabel'] }}</button>
            @else
                <strong>No period</strong>
            @endif
        </p>
        <p>
            <span>Current date</span>
            <button class="gateway-link" type="button" data-open-working-date>{{ $working['dateLabel'] }}</button>
        </p>
    </div>
    @if ($company)
        <div class="gateway-company-row">
            <p>
                <span>Name of company</span>
                <span class="gateway-company-line">
                    <strong>{{ $company->name }}</strong>
                </span>
            </p>
            <p>
                <span>Date of last entry</span>
                <button class="gateway-link" type="button" data-open-working-date @if ($lastEntry) data-date="{{ \Illuminate\Support\Carbon::parse($working['saved'] ? $working['date'] : $lastEntry)->toDateString() }}" @endif>{{ $lastLabel }}</button>
            </p>
        </div>
    @endif
    <nav class="gateway-menu" data-key-menu aria-label="Gateway of Tally">
        <h2>Gateway of Tally</h2>
        <p class="gateway-group">Masters</p>
        <a class="is-current" href="{{ tally_route('books.tally.masters.create-menu') }}">Create</a>
        <a href="{{ tally_route('books.tally.masters.menu') }}">Alter</a>
        <a href="{{ tally_route('books.tally.account-groups.index') }}">Chart of Accounts</a>
        <p class="gateway-group">Transactions</p>
        <a href="{{ tally_route('books.tally.vouchers.other') }}">Vouchers</a>
        <a href="{{ tally_route('books.tally.reports.day-book') }}">Day Book</a>
        <p class="gateway-group">Utilities</p>
        <a href="{{ tally_route('books.tally.banking.menu') }}">Banking</a>
        <p class="gateway-group">Reports</p>
        <a href="{{ tally_route('books.tally.reports.balance-sheet') }}">Balance Sheet</a>
        <a href="{{ tally_route('books.tally.reports.profit-and-loss') }}">Profit &amp; Loss A/c</a>
        <a href="{{ tally_route('books.tally.reports.stock-summary') }}">Stock Summary</a>
        <a href="{{ tally_route('books.tally.reports.ratio-analysis') }}">Ratio Analysis</a>
        <a href="{{ tally_route('books.tally.reports.menu') }}">Display More Reports</a>
        <a href="{{ tally_route('books.tally.dashboard', ['screen' => 'tiles']) }}">Dashboard</a>
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('gateway-quit').submit();">Quit</a>
        <form id="gateway-quit" method="POST" action="{{ route('logout') }}" hidden>@csrf</form>
    </nav>
</div>
