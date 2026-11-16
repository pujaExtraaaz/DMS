@extends('layouts.dms')
@section('title', 'Phone Directory')

@section('content')
<div x-data="phoneDirectoryManager()" class="space-y-4">

    {{-- Live Flash Notification Banner --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900">Dismiss</button>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-xs font-semibold text-rose-700 hover:text-rose-900">Dismiss</button>
        </div>
    @endif

    {{-- Header Section --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-600 text-white shadow-md shadow-violet-500/20">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                        Phone Directory
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 border border-violet-200">
                            {{ number_format($summary['total']) }} Contacts
                        </span>
                    </h1>
                    <p class="text-xs text-slate-500">Centralized corporate directory for employees, clients, suppliers, and business associates.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Quick Important Filter Pill --}}
            <a
                href="{{ request()->fullUrlWithQuery(['important' => request('important') === '1' ? null : '1', 'page' => 1]) }}"
                class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold transition shadow-xs {{ request('important') === '1' ? 'border-amber-400 bg-amber-50 text-amber-800' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                title="{{ request('important') === '1' ? 'Show all contacts' : 'Show important contacts only' }}"
            >
                <svg class="h-4 w-4 {{ request('important') === '1' ? 'text-amber-500 fill-amber-500' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                </svg>
                <span>Important</span>
            </a>

            {{-- Column Visibility Dropdown --}}
            <div class="relative" x-data="{ colOpen: false }" @click.outside="colOpen = false">
                <button
                    type="button"
                    @click="colOpen = !colOpen"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition"
                    title="Toggle Visible Columns"
                >
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                    <span>Columns</span>
                </button>

                <div
                    x-show="colOpen"
                    x-cloak
                    x-transition
                    class="absolute right-0 z-30 mt-1 w-52 rounded-xl border border-slate-200 bg-white p-3 shadow-xl space-y-2 text-xs"
                >
                    <div class="font-semibold text-slate-900 border-b border-slate-100 pb-1.5 flex justify-between items-center">
                        <span>Visible Columns</span>
                        <button type="button" @click="resetColumns()" class="text-[10px] text-violet-600 hover:underline">Reset</button>
                    </div>
                    <template x-for="col in availableColumns" :key="col.id">
                        <label class="flex items-center gap-2 text-slate-700 hover:text-slate-900 cursor-pointer">
                            <input type="checkbox" x-model="visibleColumns[col.id]" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                            <span x-text="col.label"></span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- Export Menu --}}
            <div class="relative" x-data="{ exportOpen: false }" @click.outside="exportOpen = false">
                <button
                    type="button"
                    @click="exportOpen = !exportOpen"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition"
                >
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Export</span>
                    <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>

                <div
                    x-show="exportOpen"
                    x-cloak
                    x-transition
                    class="absolute right-0 z-30 mt-1 w-44 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl text-xs space-y-0.5"
                >
                    <a href="{{ route('phone-directory.export.excel', request()->query()) }}" class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-slate-700 hover:bg-violet-50 hover:text-violet-700 transition">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        <span>Export Excel (.xlsx)</span>
                    </a>
                    <a href="{{ route('phone-directory.export.csv', request()->query()) }}" class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-slate-700 hover:bg-violet-50 hover:text-violet-700 transition">
                        <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        <span>Export CSV</span>
                    </a>
                    <a href="{{ route('phone-directory.export.pdf', request()->query()) }}" target="_blank" class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-slate-700 hover:bg-violet-50 hover:text-violet-700 transition">
                        <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        <span>Export PDF</span>
                    </a>
                </div>
            </div>

            {{-- Import Button --}}
            <a
                href="{{ route('phone-directory.import.index') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition"
                title="Bulk Import Contacts via CSV or Excel"
            >
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                <span>Import</span>
            </a>

            {{-- Master Management --}}
            <a
                href="{{ route('phone-directory.departments.index') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition"
                title="Manage Departments and Categories"
            >
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Masters</span>
            </a>

            {{-- Add Contact Button (Primary Refrens Violet) --}}
            <a
                href="{{ route('phone-directory.contacts.create') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3.5 py-2 text-xs font-semibold text-white shadow-md shadow-violet-600/30 hover:bg-violet-700 transition"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                <span>Add Contact</span>
            </a>
        </div>
    </div>

    {{-- Summary Cards (Section 11) --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        {{-- Total --}}
        <a href="{{ route('phone-directory.index') }}" class="group block rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-xs hover:border-violet-300 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-50 text-violet-600 group-hover:bg-violet-600 group-hover:text-white transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </span>
            </div>
            <p class="mt-2 text-xl font-bold text-slate-900">{{ number_format($summary['total']) }}</p>
            <p class="text-[10px] text-slate-400">All active records</p>
        </a>

        {{-- Employees --}}
        <a href="{{ route('phone-directory.index', ['contact_type' => 'employee']) }}" class="group block rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-xs hover:border-indigo-300 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Employees</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </span>
            </div>
            <p class="mt-2 text-xl font-bold text-slate-900">{{ number_format($summary['employees']) }}</p>
            <p class="text-[10px] text-slate-400">Internal team</p>
        </a>

        {{-- Customers --}}
        <a href="{{ route('phone-directory.index', ['contact_type' => 'customer']) }}" class="group block rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-xs hover:border-emerald-300 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Customers</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                </span>
            </div>
            <p class="mt-2 text-xl font-bold text-slate-900">{{ number_format($summary['customers']) }}</p>
            <p class="text-[10px] text-slate-400">Buyers & clients</p>
        </a>

        {{-- Suppliers --}}
        <a href="{{ route('phone-directory.index', ['contact_type' => 'supplier']) }}" class="group block rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-xs hover:border-sky-300 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Suppliers</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-50 text-sky-600 group-hover:bg-sky-600 group-hover:text-white transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </span>
            </div>
            <p class="mt-2 text-xl font-bold text-slate-900">{{ number_format($summary['suppliers']) }}</p>
            <p class="text-[10px] text-slate-400">Vendors & partners</p>
        </a>

        {{-- Important Contacts --}}
        <a href="{{ route('phone-directory.index', ['important' => '1']) }}" class="group block rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-xs hover:border-amber-300 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Important</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition">
                    <svg class="h-4 w-4 fill-amber-500 text-amber-500" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                </span>
            </div>
            <p class="mt-2 text-xl font-bold text-slate-900">{{ number_format($summary['important']) }}</p>
            <p class="text-[10px] text-slate-400">Priority contacts</p>
        </a>

        {{-- Archived --}}
        <a href="{{ route('phone-directory.index', ['archive_view' => 'archived']) }}" class="group block rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-xs hover:border-slate-400 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Archived</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 group-hover:bg-slate-700 group-hover:text-white transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                </span>
            </div>
            <p class="mt-2 text-xl font-bold text-slate-900">{{ number_format($summary['archived']) }}</p>
            <p class="text-[10px] text-slate-400">Archived records</p>
        </a>
    </div>

    {{-- Main Table Card with Search & Filters --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">

        {{-- Filter & Search Form --}}
        <form method="GET" action="{{ route('phone-directory.index') }}" id="filterForm" class="border-b border-slate-200/70 bg-slate-50/50 p-3.5">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">

            {{-- Row 1: Global Search & Quick Toggles --}}
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                {{-- Global Search Bar --}}
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input
                        type="text"
                        name="search"
                        id="globalSearchInput"
                        value="{{ request('search') }}"
                        placeholder="Search Name, Phone, Email, Company, Designation, Department, City..."
                        class="w-full rounded-xl border border-slate-300 bg-white py-2 pl-9 pr-10 text-xs text-slate-800 placeholder-slate-400 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 shadow-xs"
                    >
                    @if(request('search'))
                        <button
                            type="button"
                            onclick="document.getElementById('globalSearchInput').value = ''; document.getElementById('filterForm').submit();"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600"
                            title="Clear search"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="filtersExpanded = !filtersExpanded"
                        class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold shadow-xs transition"
                        :class="filtersExpanded ? 'border-violet-400 bg-violet-50 text-violet-700' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
                    >
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        <span>Filter Options</span>
                        <svg class="h-3 w-3 text-slate-400 transition-transform" :class="filtersExpanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center gap-1 rounded-xl bg-violet-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-violet-700 transition"
                    >
                        <span>Apply</span>
                    </button>

                    @if(request()->anyFilled(['search', 'contact_type', 'department_id', 'designation', 'company_name', 'state', 'city', 'status', 'category_id', 'important']) || request('archive_view') === 'archived')
                        <a
                            href="{{ route('phone-directory.index') }}"
                            class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition"
                            title="Reset all filters"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </div>

            {{-- Row 2: Collapsible Deep Filters --}}
            <div x-show="filtersExpanded" x-cloak class="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6 border-t border-slate-200/60 pt-3">
                {{-- Contact Type --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Contact Type</label>
                    <select name="contact_type" class="w-full rounded-lg border-slate-300 py-1.5 pl-2.5 pr-7 text-xs text-slate-700 focus:border-violet-500 focus:ring-violet-500 shadow-xs">
                        <option value="">All Types</option>
                        @foreach($contactTypes as $key => $label)
                            <option value="{{ $key }}" @selected(request('contact_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Department --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Department</label>
                    <select name="department_id" class="w-full rounded-lg border-slate-300 py-1.5 pl-2.5 pr-7 text-xs text-slate-700 focus:border-violet-500 focus:ring-violet-500 shadow-xs">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected((string) request('department_id') === (string) $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- State --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">State</label>
                    <select name="state" class="w-full rounded-lg border-slate-300 py-1.5 pl-2.5 pr-7 text-xs text-slate-700 focus:border-violet-500 focus:ring-violet-500 shadow-xs">
                        <option value="">All States</option>
                        @foreach($states as $code => $st)
                            <option value="{{ $st }}" @selected(request('state') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- City --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">City</label>
                    <input
                        type="text"
                        name="city"
                        value="{{ request('city') }}"
                        placeholder="Filter city..."
                        list="cityList"
                        class="w-full rounded-lg border-slate-300 py-1.5 px-2.5 text-xs text-slate-700 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                    >
                    <datalist id="cityList">
                        @foreach($cities as $c)
                            <option value="{{ $c }}"></option>
                        @endforeach
                    </datalist>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border-slate-300 py-1.5 pl-2.5 pr-7 text-xs text-slate-700 focus:border-violet-500 focus:ring-violet-500 shadow-xs">
                        <option value="">All Statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>

                {{-- Archive View --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Archive View</label>
                    <select name="archive_view" class="w-full rounded-lg border-slate-300 py-1.5 pl-2.5 pr-7 text-xs text-slate-700 focus:border-violet-500 focus:ring-violet-500 shadow-xs">
                        <option value="active" @selected(request('archive_view', 'active') === 'active')>Active Contacts</option>
                        <option value="archived" @selected(request('archive_view') === 'archived')>Archived Contacts</option>
                        <option value="all" @selected(request('archive_view') === 'all')>All (Active + Archived)</option>
                    </select>
                </div>
            </div>
        </form>

        {{-- Bulk Action Bar --}}
        <div
            x-show="selectedIds.length > 0"
            x-cloak
            class="flex flex-wrap items-center justify-between gap-3 bg-violet-50/90 border-b border-violet-200 px-4 py-2.5 transition"
        >
            <div class="flex items-center gap-2">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-violet-600 text-[10px] font-bold text-white" x-text="selectedIds.length"></span>
                <span class="text-xs font-semibold text-violet-900">contact(s) selected</span>
                <button type="button" @click="selectedIds = []" class="ml-2 text-xs font-medium text-violet-700 hover:underline">Clear selection</button>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="executeBulkAction('archive')"
                    class="inline-flex items-center gap-1 rounded-lg border border-violet-300 bg-white px-2.5 py-1 text-xs font-medium text-violet-800 shadow-xs hover:bg-violet-100 transition"
                >
                    <svg class="h-3.5 w-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                    <span>Archive Selected</span>
                </button>

                <button
                    type="button"
                    @click="exportSelectedCsv()"
                    class="inline-flex items-center gap-1 rounded-lg border border-violet-300 bg-white px-2.5 py-1 text-xs font-medium text-violet-800 shadow-xs hover:bg-violet-100 transition"
                >
                    <svg class="h-3.5 w-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    <span>Export Selected</span>
                </button>

                <button
                    type="button"
                    @click="executeBulkAction('delete')"
                    class="inline-flex items-center gap-1 rounded-lg border border-rose-300 bg-white px-2.5 py-1 text-xs font-medium text-rose-700 shadow-xs hover:bg-rose-50 transition"
                >
                    <svg class="h-3.5 w-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    <span>Delete Selected</span>
                </button>
            </div>
        </div>

        {{-- Contacts Table (Refrens Inspired) --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50/80 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                    <tr>
                        {{-- Select All Checkbox --}}
                        <th scope="col" class="w-10 px-3.5 py-3 text-center">
                            <input
                                type="checkbox"
                                @change="toggleSelectAll($event)"
                                :checked="selectedIds.length > 0 && selectedIds.length === pageIds.length"
                                class="rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                            >
                        </th>

                        {{-- Star / Important --}}
                        <th scope="col" class="w-8 px-1 py-3 text-center" title="Important Indicator">
                            <svg class="h-3.5 w-3.5 mx-auto text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                        </th>

                        {{-- Profile Avatar --}}
                        <th scope="col" x-show="visibleColumns.profile" class="w-12 px-2 py-3 text-center">
                            Profile
                        </th>

                        {{-- Contact Name --}}
                        <th scope="col" class="px-3.5 py-3 text-left">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'contact_name', 'direction' => ($sort === 'contact_name' && $direction === 'asc') ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1 group">
                                <span>Contact Name</span>
                                @if($sort === 'contact_name')
                                    <span class="text-violet-600 font-bold">{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                                @else
                                    <span class="text-slate-300 group-hover:text-slate-500">↕</span>
                                @endif
                            </a>
                        </th>

                        {{-- Contact Type --}}
                        <th scope="col" x-show="visibleColumns.contact_type" class="px-3 py-3 text-left">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'contact_type', 'direction' => ($sort === 'contact_type' && $direction === 'asc') ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1 group">
                                <span>Type</span>
                                @if($sort === 'contact_type')
                                    <span class="text-violet-600 font-bold">{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Designation --}}
                        <th scope="col" x-show="visibleColumns.designation" class="px-3 py-3 text-left">
                            Designation
                        </th>

                        {{-- Department --}}
                        <th scope="col" x-show="visibleColumns.department" class="px-3 py-3 text-left">
                            Department
                        </th>

                        {{-- Company --}}
                        <th scope="col" x-show="visibleColumns.company" class="px-3 py-3 text-left">
                            Company
                        </th>

                        {{-- Phone --}}
                        <th scope="col" x-show="visibleColumns.phone" class="px-3 py-3 text-left">
                            Primary Mobile
                        </th>

                        {{-- Email --}}
                        <th scope="col" x-show="visibleColumns.email" class="px-3 py-3 text-left">
                            Email
                        </th>

                        {{-- Location --}}
                        <th scope="col" x-show="visibleColumns.location" class="px-3 py-3 text-left">
                            Location
                        </th>

                        {{-- Actions --}}
                        <th scope="col" class="px-4 py-3 text-right">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($contacts as $contact)
                        <tr class="hover:bg-violet-50/40 transition-colors {{ $contact->is_important ? 'bg-amber-50/20' : '' }}">
                            {{-- Checkbox --}}
                            <td class="w-10 px-3.5 py-2.5 text-center">
                                <input
                                    type="checkbox"
                                    value="{{ $contact->id }}"
                                    x-model="selectedIds"
                                    class="rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                >
                            </td>

                            {{-- Star / Important Toggle --}}
                            <td class="w-8 px-1 py-2.5 text-center">
                                <button
                                    type="button"
                                    @click="toggleImportant({{ $contact->id }})"
                                    class="text-slate-300 hover:text-amber-500 transition-colors focus:outline-none"
                                    title="{{ $contact->is_important ? 'Remove from Important' : 'Mark as Important' }}"
                                >
                                    <svg
                                        class="h-4 w-4 {{ $contact->is_important ? 'text-amber-400 fill-amber-400' : 'text-slate-300' }}"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                    </svg>
                                </button>
                            </td>

                            {{-- Profile Avatar --}}
                            <td x-show="visibleColumns.profile" class="w-12 px-2 py-2.5 text-center">
                                @if($contact->profile_photo_url)
                                    <img
                                        src="{{ $contact->profile_photo_url }}"
                                        alt="{{ $contact->contact_name }}"
                                        class="h-8 w-8 mx-auto rounded-full object-cover border border-slate-200 shadow-2xs"
                                    >
                                @else
                                    <div class="h-8 w-8 mx-auto flex items-center justify-center rounded-full text-xs font-bold border shadow-2xs {{ $contact->avatar_bg_class }}">
                                        {{ $contact->initials }}
                                    </div>
                                @endif
                            </td>

                            {{-- Contact Name --}}
                            <td class="px-3.5 py-2.5">
                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="openQuickView({{ $contact->id }})"
                                        class="font-semibold text-slate-900 hover:text-violet-700 text-left cursor-pointer hover:underline focus:outline-none"
                                    >
                                        {{ $contact->contact_name }}
                                    </button>
                                    @if($contact->isArchived())
                                        <span class="rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-medium text-rose-700 border border-rose-200">Archived</span>
                                    @endif
                                </div>
                                @if($contact->company_name)
                                    <div class="text-[11px] text-slate-500 truncate max-w-[200px]" title="{{ $contact->company_name }}">
                                        {{ $contact->company_name }}
                                    </div>
                                @endif
                            </td>

                            {{-- Contact Type --}}
                            <td x-show="visibleColumns.contact_type" class="px-3 py-2.5">
                                @php
                                    $badgeClasses = match($contact->contact_type) {
                                        'employee' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'customer' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'supplier' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'dealer' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'distributor' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'business_partner' => 'bg-teal-50 text-teal-700 border-teal-200',
                                        'service_provider' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                        'management' => 'bg-violet-50 text-violet-700 border-violet-200',
                                        'government_official' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border {{ $badgeClasses }}">
                                    {{ \App\Domains\PhoneDirectory\Models\PhoneDirectoryContact::CONTACT_TYPES[$contact->contact_type] ?? ucfirst($contact->contact_type) }}
                                </span>
                            </td>

                            {{-- Designation --}}
                            <td x-show="visibleColumns.designation" class="px-3 py-2.5 text-slate-600">
                                <span class="truncate block max-w-[150px]" title="{{ $contact->designation }}">{{ $contact->designation ?: '—' }}</span>
                            </td>

                            {{-- Department --}}
                            <td x-show="visibleColumns.department" class="px-3 py-2.5 text-slate-600">
                                @if($contact->department)
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                        {{ $contact->department->name }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Company --}}
                            <td x-show="visibleColumns.company" class="px-3 py-2.5 text-slate-600">
                                <span class="truncate block max-w-[150px]" title="{{ $contact->company_name }}">{{ $contact->company_name ?: '—' }}</span>
                            </td>

                            {{-- Phone --}}
                            <td x-show="visibleColumns.phone" class="px-3 py-2.5">
                                <div class="flex items-center gap-1.5">
                                    <a
                                        href="tel:{{ $contact->clean_mobile }}"
                                        class="font-medium text-slate-800 hover:text-violet-700 transition"
                                        title="Click to call"
                                    >
                                        {{ $contact->mobile_number }}
                                    </a>
                                </div>
                            </td>

                            {{-- Email --}}
                            <td x-show="visibleColumns.email" class="px-3 py-2.5">
                                @if($contact->email)
                                    <a
                                        href="mailto:{{ $contact->email }}"
                                        class="text-slate-600 hover:text-violet-700 truncate block max-w-[180px]"
                                        title="{{ $contact->email }}"
                                    >
                                        {{ $contact->email }}
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Location --}}
                            <td x-show="visibleColumns.location" class="px-3 py-2.5 text-slate-600">
                                <span class="truncate block max-w-[140px]" title="{{ $contact->formatted_location }}">{{ $contact->formatted_location }}</span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    {{-- Quick Call Action --}}
                                    @if($contact->mobile_number)
                                        <a
                                            href="tel:{{ $contact->clean_mobile }}"
                                            class="p-1 rounded-lg text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 transition"
                                            title="Call {{ $contact->mobile_number }}"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                            </svg>
                                        </a>

                                        {{-- WhatsApp Action --}}
                                        <a
                                            href="https://wa.me/{{ $contact->clean_whatsapp }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="p-1 rounded-lg text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 transition"
                                            title="Message on WhatsApp"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.698.056-1.127-.082-.26-.084-.594-.213-1.025-.399-1.821-.787-3.007-2.637-3.1-2.759-.092-.123-.743-.988-.743-1.884 0-.897.469-1.339.636-1.521.168-.182.366-.228.488-.228.122 0 .245.002.351.007.112.006.262-.043.41.312.152.365.519 1.267.564 1.36.046.092.076.2.015.323-.061.122-.092.198-.183.305-.092.106-.193.237-.276.319-.092.091-.188.19-.081.374.107.182.476.786 1.021 1.272.702.626 1.294.819 1.477.91.183.092.29.076.397-.046.107-.122.457-.533.58-.716.121-.182.244-.152.411-.091.167.061 1.059.5 1.242.591.183.092.305.137.35.214.045.076.045.441-.099.846z"/>
                                            </svg>
                                        </a>
                                    @endif

                                    {{-- Quick Email Action --}}
                                    @if($contact->email)
                                        <a
                                            href="mailto:{{ $contact->email }}"
                                            class="p-1 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition"
                                            title="Send Email"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                            </svg>
                                        </a>
                                    @endif

                                    {{-- View Details Modal --}}
                                    <button
                                        type="button"
                                        @click="openQuickView({{ $contact->id }})"
                                        class="p-1 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition"
                                        title="View Complete Profile"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </button>

                                    {{-- Edit --}}
                                    <a
                                        href="{{ route('phone-directory.contacts.edit', $contact) }}"
                                        class="p-1 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-violet-700 transition"
                                        title="Edit Contact"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                    </a>

                                    {{-- Archive / Restore --}}
                                    <form method="POST" action="{{ route('phone-directory.contacts.archive', $contact) }}" class="inline" onsubmit="return confirm('{{ $contact->isArchived() ? 'Restore this contact?' : 'Archive this contact?' }}');">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="p-1 rounded-lg {{ $contact->isArchived() ? 'text-emerald-600 hover:bg-emerald-50' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }} transition"
                                            title="{{ $contact->isArchived() ? 'Restore Contact' : 'Archive Contact' }}"
                                        >
                                            @if($contact->isArchived())
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                                            @else
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                                            @endif
                                        </button>
                                    </form>

                                    {{-- More Actions Dropdown --}}
                                    <div class="relative" x-data="{ moreOpen: false }" @click.outside="moreOpen = false">
                                        <button
                                            type="button"
                                            @click="moreOpen = !moreOpen"
                                            class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                                        </button>

                                        <div
                                            x-show="moreOpen"
                                            x-cloak
                                            x-transition
                                            class="absolute right-0 z-20 mt-1 w-44 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl text-xs space-y-0.5 text-left"
                                        >
                                            <a href="{{ route('phone-directory.contacts.show', $contact) }}" class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-slate-700 hover:bg-violet-50 hover:text-violet-700">
                                                <span>Full Page Profile</span>
                                            </a>
                                            <button
                                                type="button"
                                                @click="toggleStatus({{ $contact->id }}); moreOpen = false"
                                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-slate-700 hover:bg-violet-50 hover:text-violet-700"
                                            >
                                                <span>{{ $contact->status === 'active' ? 'Mark Inactive' : 'Mark Active' }}</span>
                                            </button>
                                            <form method="POST" action="{{ route('phone-directory.contacts.destroy', $contact) }}" onsubmit="return confirm('Permanently delete this contact?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-rose-600 hover:bg-rose-50">
                                                    <span>Delete Contact</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-12 text-center text-slate-500">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                </div>
                                <h3 class="mt-3 text-sm font-semibold text-slate-900">No contacts found</h3>
                                <p class="mt-1 text-xs text-slate-500">Try adjusting your search terms or filter criteria.</p>
                                <div class="mt-4">
                                    <a href="{{ route('phone-directory.contacts.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-violet-700">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                        <span>Add First Contact</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table Footer & Pagination Controls (Section 6) --}}
        <div class="flex flex-col gap-3 border-t border-slate-200/80 bg-slate-50/50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3 text-xs text-slate-600">
                <span>Showing <strong>{{ $contacts->firstItem() ?? 0 }}</strong> to <strong>{{ $contacts->lastItem() ?? 0 }}</strong> of <strong>{{ number_format($contacts->total()) }}</strong> contacts</span>

                <div class="flex items-center gap-1.5 border-l border-slate-300 pl-3">
                    <label for="perPageSelect" class="text-[11px] text-slate-500">Rows per page:</label>
                    <select
                        id="perPageSelect"
                        onchange="window.location.href = '{{ request()->fullUrlWithQuery(['per_page' => 'PLACEHOLDER', 'page' => 1]) }}'.replace('PLACEHOLDER', this.value)"
                        class="rounded-lg border-slate-300 py-1 pl-2 pr-6 text-xs text-slate-700 focus:border-violet-500 focus:ring-violet-500 shadow-2xs"
                    >
                        @foreach([10, 25, 50, 100] as $opt)
                            <option value="{{ $opt }}" @selected($perPage == $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                {{ $contacts->links() }}
            </div>
        </div>
    </div>

    {{-- Quick View Modal (Slide-over / Modal) --}}
    <div
        x-show="quickViewOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        @keydown.escape.window="quickViewOpen = false"
    >
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="quickViewOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="quickViewOpen"
                x-transition
                class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
                @click.stop
            >
                {{-- Modal Header --}}
                <div class="bg-gradient-to-r from-violet-600 to-indigo-700 p-5 text-white">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <template x-if="quickContact.profile_photo_url">
                                <img :src="quickContact.profile_photo_url" class="h-14 w-14 rounded-full object-cover border-2 border-white shadow-md">
                            </template>
                            <template x-if="!quickContact.profile_photo_url">
                                <div class="h-14 w-14 rounded-full flex items-center justify-center text-lg font-bold border-2 border-white shadow-md bg-white/20 text-white" x-text="quickContact.initials"></div>
                            </template>
                            <div>
                                <h3 class="text-lg font-bold" x-text="quickContact.contact_name"></h3>
                                <p class="text-xs text-violet-200" x-text="quickContact.designation || 'Contact'"></p>
                                <p class="text-xs font-semibold text-white/90" x-text="quickContact.company_name || ''"></p>
                            </div>
                        </div>

                        <button type="button" @click="quickViewOpen = false" class="rounded-lg bg-white/10 p-1.5 text-white hover:bg-white/20">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {{-- Quick Action Buttons in Banner --}}
                    <div class="mt-4 flex items-center gap-2 pt-2 border-t border-white/20">
                        <template x-if="quickContact.clean_mobile">
                            <a :href="'tel:' + quickContact.clean_mobile" class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm hover:bg-emerald-50 transition">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                <span>Call</span>
                            </a>
                        </template>

                        <template x-if="quickContact.clean_whatsapp">
                            <a :href="'https://wa.me/' + quickContact.clean_whatsapp" target="_blank" class="inline-flex items-center gap-1 rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-600 transition">
                                <span>WhatsApp</span>
                            </a>
                        </template>

                        <template x-if="quickContact.email">
                            <a :href="'mailto:' + quickContact.email" class="inline-flex items-center gap-1 rounded-lg bg-white/20 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-white/30 transition">
                                <span>Email</span>
                            </a>
                        </template>
                    </div>
                </div>

                {{-- Modal Body --}}
                <div class="p-5 space-y-4 max-h-[60vh] overflow-y-auto text-xs">
                    {{-- Key Badges --}}
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full bg-violet-100 px-2.5 py-1 text-violet-800 font-semibold" x-text="quickContact.contact_type_label"></span>
                        <template x-if="quickContact.department_name && quickContact.department_name !== '—'">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-700 font-semibold" x-text="'Dept: ' + quickContact.department_name"></span>
                        </template>
                        <template x-if="quickContact.category_name && quickContact.category_name !== '—'">
                            <span class="rounded-full bg-purple-100 px-2.5 py-1 text-purple-800 font-semibold" x-text="'Category: ' + quickContact.category_name"></span>
                        </template>
                        <template x-if="quickContact.is_important">
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-amber-800 font-semibold">★ Important</span>
                        </template>
                    </div>

                    {{-- Contact Numbers & Emails --}}
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5 space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Primary Mobile</span>
                                <span class="font-semibold text-slate-800" x-text="quickContact.mobile_number || '—'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Alt Mobile</span>
                                <span class="text-slate-700" x-text="quickContact.alternate_mobile || '—'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Office Phone</span>
                                <span class="text-slate-700" x-text="quickContact.office_phone ? quickContact.office_phone + (quickContact.extension_number ? ' Ext: ' + quickContact.extension_number : '') : '—'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">WhatsApp</span>
                                <span class="text-slate-700" x-text="quickContact.whatsapp_number || quickContact.mobile_number || '—'"></span>
                            </div>
                        </div>

                        <div class="border-t border-slate-200/60 pt-2">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Email Address</span>
                            <span class="font-medium text-slate-800" x-text="quickContact.email || '—'"></span>
                        </div>
                    </div>

                    {{-- Address Details --}}
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Address</span>
                        <p class="text-slate-800 font-medium" x-text="quickContact.address || '—'"></p>
                        <p class="text-slate-600" x-text="quickContact.formatted_location + (quickContact.pincode ? ' - ' + quickContact.pincode : '')"></p>
                    </div>

                    {{-- Notes --}}
                    <template x-if="quickContact.notes">
                        <div class="rounded-xl border border-slate-100 bg-amber-50/40 p-3 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-amber-700 block">Notes</span>
                            <p class="text-slate-700 whitespace-pre-wrap" x-text="quickContact.notes"></p>
                        </div>
                    </template>
                </div>

                {{-- Modal Footer --}}
                <div class="border-t border-slate-100 bg-slate-50 p-3.5 flex justify-end gap-2">
                    <template x-if="quickContact.id">
                        <a :href="'{{ url('phone-directory/contacts') }}/' + quickContact.id + '/edit'" class="inline-flex items-center gap-1 rounded-xl bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-violet-700">
                            <span>Edit Contact</span>
                        </a>
                    </template>
                    <button type="button" @click="quickViewOpen = false" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function phoneDirectoryManager() {
    return {
        selectedIds: [],
        pageIds: @json($contacts->pluck('id')),
        filtersExpanded: {{ (request()->anyFilled(['contact_type', 'department_id', 'designation', 'company_name', 'state', 'city', 'status']) || request('archive_view') === 'archived') ? 'true' : 'false' }},
        quickViewOpen: false,
        quickContact: {},

        availableColumns: [
            { id: 'profile', label: 'Profile Avatar' },
            { id: 'contact_type', label: 'Contact Type' },
            { id: 'designation', label: 'Designation' },
            { id: 'department', label: 'Department' },
            { id: 'company', label: 'Company' },
            { id: 'phone', label: 'Primary Mobile' },
            { id: 'email', label: 'Email Address' },
            { id: 'location', label: 'Location (City, State)' },
        ],

        visibleColumns: {
            profile: true,
            contact_type: true,
            designation: true,
            department: true,
            company: true,
            phone: true,
            email: true,
            location: true,
        },

        init() {
            // Restore column preferences from localStorage
            try {
                const saved = localStorage.getItem('dms_pd_cols');
                if (saved) {
                    this.visibleColumns = Object.assign(this.visibleColumns, JSON.parse(saved));
                }
            } catch (e) {}

            this.$watch('visibleColumns', (val) => {
                try {
                    localStorage.setItem('dms_pd_cols', JSON.stringify(val));
                } catch (e) {}
            });
        },

        resetColumns() {
            this.availableColumns.forEach(c => {
                this.visibleColumns[c.id] = true;
            });
        },

        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedIds = [...this.pageIds];
            } else {
                this.selectedIds = [];
            }
        },

        async openQuickView(id) {
            try {
                const res = await fetch(`{{ url('phone-directory/contacts') }}/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    this.quickContact = data.contact;
                    this.quickViewOpen = true;
                }
            } catch (e) {
                console.error(e);
            }
        },

        async toggleImportant(id) {
            try {
                const res = await fetch(`{{ url('phone-directory/contacts') }}/${id}/toggle-important`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (e) {
                console.error(e);
            }
        },

        async toggleStatus(id) {
            try {
                const res = await fetch(`{{ url('phone-directory/contacts') }}/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (e) {
                console.error(e);
            }
        },

        async executeBulkAction(action) {
            if (!confirm(`Are you sure you want to ${action} ${this.selectedIds.length} selected contact(s)?`)) {
                return;
            }

            try {
                const res = await fetch(`{{ route('phone-directory.contacts.bulk-action') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        action: action,
                        ids: this.selectedIds
                    })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Bulk action failed');
                }
            } catch (e) {
                alert('An error occurred while executing bulk action');
            }
        },

        exportSelectedCsv() {
            if (this.selectedIds.length === 0) return;
            const idsParam = this.selectedIds.join(',');
            window.location.href = `{{ route('phone-directory.export.csv') }}?ids=${idsParam}`;
        }
    };
}
</script>
@endpush
@endsection
