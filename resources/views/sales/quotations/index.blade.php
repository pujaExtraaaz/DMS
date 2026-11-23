@extends('layouts.dms')
@section('title', 'Quotations')
@section('content')
@php
    $canDelete = auth()->user()?->hasRole('super-admin')
        || auth()->user()?->can('quotations.delete')
        || auth()->user()?->can('quotations.manage')
        || auth()->user()?->can('sales.delete')
        || auth()->user()?->can('sales.manage');
@endphp
<div id="listing-container" data-dynamic-container x-data="quotationIndex()">
    <x-ui.page-header title="Quotations">
        <x-slot name="actions">
            <x-ui.export-menu />
            <x-ui.button variant="primary" :href="route('quotations.create')">+ New Quotation</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search', request('q'))"
            search-placeholder="Search Quotation No, Customer, Notes..."
            :reset-url="route('quotations.index')"
        >
            <x-slot name="filters">
                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Status: All</option>
                    @foreach(['draft', 'sent', 'accepted', 'rejected', 'converted'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>

                <select
                    name="customer_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Customer: All</option>
                    @foreach($customers ?? [] as $c)
                        <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>

                <div class="flex items-center gap-1">
                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        data-dynamic-filter
                        placeholder="From"
                        title="From Date"
                        class="rounded-lg border-slate-300 py-1 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    />
                    <span class="text-xs text-slate-400">to</span>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        data-dynamic-filter
                        placeholder="To"
                        title="To Date"
                        class="rounded-lg border-slate-300 py-1 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    />
                </div>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="quotation_no" :current-sort="$sort ?? request('sort', 'quotation_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Quotation No
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="customer" :current-sort="$sort ?? request('sort', 'quotation_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Customer
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="quotation_date" :current-sort="$sort ?? request('sort', 'quotation_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc">
                            Date
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="status" :current-sort="$sort ?? request('sort', 'quotation_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Status
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="grand_total" :current-sort="$sort ?? request('sort', 'quotation_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc" align="right">
                            Total
                        </x-ui.sortable-th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($items as $item)
                        <tr id="quotation-row-{{ $item->id }}" class="hover:bg-slate-50/75 transition-colors">
                            <td class="px-3 py-2 font-medium font-mono text-xs text-slate-900">{{ $item->quotation_no }}</td>
                            <td class="px-3 py-2 text-slate-900 font-medium">{{ $item->customer?->name }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->quotation_date?->format('d M Y') }}</td>
                            <td class="px-3 py-2">
                                <x-ui.badge variant="info">{{ ucfirst($item->status) }}</x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right font-medium text-slate-900">₹{{ number_format($item->grand_total, 2) }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap space-x-1">
                                <x-ui.button variant="secondary" size="sm" :href="route('quotations.show', $item)">View</x-ui.button>
                                <x-ui.button variant="secondary" size="sm" :href="route('quotations.preview', $item)" target="_blank">Preview</x-ui.button>
                                @if($item->isEditable())
                                    <x-ui.button variant="secondary" size="sm" :href="route('quotations.edit', $item)">Edit</x-ui.button>
                                @endif
                                @if($canDelete)
                                    <x-ui.button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        @click="promptDelete({{ $item->id }}, '{{ $item->quotation_no }}', '{{ addslashes($item->customer?->name ?? 'N/A') }}')"
                                        data-delete-quotation="{{ $item->id }}"
                                    >
                                        Delete
                                    </x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                <x-ui.empty-state
                                    title="No quotations found"
                                    description="Try clearing search or filters to see more results."
                                    class="border-0 rounded-none py-10"
                                >
                                    <x-slot name="action">
                                        <a href="{{ route('quotations.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
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
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true"
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

            <!-- Modal Panel -->
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
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Quotation</h3>
                            <div class="mt-2 space-y-3">
                                <p class="text-xs text-slate-500">Are you sure you want to delete this quotation? This action cannot be undone.</p>

                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs text-left">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Quotation Number:</span>
                                        <span class="font-mono font-bold text-slate-900" x-text="deletingQuotationNo"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Customer:</span>
                                        <span class="font-semibold text-slate-800" x-text="deletingCustomerName"></span>
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
                        <span x-show="!isDeleting">Delete</span>
                        <span x-show="isDeleting" class="inline-flex items-center gap-1.5" x-cloak>
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
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-4 right-4 z-50 max-w-sm rounded-lg p-4 shadow-lg text-white text-sm"
         :class="notification.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
        <div class="flex items-center gap-2">
            <span x-text="notification.message"></span>
            <button type="button" @click="notification.show = false" class="ml-auto text-white/80 hover:text-white">&times;</button>
        </div>
    </div>
</div>

<script>
function quotationIndex() {
    return {
        showDeleteModal: false,
        isDeleting: false,
        deletingId: null,
        deletingQuotationNo: '',
        deletingCustomerName: '',
        notification: {
            show: false,
            type: 'success',
            message: '',
            timer: null,
        },

        promptDelete(id, quotationNo, customerName) {
            this.deletingId = id;
            this.deletingQuotationNo = quotationNo;
            this.deletingCustomerName = customerName;
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingId = null;
            this.deletingQuotationNo = '';
            this.deletingCustomerName = '';
        },

        showToast(message, type = 'success') {
            if (this.notification.timer) {
                clearTimeout(this.notification.timer);
            }
            this.notification.message = message;
            this.notification.type = type;
            this.notification.show = true;
            this.notification.timer = setTimeout(() => {
                this.notification.show = false;
            }, 3500);
        },

        async executeDelete() {
            if (!this.deletingId || this.isDeleting) return;
            this.isDeleting = true;

            const deletedId = this.deletingId;
            const quotationNo = this.deletingQuotationNo;
            const url = `{{ url('quotations') }}/${deletedId}`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json().catch(() => null);

                if (response.ok && data?.success) {
                    this.showDeleteModal = false;
                    this.showToast(data.message || 'Quotation deleted successfully.', 'success');

                    const row = document.getElementById(`quotation-row-${deletedId}`);
                    if (row) {
                        row.remove();
                    }

                    const remainingRows = document.querySelectorAll('#listing-container tbody tr[id^="quotation-row-"]');
                    if (remainingRows.length === 0) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 600);
                    } else {
                        this.refreshList();
                    }
                } else {
                    const errorMsg = data?.message || 'Failed to delete quotation.';
                    this.showToast(errorMsg, 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Network error while deleting quotation.', 'error');
            } finally {
                this.isDeleting = false;
                if (!this.showDeleteModal) {
                    this.deletingId = null;
                    this.deletingQuotationNo = '';
                    this.deletingCustomerName = '';
                }
            }
        },

        async refreshList() {
            try {
                const url = window.location.href;
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.ok) {
                    const text = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(text, 'text/html');
                    const newTable = doc.querySelector('#listing-container table');
                    const oldTable = document.querySelector('#listing-container table');
                    if (newTable && oldTable) {
                        oldTable.innerHTML = newTable.innerHTML;
                    }
                    const newPagination = doc.querySelector('#listing-container .mt-4');
                    const oldPagination = document.querySelector('#listing-container .mt-4');
                    if (newPagination && oldPagination) {
                        oldPagination.innerHTML = newPagination.innerHTML;
                    }
                }
            } catch (e) {
                console.error('Failed to refresh list', e);
            }
        }
    };
}
</script>
@endsection
