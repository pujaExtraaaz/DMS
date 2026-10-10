@extends('layouts.dms')
@section('title', 'CRM Leads')
@section('content')
<div x-data="leadListManager()" class="space-y-4">
    <!-- Live delete notification banner -->
    <div x-show="notification.show"
         x-transition
         x-cloak
         :class="notification.type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-800 border-red-200'"
         class="rounded-lg border p-3 text-sm flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2">
            <span class="font-bold shrink-0" x-text="notification.type === 'success' ? '✓' : '⚠'"></span>
            <span x-text="notification.message"></span>
        </div>
        <button type="button" @click="notification.show = false" class="text-xs font-semibold opacity-70 hover:opacity-100">Dismiss</button>
    </div>

    <div id="listing-container" data-dynamic-container>
        <x-ui.page-header title="CRM Leads">
            <x-slot name="actions">
                <x-ui.button :href="route('crm.leads.bulk-upload')" variant="secondary">
                    <svg class="w-4 h-4 mr-1.5 -ml-0.5 inline-block text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Bulk Upload
                </x-ui.button>
                <x-ui.button :href="route('crm.leads.create')" variant="primary">+ New Lead</x-ui.button>
            </x-slot>
        </x-ui.page-header>

        @if(session('bulk_errors_file'))
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    <div>
                        <h4 class="text-xs font-semibold text-amber-900">Some rows could not be imported</h4>
                        <p class="text-xs text-amber-700">A detailed error report CSV is available for inspection, correction, and re-import.</p>
                    </div>
                </div>
                <a href="{{ route('crm.leads.bulk-upload.errors', ['filename' => session('bulk_errors_file')]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download Error Report CSV
                </a>
            </div>
        @endif

        <x-ui.card>
            <x-ui.table-toolbar
                :search="$search ?? request('search')"
                search-placeholder="Search contact name, company, mobile, email, tag..."
                :reset-url="route('crm.leads.index')"
            >
                <x-slot name="filters">
                    <select
                        name="status"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Status: All</option>
                        @foreach(['new','contacted','follow_up','qualified','unqualified','converted','lost'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s === 'follow_up' ? 'Follow-up' : ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </x-slot>
            </x-ui.table-toolbar>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <x-ui.sortable-th column="name" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Lead</x-ui.sortable-th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Contact</th>
                            <x-ui.sortable-th column="source" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Source</x-ui.sortable-th>
                            <x-ui.sortable-th column="assignee" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Assignee</x-ui.sortable-th>
                            <x-ui.sortable-th column="status" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Status</x-ui.sortable-th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($items as $item)
                            <tr class="hover:bg-slate-50" id="lead-row-{{ $item->id }}">
                                <td class="px-3 py-2 font-medium text-slate-900">
                                    {{ $item->name }}
                                    @if($item->company_name ?: $item->organization)
                                        <div class="text-xs text-slate-500">{{ $item->company_name ?: $item->organization }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-slate-600">
                                    {{ $item->mobile ?? '—' }}
                                    @if($item->email)
                                        <div class="text-xs text-slate-400">{{ $item->email }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-slate-600">{{ $item->source?->name ?: 'Meta' }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $item->assignee?->name ?: '—' }}</td>
                                <td class="px-3 py-2">
                                    @php
                                        $statusKey = in_array($item->status, ['followup', 'follow-up'], true) ? 'follow_up' : $item->status;
                                        $badgeVariant = match($statusKey) {
                                            'qualified', 'converted' => 'success',
                                            'contacted' => 'primary',
                                            'follow_up' => 'warning',
                                            'lost', 'unqualified' => 'danger',
                                            'new' => 'info',
                                            default => 'neutral',
                                        };
                                        $statusLabel = match($statusKey) {
                                            'follow_up' => 'Follow-up',
                                            default => ucfirst($statusKey),
                                        };
                                    @endphp
                                    <div x-show="editingStatusId !== {{ $item->id }}" class="flex items-center gap-1.5 flex-wrap">
                                        <x-ui.badge :variant="$badgeVariant">
                                            {{ $statusLabel }}
                                        </x-ui.badge>
                                        @if($item->converted_customer_id)
                                            <a href="{{ route('masters.customers.show', $item->converted_customer_id) }}"
                                               class="text-[11px] font-medium text-emerald-700 hover:text-emerald-900 hover:underline inline-block"
                                               title="View converted Party">
                                                ({{ $item->convertedCustomer?->code ?: 'Party #'.$item->converted_customer_id }})
                                            </a>
                                        @endif
                                        @if(auth()->user()?->hasRole('super-admin') || auth()->user()?->hasAnyPermission(['crm.manage', 'crm.edit']))
                                            <button type="button"
                                                    @click="startEditStatus({{ $item->id }}, '{{ $statusKey }}')"
                                                    class="text-[11px] font-medium text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center gap-0.5 ml-1"
                                                    title="Change status for {{ $item->name }}"
                                                    data-change-status="{{ $item->id }}">
                                                Change
                                            </button>
                                        @endif
                                    </div>
                                    <div x-show="editingStatusId === {{ $item->id }}" x-cloak class="flex items-center gap-1 mt-1">
                                        <select x-model="selectedStatus" :disabled="isSavingStatus" class="rounded border-slate-300 py-1 pl-2 pr-6 text-xs text-slate-800 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="new">New</option>
                                            <option value="contacted">Contacted</option>
                                            <option value="follow_up">Follow-up</option>
                                            <option value="qualified">Qualified</option>
                                            <option value="lost">Lost</option>
                                        </select>
                                        <button type="button"
                                                @click="saveStatus({{ $item->id }})"
                                                :disabled="isSavingStatus"
                                                class="inline-flex items-center rounded bg-indigo-600 px-2 py-1 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50">
                                            <span x-show="!isSavingStatus">Save</span>
                                            <span x-show="isSavingStatus">...</span>
                                        </button>
                                        <button type="button"
                                                @click="cancelEditStatus()"
                                                :disabled="isSavingStatus"
                                                class="inline-flex items-center rounded border border-slate-300 bg-white px-1.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-50"
                                                title="Cancel">
                                            ✕
                                        </button>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap space-x-1">
                                    @php
                                        $isQualified = ($item->status === 'qualified');
                                        $isConverted = ($item->status === 'converted' || (bool) $item->converted_customer_id);
                                    @endphp
                                    @if($isConverted)
                                        <button type="button"
                                                disabled
                                                class="inline-flex items-center justify-center font-medium rounded-lg opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border border-slate-200 px-2.5 py-1.5 text-xs"
                                                title="Already converted to Customer"
                                                aria-label="Already converted to Customer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </button>
                                    @elseif($isQualified)
                                        <button type="button"
                                                @click="promptConvert({{ $item->id }}, '{{ addslashes($item->company_name ?: $item->name) }}', '{{ addslashes($item->name) }}')"
                                                class="inline-flex items-center justify-center font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500 shadow-sm px-2.5 py-1.5 text-xs"
                                                title="Convert to Customer"
                                                aria-label="Convert to Customer"
                                                data-convert-lead="{{ $item->id }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                                        </button>
                                    @else
                                        <button type="button"
                                                disabled
                                                class="inline-flex items-center justify-center font-medium rounded-lg opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border border-slate-200 px-2.5 py-1.5 text-xs"
                                                title="Conversion unavailable: Lead must have Qualified status"
                                                aria-label="Conversion unavailable: Lead must have Qualified status">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                                        </button>
                                    @endif
                                    <x-ui.button size="sm" variant="secondary" :href="route('crm.leads.show', $item)" class="!px-2.5 !py-1.5" title="View Lead" aria-label="View Lead">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </x-ui.button>
                                    <x-ui.button size="sm" variant="secondary" :href="route('crm.leads.edit', $item)" class="!px-2.5 !py-1.5" title="Edit Lead" aria-label="Edit Lead">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </x-ui.button>
                                    <form method="POST" action="{{ route('crm.leads.destroy', $item) }}" class="inline delete-lead-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                data-delete-lead="{{ $item->id }}"
                                                data-lead-name="{{ $item->name }}"
                                                class="inline-flex items-center justify-center font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-sm px-2.5 py-1.5 text-xs"
                                                title="Delete Lead"
                                                aria-label="Delete Lead">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-slate-500" data-empty="true">
                                    <x-ui.empty-state title="No leads found" description="Try adjusting your search or filters." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $items->links() }}</div>
        </x-ui.card>
    </div>

    <!-- Confirmation Modal -->
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true"
         @keydown.escape.window="cancelDelete()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
                 @click="cancelDelete()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Dialog panel -->
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <div class="bg-white p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10 text-red-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Lead</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm text-slate-600">
                                    Are you sure you want to delete this lead? This action cannot be undone.
                                </p>
                                <p class="text-xs text-slate-500 font-medium pt-1" x-show="deletingLeadName">
                                    Selected Lead: <span class="text-slate-800 font-semibold" x-text="deletingLeadName"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 flex flex-row-reverse gap-2.5 border-t border-slate-100">
                    <button type="button"
                            @click="executeDelete()"
                            :disabled="isDeleting"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isDeleting" class="inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete
                        </span>
                        <span x-show="isDeleting" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            Deleting...
                        </span>
                    </button>
                    <button type="button"
                            @click="cancelDelete()"
                            :disabled="isDeleting"
                            class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm border border-slate-300 hover:bg-slate-50 transition disabled:opacity-50">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Convert to Customer Modal -->
    <div x-show="showConvertModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="convert-modal-title" role="dialog" aria-modal="true"
         @keydown.escape.window="cancelConvert()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="showConvertModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                 @click="cancelConvert()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showConvertModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <div class="bg-white p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10 text-emerald-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="convert-modal-title">Convert Lead to Customer</h3>
                            <div class="mt-2 space-y-2">
                                <p class="text-sm text-slate-600">
                                    Are you sure you want to convert this lead into a Customer (Party)? You will be redirected to the customer form to review and complete party details before saving.
                                </p>
                                <div class="rounded-lg bg-slate-50 border border-slate-100 p-2.5 text-xs text-slate-700">
                                    <div><span class="text-slate-500 font-medium">Party / Company:</span> <span class="font-semibold text-slate-900" x-text="convertingLeadTitle"></span></div>
                                    <div x-show="convertingLeadContact && convertingLeadContact !== convertingLeadTitle" class="mt-1"><span class="text-slate-500 font-medium">Contact Person:</span> <span class="text-slate-800" x-text="convertingLeadContact"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 flex flex-row-reverse gap-2.5 border-t border-slate-100">
                    <button type="button"
                            @click="proceedToConvertForm()"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Convert
                    </button>
                    <button type="button"
                            @click="cancelConvert()"
                            class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm border border-slate-300 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-delete-lead]');
    if (btn) {
        e.preventDefault();
        const id = btn.getAttribute('data-delete-lead');
        const name = btn.getAttribute('data-lead-name');
        window.dispatchEvent(new CustomEvent('confirm-lead-delete', { detail: { id, name } }));
    }
});

function leadListManager() {
    return {
        // Delete state
        showDeleteModal: false,
        deletingLeadId: null,
        deletingLeadName: '',
        isDeleting: false,

        // Status editing state
        editingStatusId: null,
        selectedStatus: '',
        isSavingStatus: false,

        // Convert state
        showConvertModal: false,
        convertingLeadId: null,
        convertingLeadTitle: '',
        convertingLeadContact: '',
        isConverting: false,
        duplicateWarning: null,

        notification: { show: false, type: 'success', message: '' },

        init() {
            window.addEventListener('confirm-lead-delete', (e) => {
                this.confirmDelete(e.detail.id, e.detail.name);
            });
        },

        confirmDelete(id, name) {
            this.deletingLeadId = id;
            this.deletingLeadName = name || 'this lead';
            this.showDeleteModal = true;
        },
        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingLeadId = null;
            this.deletingLeadName = '';
        },
        async executeDelete() {
            if (!this.deletingLeadId || this.isDeleting) return;
            this.isDeleting = true;

            const url = `{{ url('crm/leads') }}/${this.deletingLeadId}`;
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
                    this.showNotification('success', data.message || 'Lead deleted successfully.');

                    const rows = document.querySelectorAll('#listing-container tbody tr:not([data-empty])');
                    const currentUrl = new URL(window.location.href);
                    let currentPage = parseInt(currentUrl.searchParams.get('page') || '1', 10);

                    if (rows.length <= 1 && currentPage > 1) {
                        currentUrl.searchParams.set('page', currentPage - 1);
                        window.history.replaceState(null, '', currentUrl.toString());
                    }

                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete lead.');
                    this.showDeleteModal = false;
                }
            } catch (err) {
                console.error('Delete error:', err);
                this.showNotification('error', 'A network error occurred while deleting the lead.');
                this.showDeleteModal = false;
            } finally {
                this.isDeleting = false;
                this.deletingLeadId = null;
            }
        },

        // Status Management
        startEditStatus(id, currentStatus) {
            this.editingStatusId = id;
            this.selectedStatus = currentStatus;
        },
        cancelEditStatus() {
            this.editingStatusId = null;
            this.selectedStatus = '';
        },
        async saveStatus(id) {
            if (this.isSavingStatus || !this.selectedStatus) return;
            this.isSavingStatus = true;

            const url = `{{ url('crm/leads') }}/${id}/status`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status: this.selectedStatus }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.editingStatusId = null;
                    this.showNotification('success', data.message || 'Status updated successfully.');
                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to update status.');
                }
            } catch (err) {
                console.error('Status update error:', err);
                this.showNotification('error', 'A network error occurred while updating status.');
            } finally {
                this.isSavingStatus = false;
            }
        },

        // Convert to Customer
        promptConvert(id, title, contact) {
            this.convertingLeadId = id;
            this.convertingLeadTitle = title || 'this lead';
            this.convertingLeadContact = contact || '';
            this.duplicateWarning = null;
            this.showConvertModal = true;
        },
        proceedToConvertForm() {
            if (!this.convertingLeadId) return;
            window.location.href = `{{ route('masters.customers.create') }}?lead_id=${this.convertingLeadId}`;
        },
        cancelConvert() {
            if (this.isConverting) return;
            this.showConvertModal = false;
            this.convertingLeadId = null;
            this.convertingLeadTitle = '';
            this.convertingLeadContact = '';
            this.duplicateWarning = null;
        },
        async executeConvert(linkExisting = false) {
            if (!this.convertingLeadId || this.isConverting) return;
            this.isConverting = true;

            const url = `{{ url('crm/leads') }}/${this.convertingLeadId}/convert`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ link_existing: linkExisting }),
                });

                const data = await response.json();

                if (response.status === 409 && data.duplicate_detected) {
                    this.duplicateWarning = data;
                    return;
                }

                if (response.ok && data.success) {
                    this.showConvertModal = false;
                    this.showNotification('success', data.message || 'Lead converted successfully.');
                    this.convertingLeadId = null;
                    this.duplicateWarning = null;
                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to convert lead.');
                }
            } catch (err) {
                console.error('Convert error:', err);
                this.showNotification('error', 'A network error occurred while converting the lead.');
            } finally {
                this.isConverting = false;
            }
        },

        // Shared dynamic table refresh
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
