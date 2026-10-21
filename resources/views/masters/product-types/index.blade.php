@extends('layouts.dms')
@section('title', 'Product Types · Masters')

@section('content')
<div id="listing-container" data-dynamic-container x-data="productTypesManager()" class="space-y-6">

    {{-- Toast Notification --}}
    <div x-cloak
         x-show="notification.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed top-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl border text-sm max-w-md"
         :class="notification.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-red-50 border-red-200 text-red-900'">
        <div class="shrink-0 w-8 h-8 rounded-xl flex items-center justify-center font-bold"
             :class="notification.type === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
            <span x-text="notification.type === 'success' ? '✓' : '!'"></span>
        </div>
        <div class="flex-1 text-xs font-medium" x-text="notification.message"></div>
        <button type="button" @click="notification.show = false" class="text-slate-400 hover:text-slate-600">✕</button>
    </div>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-100">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center shadow-md shadow-indigo-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Product Types</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Define, classify, and manage standard item classifications across products and inventory.</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="openAddModal()"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Product Type</span>
            </button>
        </div>
    </div>

    {{-- Summary Statistics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Types --}}
        <div class="bg-white rounded-2xl p-4 border border-indigo-100 shadow-2xs relative overflow-hidden group hover:border-indigo-200 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">Total Types</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-slate-900">{{ number_format($totalCount ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Defined product categories</div>
        </div>

        {{-- Active Types --}}
        <div class="bg-white rounded-2xl p-4 border border-emerald-100 shadow-2xs relative overflow-hidden group hover:border-emerald-200 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Active Types</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-emerald-700">{{ number_format($activeCount ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Available for product assignment</div>
        </div>

        {{-- Inactive Types --}}
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs relative overflow-hidden group hover:border-slate-300 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Inactive Types</span>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-slate-700">{{ number_format($inactiveCount ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Deactivated / archived records</div>
        </div>

        {{-- Associated Products --}}
        <div class="bg-white rounded-2xl p-4 border border-purple-100 shadow-2xs relative overflow-hidden group hover:border-purple-200 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-purple-700">Catalog Coverage</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-purple-700">
                {{ number_format($items->sum('products_count')) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">Products assigned on this page</div>
        </div>
    </div>

    {{-- Main Card --}}
    <x-ui.card>
        {{-- Table Toolbar --}}
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search Product Type Name, Code, Description..."
            :reset-url="route('masters.product-types.index')"
        >
            <x-slot name="filters">
                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-xl border-slate-200 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs"
                >
                    <option value="">Status: All</option>
                    <option value="active" @selected(($status ?? request('status')) === 'active')>Active</option>
                    <option value="inactive" @selected(($status ?? request('status')) === 'inactive')>Inactive</option>
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/75">
                    <tr>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Product Type Name
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="code" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Code
                        </x-ui.sortable-th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Description</th>
                        <x-ui.sortable-th column="products_count" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Assigned Products
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="is_active" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Status
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="created_at" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Created Date
                        </x-ui.sortable-th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            {{-- Name --}}
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($item->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-900 text-sm tracking-tight hover:text-indigo-600 cursor-pointer"
                                             @click="openViewModal('{{ $item->id }}')">
                                            {{ $item->name }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Code --}}
                            <td class="px-4 py-3.5 font-mono text-xs text-slate-600">
                                <span class="bg-slate-100 px-2 py-0.5 rounded text-[11px] font-medium text-slate-700">
                                    {{ $item->code ?: '—' }}
                                </span>
                            </td>

                            {{-- Description --}}
                            <td class="px-4 py-3.5 text-xs text-slate-500 max-w-xs truncate" title="{{ $item->description }}">
                                {{ $item->description ? Str::limit($item->description, 50) : '—' }}
                            </td>

                            {{-- Products Count --}}
                            <td class="px-4 py-3.5 text-xs text-slate-600">
                                @if(($item->products_count ?? 0) > 0)
                                    <a href="{{ route('masters.products.index', ['search' => $item->name]) }}"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <span>{{ $item->products_count }} {{ Str::plural('Product', $item->products_count) }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400">0 Products</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $item->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $item->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            {{-- Created Date --}}
                            <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                                {{ $item->created_at ? $item->created_at->format('d M Y') : '—' }}
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- View --}}
                                    <button type="button"
                                            @click="openViewModal('{{ $item->id }}')"
                                            class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                                            title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>

                                    {{-- Edit --}}
                                    <button type="button"
                                            @click="openEditModal({
                                                id: '{{ $item->id }}',
                                                name: '{{ addslashes($item->name) }}',
                                                code: '{{ addslashes($item->code ?? '') }}',
                                                description: '{{ addslashes($item->description ?? '') }}',
                                                is_active: {{ $item->is_active ? 'true' : 'false' }}
                                            })"
                                            class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                                            title="Edit Product Type">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    {{-- Delete / Deactivate --}}
                                    <button type="button"
                                            @click="promptDelete('{{ $item->id }}', '{{ addslashes($item->name) }}', {{ ($item->products_count ?? 0) }})"
                                            class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                            title="Delete or Deactivate Product Type">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-0">
                                <x-ui.empty-state
                                    title="No matching product types found"
                                    description="Try clearing search or filters to see more results."
                                    class="border-0 rounded-none py-12"
                                >
                                    <x-slot name="action">
                                        <a href="{{ route('masters.product-types.index') }}" data-reset-filters class="inline-flex items-center rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                            Reset Filters
                                        </a>
                                    </x-slot>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="mt-4 pt-3 border-t border-slate-100">
                {{ $items->links() }}
            </div>
        @endif
    </x-ui.card>

    {{-- ============================================================= --}}
    {{-- ADD / EDIT PRODUCT TYPE MODAL                                 --}}
    {{-- ============================================================= --}}
    <div x-cloak
         x-show="showFormModal"
         class="fixed inset-y-0 right-0 z-50 overflow-y-auto transition-all duration-300"
         :class="($root && $root.sidebarCollapsed) ? 'left-0 lg:left-[72px]' : 'left-0 lg:left-64'"
         role="dialog"
         aria-modal="true"
         aria-labelledby="form-modal-title"
         @keydown.escape.window="closeFormModal()">

        {{-- Backdrop covering main content area, keeping sidebar visible --}}
        <div x-show="showFormModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-y-0 right-0 bg-slate-900/60 backdrop-blur-xs transition-all duration-300"
             :class="($root && $root.sidebarCollapsed) ? 'left-0 lg:left-[72px]' : 'left-0 lg:left-64'"
             @click="closeFormModal()"></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6 lg:p-8 pointer-events-none">
            <div x-show="showFormModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                 class="pointer-events-auto relative w-full max-w-[700px] max-h-[85vh] flex flex-col bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden my-auto"
                 style="max-width: min(700px, 100%);"
                 @click.stop>

                {{-- Modal Header --}}
                <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/80 sticky top-0 z-10 backdrop-blur-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-sm shadow-sm shadow-indigo-100">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900" id="form-modal-title" x-text="isEditMode ? 'Edit Product Type' : 'Add New Product Type'"></h3>
                            <p class="text-xs text-slate-500">Provide product classification details below.</p>
                        </div>
                    </div>
                    <button type="button"
                            @click="closeFormModal()"
                            class="text-slate-400 hover:text-slate-600 rounded-lg p-1.5 hover:bg-slate-100 transition"
                            title="Close modal">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Modal Body and Sticky Footer inside Form --}}
                <form @submit.prevent="submitForm()" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    {{-- Scrollable Form Content --}}
                    <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                        {{-- Error Summary Banner --}}
                        <div x-show="formErrorSummary"
                             x-text="formErrorSummary"
                             class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl"
                             style="display: none;"></div>

                        {{-- Product Type Name --}}
                        <div>
                            <label for="form_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Product Type Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="form_name"
                                x-model="form.name"
                                @blur="applyTitleCase()"
                                @input="errors.name = ''"
                                placeholder="e.g. Finished Goods, Spare Part, Hardware"
                                maxlength="100"
                                class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs placeholder:text-slate-400 py-2.5 px-3.5"
                                :class="errors.name ? 'border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500' : ''"
                            >
                            <p class="mt-1 text-[11px] text-slate-500">
                                Automatic Title Case formatting is applied on save (e.g. Finished Goods, Spare Part).
                            </p>
                            <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600 font-medium"></p>
                        </div>

                        {{-- Code --}}
                        <div>
                            <label for="form_code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Code
                            </label>
                            <input
                                type="text"
                                id="form_code"
                                x-model="form.code"
                                :readonly="isEditMode"
                                placeholder="Auto-generated on save"
                                class="w-full rounded-xl border-slate-200 text-sm font-mono shadow-2xs placeholder:text-slate-400 py-2.5 px-3.5"
                                :class="isEditMode ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : 'focus:border-indigo-500 focus:ring-indigo-500'"
                            >
                            <p class="mt-1 text-[11px] text-slate-500">
                                <span x-show="!isEditMode">Leave blank to auto-assign sequential code (e.g. PT-GLOBAL-00001).</span>
                                <span x-show="isEditMode">System code is permanent to preserve catalog and transaction history.</span>
                            </p>
                        </div>

                        {{-- Description --}}
                        <div>
                            <label for="form_description" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Description <span class="text-xs font-normal text-slate-400 lowercase">(optional)</span>
                            </label>
                            <textarea
                                id="form_description"
                                x-model="form.description"
                                rows="3"
                                maxlength="1000"
                                placeholder="Enter short description or purpose for this product type..."
                                class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs placeholder:text-slate-400 py-2.5 px-3.5"
                            ></textarea>
                        </div>

                        {{-- Active Toggle --}}
                        <div class="pt-2 border-t border-slate-100">
                            <label class="inline-flex items-center gap-3 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    x-model="form.is_active"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                                >
                                <div>
                                    <span class="text-sm font-semibold text-slate-800">Active</span>
                                    <p class="text-xs text-slate-500">Active types are available for assignment across the product catalog.</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Sticky Modal Footer --}}
                    <div class="shrink-0 flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/80 sticky bottom-0 z-10 backdrop-blur-xs">
                        <button type="button"
                                @click="closeFormModal()"
                                :disabled="isSaving"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                            Cancel
                        </button>
                        <button type="submit"
                                :disabled="isSaving"
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-indigo-600 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition">
                            <svg x-show="isSaving" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isSaving ? 'Saving...' : (isEditMode ? 'Update Product Type' : 'Create Product Type')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- VIEW DETAILS MODAL                                            --}}
    {{-- ============================================================= --}}
    <div x-cloak
         x-show="showViewModal"
         class="fixed inset-y-0 right-0 z-50 overflow-y-auto transition-all duration-300"
         :class="($root && $root.sidebarCollapsed) ? 'left-0 lg:left-[72px]' : 'left-0 lg:left-64'"
         role="dialog"
         aria-modal="true"
         @keydown.escape.window="showViewModal = false">

        {{-- Backdrop --}}
        <div x-show="showViewModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-y-0 right-0 bg-slate-900/60 backdrop-blur-xs transition-all duration-300"
             :class="($root && $root.sidebarCollapsed) ? 'left-0 lg:left-[72px]' : 'left-0 lg:left-64'"
             @click="showViewModal = false"></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6 lg:p-8 pointer-events-none">
            <div x-show="showViewModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                 class="pointer-events-auto relative w-full max-w-lg max-h-[85vh] flex flex-col bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden my-auto"
                 style="max-width: min(560px, 100%);"
                 @click.stop>

                <div class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/80 sticky top-0 z-10 backdrop-blur-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                        <h3 class="text-sm font-bold text-slate-900">Product Type Details</h3>
                    </div>
                    <button type="button" @click="showViewModal = false" class="text-slate-400 hover:text-slate-600 rounded-lg p-1.5 hover:bg-slate-100 transition">✕</button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-4" x-show="viewData">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Name</span>
                            <div class="text-base font-bold text-slate-900" x-text="viewData?.name"></div>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                              :class="viewData?.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                              x-text="viewData?.is_active ? 'Active' : 'Inactive'"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-slate-50 p-3 rounded-xl">
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Code</span>
                            <div class="font-mono font-bold text-slate-800 mt-0.5" x-text="viewData?.code || '—'"></div>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-xl">
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Linked Products</span>
                            <div class="font-bold text-indigo-700 mt-0.5" x-text="(viewData?.products_count || 0) + ' Products'"></div>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-xl text-xs">
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Description</span>
                        <p class="text-slate-700 mt-1" x-text="viewData?.description || 'No description provided.'"></p>
                    </div>

                    <div class="pt-2 text-[11px] text-slate-400 flex items-center justify-between">
                        <span>Created: <strong class="text-slate-600" x-text="viewData?.created_at || '—'"></strong></span>
                    </div>
                </div>

                <div class="shrink-0 flex items-center justify-end gap-2 px-6 py-3.5 border-t border-slate-100 bg-slate-50/80 sticky bottom-0 z-10 backdrop-blur-xs">
                    <button type="button"
                            @click="showViewModal = false"
                            class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                        Close
                    </button>
                    <button type="button"
                            @click="showViewModal = false; openEditModal(viewData)"
                            class="px-4 py-2 rounded-xl bg-indigo-600 text-xs font-semibold text-white hover:bg-indigo-500 shadow-sm transition">
                        Edit
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- DELETE / DEACTIVATE CONFIRMATION MODAL                        --}}
    {{-- ============================================================= --}}
    <div x-cloak
         x-show="showDeleteModal"
         class="fixed inset-y-0 right-0 z-50 overflow-y-auto transition-all duration-300"
         :class="($root && $root.sidebarCollapsed) ? 'left-0 lg:left-[72px]' : 'left-0 lg:left-64'"
         role="dialog"
         aria-modal="true"
         @keydown.escape.window="cancelDelete()">

        {{-- Backdrop --}}
        <div x-show="showDeleteModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-y-0 right-0 bg-slate-900/60 backdrop-blur-xs transition-all duration-300"
             :class="($root && $root.sidebarCollapsed) ? 'left-0 lg:left-[72px]' : 'left-0 lg:left-64'"
             @click="cancelDelete()"></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6 lg:p-8 pointer-events-none">
            <div x-show="showDeleteModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                 class="pointer-events-auto relative w-full max-w-md max-h-[85vh] flex flex-col bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden my-auto"
                 style="max-width: min(480px, 100%);"
                 @click.stop>

                <div class="flex-1 overflow-y-auto p-6">
                    <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>

                    <h3 class="text-base font-bold text-slate-900 text-center mb-1">
                        Confirm Action for Product Type
                    </h3>

                    <p class="text-xs text-slate-600 text-center mb-4">
                        Are you sure you want to remove <strong class="text-slate-900" x-text="deletingTypeName"></strong>?
                    </p>

                    <div x-show="deletingProductCount > 0" class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 mb-4">
                        <div class="font-bold flex items-center gap-1.5 mb-0.5">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Protected Product References</span>
                        </div>
                        <p>This product type is assigned to <strong x-text="deletingProductCount"></strong> product(s). To protect historical catalog and transaction data, it will be <strong>safely deactivated</strong> instead of deleted permanently.</p>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button type="button"
                                @click="cancelDelete()"
                                :disabled="isDeleting"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="button"
                                @click="executeDelete()"
                                :disabled="isDeleting"
                                class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-red-600 text-xs font-semibold text-white shadow-sm hover:bg-red-700 disabled:opacity-50 transition">
                            <svg x-show="isDeleting" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isDeleting ? 'Processing...' : (deletingProductCount > 0 ? 'Deactivate Type' : 'Delete Type')"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Alpine.js Management Script --}}
<script>
function productTypesManager() {
    return {
        showFormModal: false,
        showViewModal: false,
        showDeleteModal: false,
        isEditMode: false,
        editingId: null,
        isSaving: false,
        isDeleting: false,
        deletingId: null,
        deletingTypeName: '',
        deletingProductCount: 0,
        viewData: null,

        notification: {
            show: false,
            type: 'success',
            message: ''
        },

        form: {
            name: '',
            code: '',
            description: '',
            is_active: true
        },

        errors: {},
        formErrorSummary: '',

        toTitleCase(str) {
            if (!str) return '';
            return str.replace(/\b\w+/g, function(s) {
                return s.charAt(0).toUpperCase() + s.substr(1).toLowerCase();
            });
        },

        applyTitleCase() {
            if (this.form.name) {
                this.form.name = this.toTitleCase(this.form.name.trim());
            }
        },

        openAddModal() {
            this.isEditMode = false;
            this.editingId = null;
            this.form = {
                name: '',
                code: '',
                description: '',
                is_active: true
            };
            this.errors = {};
            this.formErrorSummary = '';
            this.showFormModal = true;
        },

        openEditModal(item) {
            this.isEditMode = true;
            this.editingId = item.id;
            this.form = {
                name: item.name || '',
                code: item.code || '',
                description: item.description || '',
                is_active: Boolean(item.is_active)
            };
            this.errors = {};
            this.formErrorSummary = '';
            this.showFormModal = true;
        },

        closeFormModal() {
            if (this.isSaving) return;
            this.showFormModal = false;
            this.errors = {};
            this.formErrorSummary = '';
        },

        async openViewModal(id) {
            try {
                const res = await fetch(`{{ url('masters/product-types') }}/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success && data.item) {
                    this.viewData = data.item;
                    this.showViewModal = true;
                }
            } catch (err) {
                console.error('Error fetching product type:', err);
            }
        },

        async submitForm() {
            this.errors = {};
            this.formErrorSummary = '';

            const name = (this.form.name || '').trim();
            if (!name) {
                this.errors.name = 'Product Type Name is required.';
                this.formErrorSummary = 'Please provide a valid Product Type Name.';
                return;
            }

            this.applyTitleCase();
            this.isSaving = true;

            const isAdd = !this.isEditMode;
            const url = isAdd
                ? '{{ route("masters.product-types.store") }}'
                : `{{ url("masters/product-types") }}/${this.editingId}`;

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const payload = {
                name: this.form.name,
                code: this.form.code ? this.form.code.trim() : null,
                description: this.form.description ? this.form.description.trim() : null,
                is_active: Boolean(this.form.is_active)
            };

            try {
                const res = await fetch(url, {
                    method: isAdd ? 'POST' : 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    this.closeFormModal();
                    this.showToast('success', data.message || 'Product Type saved successfully.');
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else if (res.status === 422) {
                    if (data.errors) {
                        for (const key in data.errors) {
                            this.errors[key] = data.errors[key][0];
                        }
                    }
                    this.formErrorSummary = data.message || 'Please fix the errors indicated in the form.';
                } else {
                    this.formErrorSummary = data.message || 'An error occurred while saving.';
                }
            } catch (err) {
                console.error('Save product type error:', err);
                this.formErrorSummary = 'Network error: could not connect to server.';
            } finally {
                this.isSaving = false;
            }
        },

        promptDelete(id, name, productCount) {
            this.deletingId = id;
            this.deletingTypeName = name;
            this.deletingProductCount = productCount || 0;
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingId = null;
            this.deletingTypeName = '';
            this.deletingProductCount = 0;
        },

        async executeDelete() {
            if (!this.deletingId || this.isDeleting) return;
            this.isDeleting = true;

            const url = `{{ url("masters/product-types") }}/${this.deletingId}`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    this.showDeleteModal = false;
                    this.showToast('success', data.message || 'Product Type removed successfully.');
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    this.showToast('error', data.message || 'Failed to remove product type.');
                    this.showDeleteModal = false;
                }
            } catch (err) {
                console.error('Delete error:', err);
                this.showToast('error', 'Network error occurred while removing product type.');
                this.showDeleteModal = false;
            } finally {
                this.isDeleting = false;
                this.deletingId = null;
            }
        },

        showToast(type, message) {
            this.notification = { show: true, type, message };
            setTimeout(() => {
                this.notification.show = false;
            }, 5000);
        }
    };
}
</script>
@endsection

