@extends('layouts.dms')
@section('title', 'Stock Transfers')

@section('content')
<div id="listing-container" data-dynamic-container x-data="stockTransferIndex()" class="space-y-4">
    <x-ui.page-header title="Stock Transfers">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('inventory.transfers.create', ['tab' => $tab ?? 'location'])">
                + New Transfer
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <!-- Operation Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6 text-sm font-medium">
            <a href="{{ route('inventory.transfers.index', ['tab' => 'location']) }}"
               class="{{ ($tab ?? 'location') === 'location' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700' }} whitespace-nowrap py-3 px-1 border-b-2 flex items-center gap-2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                Location Transfers (Warehouse &rarr; Warehouse)
            </a>

            <a href="{{ route('inventory.transfers.index', ['tab' => 'name']) }}"
               class="{{ ($tab ?? 'location') === 'name' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700' }} whitespace-nowrap py-3 px-1 border-b-2 flex items-center gap-2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
                Item / Stock Name Transfers (Stock Reclassification)
            </a>
        </nav>
    </div>

    @if(($tab ?? 'location') === 'name')
        <!-- ITEM / STOCK NAME TRANSFERS TABLE -->
        <x-ui.card>
            <x-ui.table-toolbar
                :search="$search ?? request('search')"
                search-placeholder="Search Reference No, Remarks, Item Names..."
                :reset-url="route('inventory.transfers.index', ['tab' => 'name'])"
            >
                <x-slot name="filters">
                    <input type="hidden" name="tab" value="name" data-dynamic-filter>

                    <select
                        name="warehouse_id"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Warehouse: All</option>
                        @foreach($warehouses ?? [] as $w)
                            <option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>{{ $w->name }}</option>
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
                <table class="min-w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <x-ui.sortable-th column="reclassification_no" :current-sort="$sort ?? 'reclassification_date'" :current-direction="$direction ?? 'desc'">
                                Reference No
                            </x-ui.sortable-th>
                            <x-ui.sortable-th column="reclassification_date" :current-sort="$sort ?? 'reclassification_date'" :current-direction="$direction ?? 'desc'" default-direction="desc">
                                Date
                            </x-ui.sortable-th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Warehouse</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Current Item</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">New Item</th>
                            <x-ui.sortable-th column="quantity" :current-sort="$sort ?? 'reclassification_date'" :current-direction="$direction ?? 'desc'" align="right">
                                Quantity
                            </x-ui.sortable-th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Performed By</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($reclassifications as $rec)
                            <tr class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-3 py-2 font-medium font-mono text-xs text-indigo-700">{{ $rec->reclassification_no }}</td>
                                <td class="px-3 py-2 text-slate-600 text-xs">{{ $rec->reclassification_date?->format('d M Y') }}</td>
                                <td class="px-3 py-2 text-slate-900 font-medium text-xs">{{ $rec->warehouse?->name ?: '—' }}</td>
                                <td class="px-3 py-2 text-slate-900 text-xs font-medium">
                                    <span class="text-rose-700 font-semibold">{{ $rec->fromProduct?->name }}</span>
                                    <span class="block text-[11px] text-slate-400 font-mono">{{ $rec->fromProduct?->sku }}</span>
                                </td>
                                <td class="px-3 py-2 text-slate-900 text-xs font-medium">
                                    <span class="text-emerald-700 font-semibold">{{ $rec->toProduct?->name }}</span>
                                    <span class="block text-[11px] text-slate-400 font-mono">{{ $rec->toProduct?->sku }}</span>
                                </td>
                                <td class="px-3 py-2 text-right font-mono font-bold text-xs text-slate-800">
                                    {{ number_format((float) $rec->quantity, 2) }} {{ $rec->uom?->code }}
                                </td>
                                <td class="px-3 py-2 text-slate-600 text-xs">{{ $rec->creator?->name ?: 'System' }}</td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <x-ui.button size="sm" variant="secondary" :href="route('inventory.transfers.reclassifications.show', $rec)" title="View Details">View</x-ui.button>
                                        <x-ui.button size="sm" variant="secondary" :href="route('inventory.transfers.reclassifications.edit', $rec)" title="Edit Name Transfer">Edit</x-ui.button>
                                        <button type="button"
                                                @click="promptDeleteReclassification('{{ $rec->id }}', '{{ addslashes($rec->reclassification_no) }}', '{{ addslashes($rec->fromProduct?->name ?? '') }}', '{{ addslashes($rec->toProduct?->name ?? '') }}')"
                                                :disabled="isDeleting"
                                                class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-xs px-2.5 py-1.5 text-xs"
                                                title="Delete Name Transfer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-0">
                                    <x-ui.empty-state
                                        title="No item name transfers found"
                                        description="Stock name transfers (reclassifications) will appear here once created."
                                        class="border-0 rounded-none py-10"
                                    >
                                        <x-slot name="action">
                                            <a href="{{ route('inventory.transfers.create', ['tab' => 'name']) }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                                + New Name Transfer
                                            </a>
                                        </x-slot>
                                    </x-ui.empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reclassifications->hasPages())
                <div class="mt-4">{{ $reclassifications->links() }}</div>
            @endif
        </x-ui.card>
    @else
        <!-- LOCATION TRANSFERS TABLE (Existing Functionality Preserved 100%) -->
        <x-ui.card>
            <x-ui.table-toolbar
                :search="$search ?? request('search')"
                search-placeholder="Search Transfer No, Notes..."
                :reset-url="route('inventory.transfers.index', ['tab' => 'location'])"
            >
                <x-slot name="filters">
                    <input type="hidden" name="tab" value="location" data-dynamic-filter>

                    <select
                        name="status"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Status: All</option>
                        @foreach(['draft', 'in_transit', 'received', 'completed', 'cancelled'] as $st)
                            <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                        @endforeach
                    </select>

                    <select
                        name="from_warehouse_id"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">From: All</option>
                        @foreach($warehouses ?? [] as $w)
                            <option value="{{ $w->id }}" @selected(request('from_warehouse_id') == $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>

                    <select
                        name="to_warehouse_id"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">To: All</option>
                        @foreach($warehouses ?? [] as $w)
                            <option value="{{ $w->id }}" @selected(request('to_warehouse_id') == $w->id)>{{ $w->name }}</option>
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
                <table class="min-w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <x-ui.sortable-th column="transfer_no" :current-sort="$sort ?? request('sort', 'transfer_date')" :current-direction="$direction ?? request('direction', 'desc')">
                                Transfer No
                            </x-ui.sortable-th>
                            <x-ui.sortable-th column="transfer_date" :current-sort="$sort ?? request('sort', 'transfer_date')" :current-direction="$direction ?? request('direction', 'desc')" default-direction="desc">
                                Date
                            </x-ui.sortable-th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">From</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">To</th>
                            <x-ui.sortable-th column="status" :current-sort="$sort ?? request('sort', 'transfer_date')" :current-direction="$direction ?? request('direction', 'desc')">
                                Status
                            </x-ui.sortable-th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($transfers as $transfer)
                            <tr class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-3 py-2 font-medium font-mono text-xs text-slate-900">{{ $transfer->transfer_no }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $transfer->transfer_date?->format('d M Y') }}</td>
                                <td class="px-3 py-2 text-slate-900 font-medium">{{ $transfer->fromWarehouse?->name ?: '—' }}</td>
                                <td class="px-3 py-2 text-slate-900 font-medium">{{ $transfer->toWarehouse?->name ?: '—' }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 text-slate-800">
                                        {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <x-ui.button size="sm" variant="secondary" :href="route('inventory.transfers.show', $transfer)" title="View Details">View</x-ui.button>
                                        @if(in_array($transfer->status, ['draft', 'in_transit']))
                                            <x-ui.button size="sm" variant="secondary" :href="route('inventory.transfers.edit', $transfer)" title="Edit Transfer">Edit</x-ui.button>
                                            <button type="button"
                                                    @click="promptDeleteTransfer('{{ $transfer->id }}', '{{ addslashes($transfer->transfer_no) }}', '{{ $transfer->status }}')"
                                                    :disabled="isDeleting"
                                                    class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-xs px-2.5 py-1.5 text-xs"
                                                    title="Delete Transfer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Delete</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-0">
                                    <x-ui.empty-state
                                        title="No stock transfers found"
                                        description="Try clearing search or filters to see more results."
                                        class="border-0 rounded-none py-10"
                                    >
                                        <x-slot name="action">
                                            <a href="{{ route('inventory.transfers.index', ['tab' => 'location']) }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
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
            @if($transfers->hasPages())
                <div class="mt-4">{{ $transfers->links() }}</div>
            @endif
        </x-ui.card>
    @endif

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
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Stock Transfer?</h3>
                            <div class="mt-2 space-y-3">
                                <p class="text-xs text-slate-500">Are you sure you want to delete this stock transfer? Any related inventory movements must be handled safely.</p>

                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs text-left">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Reference / Transfer No:</span>
                                        <span class="font-mono font-bold text-slate-900" x-text="deletingTransferNo"></span>
                                    </div>
                                    <template x-if="deleteType === 'location'">
                                        <div class="flex justify-between items-center text-slate-600">
                                            <span class="font-medium">Transfer Status:</span>
                                            <span class="font-semibold uppercase text-slate-800" x-text="deletingStatus"></span>
                                        </div>
                                    </template>
                                    <template x-if="deleteType === 'name'">
                                        <div>
                                            <div class="flex justify-between items-center text-slate-600">
                                                <span class="font-medium">Current Item:</span>
                                                <span class="font-semibold text-rose-700" x-text="deletingExtra.from"></span>
                                            </div>
                                            <div class="flex justify-between items-center text-slate-600 mt-1">
                                                <span class="font-medium">New Item:</span>
                                                <span class="font-semibold text-emerald-700" x-text="deletingExtra.to"></span>
                                            </div>
                                        </div>
                                    </template>
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
function stockTransferIndex() {
    return {
        showDeleteModal: false,
        isDeleting: false,
        deleteType: 'location', // 'location' or 'name'
        deletingId: null,
        deletingTransferNo: '',
        deletingStatus: '',
        deletingExtra: { from: '', to: '' },
        notification: {
            show: false,
            type: 'success',
            message: '',
        },

        promptDeleteTransfer(id, transferNo, status) {
            this.deleteType = 'location';
            this.deletingId = id;
            this.deletingTransferNo = transferNo;
            this.deletingStatus = status;
            this.showDeleteModal = true;
        },

        promptDeleteReclassification(id, refNo, fromName, toName) {
            this.deleteType = 'name';
            this.deletingId = id;
            this.deletingTransferNo = refNo;
            this.deletingExtra = { from: fromName, to: toName };
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingId = null;
            this.deletingTransferNo = '';
            this.deletingStatus = '';
            this.deletingExtra = { from: '', to: '' };
        },

        async executeDelete() {
            if (!this.deletingId || this.isDeleting) return;
            this.isDeleting = true;

            let url = '';
            if (this.deleteType === 'location') {
                url = `{{ url('inventory/transfers') }}/${this.deletingId}`;
            } else {
                url = `{{ url('inventory/transfers/reclassifications') }}/${this.deletingId}`;
            }

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
                    this.showNotification('success', data.message || 'Stock transfer deleted successfully.');

                    const rows = document.querySelectorAll('#listing-container tbody tr:not([data-empty])');
                    const currentUrl = new URL(window.location.href);
                    let currentPage = parseInt(currentUrl.searchParams.get('page') || '1', 10);

                    if (rows.length <= 1 && currentPage > 1) {
                        currentUrl.searchParams.set('page', currentPage - 1);
                        window.history.replaceState(null, '', currentUrl.toString());
                    }

                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete stock transfer.');
                }
            } catch (err) {
                console.error(err);
                this.showNotification('error', 'Network error occurred while deleting stock transfer.');
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
