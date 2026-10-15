@extends('layouts.dms')
@section('title', 'Purchase Invoices')
@section('content')
<div id="listing-container" data-dynamic-container x-data="purchaseInvoiceIndex()">
    <x-ui.page-header title="Purchase Invoices">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('purchasing.invoices.create')">+ New PI</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search invoice no, supplier..."
            :reset-url="route('purchasing.invoices.index')"
        >
            <x-slot name="filters">
                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Status: All</option>
                    @foreach(['draft', 'pending', 'approved', 'paid', 'partially_paid', 'cancelled'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                    @endforeach
                </select>

                <select
                    name="supplier_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Supplier: All</option>
                    @foreach($suppliers ?? [] as $s)
                        <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
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
            <x-slot name="actions">
                <div class="relative inline-block text-left" x-data="{ open: false }">
                    <button
                        type="button"
                        @click="open = !open"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none"
                    >
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Export</span>
                        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div
                        x-show="open"
                        x-cloak
                        @click.away="open = false"
                        class="absolute right-0 z-20 mt-1 w-36 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                    >
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="flex items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">
                            Excel (.xlsx)
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="flex items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">
                            CSV
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="flex items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">
                            PDF
                        </a>
                    </div>
                </div>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="invoice_number" :current-sort="$sort ?? request('sort', 'invoice_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Invoice No
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="supplier_invoice_number" :current-sort="$sort ?? request('sort', 'invoice_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Supplier Inv
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="supplier" :current-sort="$sort ?? request('sort', 'invoice_date')" :current-direction="$direction ?? request('direction', 'desc')">
                            Supplier
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="invoice_date" :current-sort="$sort ?? request('sort', 'invoice_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc">
                            Date
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="total_amount" :current-sort="$sort ?? request('sort', 'invoice_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc" align="right">
                            Total
                        </x-ui.sortable-th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium font-mono text-xs text-slate-900">
                                {{ $invoice->invoice_number ?? $invoice->invoice_no }}
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-xs text-slate-600">
                                {{ $invoice->supplier_invoice_number ?? $invoice->supplier_invoice_no ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">
                                {{ $invoice->supplier?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $invoice->invoice_date?->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-medium text-slate-900">
                                ₹{{ number_format($invoice->total_amount ?? $invoice->grand_total, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap space-x-1">
                                <x-ui.button
                                    size="sm"
                                    variant="secondary"
                                    :href="route('purchasing.invoices.preview', $invoice)"
                                    title="Preview Purchase Invoice"
                                    aria-label="Preview Purchase Invoice"
                                >
                                    Preview
                                </x-ui.button>
                                <x-ui.button
                                    size="sm"
                                    variant="secondary"
                                    :href="route('purchasing.invoices.show', $invoice)"
                                    title="View Purchase Invoice"
                                    aria-label="View Purchase Invoice"
                                >
                                    View
                                </x-ui.button>
                                @if($invoice->isEditable())
                                    <x-ui.button
                                        size="sm"
                                        variant="secondary"
                                        :href="route('purchasing.invoices.edit', $invoice)"
                                        class="!px-2.5 !py-1.5 inline-flex items-center gap-1"
                                        title="Edit Purchase Invoice"
                                        aria-label="Edit Purchase Invoice"
                                    >
                                        <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        <span>Edit</span>
                                    </x-ui.button>
                                @else
                                    <button type="button" disabled class="inline-flex items-center gap-1 font-medium rounded-lg opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border border-slate-200 px-2.5 py-1.5 text-xs" title="Cannot edit: invoice is {{ str_replace('_', ' ', $invoice->status) }}" aria-label="Cannot edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        <span>Edit</span>
                                    </button>
                                @endif
                                @if($invoice->canBeDeleted())
                                    <form method="POST" action="{{ route('purchasing.invoices.destroy', $invoice) }}" class="inline delete-pi-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                @click="promptDelete({
                                                    id: '{{ $invoice->id }}',
                                                    invoiceNumber: '{{ $invoice->invoice_number ?? $invoice->invoice_no }}',
                                                    supplierName: '{{ addslashes($invoice->supplier?->name ?? '—') }}',
                                                    invoiceDate: '{{ $invoice->invoice_date?->format('d M Y') ?? '—' }}',
                                                    grandTotal: '₹{{ number_format($invoice->total_amount ?? $invoice->grand_total, 2) }}'
                                                })"
                                                class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-sm px-2.5 py-1.5 text-xs"
                                                title="Delete Purchase Invoice"
                                                aria-label="Delete Purchase Invoice"
                                                data-delete-pi="{{ $invoice->id }}"
                                                data-pi-no="{{ $invoice->invoice_number ?? $invoice->invoice_no }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                @else
                                    <button type="button"
                                            disabled
                                            class="inline-flex items-center gap-1 font-medium rounded-lg opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border border-slate-200 px-2.5 py-1.5 text-xs"
                                            title="{{ $invoice->getDeleteBlockReason() ?? 'Cannot delete purchase invoice' }}"
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
                            <td colspan="6" class="p-0">
                                <x-ui.empty-state
                                    title="No purchase invoices found"
                                    description="Try clearing search or filters to see more results."
                                    class="border-0 rounded-none py-10"
                                >
                                    <x-slot name="action">
                                        <a href="{{ route('purchasing.invoices.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
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
        <div class="mt-4">{{ $invoices->links() }}</div>
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
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Purchase Invoice?</h3>
                            
                            <div class="mt-3 bg-slate-50 rounded-lg p-3 border border-slate-100 text-xs space-y-1.5">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Invoice Number:</span>
                                    <span class="font-bold font-mono text-slate-800" x-text="deletingInvoiceNo"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Supplier:</span>
                                    <span class="font-semibold text-slate-800" x-text="deletingSupplierName"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Invoice Date:</span>
                                    <span class="text-slate-700" x-text="deletingInvoiceDate"></span>
                                </div>
                                <div class="flex justify-between border-t border-slate-200 pt-1 font-semibold">
                                    <span class="text-slate-600">Grand Total:</span>
                                    <span class="text-indigo-600 font-mono" x-text="deletingGrandTotal"></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <p class="text-xs text-amber-700 bg-amber-50 p-2.5 rounded border border-amber-200 leading-relaxed">
                                    <strong>Warning:</strong> Deleting this purchase invoice may affect linked accounting and inventory records.
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
                        <span x-show="!isDeleting">Delete Invoice</span>
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
function purchaseInvoiceIndex() {
    return {
        showDeleteModal: false,
        deletingInvoiceId: null,
        deletingInvoiceNo: '',
        deletingSupplierName: '',
        deletingInvoiceDate: '',
        deletingGrandTotal: '',
        isDeleting: false,
        notification: { show: false, type: 'success', message: '' },

        promptDelete(data) {
            this.deletingInvoiceId = data.id;
            this.deletingInvoiceNo = data.invoiceNumber || 'this purchase invoice';
            this.deletingSupplierName = data.supplierName || '—';
            this.deletingInvoiceDate = data.invoiceDate || '—';
            this.deletingGrandTotal = data.grandTotal || '—';
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingInvoiceId = null;
            this.deletingInvoiceNo = '';
            this.deletingSupplierName = '';
            this.deletingInvoiceDate = '';
            this.deletingGrandTotal = '';
        },

        async executeDelete() {
            if (!this.deletingInvoiceId || this.isDeleting) return;
            this.isDeleting = true;

            const url = `{{ url('purchasing/invoices') }}/${this.deletingInvoiceId}`;
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
                    this.showNotification('success', data.message || 'Purchase invoice deleted successfully.');

                    const rows = document.querySelectorAll('#listing-container tbody tr:not([data-empty])');
                    const currentUrl = new URL(window.location.href);
                    let currentPage = parseInt(currentUrl.searchParams.get('page') || '1', 10);

                    if (rows.length <= 1 && currentPage > 1) {
                        currentUrl.searchParams.set('page', currentPage - 1);
                        window.history.replaceState(null, '', currentUrl.toString());
                    }

                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete purchase invoice.');
                    this.showDeleteModal = false;
                }
            } catch (err) {
                console.error('Delete error:', err);
                this.showNotification('error', 'A network error occurred while deleting the purchase invoice.');
                this.showDeleteModal = false;
            } finally {
                this.isDeleting = false;
                this.deletingInvoiceId = null;
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
