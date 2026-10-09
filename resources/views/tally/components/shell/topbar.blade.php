@php
    $company = $workspace->company();
    $branch = $workspace->branch();
    $year = $workspace->financialYear();
@endphp
<header class="topbar tally-bar">
    <button class="icon-btn tally-menu" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false">Menu</button>
    <a class="tally-brand" href="{{ tally_route('books.tally.dashboard') }}">Tally Web</a>
    <button class="search-btn" type="button" data-open-search>
        <span>Find details entered in masters and transactions. (Alt+F)</span>
    </button>
    <button class="tally-bell" type="button" data-open-menu="notifications" aria-label="Notifications">
        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 22a2.2 2.2 0 0 0 2.2-2.2h-4.4A2.2 2.2 0 0 0 12 22Zm7-6.2V11a7 7 0 0 0-5-6.7V3.5a2 2 0 1 0-4 0v.8A7 7 0 0 0 5 11v4.8L3.2 18h17.6L19 15.8Z"/></svg>
    </button>
    <nav class="tally-commands" aria-label="Commands">
        <button type="button" data-open-menu="company"><u>K</u>: Company</button>
        <button type="button" data-open-menu="year"><u>Y</u>: Data</button>
        <a href="{{ tally_route('books.tally.utilities.import') }}"><u>Z</u>: Exchange</a>
        <button class="is-marked" type="button" data-open-goto><u>G</u>: Go To</button>
        <a href="{{ tally_route('books.tally.utilities.import') }}"><u>O</u>: Import</a>
        <a href="{{ tally_route('books.tally.utilities.export') }}"><u>E</u>: Export</a>
        <button type="button" data-share><u>M</u>: Share</button>
        <span data-share-status hidden></span>
        <button type="button" data-print><u>P</u>: Print</button>
        <details class="menu tally-help" data-menu="help">
            <summary>F1: Help</summary>
            <div class="menu-panel">
                <button class="menu-link" type="button" data-open-shortcut-list>Keyboard shortcuts</button>
                <a class="menu-link" href="{{ tally_route('books.tally.settings.shortcuts') }}">Change shortcut keys</a>
                <a class="menu-link" href="{{ tally_route('books.tally.profile.edit') }}">Account details</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="menu-link" type="submit">Log out</button>
                </form>
            </div>
        </details>
    </nav>
    <div class="context-menus">
        <details class="menu" data-menu="company">
            <summary>
                <span class="menu-kicker">Company</span>
                <span class="menu-value">{{ $company->name ?? 'Select company' }}</span>
            </summary>
            <div class="menu-panel">
                @forelse ($workspace->companies() as $option)
                    <form method="POST" action="{{ tally_route('books.tally.context.company') }}">
                        @csrf
                        <input type="hidden" name="company_id" value="{{ $option->id }}">
                        <button type="submit" @class(['is-current' => $company && $company->is($option)])>
                            <strong>{{ $option->name }}</strong>
                            @if ($option->city)
                                <small>{{ $option->city }}</small>
                            @endif
                        </button>
                    </form>
                @empty
                    <p class="menu-empty">No active company.</p>
                @endforelse
                <a class="menu-link" href="{{ tally_route('books.tally.companies.create') }}">New company</a>
            </div>
        </details>
        <details class="menu" data-menu="branch">
            <summary>
                <span class="menu-kicker">Branch</span>
                <span class="menu-value">{{ $branch->name ?? 'Select branch' }}</span>
            </summary>
            <div class="menu-panel">
                @if (! $company)
                    <p class="menu-empty">Select a company first.</p>
                @else
                    @forelse ($workspace->branches() as $option)
                        <form method="POST" action="{{ tally_route('books.tally.context.branch') }}">
                            @csrf
                            <input type="hidden" name="branch_id" value="{{ $option->id }}">
                            <button type="submit" @class(['is-current' => $branch && $branch->is($option)])>
                                <strong>{{ $option->name }}</strong>
                                <small>{{ $option->code }}</small>
                            </button>
                        </form>
                    @empty
                        <p class="menu-empty">This company has no active branch.</p>
                    @endforelse
                    <a class="menu-link" href="{{ tally_route('books.tally.companies.branches.create', $company) }}">New branch</a>
                @endif
            </div>
        </details>
        <details class="menu" data-menu="year">
            <summary>
                <span class="menu-kicker">Financial year</span>
                <span class="menu-value">{{ $year->name ?? 'Select year' }}</span>
            </summary>
            <div class="menu-panel">
                @if (! $company)
                    <p class="menu-empty">Select a company first.</p>
                @else
                    @forelse ($workspace->financialYears() as $option)
                        <form method="POST" action="{{ tally_route('books.tally.context.financial-year') }}">
                            @csrf
                            <input type="hidden" name="financial_year_id" value="{{ $option->id }}">
                            <button type="submit" @class(['is-current' => $year && $year->is($option)])>
                                <strong>{{ $option->name }}</strong>
                                <small>{{ $option->rangeLabel() }}</small>
                            </button>
                        </form>
                    @empty
                        <p class="menu-empty">This company has no active financial year.</p>
                    @endforelse
                    <a class="menu-link" href="{{ tally_route('books.tally.companies.financial-years.create', $company) }}">New financial year</a>
                @endif
            </div>
        </details>
    </div>
    <div class="topbar-end">
        <details class="menu" data-menu="notifications">
            <summary class="icon-btn" aria-label="Notifications">Alerts</summary>
            <div class="menu-panel">
                <p class="menu-empty">No data exceptions.</p>
            </div>
        </details>
        <details class="menu" data-menu="user">
            <summary>
                <span class="menu-kicker">{{ tally_super_admin() ? 'Super Admin' : (auth()->user()->getRoleNames()->first() ?: 'User') }}</span>
                <span class="menu-value">{{ auth()->user()->name }}</span>
            </summary>
            <div class="menu-panel">
                <a class="menu-link" href="{{ tally_route('books.tally.profile.edit') }}">Account details</a>
                <button class="menu-link" type="button" data-open-shortcut-list>Keyboard shortcuts</button>
                <a class="menu-link" href="{{ tally_route('books.tally.settings.shortcuts') }}">Change shortcut keys</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="menu-link" type="submit">Log out</button>
                </form>
            </div>
        </details>
    </div>
</header>
