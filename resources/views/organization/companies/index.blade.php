@extends('layouts.dms')
@section('title', 'Companies')
@section('content')
<div id="listing-container" data-dynamic-container x-data="companyIndex()">
    <x-ui.page-header title="Companies">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('organization.companies.create')">+ Add Company</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search Company Name, Code, GSTIN..."
            :reset-url="route('organization.companies.index')"
        >
            <x-slot name="filters">
                <select
                    name="business_group_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Business Group: All</option>
                    @foreach($businessGroups ?? [] as $bg)
                        <option value="{{ $bg->id }}" @selected(request('business_group_id') == $bg->id)>{{ $bg->name }}</option>
                    @endforeach
                </select>

                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Status: All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Company Name
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="code" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Code
                        </x-ui.sortable-th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Details</th>
                        <x-ui.sortable-th column="is_active" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Status
                        </x-ui.sortable-th>
                        <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-900">
                                <div class="flex items-center gap-2">
                                    <span>{{ $item->name }}</span>
                                    @if($item->is_default)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-xs" title="Default Company">
                                            Default
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 font-mono text-xs">{{ $item->code }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->gstin ?: '—' }} · {{ $item->businessGroup?->name ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($item->is_active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                                <x-ui.button variant="secondary" size="sm" :href="route('organization.companies.show', $item)" title="View Company" aria-label="View Company">View</x-ui.button>
                                <x-ui.button variant="secondary" size="sm" :href="route('organization.companies.edit', $item)" title="Edit Company" aria-label="Edit Company">Edit</x-ui.button>
                                @if($item->is_default)
                                    <button type="button" disabled class="inline-flex items-center gap-1 font-medium rounded-lg opacity-80 cursor-default bg-indigo-50 text-indigo-700 border border-indigo-200 px-2.5 py-1.5 text-xs shadow-xs" title="Currently selected default company" aria-label="Default Company">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Default Company</span>
                                    </button>
                                @else
                                    @if($item->is_active)
                                        <button type="button"
                                                @click="setDefaultCompany('{{ $item->id }}', '{{ addslashes($item->name) }}')"
                                                :disabled="isUpdatingDefault"
                                                class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-indigo-500 shadow-xs px-2.5 py-1.5 text-xs disabled:opacity-50"
                                                title="Set as Default Company"
                                                aria-label="Set as Default Company"
                                                data-set-default="{{ $item->id }}">
                                            <span>Set as Default</span>
                                        </button>
                                    @else
                                        <button type="button"
                                                disabled
                                                class="inline-flex items-center gap-1 font-medium rounded-lg opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border border-slate-200 px-2.5 py-1.5 text-xs"
                                                title="Cannot set an inactive company as default"
                                                aria-label="Cannot set default">
                                            <span>Set as Default</span>
                                        </button>
                                    @endif
                                @endif
                                @if($item->canBeDeleted())
                                    <button type="button"
                                            @click="promptDelete('{{ $item->id }}', '{{ addslashes($item->name) }}')"
                                            class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-sm px-2.5 py-1.5 text-xs"
                                            title="Delete Company"
                                            aria-label="Delete Company"
                                            data-delete-company="{{ $item->id }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Delete</span>
                                    </button>
                                @else
                                    <button type="button"
                                            disabled
                                            class="inline-flex items-center gap-1 font-medium rounded-lg opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border border-slate-200 px-2.5 py-1.5 text-xs"
                                            title="{{ $item->getDeleteBlockReason() ?? 'Cannot delete company' }}"
                                            aria-label="Cannot delete">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Delete</span>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-0">
                                <x-ui.empty-state
                                    title="No matching companies found"
                                    description="Try clearing search or filters to see more results."
                                    class="border-0 rounded-none py-10"
                                >
                                    <x-slot name="action">
                                        <a href="{{ route('organization.companies.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
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
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>

    <!-- Delete Confirmation Modal -->
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
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Company</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm text-slate-600">
                                    Are you sure you want to delete this company? This action cannot be undone.
                                </p>
                                <p class="text-xs text-slate-500 font-medium pt-1" x-show="deletingCompanyName">
                                    Company: <span class="text-slate-800 font-bold" x-text="deletingCompanyName"></span>
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
                        <span x-show="!isDeleting">Confirm Delete</span>
                        <span x-show="isDeleting" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Deleting...
                        </span>
                    </button>
                    <button type="button"
                            @click="cancelDelete()"
                            :disabled="isDeleting"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div x-show="notification.show"
         x-cloak
         x-transition
         class="fixed bottom-4 right-4 z-50 max-w-sm rounded-lg p-4 shadow-lg text-white text-sm"
         :class="notification.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
        <div class="flex items-center gap-2">
            <span x-text="notification.message"></span>
            <button type="button" @click="notification.show = false" class="ml-auto text-white/80 hover:text-white">&times;</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function companyIndex() {
    return {
        showDeleteModal: false,
        deletingCompanyId: null,
        deletingCompanyName: '',
        isDeleting: false,
        isUpdatingDefault: false,
        notification: { show: false, type: 'success', message: '' },

        async setDefaultCompany(id, name) {
            if (!id || this.isUpdatingDefault) return;
            this.isUpdatingDefault = true;

            const url = `{{ url('organization/companies') }}/${id}/set-default`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showNotification('success', data.message || `Company '${name}' is now set as the default company.`);
                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to set default company.');
                }
            } catch (err) {
                console.error('Set default error:', err);
                this.showNotification('error', 'A network error occurred while updating the default company.');
            } finally {
                this.isUpdatingDefault = false;
            }
        },

        promptDelete(id, name) {
            this.deletingCompanyId = id;
            this.deletingCompanyName = name || 'this company';
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingCompanyId = null;
            this.deletingCompanyName = '';
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

                    const rows = document.querySelectorAll('#listing-container tbody tr:not([data-empty])');
                    const currentUrl = new URL(window.location.href);
                    let currentPage = parseInt(currentUrl.searchParams.get('page') || '1', 10);

                    if (rows.length <= 1 && currentPage > 1) {
                        currentUrl.searchParams.set('page', currentPage - 1);
                        window.history.replaceState(null, '', currentUrl.toString());
                    }

                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete company.');
                    this.showDeleteModal = false;
                }
            } catch (err) {
                console.error('Delete error:', err);
                this.showNotification('error', 'A network error occurred while deleting the company.');
                this.showDeleteModal = false;
            } finally {
                this.isDeleting = false;
                this.deletingCompanyId = null;
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
