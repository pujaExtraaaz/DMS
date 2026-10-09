@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/tally-books.css') }}">
</head>
<body data-home="{{ tally_route('books.tally.dashboard') }}">
    <a href="{{ route('dashboard') }}" style="display:block;background:#0f172a;color:#fff;padding:.45rem .9rem;font-size:.8rem;text-decoration:none;">← Back to DMS</a>
    <a class="skip" href="#content">Skip to content</a>
    <div class="app tally" id="app">
        <x-tally::shell.sidebar :sections="$navigation" :shell-nav="$shellNav" />
        <div class="workspace">
            <x-tally::shell.topbar />
            <x-tally::shell.breadcrumbs :items="$breadcrumbs" />
            @if (session('status'))
                <p class="flash" role="status">{{ session('status') }}</p>
            @endif
            @if (session('error'))
                <p class="flash is-error" role="alert">{{ session('error') }}</p>
            @endif
            @foreach (['company_id', 'branch_id', 'financial_year_id'] as $contextField)
                @error($contextField)
                    <p class="flash is-error" role="alert">{{ $message }}</p>
                @enderror
            @endforeach
            <div class="workspace-body">
                <main id="content" class="content">
                    {{ $slot }}
                </main>
                <x-tally::shell.function-keys />
            </div>
            <footer class="statusbar tally-foot">
                <span>{{ \Tally\Support\Shell\TallyPanel::exception() }}</span>
                <span>{{ $workspace->company()->name ?? 'No company' }}</span>
                <span>{{ $workspace->branch() ? $workspace->branch()->code : '' }}</span>
                <span class="statusbar-fill"></span>
                @foreach (\Tally\Support\Shell\TallyPanel::footer() as $item)
                    <span>{{ $item['key'] }}: {{ $item['label'] }}</span>
                @endforeach
            </footer>
        </div>
    </div>
    <button class="backdrop" type="button" data-close-sidebar hidden></button>
    <x-tally::shell.global-search />
    <x-tally::shell.shortcut-help :shortcuts="$shortcuts" />
    <div id="go-to-dialog" class="dialog" hidden>
        <div class="dialog-card tally-goto" role="dialog" aria-modal="true" aria-labelledby="go-to-title">
            <header class="dialog-head">
                <h2 id="go-to-title">Go To</h2>
                <button class="icon-btn" type="button" data-close-dialog>Close</button>
            </header>
            <div class="tally-goto-list">
                @foreach (config('navigation.sections') as $section)
                    <p class="gateway-group">{{ $section['label'] }}</p>
                    @if (! empty($section['route']) && tally_route_has($section['route']))
                        <a href="{{ tally_route($section['route']) }}">{{ $section['label'] }}</a>
                    @endif
                    @foreach ($section['children'] ?? [] as $child)
                        @if (! empty($child['route']) && tally_route_has($child['route']))
                            <a href="{{ tally_route($child['route'], $child['params'] ?? []) }}">{{ $child['label'] }}</a>
                        @endif
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
    <div id="calculator-dialog" class="dialog" hidden>
        <div class="dialog-card" role="dialog" aria-modal="true" aria-labelledby="calculator-title">
            <header class="dialog-head">
                <h2 id="calculator-title">Calculator</h2>
                <button class="icon-btn" type="button" data-close-dialog>Close</button>
            </header>
            <input id="calculator-display" class="input calc-display" value="0" readonly>
            <div class="calc-grid">
                @foreach (['7', '8', '9', '/', '4', '5', '6', '*', '1', '2', '3', '-', '0', '.', '=', '+', 'c'] as $calcKey)
                    <button class="btn" type="button" data-calc="{{ $calcKey }}">{{ $calcKey === 'c' ? 'C' : $calcKey }}</button>
                @endforeach
            </div>
        </div>
    </div>
    @php
        $working = app(\Tally\Context\WorkingCalendar::class)->present($workspace->company(), auth()->user(), $workspace->financialYear());
    @endphp
    <div id="date-dialog" class="dialog" hidden>
        <div class="dialog-card" role="dialog" aria-modal="true" aria-labelledby="date-dialog-title">
            <header class="dialog-head">
                <h2 id="date-dialog-title">Date</h2>
                <button class="icon-btn" type="button" data-close-date>Close</button>
            </header>
            <div data-date-choice>
                <button class="btn" type="button" data-choose-date>Change Date</button>
                <button class="btn" type="button" data-choose-period>Change Period</button>
            </div>
            <form data-date-form method="POST" action="{{ tally_route('books.tally.context.date') }}" hidden>
                @csrf
                <label class="field">
                    <span>Date</span>
                    <input class="input" type="date" name="as_on" value="{{ $working['date'] }}" @if ($working['min']) min="{{ $working['min'] }}" max="{{ $working['max'] }}" @endif required>
                </label>
                <button class="btn btn-primary" type="submit">Accept</button>
            </form>
            <form data-period-form method="POST" action="{{ tally_route('books.tally.context.period') }}" hidden>
                @csrf
                <label class="field">
                    <span>From</span>
                    <input class="input" type="date" name="period_from" value="{{ $working['from'] }}" @if ($working['min']) min="{{ $working['min'] }}" max="{{ $working['max'] }}" @endif required>
                </label>
                <label class="field">
                    <span>To</span>
                    <input class="input" type="date" name="period_to" value="{{ $working['to'] }}" @if ($working['min']) min="{{ $working['min'] }}" max="{{ $working['max'] }}" @endif required>
                </label>
                <button class="btn btn-primary" type="submit">Accept</button>
            </form>
        </div>
    </div>
    <script type="application/json" id="shell-config">@json(['shortcuts' => $shortcuts, 'search' => $searchIndex, 'searchUrl' => tally_route('books.tally.search.records')])</script>
    <script type="module" src="{{ asset('js/tally-books.js') }}"></script>
    @include('partials.sync-conflict')
</body>
</html>
