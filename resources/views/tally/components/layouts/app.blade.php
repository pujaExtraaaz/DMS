@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f4c81">
    <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name') }} · Books</title>

    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('icons/icon-512.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/tally-books.css') }}">
    <link rel="stylesheet" href="{{ asset('css/books-dms.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pwa-mobile.css') }}">
    @livewireStyles
</head>
<body class="font-sans antialiased bg-slate-100 text-slate-900 overflow-hidden dms-pwa books-shell" data-home="{{ tally_route('books.tally.dashboard') }}">
    <a class="skip" href="#content">Skip to content</a>
    <div
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: localStorage.getItem('dmsSidebarCollapsed') === 'true',
            toggleCollapsed() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('dmsSidebarCollapsed', this.sidebarCollapsed);
            }
        }"
        class="flex h-[100dvh] w-full overflow-hidden"
        id="app"
    >
        @include('partials.sidebar')

        <div class="relative z-0 flex flex-1 flex-col min-w-0 h-[100dvh] overflow-hidden bg-slate-100">
            <header class="shrink-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200/80 shadow-sm pt-[env(safe-area-inset-top)] overflow-visible">
                <div class="flex items-center justify-between h-14 sm:h-16 px-3 sm:px-6 lg:px-8 gap-3 overflow-visible">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            class="lg:hidden inline-flex items-center justify-center rounded-lg p-2.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition"
                        >
                            <span class="sr-only">Open menu</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            @click="toggleCollapsed()"
                            class="hidden lg:inline-flex items-center justify-center rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition"
                        >
                            <span class="sr-only">Toggle sidebar</span>
                            <svg
                                class="h-5 w-5 transition-transform duration-300"
                                :class="sidebarCollapsed ? 'rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>

                        <div class="min-w-0">
                            <p class="hidden sm:block text-xs font-medium uppercase tracking-wider text-slate-400">10 · Books</p>
                            <h1 class="truncate text-base sm:text-lg font-semibold text-slate-900">{{ $title ?: 'Books' }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 min-w-0 overflow-visible relative z-50">
                        <x-tally::shell.topbar />
                        @php
                            $roleName = Auth::user()->roles->first()?->name ?? 'User';
                            $roleLabel = str($roleName)->replace('-', ' ')->title();
                        @endphp
                        <span class="hidden md:inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $roleLabel }}</span>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto overflow-x-hidden dms-content">
                <div class="px-3 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-4 sm:space-y-6 max-w-[1600px] mx-auto w-full pb-24 lg:pb-6">
                    <x-tally::shell.breadcrumbs :items="$breadcrumbs" />

                    @if (session('status'))
                        <x-ui.alert type="success" :message="session('status')" />
                    @endif
                    @if (session('error'))
                        <x-ui.alert type="error" :message="session('error')" />
                    @endif
                    @foreach (['company_id', 'branch_id', 'financial_year_id'] as $contextField)
                        @error($contextField)
                            <x-ui.alert type="error" :message="$message" />
                        @enderror
                    @endforeach

                    <div id="content" class="content books-dms">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            @include('partials.mobile-bottom-nav')
        </div>
    </div>

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
    @livewireScripts
    <script src="{{ asset('js/dynamic-tables.js') }}" defer></script>
    @stack('scripts')
    @include('partials.sync-conflict')
</body>
</html>
