@extends('layouts.dms')
@section('title', 'Parties')
@section('content')
<div id="listing-container" data-dynamic-container x-data="partyIndex()">
    <x-ui.page-header title="Parties">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('masters.customers.create')">+ Add Party</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search Name, Code, Phone, GSTIN..."
            :reset-url="route('masters.customers.index')"
        >
            <x-slot name="filters">
                <select
                    name="party_type"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Party Type: All</option>
                    @foreach([\App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_DEBTORS => 'Sundry Debtors', \App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_CREDITORS => 'Sundry Creditors', \App\Domains\Master\Models\Customer::PARTY_TYPE_BOTH => 'Both'] as $val => $label)
                        <option value="{{ $val }}" @selected(request('party_type') === $val)>{{ $label }}</option>
                    @endforeach
                </select>

                <select
                    name="area_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Area: All</option>
                    @foreach($areas ?? [] as $a)
                        <option value="{{ $a->id }}" @selected(request('area_id') == $a->id)>{{ $a->name }}</option>
                    @endforeach
                </select>

                <select
                    name="customer_type_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Category: All</option>
                    @foreach($customerTypes ?? [] as $ct)
                        <option value="{{ $ct->id }}" @selected(request('customer_type_id') == $ct->id)>{{ $ct->name }}</option>
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

        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <x-ui.sortable-th column="name" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                        Party Name
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="code" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                        Code
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="party_type" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                        Party Type
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="phone" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                        Phone
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="gstin" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                        GSTIN
                    </x-ui.sortable-th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Area</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </x-slot>
            @forelse($items as $item)
                <tr class="hover:bg-slate-50/75 transition-colors">
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">
                        <a href="{{ route('masters.customers.edit', $item) }}" class="text-indigo-600 hover:text-indigo-800 hover:underline">
                            {{ $item->name }}
                        </a>
                        @if($item->businessGroup)
                            <div class="text-[11px] text-purple-700 font-normal flex items-center gap-1 mt-0.5" title="Business Group">
                                <span class="font-emoji">{{ $item->businessGroup->logo_emoji ?: '🏢' }}</span>
                                <span class="truncate max-w-[200px]">{{ $item->businessGroup->name }}</span>
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm font-mono text-slate-600">{{ $item->code }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $item->party_type_key === 'sundry_creditors' ? 'bg-amber-100 text-amber-800' : ($item->party_type_key === 'both' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800') }}">
                            {{ $item->party_type_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">{{ $item->phone ?: '—' }}</td>
                    <td class="px-6 py-4 text-sm font-mono text-slate-600">{{ $item->gstin ?: '—' }}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">{{ $item->area?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button variant="ghost" size="sm" :href="route('masters.customers.edit', $item)">Edit</x-ui.button>
                            <button type="button"
                                    @click="promptDelete('{{ $item->id }}', '{{ addslashes($item->name) }}', '{{ addslashes($item->code) }}')"
                                    data-delete-customer="{{ $item->id }}"
                                    data-delete-party="{{ $item->id }}"
                                    class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:text-red-700 hover:bg-red-50 transition border border-transparent hover:border-red-200"
                                    title="Delete Party">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Delete</span>
                            </button>
                            <form id="delete-form-{{ $item->id }}" method="POST" action="{{ route('masters.customers.destroy', $item) }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="p-0">
                        <x-ui.empty-state
                            title="No matching parties found"
                            description="Try clearing search or filters to see more results."
                            class="border-0 rounded-none py-10"
                        >
                            <x-slot name="action">
                                <a href="{{ route('masters.customers.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                    Reset Filters
                                </a>
                            </x-slot>
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
        @if($items->hasPages())
            <div class="mt-4">{{ $items->links() }}</div>
        @endif
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
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"
                 @click="cancelDelete()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="sm:flex sm:items-start gap-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left flex-1">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Party</h3>
                            <div class="mt-2 space-y-3">
                                <p class="text-xs text-slate-500">Are you sure you want to delete this party? If related records (orders, purchases, invoices, or financial records) exist, it will be safely archived to preserve historical data.</p>
                                
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs text-left">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Party Name:</span>
                                        <span class="font-semibold text-slate-900" x-text="deletingPartyName"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Party Code:</span>
                                        <span class="font-mono text-slate-700" x-text="deletingPartyCode"></span>
                                    </div>
                                </div>
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
function partyIndex() {
    return {
        showDeleteModal: false,
        isDeleting: false,
        deletingPartyId: null,
        deletingPartyName: '',
        deletingPartyCode: '',
        notification: {
            show: false,
            type: 'success',
            message: '',
        },

        promptDelete(id, name, code) {
            this.deletingPartyId = id;
            this.deletingPartyName = name;
            this.deletingPartyCode = code;
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingPartyId = null;
            this.deletingPartyName = '';
            this.deletingPartyCode = '';
        },

        async executeDelete() {
            if (!this.deletingPartyId || this.isDeleting) return;
            this.isDeleting = true;

            const url = `{{ url('masters/customers') }}/${this.deletingPartyId}`;
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
                    this.showNotification('success', data.message || 'Party deleted successfully.');

                    const rows = document.querySelectorAll('#listing-container tbody tr:not([data-empty])');
                    const currentUrl = new URL(window.location.href);
                    let currentPage = parseInt(currentUrl.searchParams.get('page') || '1', 10);

                    if (rows.length <= 1 && currentPage > 1) {
                        currentUrl.searchParams.set('page', currentPage - 1);
                        window.history.replaceState(null, '', currentUrl.toString());
                    }

                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete party.');
                }
            } catch (err) {
                console.error(err);
                this.showNotification('error', 'Network error occurred while deleting party.');
            } finally {
                this.isDeleting = false;
            }
        },

        async refreshList() {
            try {
                const res = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) {
                    window.location.reload();
                    return;
                }
                const html = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.querySelector('#listing-container');
                const currentContainer = document.querySelector('#listing-container');

                if (newContainer && currentContainer) {
                    const currentTableWrapper = currentContainer.querySelector('.overflow-x-auto');
                    const newTableWrapper = newContainer.querySelector('.overflow-x-auto');
                    if (currentTableWrapper && newTableWrapper) {
                        currentTableWrapper.innerHTML = newTableWrapper.innerHTML;
                        if (window.Alpine && typeof Alpine.initTree === 'function') {
                            Alpine.initTree(currentTableWrapper);
                        }
                    }

                    const currentPagination = currentContainer.querySelector('.mt-4');
                    const newPagination = newContainer.querySelector('.mt-4');
                    if (currentPagination && newPagination) {
                        currentPagination.innerHTML = newPagination.innerHTML;
                    } else if (!currentPagination && newPagination) {
                        currentTableWrapper.insertAdjacentElement('afterend', newPagination);
                    } else if (currentPagination && !newPagination) {
                        currentPagination.remove();
                    }
                } else {
                    window.location.reload();
                }
            } catch (e) {
                console.error('Failed to refresh table dynamic container:', e);
                window.location.reload();
            }
        },

        showNotification(type, message) {
            this.notification.type = type;
            this.notification.message = message;
            this.notification.show = true;
            setTimeout(() => {
                this.notification.show = false;
            }, 4500);
        }
    };
}
</script>
@endpush
@endsection