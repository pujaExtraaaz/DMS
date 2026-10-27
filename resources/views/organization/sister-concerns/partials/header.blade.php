@props([
    'activeTab' => 'dashboard',
    'tabCounts' => [],
])

@php
    $bgCount = $tabCounts['business_groups'] ?? \App\Domains\Organization\Models\BusinessGroup::count();
    $scCount = $tabCounts['sister_concerns'] ?? \App\Domains\Organization\Models\Company::count();
    $txCount = $tabCounts['transactions'] ?? 0;
@endphp

<div class="space-y-6 mb-6">
    {{-- A. Header Banner (Purple Gradient) --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-800 via-indigo-800 to-purple-900 p-6 sm:p-8 text-white shadow-xl">
        <div class="absolute inset-0 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px] opacity-10"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-white shadow-inner">
                    <svg class="h-7 w-7 text-purple-200" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">Sister Concern Management</h1>
                    <p class="mt-1 text-sm sm:text-base text-purple-200 font-medium">Business Groups • Sister Concerns • Inter-Company Transactions</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @if($activeTab === 'sister-concerns')
                    <button type="button"
                            @click="$dispatch('open-add-company-modal')"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-purple-800 shadow-md hover:bg-purple-50 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Company</span>
                    </button>
                @elseif($activeTab === 'transactions')
                    <button type="button"
                            @click="$dispatch('open-add-transaction-modal')"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-purple-800 shadow-md hover:bg-purple-50 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>New Transaction</span>
                    </button>
                @elseif($activeTab === 'business-groups')
                    <button type="button"
                            @click="$dispatch('open-add-group-modal')"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-purple-800 shadow-md hover:bg-purple-50 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Group</span>
                    </button>
                @else
                    <a href="{{ route('organization.business-groups.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-purple-800 shadow-md hover:bg-purple-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Group</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- B. Navigation Tabs --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6 sm:space-x-8 overflow-x-auto scrollbar-none" aria-label="Sister Concern Navigation Tabs">
            {{-- Tab 1: Dashboard --}}
            <a href="{{ route('organization.sister-concerns.dashboard') }}"
               class="whitespace-nowrap pb-3.5 px-1 border-b-2 text-sm font-semibold flex items-center gap-2 transition {{ $activeTab === 'dashboard' ? 'border-purple-600 text-purple-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <svg class="w-4 h-4 {{ $activeTab === 'dashboard' ? 'text-purple-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Dashboard</span>
            </a>

            {{-- Tab 2: Business Groups --}}
            <a href="{{ route('organization.business-groups.index') }}"
               class="whitespace-nowrap pb-3.5 px-1 border-b-2 text-sm font-semibold flex items-center gap-2 transition {{ $activeTab === 'business-groups' ? 'border-purple-600 text-purple-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <svg class="w-4 h-4 {{ $activeTab === 'business-groups' ? 'text-purple-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span>Business Groups</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $activeTab === 'business-groups' ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $bgCount }}
                </span>
            </a>

            {{-- Tab 3: Sister Concerns --}}
            <a href="{{ route('organization.sister-concerns.companies') }}"
               class="whitespace-nowrap pb-3.5 px-1 border-b-2 text-sm font-semibold flex items-center gap-2 transition {{ $activeTab === 'sister-concerns' ? 'border-purple-600 text-purple-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <svg class="w-4 h-4 {{ $activeTab === 'sister-concerns' ? 'text-purple-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span>Sister Concerns</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $activeTab === 'sister-concerns' ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $scCount }}
                </span>
            </a>

            {{-- Tab 4: Transactions --}}
            <a href="{{ route('organization.sister-concerns.transactions') }}"
               class="whitespace-nowrap pb-3.5 px-1 border-b-2 text-sm font-semibold flex items-center gap-2 transition {{ $activeTab === 'transactions' ? 'border-purple-600 text-purple-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <svg class="w-4 h-4 {{ $activeTab === 'transactions' ? 'text-purple-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>Transactions</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $activeTab === 'transactions' ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $txCount }}
                </span>
            </a>
        </nav>
    </div>
</div>

