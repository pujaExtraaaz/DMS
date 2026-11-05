@extends('layouts.dms')
@section('title', 'Sister Concern - Business Groups')

@section('content')
<div id="listing-container" data-dynamic-container x-data="businessGroupPage(@js($companies ?? []))" @open-add-group-modal.window="openAddModal()">
    {{-- Unified Header Banner & Tabs --}}
    @include('organization.sister-concerns.partials.header', [
        'activeTab' => 'business-groups',
        'tabCounts' => $tabCounts ?? [],
    ])

    @if(request('return_url'))
        <div class="mb-4 bg-purple-50/90 border border-purple-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-purple-900 shadow-2xs">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </div>
                <div>
                    <span class="font-bold">Returning to Party Form</span>
                    <p class="text-purple-700 text-[11px]">Create a Business Group here, then return to Create Party to select it in the Business Group dropdown.</p>
                </div>
            </div>
            <a href="{{ request('return_url') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white text-purple-700 border border-purple-200 font-semibold rounded-xl hover:bg-purple-100 hover:text-purple-900 transition shadow-2xs shrink-0">
                &larr; Back to Party Form
            </a>
        </div>
    @endif

    {{-- Main Content Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        {{-- Toolbar: Search, Counter & Add Button --}}
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                {{-- Search Input --}}
                <form method="GET" action="{{ route('organization.business-groups.index') }}" class="relative w-full sm:w-80">
                    <input type="text"
                           name="search"
                           value="{{ $search ?? request('search') }}"
                           placeholder="Search Groups by name, code, HQ..."
                           class="w-full rounded-xl border-slate-200 pl-3 pr-9 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                    @if(request('search'))
                        <a href="{{ route('organization.business-groups.index') }}"
                           title="Clear search"
                           class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </form>

                {{-- Total Count Badge --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                    <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Total: {{ $items->total() }} {{ Str::plural('Group', $items->total()) }}
                </span>
            </div>

        </div>

        {{-- Table Container --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[11px] tracking-wider">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left w-16">Logo</th>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? 'name'" :current-direction="$direction ?? 'asc'">Group Name</x-ui.sortable-th>
                        <x-ui.sortable-th column="industry" :current-sort="$sort ?? 'name'" :current-direction="$direction ?? 'asc'">Industry</x-ui.sortable-th>
                        <x-ui.sortable-th column="headquarters" :current-sort="$sort ?? 'name'" :current-direction="$direction ?? 'asc'">Headquarters (HQ)</x-ui.sortable-th>
                        <x-ui.sortable-th column="links_count" :current-sort="$sort ?? 'name'" :current-direction="$direction ?? 'asc'">Companies</x-ui.sortable-th>
                        <th scope="col" class="px-4 py-3 text-left">Turnover</th>
                        <th scope="col" class="px-4 py-3 text-left">Employees</th>
                        <x-ui.sortable-th column="is_active" :current-sort="$sort ?? 'name'" :current-direction="$direction ?? 'asc'">Status</x-ui.sortable-th>
                        <th scope="col" class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($items as $item)
                        <tr class="hover:bg-purple-50/20 transition-colors">
                            {{-- 1. Group Logo --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-100 text-purple-700 flex items-center justify-center text-xl shadow-2xs font-emoji">
                                    {{ $item->logo_emoji ?: '🏢' }}
                                </div>
                            </td>

                            {{-- 2. Group Name --}}
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900 text-sm">{{ $item->name }}</div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                    <span class="font-mono bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded border border-slate-200 text-[10px]">{{ $item->code }}</span>
                                    @if($item->parentCompany)
                                        <span class="text-slate-400">•</span>
                                        <span class="text-slate-600 truncate max-w-[180px]" title="Parent: {{ $item->parentCompany->name }}">Parent: {{ $item->parentCompany->name }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- 3. Industry --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-medium text-slate-700">{{ $item->industry ?: '—' }}</span>
                            </td>

                            {{-- 4. Headquarters (HQ) --}}
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                @if($item->headquarters || $item->country)
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span>{{ $item->headquarters ? $item->headquarters . ($item->country ? ', ' . $item->country : '') : $item->country }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- 5. Companies --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    {{ $item->calculated_companies_count ?? $item->links_count }} {{ Str::plural('Company', $item->calculated_companies_count ?? $item->links_count) }}
                                </span>
                            </td>

                            {{-- 6. Turnover --}}
                            <td class="px-4 py-3 whitespace-nowrap font-bold text-slate-900">
                                {{ $item->calculated_turnover ?? '₹0.00' }}
                            </td>

                            {{-- 7. Employees --}}
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600 font-medium">
                                {{ $item->calculated_employees > 0 ? number_format($item->calculated_employees) : '—' }}
                            </td>

                            {{-- 8. Status --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <x-ui.badge :variant="$item->is_active ? 'emerald' : 'gray'">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </x-ui.badge>
                            </td>

                            {{-- 9. Actions --}}
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- View Action --}}
                                    <button type="button"
                                            @click="openViewModal({{ json_encode($item) }})"
                                            class="inline-flex items-center gap-1 font-semibold rounded-lg px-2.5 py-1.5 text-xs text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 shadow-2xs transition"
                                            title="View Business Group Details"
                                            aria-label="View Business Group Details">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>View</span>
                                    </button>

                                    {{-- Edit Action --}}
                                    <a href="{{ route('organization.business-groups.edit', $item) }}"
                                       @click.prevent="openEditModal({{ json_encode($item) }})"
                                       class="inline-flex items-center gap-1 font-semibold rounded-lg px-2.5 py-1.5 text-xs text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 shadow-2xs transition cursor-pointer"
                                       title="Edit Business Group"
                                       aria-label="Edit Business Group">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        <span>Edit</span>
                                    </a>

                                    {{-- Delete Action --}}
                                    @if($item->isDefault())
                                        <button type="button"
                                                disabled
                                                class="inline-flex items-center gap-1 font-medium rounded-lg opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border border-slate-200 px-2 py-1.5 text-xs"
                                                title="Default Group cannot be deleted as it is a required system group."
                                                aria-label="Default Group cannot be deleted as it is a required system group.">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </button>
                                    @else
                                        <button type="button"
                                                data-delete-group="{{ $item->id }}"
                                                @click="promptDelete('{{ $item->id }}', '{{ addslashes($item->name) }}', '{{ addslashes($item->code) }}', {{ (int) ($item->calculated_companies_count ?? $item->links_count) }})"
                                                class="inline-flex items-center gap-1 font-medium rounded-lg text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 px-2.5 py-1.5 text-xs transition"
                                                title="Delete Sister Concern Group"
                                                aria-label="Delete Sister Concern Group">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                <div class="max-w-sm mx-auto text-center">
                                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900">No Business Groups Found</h4>
                                    <p class="text-xs text-slate-500 mt-1">Try adjusting your search terms or create a new business group.</p>
                                    <button type="button"
                                            @click="openAddModal()"
                                            class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-3.5 py-1.5 text-xs font-semibold shadow-xs transition">
                                        + Add New Group
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
    {{-- MODAL 1: ADD / EDIT BUSINESS GROUP MODAL                                  --}}
    {{-- ========================================================================= --}}
    <template x-teleport="body">
        <div x-show="isFormModalOpen"
             x-cloak
             class="fixed inset-0 z-[100] overflow-y-auto"
             aria-labelledby="form-modal-title"
             role="dialog"
             aria-modal="true"
             @keydown.escape.window="closeFormModal()">

            {{-- Fullscreen Backdrop covering whole window including sidebar --}}
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

                {{-- Fixed Header --}}
                <div class="shrink-0 px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white/95 backdrop-blur-xs sticky top-0 z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-100 text-purple-700 flex items-center justify-center text-xl shadow-2xs font-emoji">
                            <span x-text="form.logo_emoji || '🏢'"></span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900" id="form-modal-title" x-text="modalMode === 'add' ? 'Add Business Group' : 'Edit Business Group'"></h3>
                            <p class="text-xs text-slate-500" x-text="modalMode === 'add' ? 'Create and configure a new corporate sister concern group' : 'Update group details, parent entity, and sister concern links'"></p>
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

                {{-- Scrollable Form Body and Sticky Footer inside Form --}}
                <form @submit.prevent="submitForm()" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">

                        {{-- Section A: Group Details --}}
                        <div>
                            <div class="flex items-center justify-between pb-2 mb-4 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section A</span>
                                    <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">Group Details</h4>
                                </div>
                                <span class="text-[11px] text-slate-400 font-normal">* Indicates required fields</span>
                            </div>

                            {{-- 3-Column Grid for Desktop, 2-Column for Medium, 1-Column for Small --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-5">
                                {{-- 1. Group Name --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Group Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           x-model="form.name"
                                           placeholder="e.g., Avit Digital Group"
                                           class="w-full rounded-xl border-slate-200 text-xs sm:text-sm py-2 px-3 focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :class="errors.name ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.name" class="text-[11px] text-red-600 mt-0.5" x-text="errors.name"></p>
                                </div>

                                {{-- 2. Group Code --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Group Code <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           x-model="form.code"
                                           placeholder="e.g., ADG-01"
                                           class="w-full rounded-xl border-slate-200 text-xs sm:text-sm font-mono py-2 px-3 focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400 uppercase"
                                           :class="errors.code ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.code" class="text-[11px] text-red-600 mt-0.5" x-text="errors.code"></p>
                                </div>

                                {{-- 3. Parent Company --}}
                                <div class="space-y-1.5 relative" @click.outside="parentCompanyDropdownOpen = false">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Parent Company <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text"
                                               x-model="parentCompanySearch"
                                               @focus="parentCompanyDropdownOpen = true"
                                               @input="parentCompanyDropdownOpen = true"
                                               placeholder="Search Parent Company..."
                                               class="w-full rounded-xl border-slate-200 text-xs sm:text-sm py-2 pl-3 pr-8 focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                               :class="errors.parent_company_id ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                        <button type="button"
                                                x-show="form.parent_company_id"
                                                @click="clearParentCompany()"
                                                class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600"
                                                title="Clear selection">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>

                                    {{-- Dropdown options --}}
                                    <div x-show="parentCompanyDropdownOpen"
                                         x-transition
                                         class="absolute z-30 left-0 right-0 mt-1 max-h-48 overflow-y-auto bg-white rounded-xl shadow-lg border border-slate-200 divide-y divide-slate-100 text-xs">
                                        <template x-for="c in filteredCompanies" :key="c.id">
                                            <div @click="selectParentCompany(c)"
                                                 class="p-2.5 hover:bg-purple-50 hover:text-purple-900 cursor-pointer flex items-center justify-between transition-colors"
                                                 :class="String(form.parent_company_id) === String(c.id) ? 'bg-purple-50 font-bold text-purple-900' : 'text-slate-700'">
                                                <span x-text="c.name"></span>
                                                <span class="font-mono text-[10px] text-slate-400 bg-slate-100 px-1 py-0.5 rounded" x-text="c.code"></span>
                                            </div>
                                        </template>
                                        <div x-show="filteredCompanies.length === 0" class="p-3 text-center text-slate-400 text-[11px] italic">
                                            No companies found
                                        </div>
                                    </div>
                                    <p x-show="errors.parent_company_id" class="text-[11px] text-red-600 mt-0.5" x-text="errors.parent_company_id"></p>
                                </div>

                                {{-- 4. Incorporation Date --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Incorporation Date</label>
                                    <input type="date"
                                           x-model="form.incorporation_date"
                                           class="w-full rounded-xl border-slate-200 text-xs sm:text-sm py-2 px-3 focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                </div>

                                {{-- 5. Headquarters --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Headquarters (HQ)</label>
                                    <input type="text"
                                           x-model="form.headquarters"
                                           placeholder="e.g., Mumbai, Maharashtra"
                                           class="w-full rounded-xl border-slate-200 text-xs sm:text-sm py-2 px-3 focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- 6. Country --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Country</label>
                                    <input type="text"
                                           x-model="form.country"
                                           placeholder="e.g., India"
                                           list="country-suggestions"
                                           class="w-full rounded-xl border-slate-200 text-xs sm:text-sm py-2 px-3 focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                    <datalist id="country-suggestions">
                                        <option value="India">
                                        <option value="United States">
                                        <option value="United Arab Emirates">
                                        <option value="United Kingdom">
                                        <option value="Singapore">
                                        <option value="Germany">
                                        <option value="Australia">
                                    </datalist>
                                </div>

                                {{-- 7. Industry --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Industry</label>
                                    <input type="text"
                                           x-model="form.industry"
                                           placeholder="e.g., Technology & Electronics"
                                           list="industry-suggestions"
                                           class="w-full rounded-xl border-slate-200 text-xs sm:text-sm py-2 px-3 focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                    <datalist id="industry-suggestions">
                                        <option value="Technology & Consumer Electronics">
                                        <option value="Manufacturing & Industrial">
                                        <option value="Retail & FMCG Distribution">
                                        <option value="Logistics & Supply Chain">
                                        <option value="Healthcare & Pharmaceuticals">
                                        <option value="Financial & Corporate Services">
                                        <option value="Construction & Real Estate">
                                        <option value="Food & Agro Products">
                                    </datalist>
                                </div>

                                {{-- 8. Website --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Website</label>
                                    <input type="text"
                                           x-model="form.website"
                                           placeholder="e.g., https://avitdigital.com"
                                           class="w-full rounded-xl border-slate-200 text-xs sm:text-sm py-2 px-3 focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :class="errors.website ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.website" class="text-[11px] text-red-600 mt-0.5" x-text="errors.website"></p>
                                </div>

                                {{-- 9. Email --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Email</label>
                                    <input type="email"
                                           x-model="form.email"
                                           placeholder="e.g., corporate@avitdigital.com"
                                           class="w-full rounded-xl border-slate-200 text-xs sm:text-sm py-2 px-3 focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"
                                           :class="errors.email ? 'border-red-300 ring-1 ring-red-400 bg-red-50/20' : ''">
                                    <p x-show="errors.email" class="text-[11px] text-red-600 mt-0.5" x-text="errors.email"></p>
                                </div>

                                {{-- 10. Phone --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Phone</label>
                                    <input type="text"
                                           x-model="form.phone"
                                           placeholder="e.g., +91 98765 43210"
                                           class="w-full rounded-xl border-slate-200 text-xs sm:text-sm py-2 px-3 focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400">
                                </div>

                                {{-- 11. Group Logo (Emoji) --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Group Logo (Emoji)</label>
                                    <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                                        <div class="w-10 h-10 shrink-0 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 flex items-center justify-center text-xl font-emoji shadow-2xs">
                                            <span x-text="form.logo_emoji || '🏢'"></span>
                                        </div>
                                        <input type="text"
                                               x-model="form.logo_emoji"
                                               maxlength="10"
                                               placeholder="Emoji"
                                               class="w-16 shrink-0 rounded-xl border-slate-200 text-xs font-emoji text-center focus:border-purple-500 focus:ring-purple-500 shadow-2xs py-2 px-1">
                                        <div class="flex items-center gap-1 flex-wrap">
                                            <template x-for="emo in ['🏢','🏭','💼','⚡','🌐','🏬','📦','🚀','🏥','🛒','🏗️']" :key="emo">
                                                <button type="button"
                                                        @click="form.logo_emoji = emo"
                                                        class="w-7 h-7 rounded-lg hover:bg-purple-100 flex items-center justify-center text-sm transition"
                                                        :title="emo">
                                                    <span x-text="emo"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- 12. Status --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Status</label>
                                    <select x-model="form.is_active"
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-sm py-2 px-3 focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                        <option :value="1">Active</option>
                                        <option :value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Section B: Description & Notes --}}
                        <div class="pt-2">
                            <div class="flex items-center gap-2 pb-2 mb-4 border-b border-slate-100">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section B</span>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">Description & Notes</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-5">
                                {{-- Description --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Description</label>
                                    <textarea x-model="form.description"
                                              rows="3"
                                              placeholder="Brief summary or portfolio profile of the corporate group..."
                                              class="w-full rounded-xl border-slate-200 text-xs sm:text-sm py-2 px-3 focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"></textarea>
                                </div>

                                {{-- Notes --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Notes</label>
                                    <textarea x-model="form.notes"
                                              rows="3"
                                              placeholder="Internal governance, statutory or financial remarks..."
                                              class="w-full rounded-xl border-slate-200 text-xs sm:text-sm py-2 px-3 focus:border-purple-500 focus:ring-purple-500 shadow-2xs placeholder:text-slate-400"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Linked Companies / Sister Concerns (Preservation) --}}
                        <div class="pt-2">
                            <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded">Entities</span>
                                    <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">Sister Concerns / Member Companies</h4>
                                </div>
                                <span class="text-[11px] text-slate-400">Select sister companies attached to this group</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-40 overflow-y-auto p-3 rounded-xl border border-slate-200 bg-slate-50/50">
                                <template x-for="comp in allCompanies" :key="comp.id">
                                    <label class="flex items-center gap-2 text-xs p-1.5 rounded-lg hover:bg-white transition cursor-pointer select-none">
                                        <input type="checkbox"
                                               :value="comp.id"
                                               :checked="isCompanySelected(comp.id)"
                                               @change="toggleCompany(comp.id)"
                                               class="rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                                        <span class="font-medium text-slate-700 truncate" x-text="comp.name"></span>
                                        <span class="text-[10px] font-mono text-slate-400 ml-auto" x-text="comp.code"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                    </div>

                    {{-- Fixed Footer --}}
                    <div class="shrink-0 px-6 py-4 bg-slate-50/95 backdrop-blur-xs border-t border-slate-100 flex items-center justify-between sticky bottom-0 z-10">
                        <div>
                            <p x-show="formErrorSummary" class="text-xs text-red-600 font-semibold" x-text="formErrorSummary"></p>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <button type="button"
                                    @click="closeFormModal()"
                                    :disabled="isSaving"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                    :disabled="isSaving"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 text-xs font-semibold shadow-xs transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="isSaving" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isSaving ? 'Saving...' : (modalMode === 'add' ? 'Add Group' : 'Update Group')"></span>
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </template>

    {{-- ========================================================================= --}}
    {{-- MODAL 2: VIEW BUSINESS GROUP DETAILS MODAL                                --}}
    {{-- ========================================================================= --}}
    <template x-teleport="body">
        <div x-show="isViewModalOpen"
             x-cloak
             class="fixed inset-0 z-[100] overflow-y-auto"
             aria-labelledby="view-modal-title"
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
                     class="pointer-events-auto relative w-full max-w-3xl max-h-[88vh] flex flex-col bg-white rounded-2xl text-left shadow-2xl border border-slate-200 overflow-hidden my-auto"
                     style="width: min(800px, calc(100vw - 32px)); max-width: min(800px, calc(100vw - 32px));"
                     @click.stop>

                {{-- Header with Purple Theme --}}
                <div class="shrink-0 px-6 py-5 bg-gradient-to-r from-purple-800 to-indigo-900 text-white flex items-center justify-between sticky top-0 z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-2xl font-emoji">
                            <span x-text="viewGroup?.logo_emoji || '🏢'"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-white" id="view-modal-title" x-text="viewGroup?.name"></h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                      :class="viewGroup?.is_active ? 'bg-emerald-500/20 text-emerald-200 border border-emerald-400/30' : 'bg-slate-500/20 text-slate-300 border border-slate-400/30'"
                                      x-text="viewGroup?.is_active ? 'Active' : 'Inactive'"></span>
                            </div>
                            <p class="text-xs text-purple-200 font-mono mt-0.5" x-text="'Code: ' + (viewGroup?.code || '—')"></p>
                        </div>
                    </div>
                    <button type="button"
                            @click="isViewModalOpen = false"
                            class="rounded-lg p-1.5 text-white/70 hover:text-white hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                {{-- Details Content --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-5 text-xs">
                    {{-- Key Info Cards --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block">Parent Company</span>
                            <span class="font-bold text-slate-900 text-sm mt-0.5 block truncate" x-text="viewGroup?.parent_company?.name || '—'"></span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block">Turnover</span>
                            <span class="font-bold text-purple-800 text-sm mt-0.5 block" x-text="viewGroup?.calculated_turnover || '₹0.00'"></span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block">Companies</span>
                            <span class="font-bold text-blue-700 text-sm mt-0.5 block" x-text="(viewGroup?.calculated_companies_count ?? viewGroup?.links_count ?? 0) + ' Entities'"></span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block">Employees</span>
                            <span class="font-bold text-slate-800 text-sm mt-0.5 block" x-text="viewGroup?.calculated_employees ? viewGroup.calculated_employees : '—'"></span>
                        </div>
                    </div>

                    {{-- Comprehensive Metadata --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-slate-100 pt-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-500">Industry:</span>
                                <span class="font-semibold text-slate-800" x-text="viewGroup?.industry || '—'"></span>
                            </div>
                            <div class="flex items-center justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-500">Headquarters:</span>
                                <span class="font-semibold text-slate-800" x-text="viewGroup?.headquarters || '—'"></span>
                            </div>
                            <div class="flex items-center justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-500">Country:</span>
                                <span class="font-semibold text-slate-800" x-text="viewGroup?.country || 'India'"></span>
                            </div>
                            <div class="flex items-center justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-500">Incorporation Date:</span>
                                <span class="font-semibold text-slate-800" x-text="viewGroup?.incorporation_date ? new Date(viewGroup.incorporation_date).toLocaleDateString() : '—'"></span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-500">Website:</span>
                                <template x-if="viewGroup?.website">
                                    <a :href="viewGroup.website.startsWith('http') ? viewGroup.website : 'https://' + viewGroup.website"
                                       target="_blank"
                                       class="font-semibold text-purple-700 hover:underline truncate max-w-[180px]"
                                       x-text="viewGroup.website"></a>
                                </template>
                                <template x-if="!viewGroup?.website">
                                    <span class="text-slate-400">—</span>
                                </template>
                            </div>
                            <div class="flex items-center justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-500">Email:</span>
                                <template x-if="viewGroup?.email">
                                    <a :href="'mailto:' + viewGroup.email" class="font-semibold text-purple-700 hover:underline" x-text="viewGroup.email"></a>
                                </template>
                                <template x-if="!viewGroup?.email">
                                    <span class="text-slate-400">—</span>
                                </template>
                            </div>
                            <div class="flex items-center justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-500">Phone:</span>
                                <span class="font-semibold text-slate-800" x-text="viewGroup?.phone || '—'"></span>
                            </div>
                            <div class="flex items-center justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-500">System Protected:</span>
                                <span class="font-semibold" :class="viewGroup?.code === 'DEFAULT' ? 'text-amber-600' : 'text-slate-600'" x-text="viewGroup?.code === 'DEFAULT' ? 'Yes (Default Group)' : 'No'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Description & Notes --}}
                    <div class="border-t border-slate-100 pt-4 space-y-3">
                        <div x-show="viewGroup?.description">
                            <span class="text-slate-400 font-semibold block mb-1">Description</span>
                            <p class="text-slate-700 bg-slate-50 rounded-xl p-3 border border-slate-100" x-text="viewGroup?.description"></p>
                        </div>
                        <div x-show="viewGroup?.notes">
                            <span class="text-slate-400 font-semibold block mb-1">Internal Notes</span>
                            <p class="text-slate-700 bg-amber-50/50 rounded-xl p-3 border border-amber-100/60" x-text="viewGroup?.notes"></p>
                        </div>
                    </div>

                    {{-- Attached Sister Concerns --}}
                    <div class="border-t border-slate-100 pt-4" x-show="viewGroup?.companies && viewGroup?.companies.length > 0">
                        <span class="text-slate-400 font-semibold block mb-2">Attached Companies</span>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="c in viewGroup?.companies" :key="c.id">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-purple-50 text-purple-800 border border-purple-200 text-xs font-semibold">
                                    <span x-text="c.name"></span>
                                    <span class="font-mono text-[10px] text-purple-600 bg-white px-1 py-0.2 rounded" x-text="c.code"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="shrink-0 px-6 py-3.5 bg-slate-50/95 backdrop-blur-xs border-t border-slate-100 flex items-center justify-end gap-2 sticky bottom-0 z-10">
                    <button type="button"
                            @click="isViewModalOpen = false"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                        Close
                    </button>
                    <button type="button"
                            @click="isViewModalOpen = false; openEditModal(viewGroup)"
                            class="inline-flex items-center justify-center gap-1 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 text-xs font-semibold shadow-xs transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        <span>Edit Group</span>
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
             aria-labelledby="delete-modal-title"
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

            <div class="relative flex min-h-screen items-center justify-center p-4 sm:p-6 lg:p-8 pointer-events-none">
                <div x-show="showDeleteModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="pointer-events-auto relative inline-block bg-white rounded-2xl text-left overflow-hidden shadow-2xl sm:max-w-md w-full border border-slate-200 p-6 my-auto"
                     @click.stop>
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-red-100 sm:mx-0 sm:h-10 sm:w-10 text-red-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-base font-bold text-slate-900" id="delete-modal-title">Delete Sister Concern Group</h3>
                            <div class="mt-2 space-y-2">
                                <p class="text-xs text-slate-600">
                                    Are you sure you want to delete this group? This action cannot be undone.
                                </p>
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 font-medium">Group Name:</span>
                                        <span class="text-slate-900 font-bold" x-text="deletingGroupName"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 font-medium">Group Code:</span>
                                        <span class="font-mono text-slate-700 bg-white px-1.5 py-0.5 rounded border border-slate-200 text-[11px]" x-text="deletingGroupCode"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 font-medium">Attached Companies:</span>
                                        <span class="text-slate-900 font-bold" x-text="deletingGroupCompanies + ' companies'"></span>
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
                            <span x-show="!isDeleting">Delete</span>
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
function businessGroupPage(companiesData) {
    return {
        allCompanies: companiesData || [],
        parentCompanySearch: '',
        parentCompanyDropdownOpen: false,

        // Add/Edit Modal state
        isFormModalOpen: false,
        modalMode: 'add', // 'add' or 'edit'
        editingGroupId: null,
        isSaving: false,
        formErrorSummary: '',
        errors: {},

        // Form fields
        form: {
            name: '',
            code: '',
            parent_company_id: '',
            incorporation_date: '',
            headquarters: '',
            country: 'India',
            industry: '',
            website: '',
            email: '',
            phone: '',
            logo_emoji: '🏢',
            is_active: 1,
            description: '',
            notes: '',
            company_ids: [],
        },

        // View Modal state
        isViewModalOpen: false,
        viewGroup: null,

        // Delete Modal state
        showDeleteModal: false,
        isDeleting: false,
        deletingGroupId: null,
        deletingGroupName: '',
        deletingGroupCode: '',
        deletingGroupCompanies: 0,

        // Notification toast
        notification: {
            show: false,
            type: 'success',
            message: '',
        },

        get filteredCompanies() {
            if (!this.parentCompanySearch || !this.parentCompanySearch.trim()) {
                return this.allCompanies;
            }
            const q = this.parentCompanySearch.toLowerCase().trim();
            return this.allCompanies.filter(c => {
                return (c.name && c.name.toLowerCase().includes(q)) ||
                       (c.code && c.code.toLowerCase().includes(q));
            });
        },

        selectParentCompany(c) {
            this.form.parent_company_id = c.id;
            this.parentCompanySearch = c.name;
            this.parentCompanyDropdownOpen = false;
            this.errors.parent_company_id = '';
            if (!this.form.company_ids.includes(c.id)) {
                this.form.company_ids.push(c.id);
            }
        },

        clearParentCompany() {
            this.form.parent_company_id = '';
            this.parentCompanySearch = '';
        },

        isCompanySelected(id) {
            return this.form.company_ids.includes(id) || String(this.form.parent_company_id) === String(id);
        },

        toggleCompany(id) {
            const index = this.form.company_ids.indexOf(id);
            if (index > -1) {
                this.form.company_ids.splice(index, 1);
            } else {
                this.form.company_ids.push(id);
            }
        },

        openAddModal() {
            this.modalMode = 'add';
            this.editingGroupId = null;
            this.errors = {};
            this.formErrorSummary = '';

            let defaultParentId = '';
            let defaultParentName = '';
            if (this.allCompanies.length > 0) {
                const defComp = this.allCompanies.find(c => c.is_default) || this.allCompanies[0];
                defaultParentId = defComp.id;
                defaultParentName = defComp.name;
            }

            this.form = {
                name: '',
                code: '',
                parent_company_id: defaultParentId,
                incorporation_date: '',
                headquarters: '',
                country: 'India',
                industry: '',
                website: '',
                email: '',
                phone: '',
                logo_emoji: '🏢',
                is_active: 1,
                description: '',
                notes: '',
                company_ids: defaultParentId ? [defaultParentId] : [],
            };
            this.parentCompanySearch = defaultParentName;
            this.parentCompanyDropdownOpen = false;
            this.isFormModalOpen = true;
        },

        openEditModal(item) {
            this.modalMode = 'edit';
            this.editingGroupId = item.id;
            this.errors = {};
            this.formErrorSummary = '';

            let parentName = '';
            if (item.parent_company_id) {
                const found = this.allCompanies.find(c => String(c.id) === String(item.parent_company_id));
                parentName = found ? found.name : (item.parent_company?.name || '');
            } else if (item.parent_company) {
                parentName = item.parent_company.name;
            }

            const attachedIds = [];
            if (item.companies && Array.isArray(item.companies)) {
                item.companies.forEach(c => attachedIds.push(c.id));
            }
            if (item.parent_company_id && !attachedIds.includes(item.parent_company_id)) {
                attachedIds.push(item.parent_company_id);
            }

            // Format date YYYY-MM-DD
            let incDate = '';
            if (item.incorporation_date) {
                incDate = item.incorporation_date.substring(0, 10);
            }

            this.form = {
                name: item.name || '',
                code: item.code || '',
                parent_company_id: item.parent_company_id || '',
                incorporation_date: incDate,
                headquarters: item.headquarters || '',
                country: item.country || 'India',
                industry: item.industry || '',
                website: item.website || '',
                email: item.email || '',
                phone: item.phone || '',
                logo_emoji: item.logo_emoji || '🏢',
                is_active: item.is_active ? 1 : 0,
                description: item.description || '',
                notes: item.notes || '',
                company_ids: attachedIds,
            };

            this.parentCompanySearch = parentName;
            this.parentCompanyDropdownOpen = false;
            this.isFormModalOpen = true;
        },

        closeFormModal() {
            if (this.isSaving) return;
            this.isFormModalOpen = false;
            this.errors = {};
            this.formErrorSummary = '';
        },

        openViewModal(item) {
            this.viewGroup = item;
            this.isViewModalOpen = true;
        },

        validateFrontend() {
            this.errors = {};
            this.formErrorSummary = '';
            let valid = true;

            if (!this.form.name || !this.form.name.trim()) {
                this.errors.name = 'Group Name is required.';
                valid = false;
            }

            if (!this.form.code || !this.form.code.trim()) {
                this.errors.code = 'Group Code is required.';
                valid = false;
            }

            if (this.allCompanies.length > 0 && !this.form.parent_company_id) {
                this.errors.parent_company_id = 'Parent Company is required.';
                valid = false;
            }

            if (this.form.email && this.form.email.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.form.email.trim())) {
                    this.errors.email = 'Please enter a valid email address.';
                    valid = false;
                }
            }

            if (!valid) {
                this.formErrorSummary = 'Please fill in all required fields marked with an asterisk (*).';
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
                ? "{{ route('organization.business-groups.store') }}"
                : `{{ url('organization/business-groups') }}/${this.editingGroupId}`;

            const payload = {
                name: this.form.name.trim(),
                code: this.form.code.trim().toUpperCase(),
                parent_company_id: this.form.parent_company_id || null,
                incorporation_date: this.form.incorporation_date || null,
                headquarters: this.form.headquarters ? this.form.headquarters.trim() : null,
                country: this.form.country ? this.form.country.trim() : 'India',
                industry: this.form.industry ? this.form.industry.trim() : null,
                website: this.form.website ? this.form.website.trim() : null,
                email: this.form.email ? this.form.email.trim() : null,
                phone: this.form.phone ? this.form.phone.trim() : null,
                logo_emoji: this.form.logo_emoji ? this.form.logo_emoji.trim() : '🏢',
                is_active: Number(this.form.is_active) === 1 ? 1 : 0,
                description: this.form.description ? this.form.description.trim() : null,
                notes: this.form.notes ? this.form.notes.trim() : null,
                company_ids: this.form.company_ids || [],
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
                    this.showNotification('success', data.message || (isAdd ? 'Group added successfully.' : 'Group updated successfully.'));
                    await this.refreshList();
                } else if (response.status === 422) {
                    if (data.errors) {
                        for (const key in data.errors) {
                            this.errors[key] = data.errors[key][0];
                        }
                    }
                    this.formErrorSummary = data.message || 'Please correct the validation errors in the form.';
                } else {
                    this.formErrorSummary = data.message || 'An error occurred while saving the business group.';
                }
            } catch (err) {
                console.error('Save group error:', err);
                this.formErrorSummary = 'Network error: could not save business group. Please try again.';
            } finally {
                this.isSaving = false;
            }
        },

        promptDelete(id, name, code, companiesCount) {
            this.deletingGroupId = id;
            this.deletingGroupName = name;
            this.deletingGroupCode = code;
            this.deletingGroupCompanies = companiesCount || 0;
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingGroupId = null;
            this.deletingGroupName = '';
            this.deletingGroupCode = '';
            this.deletingGroupCompanies = 0;
        },

        async executeDelete() {
            if (!this.deletingGroupId || this.isDeleting) return;
            this.isDeleting = true;

            const url = `{{ url('organization/business-groups') }}/${this.deletingGroupId}`;
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
                    this.showNotification('success', data.message || 'Sister concern group deleted successfully.');
                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete sister concern group.');
                    this.showDeleteModal = false;
                }
            } catch (err) {
                console.error('Delete error:', err);
                this.showNotification('error', 'A network error occurred while deleting the group.');
                this.showDeleteModal = false;
            } finally {
                this.isDeleting = false;
                this.deletingGroupId = null;
            }
        },

        async refreshList() {
            const container = document.querySelector('#listing-container');
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
                    const newContainer = doc.querySelector('#listing-container');
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
