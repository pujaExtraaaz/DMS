@extends('layouts.dms')
@section('title', 'Sister Concern Management · Sister Concerns')

@section('content')
<div id="companies-listing-container" data-dynamic-container x-data="sisterConcernCompaniesPage(@js($businessGroups ?? []), @js($states ?? []), @js($financialYears ?? []))" @open-add-company-modal.window="openAddModal()">
    {{-- Header Banner & Navigation Tabs --}}
    @include('organization.sister-concerns.partials.header', [
        'activeTab' => 'sister-concerns',
        'tabCounts' => $tabCounts ?? [],
    ])

    {{-- Main Content Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        {{-- Toolbar: Search, Filters & Add Button --}}
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                {{-- Search Input --}}
                <form method="GET" action="{{ route('organization.sister-concerns.companies') }}" class="relative w-full sm:w-72">
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Search Company, GSTIN, State..."
                           class="w-full rounded-xl border-slate-200 pl-3 pr-9 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                    @if(!empty($search))
                        <a href="{{ route('organization.sister-concerns.companies', array_filter(['business_group_id' => $selectedGroup])) }}"
                           title="Clear search"
                           class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </a>
                    @endif
                </form>

                {{-- Business Group Filter Dropdown --}}
                <form method="GET" action="{{ route('organization.sister-concerns.companies') }}">
                    @if(!empty($search))
                        <input type="hidden" name="search" value="{{ $search }}">
                    @endif
                    <select name="business_group_id"
                            onchange="this.form.submit()"
                            class="rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs text-slate-700 font-medium">
                        <option value="">All Business Groups</option>
                        @foreach($businessGroups as $bg)
                            <option value="{{ $bg->id }}" @selected(($selectedGroup ?? '') == $bg->id)>{{ $bg->name }}</option>
                        @endforeach
                    </select>
                </form>

                {{-- Total Count Badge --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                    <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Total: {{ $items->total() }} {{ Str::plural('Company', $items->total()) }}
                </span>
            </div>

        </div>

        {{-- Table Container --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[11px] tracking-wider">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left w-14">Logo</th>
                        <th scope="col" class="px-4 py-3 text-left">Company Name & Code</th>
                        <th scope="col" class="px-4 py-3 text-left">Company Type</th>
                        <th scope="col" class="px-4 py-3 text-left">Business Group</th>
                        <th scope="col" class="px-4 py-3 text-left">Tax ID (GSTIN / PAN)</th>
                        <th scope="col" class="px-4 py-3 text-left">Location (HQ)</th>
                        <th scope="col" class="px-4 py-3 text-left">Branches / WH</th>
                        <th scope="col" class="px-4 py-3 text-left">Relationship</th>
                        <th scope="col" class="px-4 py-3 text-left">Status</th>
                        <th scope="col" class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($items as $company)
                        <tr class="hover:bg-purple-50/20 transition-colors">
                            {{-- Logo Emoji / Badge --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="w-9 h-9 rounded-xl bg-purple-50 border border-purple-100 text-purple-700 flex items-center justify-center text-lg shadow-2xs font-emoji">
                                    {{ $company->logo_emoji ?: '🏢' }}
                                </div>
                            </td>

                            {{-- Company Name & Code --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-slate-900 text-sm">{{ $company->name }}</span>
                                    @if($company->is_default)
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">HQ</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                    <span class="font-mono bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded border border-slate-200 text-[10px]">{{ $company->code }}</span>
                                    @if($company->legal_name && $company->legal_name !== $company->name)
                                        <span class="text-slate-400">•</span>
                                        <span class="text-slate-500 truncate max-w-[180px]" title="{{ $company->legal_name }}">{{ $company->legal_name }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Company Type --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-medium text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md text-[11px]">
                                    {{ $company->company_type ?: 'Private Limited' }}
                                </span>
                            </td>

                            {{-- Business Group --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($company->businessGroup)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                        <svg class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        {{ $company->businessGroup->name }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">None</span>
                                @endif
                            </td>

                            {{-- Tax ID --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-mono text-[11px] text-slate-800 font-semibold">{{ $company->gstin ?: '—' }}</div>
                                @if($company->pan)
                                    <div class="font-mono text-[10px] text-slate-500">PAN: {{ $company->pan }}</div>
                                @endif
                            </td>

                            {{-- Location --}}
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                @if($company->city || $company->state)
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>{{ $company->city ? $company->city . ', ' : '' }}{{ $company->state ?? $company->country }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Branches / Warehouses --}}
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                <span>{{ $company->branches_count_manual > 0 ? $company->branches_count_manual : $company->branches->count() }} branches</span>
                                <span class="text-slate-300">•</span>
                                <span>{{ $company->warehouses->count() }} WH</span>
                            </td>

                            {{-- Relationship & Shareholding --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    {{ $company->relationship_type ?: 'Sister Concern' }}
                                </span>
                                @if($company->shareholding_percentage > 0)
                                    <div class="text-[10px] text-slate-500 mt-0.5 font-medium">{{ number_format($company->shareholding_percentage, 1) }}% Stake</div>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <x-ui.badge :variant="$company->is_active ? 'emerald' : 'gray'">
                                    {{ $company->is_active ? 'Active' : 'Inactive' }}
                                </x-ui.badge>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- View Action --}}
                                    <button type="button"
                                            @click="openViewModal({{ json_encode($company) }})"
                                            class="inline-flex items-center gap-1 font-semibold rounded-lg px-2.5 py-1.5 text-xs text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 shadow-2xs transition"
                                            title="View Sister Concern Details"
                                            aria-label="View Sister Concern Details">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>View</span>
                                    </button>

                                    {{-- Edit Action --}}
                                    <button type="button"
                                            @click="openEditModal({{ json_encode($company) }})"
                                            class="inline-flex items-center gap-1 font-semibold rounded-lg px-2.5 py-1.5 text-xs text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 shadow-2xs transition"
                                            title="Edit Sister Concern"
                                            aria-label="Edit Sister Concern">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        <span>Edit</span>
                                    </button>

                                    {{-- Delete Action --}}
                                    @if($company->is_default)
                                        <button type="button"
                                                disabled
                                                class="inline-flex items-center gap-1 font-medium rounded-lg opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border border-slate-200 px-2 py-1.5 text-xs"
                                                title="Default HQ company cannot be deleted.">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </button>
                                    @else
                                        <button type="button"
                                                @click="promptDelete('{{ $company->id }}', '{{ addslashes($company->name) }}', '{{ addslashes($company->code) }}')"
                                                class="inline-flex items-center gap-1 font-medium rounded-lg text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 px-2.5 py-1.5 text-xs transition"
                                                title="Delete Sister Concern">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-slate-500">
                                <div class="max-w-sm mx-auto text-center">
                                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900">No Sister Concerns Found</h4>
                                    <p class="text-xs text-slate-500 mt-1">Try adjusting your filters or add a new sister concern company.</p>
                                    <button type="button"
                                            @click="openAddModal()"
                                            class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-3.5 py-1.5 text-xs font-semibold shadow-xs transition">
                                        + Add New Sister Concern
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($items->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $items->links() }}
            </div>
        @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL 1: ADD / EDIT SISTER CONCERN MODAL (All 5 Required Sections)        --}}
    {{-- ========================================================================= --}}
    <template x-teleport="body">
        <div x-show="isFormModalOpen"
             x-cloak
             class="fixed inset-0 z-[100] overflow-y-auto"
             aria-labelledby="company-modal-title"
             role="dialog"
             aria-modal="true"
             @keydown.escape.window="closeFormModal()">

            {{-- Fullscreen Backdrop --}}
            <div x-show="isFormModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="closeFormModal()"></div>

            <div class="relative flex min-h-screen items-center justify-center p-4 sm:p-6 lg:p-8 pointer-events-none">
                {{-- Dialog Panel --}}
                <div x-show="isFormModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="pointer-events-auto relative w-full max-h-[88vh] flex flex-col bg-white rounded-2xl text-left shadow-2xl border border-slate-200 overflow-hidden my-auto"
                     style="width: min(1100px, calc(100vw - 32px)); max-width: min(1100px, calc(100vw - 32px));"
                     @click.stop>

                {{-- Fixed Modal Header --}}
                <div class="shrink-0 px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white/95 backdrop-blur-xs sticky top-0 z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-100 text-purple-700 flex items-center justify-center text-xl shadow-2xs font-emoji">
                            <span x-text="form.logo_emoji || '🏢'"></span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900" id="company-modal-title" x-text="modalMode === 'add' ? 'Add Sister Concern' : 'Edit Sister Concern'"></h3>
                            <p class="text-xs text-slate-500" x-text="modalMode === 'add' ? 'Create a corporate entity, register tax details, and link sister concern relationships' : 'Update company information, tax credentials, and operational parameters'"></p>
                        </div>
                    </div>
                    <button type="button"
                            @click="closeFormModal()"
                            class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                            title="Close modal">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Form Shell with Scrollable Body and Sticky Footer --}}
                <form @submit.prevent="submitForm()" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">

                        {{-- ============================================================= --}}
                        {{-- 1. COMPANY INFORMATION                                        --}}
                        {{-- ============================================================= --}}
                        <div>
                            <div class="flex items-center justify-between pb-2 mb-4 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section 1</span>
                                    <h4 class="text-xs font-bold uppercase tracking-wide text-slate-800">Company Information</h4>
                                </div>
                                <span class="text-[11px] text-slate-400 font-normal">* Indicates required fields</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {{-- Company Name --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Company Name <span class="text-red-500">*</span></label>
                                    <input type="text"
                                           x-model="form.name"
                                           placeholder="e.g., Avit Digital Private Limited"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :class="errors.name ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.name" class="text-[11px] text-red-600 mt-0.5" x-text="errors.name"></p>
                                </div>

                                {{-- Company Code --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Company Code <span class="text-red-500">*</span></label>
                                    <input type="text"
                                           x-model="form.code"
                                           placeholder="e.g., CMP-0001"
                                           class="w-full rounded-xl border-slate-200 text-xs font-mono uppercase focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :class="errors.code ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.code" class="text-[11px] text-red-600 mt-0.5" x-text="errors.code"></p>
                                </div>

                                {{-- Company Type (Required Dropdown) --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Company Type <span class="text-red-500">*</span></label>
                                    <select x-model="form.company_type"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs"
                                            :class="errors.company_type ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                        <option value="">-- Select Company Type --</option>
                                        <option value="Private Limited Company">Private Limited Company</option>
                                        <option value="Public Limited Company">Public Limited Company</option>
                                        <option value="Limited Liability Partnership (LLP)">Limited Liability Partnership (LLP)</option>
                                        <option value="Partnership Firm">Partnership Firm</option>
                                        <option value="Sole Proprietorship">Sole Proprietorship</option>
                                        <option value="One Person Company (OPC)">One Person Company (OPC)</option>
                                        <option value="Joint Venture">Joint Venture</option>
                                        <option value="Section 8 Company / NGO">Section 8 Company / NGO</option>
                                        <option value="Associate Entity">Associate Entity</option>
                                    </select>
                                    <p x-show="errors.company_type" class="text-[11px] text-red-600 mt-0.5" x-text="errors.company_type"></p>
                                </div>

                                {{-- Business Group --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Business Group</label>
                                    <select x-model="form.business_group_id"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                        <option value="">-- None / Standalone Entity --</option>
                                        <template x-for="bg in allGroups" :key="bg.id">
                                            <option :value="bg.id" x-text="bg.name"></option>
                                        </template>
                                    </select>
                                </div>

                                {{-- Legal Name --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Legal Name</label>
                                    <input type="text"
                                           x-model="form.legal_name"
                                           placeholder="Full legal registered corporate name"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- Incorporation No. --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Incorporation No.</label>
                                    <input type="text"
                                           x-model="form.incorporation_no"
                                           placeholder="e.g., INC-MH-2021-98765"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- Incorporation Date --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Incorporation Date</label>
                                    <input type="date"
                                           x-model="form.incorporation_date"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                </div>

                                {{-- CIN Number --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">CIN Number</label>
                                    <input type="text"
                                           x-model="form.cin"
                                           placeholder="e.g., U72900MH2021PTC123456"
                                           class="w-full rounded-xl border-slate-200 text-xs font-mono uppercase focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- Logo (Emoji) --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Logo (Emoji)</label>
                                    <div class="flex items-center gap-2">
                                        <div class="w-9 h-9 shrink-0 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 flex items-center justify-center text-lg font-emoji shadow-2xs">
                                            <span x-text="form.logo_emoji || '🏢'"></span>
                                        </div>
                                        <input type="text"
                                               x-model="form.logo_emoji"
                                               maxlength="10"
                                               placeholder="Emoji or symbol"
                                               class="w-20 rounded-xl border-slate-200 text-xs font-emoji text-center focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                        <div class="flex items-center gap-1 flex-wrap py-1">
                                            <template x-for="emo in ['🏢','🏭','💼','⚡','🌐','🏬','📦','🚀','🏥','🛒','🏗️']" :key="emo">
                                                <button type="button"
                                                        @click="form.logo_emoji = emo"
                                                        class="w-6 h-6 rounded hover:bg-purple-100 flex items-center justify-center text-sm transition"
                                                        :title="emo">
                                                    <span x-text="emo"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================= --}}
                        {{-- 2. TAX & REGULATORY                                           --}}
                        {{-- ============================================================= --}}
                        <div class="pt-2">
                            <div class="flex items-center justify-between pb-2 mb-4 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section 2</span>
                                    <h4 class="text-xs font-bold uppercase tracking-wide text-slate-800">Tax & Regulatory</h4>
                                </div>
                                <span class="text-[11px] text-slate-400 font-normal">GSTIN & PAN must follow standard statutory formats</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {{-- GSTIN (Required) --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">GSTIN <span class="text-red-500">*</span></label>
                                    <input type="text"
                                           x-model="form.gstin"
                                           @input="handleGstinInput()"
                                           maxlength="15"
                                           placeholder="e.g., 27ABCDE1234F1Z5"
                                           class="w-full rounded-xl border-slate-200 text-xs font-mono uppercase focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :class="errors.gstin ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.gstin" class="text-[11px] text-red-600 mt-0.5" x-text="errors.gstin"></p>
                                    <p x-show="!errors.gstin" class="text-[10px] text-slate-400">15-digit Goods & Services Tax Number</p>
                                </div>

                                {{-- PAN (Required) --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">PAN <span class="text-red-500">*</span></label>
                                    <input type="text"
                                           x-model="form.pan"
                                           @input="handlePanInput()"
                                           maxlength="10"
                                           placeholder="e.g., ABCDE1234F"
                                           class="w-full rounded-xl border-slate-200 text-xs font-mono uppercase focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :class="errors.pan ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.pan" class="text-[11px] text-red-600 mt-0.5" x-text="errors.pan"></p>
                                    <p x-show="!errors.pan" class="text-[10px] text-slate-400">10-character Permanent Account Number</p>
                                </div>

                                {{-- TAN --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">TAN</label>
                                    <input type="text"
                                           x-model="form.tan"
                                           maxlength="20"
                                           placeholder="e.g., MUMB12345D"
                                           class="w-full rounded-xl border-slate-200 text-xs font-mono uppercase focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- IEC Code --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">IEC Code</label>
                                    <input type="text"
                                           x-model="form.iec_code"
                                           maxlength="30"
                                           placeholder="e.g., 0312012345 (Import Export Code)"
                                           class="w-full rounded-xl border-slate-200 text-xs font-mono focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- MSME Reg No. --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">MSME Reg No.</label>
                                    <input type="text"
                                           x-model="form.msme_registration_no"
                                           placeholder="e.g., UDYAM-MH-01-0012345"
                                           class="w-full rounded-xl border-slate-200 text-xs font-mono focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================= --}}
                        {{-- 3. ADDRESS & CONTACT                                          --}}
                        {{-- ============================================================= --}}
                        <div class="pt-2">
                            <div class="flex items-center gap-2 pb-2 mb-4 border-b border-slate-100">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section 3</span>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-800">Address & Contact</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {{-- Registered Office (Address) --}}
                                <div class="space-y-1 md:col-span-2 lg:col-span-3">
                                    <label class="block text-xs font-semibold text-slate-700">Registered Office</label>
                                    <textarea x-model="form.address"
                                              rows="2"
                                              placeholder="Full corporate postal address, building name, street, locality..."
                                              class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"></textarea>
                                </div>

                                {{-- City --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">City</label>
                                    <input type="text"
                                           x-model="form.city"
                                           placeholder="e.g., Mumbai"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- State (Searchable / Dropdown) --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">State</label>
                                    <select x-model="form.state"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                        <option value="">-- Select State --</option>
                                        <template x-for="st in allStates" :key="st">
                                            <option :value="st" x-text="st"></option>
                                        </template>
                                    </select>
                                </div>

                                {{-- Country --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Country</label>
                                    <input type="text"
                                           x-model="form.country"
                                           placeholder="e.g., India"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- Pincode --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Pincode</label>
                                    <input type="text"
                                           x-model="form.pincode"
                                           maxlength="10"
                                           placeholder="e.g., 400001"
                                           class="w-full rounded-xl border-slate-200 text-xs font-mono focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- Phone --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Phone</label>
                                    <input type="text"
                                           x-model="form.phone"
                                           placeholder="e.g., +91 22 1234 5678"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- Email --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Email</label>
                                    <input type="email"
                                           x-model="form.email"
                                           placeholder="e.g., contact@avitdigital.com"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :class="errors.email ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.email" class="text-[11px] text-red-600 mt-0.5" x-text="errors.email"></p>
                                </div>

                                {{-- Website --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Website</label>
                                    <input type="text"
                                           x-model="form.website"
                                           placeholder="e.g., https://avitdigital.com"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :class="errors.website ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.website" class="text-[11px] text-red-600 mt-0.5" x-text="errors.website"></p>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================= --}}
                        {{-- 4. BUSINESS & FINANCIAL                                       --}}
                        {{-- ============================================================= --}}
                        <div class="pt-2">
                            <div class="flex items-center gap-2 pb-2 mb-4 border-b border-slate-100">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section 4</span>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-800">Business & Financial</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {{-- Nature of Business --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Nature of Business</label>
                                    <input type="text"
                                           x-model="form.nature_of_business"
                                           placeholder="e.g., Electronics Distribution & Retailing"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- Authorized Capital (₹) --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Authorized Capital (₹)</label>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           x-model="form.authorized_capital"
                                           placeholder="e.g., 5000000.00"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- Paid-up Capital (₹) --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Paid-up Capital (₹)</label>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           x-model="form.paid_up_capital"
                                           placeholder="e.g., 2500000.00"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- Annual Turnover (₹) --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Annual Turnover (₹)</label>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           x-model="form.annual_turnover"
                                           placeholder="e.g., 18500000.00"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- Financial Year --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Financial Year</label>
                                    <select x-model="form.financial_year"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                        <template x-for="fy in allFinancialYears" :key="fy">
                                            <option :value="fy" x-text="fy"></option>
                                        </template>
                                    </select>
                                </div>

                                {{-- Accounting Software --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Accounting Software</label>
                                    <input type="text"
                                           x-model="form.accounting_software"
                                           placeholder="e.g., TallyPrime / SAP / Zoho"
                                           list="software-suggestions"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                    <datalist id="software-suggestions">
                                        <option value="TallyPrime">
                                        <option value="SAP S/4HANA">
                                        <option value="Zoho Books">
                                        <option value="Busy Accounting">
                                        <option value="QuickBooks Online">
                                        <option value="Oracle NetSuite">
                                    </datalist>
                                </div>

                                {{-- Branches --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Branches</label>
                                    <input type="number"
                                           min="0"
                                           step="1"
                                           x-model="form.branches_count_manual"
                                           placeholder="Number of active branches"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :class="errors.branches_count_manual ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.branches_count_manual" class="text-[11px] text-red-600 mt-0.5" x-text="errors.branches_count_manual"></p>
                                </div>

                                {{-- Employees --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Employees</label>
                                    <input type="number"
                                           min="0"
                                           step="1"
                                           x-model="form.employees_count_manual"
                                           placeholder="Total employee headcount"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :class="errors.employees_count_manual ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.employees_count_manual" class="text-[11px] text-red-600 mt-0.5" x-text="errors.employees_count_manual"></p>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================= --}}
                        {{-- 5. RELATIONSHIP & STATUS                                      --}}
                        {{-- ============================================================= --}}
                        <div class="pt-2">
                            <div class="flex items-center gap-2 pb-2 mb-4 border-b border-slate-100">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section 5</span>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-800">Relationship & Status</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {{-- Relationship Type --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Relationship Type</label>
                                    <select x-model="form.relationship_type"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                        <option value="Sister Concern">Sister Concern</option>
                                        <option value="Subsidiary">Subsidiary</option>
                                        <option value="Holding">Holding</option>
                                        <option value="Associate">Associate</option>
                                        <option value="Joint Venture">Joint Venture</option>
                                    </select>
                                </div>

                                {{-- Shareholding (%) --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Shareholding (%)</label>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           max="100"
                                           x-model="form.shareholding_percentage"
                                           placeholder="e.g., 51.00"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :class="errors.shareholding_percentage ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.shareholding_percentage" class="text-[11px] text-red-600 mt-0.5" x-text="errors.shareholding_percentage"></p>
                                </div>

                                {{-- Status (Active / Inactive) --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Status</label>
                                    <select x-model="form.is_active"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                        <option :value="1">Active</option>
                                        <option :value="0">Inactive</option>
                                    </select>
                                </div>

                                {{-- Compliance Status --}}
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700">Compliance Status</label>
                                    <select x-model="form.compliance_status"
                                            class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                        <option value="Compliant">Compliant</option>
                                        <option value="Pending Review">Pending Review</option>
                                        <option value="Non-Compliant">Non-Compliant</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Sticky Modal Footer --}}
                    <div class="shrink-0 px-6 py-4 bg-slate-50/95 backdrop-blur-xs border-t border-slate-100 flex items-center justify-between sticky bottom-0 z-10">
                        <div>
                            <p x-show="formErrorSummary" class="text-xs text-red-600 font-semibold" x-text="formErrorSummary"></p>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <button type="button"
                                    @click="closeFormModal()"
                                    :disabled="isSaving"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                    :disabled="isSaving"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 text-xs font-semibold shadow-xs transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="isSaving" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isSaving ? 'Saving...' : (modalMode === 'add' ? 'Add Company' : 'Update Company')"></span>
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </template>

    {{-- ========================================================================= --}}
    {{-- MODAL 2: VIEW SISTER CONCERN DETAILS MODAL                                --}}
    {{-- ========================================================================= --}}
    <template x-teleport="body">
        <div x-show="isViewModalOpen"
             x-cloak
             class="fixed inset-0 z-[100] overflow-y-auto"
             aria-labelledby="view-company-modal-title"
             role="dialog"
             aria-modal="true"
             @keydown.escape.window="isViewModalOpen = false">

            {{-- Fullscreen Backdrop --}}
            <div x-show="isViewModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="isViewModalOpen = false"></div>

            <div class="relative flex min-h-screen items-center justify-center p-4 sm:p-6 lg:p-8 pointer-events-none">
                <div x-show="isViewModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="pointer-events-auto relative w-full max-h-[88vh] flex flex-col bg-white rounded-2xl text-left shadow-2xl border border-slate-200 overflow-hidden my-auto"
                     style="width: min(960px, calc(100vw - 32px)); max-width: min(960px, calc(100vw - 32px));"
                     @click.stop>

                    {{-- Purple Header --}}
                    <div class="shrink-0 px-6 py-5 bg-gradient-to-r from-purple-800 to-indigo-900 text-white flex items-center justify-between sticky top-0 z-10">
                        <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-2xl font-emoji">
                            <span x-text="viewCompany?.logo_emoji || '🏢'"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-white" id="view-company-modal-title" x-text="viewCompany?.name"></h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                      :class="viewCompany?.is_active ? 'bg-emerald-500/20 text-emerald-200 border border-emerald-400/30' : 'bg-slate-500/20 text-slate-300 border border-slate-400/30'"
                                      x-text="viewCompany?.is_active ? 'Active' : 'Inactive'"></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/30 text-indigo-100 border border-indigo-400/30"
                                      x-text="viewCompany?.relationship_type || 'Sister Concern'"></span>
                            </div>
                            <p class="text-xs text-purple-200 font-mono mt-0.5" x-text="'Code: ' + (viewCompany?.code || '—') + ' • ' + (viewCompany?.company_type || 'Private Limited')"></p>
                        </div>
                    </div>
                    <button type="button"
                            @click="isViewModalOpen = false"
                            class="rounded-lg p-1.5 text-white/70 hover:text-white hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                {{-- Body Details --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-6 text-xs">
                    {{-- 4 Stat Cards --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block">Business Group</span>
                            <span class="font-bold text-slate-900 text-sm mt-0.5 block truncate" x-text="viewCompany?.business_group?.name || '—'"></span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block">Annual Turnover</span>
                            <span class="font-bold text-purple-800 text-sm mt-0.5 block" x-text="formatCurrency(viewCompany?.annual_turnover)"></span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block">Shareholding</span>
                            <span class="font-bold text-blue-700 text-sm mt-0.5 block" x-text="(viewCompany?.shareholding_percentage ?? 0) + '%'"></span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block">Compliance</span>
                            <span class="font-bold text-slate-800 text-sm mt-0.5 block" x-text="viewCompany?.compliance_status || 'Compliant'"></span>
                        </div>
                    </div>

                    {{-- Grid 1: Tax & Regulatory --}}
                    <div class="rounded-xl border border-slate-100 p-4 bg-slate-50/50">
                        <h4 class="font-bold text-slate-800 mb-3 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Tax & Regulatory Credentials</span>
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div><span class="text-slate-400 block">GSTIN:</span><span class="font-mono font-bold text-slate-900" x-text="viewCompany?.gstin || '—'"></span></div>
                            <div><span class="text-slate-400 block">PAN:</span><span class="font-mono font-bold text-slate-900" x-text="viewCompany?.pan || '—'"></span></div>
                            <div><span class="text-slate-400 block">TAN:</span><span class="font-mono text-slate-700" x-text="viewCompany?.tan || '—'"></span></div>
                            <div><span class="text-slate-400 block">CIN:</span><span class="font-mono text-slate-700" x-text="viewCompany?.cin || '—'"></span></div>
                            <div><span class="text-slate-400 block">IEC Code:</span><span class="font-mono text-slate-700" x-text="viewCompany?.iec_code || '—'"></span></div>
                            <div><span class="text-slate-400 block">MSME Reg No:</span><span class="font-mono text-slate-700" x-text="viewCompany?.msme_registration_no || '—'"></span></div>
                        </div>
                    </div>

                    {{-- Grid 2: Address & Contact Details --}}
                    <div class="rounded-xl border border-slate-100 p-4 bg-slate-50/50">
                        <h4 class="font-bold text-slate-800 mb-3 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Address & Contact Info</span>
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2"><span class="text-slate-400 block">Registered Office:</span><span class="text-slate-800 font-medium" x-text="viewCompany?.address || '—'"></span></div>
                            <div><span class="text-slate-400 block">City & State:</span><span class="text-slate-800 font-medium" x-text="(viewCompany?.city ? viewCompany.city + ', ' : '') + (viewCompany?.state || viewCompany?.country || '—')"></span></div>
                            <div><span class="text-slate-400 block">Pincode:</span><span class="text-slate-800 font-medium font-mono" x-text="viewCompany?.pincode || '—'"></span></div>
                            <div><span class="text-slate-400 block">Email:</span><a :href="'mailto:' + viewCompany?.email" class="text-purple-700 font-medium hover:underline" x-text="viewCompany?.email || '—'"></a></div>
                            <div><span class="text-slate-400 block">Phone:</span><span class="text-slate-800 font-medium" x-text="viewCompany?.phone || '—'"></span></div>
                            <div><span class="text-slate-400 block">Website:</span><a :href="viewCompany?.website?.startsWith('http') ? viewCompany?.website : 'https://' + viewCompany?.website" target="_blank" class="text-purple-700 font-medium hover:underline" x-text="viewCompany?.website || '—'"></a></div>
                        </div>
                    </div>

                    {{-- Grid 3: Business & Financial Parameters --}}
                    <div class="rounded-xl border border-slate-100 p-4 bg-slate-50/50">
                        <h4 class="font-bold text-slate-800 mb-3 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Business & Financial Parameters</span>
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div><span class="text-slate-400 block">Authorized Capital:</span><span class="font-bold text-slate-900" x-text="formatCurrency(viewCompany?.authorized_capital)"></span></div>
                            <div><span class="text-slate-400 block">Paid-up Capital:</span><span class="font-bold text-slate-900" x-text="formatCurrency(viewCompany?.paid_up_capital)"></span></div>
                            <div><span class="text-slate-400 block">Financial Year:</span><span class="text-slate-800 font-medium" x-text="viewCompany?.financial_year || '—'"></span></div>
                            <div><span class="text-slate-400 block">Accounting Software:</span><span class="text-slate-800 font-medium" x-text="viewCompany?.accounting_software || '—'"></span></div>
                            <div><span class="text-slate-400 block">Branches:</span><span class="text-slate-800 font-bold" x-text="viewCompany?.branches_count_manual || viewCompany?.branches?.length || 0"></span></div>
                            <div><span class="text-slate-400 block">Employees:</span><span class="text-slate-800 font-bold" x-text="viewCompany?.employees_count_manual || 0"></span></div>
                            <div class="sm:col-span-2"><span class="text-slate-400 block">Nature of Business:</span><span class="text-slate-800 font-medium" x-text="viewCompany?.nature_of_business || '—'"></span></div>
                        </div>
                    </div>
                </div>

                {{-- View Modal Footer --}}
                <div class="shrink-0 px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2 sticky bottom-0 z-10">
                    <button type="button"
                            @click="isViewModalOpen = false"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                        Close
                    </button>
                    <button type="button"
                            @click="isViewModalOpen = false; openEditModal(viewCompany)"
                            class="inline-flex items-center justify-center gap-1 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 text-xs font-semibold shadow-xs transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        <span>Edit Company</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ========================================================================= --}}
    {{-- MODAL 3: DELETE CONFIRMATION MODAL                                        --}}
    {{-- ========================================================================= --}}
    <template x-teleport="body">
        <div x-show="showDeleteModal"
             x-cloak
             class="fixed inset-0 z-[100] overflow-y-auto"
             aria-labelledby="delete-company-modal-title"
             role="dialog"
             aria-modal="true"
             @keydown.escape.window="cancelDelete()">

            {{-- Fullscreen Backdrop --}}
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="cancelDelete()"></div>

            <div class="relative flex min-h-screen items-center justify-center p-4 sm:p-6 pointer-events-none">
                <div x-show="showDeleteModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="pointer-events-auto relative w-full sm:max-w-md bg-white rounded-2xl text-left overflow-hidden shadow-2xl border border-slate-200 p-6 my-auto"
                     @click.stop>
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-red-100 sm:mx-0 sm:h-10 sm:w-10 text-red-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-base font-bold text-slate-900" id="delete-company-modal-title">Delete Sister Concern</h3>
                            <div class="mt-2 space-y-2">
                                <p class="text-xs text-slate-600">
                                    Are you sure you want to delete this sister concern company? This action cannot be undone.
                                </p>
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 font-medium">Company Name:</span>
                                        <span class="text-slate-900 font-bold" x-text="deletingCompanyName"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 font-medium">Company Code:</span>
                                        <span class="font-mono text-slate-700 bg-white px-1.5 py-0.5 rounded border border-slate-200 text-[11px]" x-text="deletingCompanyCode"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-row-reverse gap-2.5">
                        <button type="button"
                                @click="executeDelete()"
                                :disabled="isDeleting"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!isDeleting">Delete Company</span>
                            <span x-show="isDeleting" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Deleting...
                            </span>
                        </button>
                        <button type="button"
                                @click="cancelDelete()"
                                :disabled="isDeleting"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Notification Toast --}}
    <div x-show="notification.show"
         x-cloak
         x-transition
         class="fixed bottom-4 right-4 z-50 max-w-sm rounded-xl p-4 shadow-xl text-white text-xs font-medium flex items-center gap-3"
         :class="notification.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
        <svg x-show="notification.type === 'success'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        <svg x-show="notification.type === 'error'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        <span x-text="notification.message" class="flex-1"></span>
        <button type="button" @click="notification.show = false" class="text-white/80 hover:text-white">&times;</button>
    </div>
</div>

@push('scripts')
<script>
function sisterConcernCompaniesPage(groupsData, statesData, fyData) {
    return {
        allGroups: groupsData || [],
        allStates: statesData || [],
        allFinancialYears: fyData || ['2026-2027', '2025-2026', '2024-2025'],

        // Modal State
        isFormModalOpen: false,
        modalMode: 'add',
        editingCompanyId: null,
        isSaving: false,
        formErrorSummary: '',
        errors: {},

        // Form Fields
        form: {
            name: '',
            code: '',
            company_type: 'Private Limited Company',
            business_group_id: '',
            legal_name: '',
            incorporation_no: '',
            incorporation_date: '',
            cin: '',
            logo_emoji: '🏢',

            // Tax
            gstin: '',
            pan: '',
            tan: '',
            iec_code: '',
            msme_registration_no: '',

            // Address & Contact
            address: '',
            city: '',
            state: '',
            country: 'India',
            pincode: '',
            phone: '',
            email: '',
            website: '',

            // Business & Financial
            nature_of_business: '',
            authorized_capital: '',
            paid_up_capital: '',
            annual_turnover: '',
            financial_year: fyData?.[0] || '2026-2027',
            accounting_software: 'TallyPrime',
            branches_count_manual: 0,
            employees_count_manual: 0,

            // Relationship & Status
            relationship_type: 'Sister Concern',
            shareholding_percentage: 0,
            is_active: 1,
            compliance_status: 'Compliant',
        },

        // View Modal
        isViewModalOpen: false,
        viewCompany: null,

        // Delete Modal
        showDeleteModal: false,
        isDeleting: false,
        deletingCompanyId: null,
        deletingCompanyName: '',
        deletingCompanyCode: '',

        notification: {
            show: false,
            type: 'success',
            message: '',
        },

        handleGstinInput() {
            if (this.form.gstin) {
                this.form.gstin = this.form.gstin.toUpperCase().trim();
                // Extract PAN if 10 characters are present in positions 3-12 (index 2-12)
                if (this.form.gstin.length >= 12 && !this.form.pan) {
                    const extractedPan = this.form.gstin.substring(2, 12);
                    const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
                    if (panRegex.test(extractedPan)) {
                        this.form.pan = extractedPan;
                        this.errors.pan = '';
                    }
                }
            }
            if (this.errors.gstin) {
                this.validateGstin();
            }
        },

        handlePanInput() {
            if (this.form.pan) {
                this.form.pan = this.form.pan.toUpperCase().trim();
            }
            if (this.errors.pan) {
                this.validatePan();
            }
        },

        validateGstin() {
            if (!this.form.gstin || !this.form.gstin.trim()) {
                this.errors.gstin = 'GSTIN is required.';
                return false;
            }
            const gstinRegex = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i;
            if (!gstinRegex.test(this.form.gstin.trim())) {
                this.errors.gstin = 'Invalid GSTIN format. Must be 15 alphanumeric characters (e.g., 27ABCDE1234F1Z5).';
                return false;
            }
            this.errors.gstin = '';
            return true;
        },

        validatePan() {
            if (!this.form.pan || !this.form.pan.trim()) {
                this.errors.pan = 'PAN is required.';
                return false;
            }
            const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i;
            if (!panRegex.test(this.form.pan.trim())) {
                this.errors.pan = 'Invalid PAN format. Must be 10 characters (5 letters, 4 digits, 1 letter, e.g., ABCDE1234F).';
                return false;
            }
            this.errors.pan = '';
            return true;
        },

        openAddModal() {
            this.modalMode = 'add';
            this.editingCompanyId = null;
            this.errors = {};
            this.formErrorSummary = '';

            this.form = {
                name: '',
                code: '',
                company_type: 'Private Limited Company',
                business_group_id: this.allGroups.length > 0 ? this.allGroups[0].id : '',
                legal_name: '',
                incorporation_no: '',
                incorporation_date: '',
                cin: '',
                logo_emoji: '🏢',

                gstin: '',
                pan: '',
                tan: '',
                iec_code: '',
                msme_registration_no: '',

                address: '',
                city: '',
                state: this.allStates.includes('Maharashtra') ? 'Maharashtra' : (this.allStates[0] || ''),
                country: 'India',
                pincode: '',
                phone: '',
                email: '',
                website: '',

                nature_of_business: '',
                authorized_capital: '',
                paid_up_capital: '',
                annual_turnover: '',
                financial_year: this.allFinancialYears[0] || '2026-2027',
                accounting_software: 'TallyPrime',
                branches_count_manual: 0,
                employees_count_manual: 0,

                relationship_type: 'Sister Concern',
                shareholding_percentage: 0,
                is_active: 1,
                compliance_status: 'Compliant',
            };

            this.isFormModalOpen = true;
        },

        openEditModal(item) {
            this.modalMode = 'edit';
            this.editingCompanyId = item.id;
            this.errors = {};
            this.formErrorSummary = '';

            let incDate = '';
            if (item.incorporation_date) {
                incDate = item.incorporation_date.substring(0, 10);
            }

            this.form = {
                name: item.name || '',
                code: item.code || '',
                company_type: item.company_type || 'Private Limited Company',
                business_group_id: item.business_group_id || '',
                legal_name: item.legal_name || '',
                incorporation_no: item.incorporation_no || '',
                incorporation_date: incDate,
                cin: item.cin || '',
                logo_emoji: item.logo_emoji || '🏢',

                gstin: item.gstin || '',
                pan: item.pan || '',
                tan: item.tan || '',
                iec_code: item.iec_code || '',
                msme_registration_no: item.msme_registration_no || '',

                address: item.address || '',
                city: item.city || '',
                state: item.state || '',
                country: item.country || 'India',
                pincode: item.pincode || '',
                phone: item.phone || '',
                email: item.email || '',
                website: item.website || '',

                nature_of_business: item.nature_of_business || '',
                authorized_capital: item.authorized_capital || '',
                paid_up_capital: item.paid_up_capital || '',
                annual_turnover: item.annual_turnover || '',
                financial_year: item.financial_year || this.allFinancialYears[0] || '2026-2027',
                accounting_software: item.accounting_software || 'TallyPrime',
                branches_count_manual: item.branches_count_manual !== null && item.branches_count_manual !== undefined ? item.branches_count_manual : (item.branches ? item.branches.length : 0),
                employees_count_manual: item.employees_count_manual !== null && item.employees_count_manual !== undefined ? item.employees_count_manual : 0,

                relationship_type: item.relationship_type || 'Sister Concern',
                shareholding_percentage: item.shareholding_percentage !== null && item.shareholding_percentage !== undefined ? item.shareholding_percentage : 0,
                is_active: item.is_active ? 1 : 0,
                compliance_status: item.compliance_status || 'Compliant',
            };

            this.isFormModalOpen = true;
        },

        closeFormModal() {
            if (this.isSaving) return;
            this.isFormModalOpen = false;
            this.errors = {};
            this.formErrorSummary = '';
        },

        openViewModal(item) {
            this.viewCompany = item;
            this.isViewModalOpen = true;
        },

        formatCurrency(val) {
            if (!val || isNaN(val)) return '₹0.00';
            const num = parseFloat(val);
            if (num >= 10000000) {
                return '₹' + (num / 10000000).toFixed(2) + ' Cr';
            } else if (num >= 100000) {
                return '₹' + (num / 100000).toFixed(2) + ' L';
            }
            return '₹' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        validateFrontend() {
            this.errors = {};
            this.formErrorSummary = '';
            let valid = true;

            if (!this.form.name || !this.form.name.trim()) {
                this.errors.name = 'Company Name is required.';
                valid = false;
            }

            if (!this.form.company_type || !this.form.company_type.trim()) {
                this.errors.company_type = 'Company Type is required.';
                valid = false;
            }

            if (!this.validateGstin()) {
                valid = false;
            }

            if (!this.validatePan()) {
                valid = false;
            }

            if (this.form.email && this.form.email.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.form.email.trim())) {
                    this.errors.email = 'Please enter a valid email address.';
                    valid = false;
                }
            }

            if (this.form.shareholding_percentage !== '' && this.form.shareholding_percentage !== null) {
                const sh = parseFloat(this.form.shareholding_percentage);
                if (isNaN(sh) || sh < 0 || sh > 100) {
                    this.errors.shareholding_percentage = 'Shareholding percentage must be between 0 and 100%.';
                    valid = false;
                }
            }

            if (this.form.branches_count_manual !== '' && this.form.branches_count_manual !== null) {
                const br = parseInt(this.form.branches_count_manual, 10);
                if (isNaN(br) || br < 0) {
                    this.errors.branches_count_manual = 'Branches count must be a non-negative whole number.';
                    valid = false;
                }
            }

            if (this.form.employees_count_manual !== '' && this.form.employees_count_manual !== null) {
                const emp = parseInt(this.form.employees_count_manual, 10);
                if (isNaN(emp) || emp < 0) {
                    this.errors.employees_count_manual = 'Employees count must be a non-negative whole number.';
                    valid = false;
                }
            }

            if (!valid) {
                this.formErrorSummary = 'Please check the form for errors and fill in all required fields.';
            }

            return valid;
        },

        async submitForm() {
            if (!this.validateFrontend()) {
                return;
            }

            this.isSaving = true;
            this.formErrorSummary = '';

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const isAdd = this.modalMode === 'add';
            const url = isAdd
                ? "{{ route('organization.companies.store') }}"
                : `{{ url('organization/companies') }}/${this.editingCompanyId}`;

            const payload = {
                name: this.form.name.trim(),
                code: this.form.code ? this.form.code.trim().toUpperCase() : null,
                company_type: this.form.company_type,
                business_group_id: this.form.business_group_id || null,
                legal_name: this.form.legal_name ? this.form.legal_name.trim() : null,
                incorporation_no: this.form.incorporation_no ? this.form.incorporation_no.trim() : null,
                incorporation_date: this.form.incorporation_date || null,
                cin: this.form.cin ? this.form.cin.trim().toUpperCase() : null,
                logo_emoji: this.form.logo_emoji ? this.form.logo_emoji.trim() : '🏢',

                gstin: this.form.gstin ? this.form.gstin.trim().toUpperCase() : null,
                pan: this.form.pan ? this.form.pan.trim().toUpperCase() : null,
                tan: this.form.tan ? this.form.tan.trim().toUpperCase() : null,
                iec_code: this.form.iec_code ? this.form.iec_code.trim().toUpperCase() : null,
                msme_registration_no: this.form.msme_registration_no ? this.form.msme_registration_no.trim().toUpperCase() : null,

                address: this.form.address ? this.form.address.trim() : null,
                city: this.form.city ? this.form.city.trim() : null,
                state: this.form.state || null,
                country: this.form.country ? this.form.country.trim() : 'India',
                pincode: this.form.pincode ? this.form.pincode.trim() : null,
                phone: this.form.phone ? this.form.phone.trim() : null,
                email: this.form.email ? this.form.email.trim() : null,
                website: this.form.website ? this.form.website.trim() : null,

                nature_of_business: this.form.nature_of_business ? this.form.nature_of_business.trim() : null,
                authorized_capital: this.form.authorized_capital ? parseFloat(this.form.authorized_capital) : null,
                paid_up_capital: this.form.paid_up_capital ? parseFloat(this.form.paid_up_capital) : null,
                annual_turnover: this.form.annual_turnover ? parseFloat(this.form.annual_turnover) : null,
                financial_year: this.form.financial_year || null,
                accounting_software: this.form.accounting_software ? this.form.accounting_software.trim() : null,
                branches_count_manual: parseInt(this.form.branches_count_manual || 0, 10),
                employees_count_manual: parseInt(this.form.employees_count_manual || 0, 10),

                relationship_type: this.form.relationship_type || 'Sister Concern',
                shareholding_percentage: this.form.shareholding_percentage !== '' ? parseFloat(this.form.shareholding_percentage) : 0,
                is_active: Number(this.form.is_active) === 1 ? 1 : 0,
                compliance_status: this.form.compliance_status || 'Compliant',
            };

            try {
                const response = await fetch(url, {
                    method: isAdd ? 'POST' : 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.closeFormModal();
                    this.showNotification('success', data.message || (isAdd ? 'Company added successfully.' : 'Company updated successfully.'));
                    await this.refreshList();
                } else if (response.status === 422) {
                    if (data.errors) {
                        for (const key in data.errors) {
                            this.errors[key] = data.errors[key][0];
                        }
                    }
                    this.formErrorSummary = data.message || 'Please correct the validation errors in the form.';
                } else {
                    this.formErrorSummary = data.message || 'An error occurred while saving the sister concern.';
                }
            } catch (err) {
                console.error('Save company error:', err);
                this.formErrorSummary = 'Network error: could not save company. Please try again.';
            } finally {
                this.isSaving = false;
            }
        },

        promptDelete(id, name, code) {
            this.deletingCompanyId = id;
            this.deletingCompanyName = name;
            this.deletingCompanyCode = code;
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingCompanyId = null;
            this.deletingCompanyName = '';
            this.deletingCompanyCode = '';
        },

        async executeDelete() {
            if (!this.deletingCompanyId || this.isDeleting) return;
            this.isDeleting = true;

            const url = `{{ url('organization/companies') }}/${this.deletingCompanyId}`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showDeleteModal = false;
                    this.showNotification('success', data.message || 'Company deleted successfully.');
                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete company.');
                    this.showDeleteModal = false;
                }
            } catch (err) {
                console.error('Delete company error:', err);
                this.showNotification('error', 'A network error occurred while deleting the company.');
                this.showDeleteModal = false;
            } finally {
                this.isDeleting = false;
                this.deletingCompanyId = null;
            }
        },

        async refreshList() {
            const container = document.querySelector('#companies-listing-container');
            if (!container) return;
            const currentUrl = new URL(window.location.href);

            try {
                const refreshRes = await fetch(currentUrl.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-DMS-Dynamic': '1',
                    }
                });

                if (refreshRes.ok) {
                    const html = await refreshRes.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newContainer = doc.querySelector('#companies-listing-container');
                    if (newContainer) {
                        container.innerHTML = newContainer.innerHTML;
                        window.history.replaceState(null, '', currentUrl.toString());
                        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                            window.Alpine.initTree(container);
                        }
                    } else {
                        window.location.href = currentUrl.toString();
                    }
                } else {
                    window.location.href = currentUrl.toString();
                }
            } catch (e) {
                console.error('Refresh list error:', e);
                window.location.href = currentUrl.toString();
            }
        },

        showNotification(type, message) {
            this.notification = { show: true, type, message };
            setTimeout(() => {
                this.notification.show = false;
            }, 6000);
        }
    };
}
</script>
@endpush
@endsection
