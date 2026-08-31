<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'DMS'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-slate-100 text-slate-900 overflow-hidden">
    <div
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: localStorage.getItem('dmsSidebarCollapsed') === 'true',
            toggleCollapsed() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('dmsSidebarCollapsed', this.sidebarCollapsed);
            }
        }"
        class="flex h-screen w-full overflow-hidden"
    >
        @include('partials.sidebar')

        {{-- Main content shell: only this area scrolls --}}
        <div
            class="flex flex-1 flex-col min-w-0 h-screen overflow-hidden transition-[margin] duration-300"
            :class="sidebarCollapsed ? 'lg:ml-0' : 'lg:ml-0'"
        >
            <header class="shrink-0 z-30 bg-white/95 backdrop-blur border-b border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3 min-w-0">
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            class="lg:hidden inline-flex items-center justify-center rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition"
                        >
                            <span class="sr-only">Open sidebar</span>
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

                        <div class="min-w-0 hidden sm:block">
                            @hasSection('breadcrumbs')
                                @yield('breadcrumbs')
                            @else
                                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Overview</p>
                                <h1 class="truncate text-lg font-semibold text-slate-900">@yield('title', 'Dashboard')</h1>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-3 sm:gap-4">
                        <div class="hidden md:flex items-center gap-2 rounded-lg bg-slate-50 border border-slate-200 px-3 py-1.5">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <input type="search" placeholder="Search..." class="w-40 lg:w-52 bg-transparent border-0 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-0 p-0" />
                        </div>

                        <div class="hidden sm:block text-right">
                            <p class="text-sm font-medium text-slate-900">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-slate-500">{{ Auth::user()->email }}</p>
                        </div>

                        @php
                            $roleName = Auth::user()->roles->first()?->name ?? 'User';
                            $roleLabel = str($roleName)->replace('-', ' ')->title();
                        @endphp
                        <x-ui.badge variant="primary">{{ $roleLabel }}</x-ui.badge>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-ui.button type="submit" variant="ghost" size="sm">Log out</x-ui.button>
                        </form>
                    </div>
                </div>
            </header>

            {{-- Scrollable main region --}}
            <main class="flex-1 overflow-y-auto overflow-x-hidden dms-content">
                <div class="px-4 sm:px-6 lg:px-8 py-6 space-y-6 max-w-[1600px] mx-auto w-full">
                    @if (session('status'))
                        <x-ui.alert type="success" :message="session('status')" />
                    @endif

                    @if (session('error'))
                        <x-ui.alert type="error" :message="session('error')" />
                    @endif

                    @if ($errors->any())
                        <x-ui.alert type="error">
                            <ul class="list-disc list-inside space-y-1 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-ui.alert>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
