@extends('layouts.dms')

@section('title', 'Orders')

@section('content')
    <div
        id="listing-container"
        data-dynamic-container
        x-data="orderIndex()"
        x-init="init()"
        @keydown.window="handleShortcut($event)"
        class="space-y-6"
    >
        <x-ui.page-header
            title="Orders"
            description="Search, review and process customer orders."
        >
            <x-slot name="actions">
                @can('create', \App\Domains\Order\Models\Order::class)
                    <x-ui.button
                        variant="primary"
                        :href="route('orders.create')"
                    >
                        + Book Order
                    </x-ui.button>
                @endcan
            </x-slot>
        </x-ui.page-header>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            @foreach([
                'pending' => 'Pending',
                'approved' => 'Approved',
                'converted' => 'Converted',
                'cancelled' => 'Cancelled',
            ] as $status => $label)
                <a
                    href="{{ route('orders.index', array_merge(request()->query(), ['status' => $status])) }}"
                    class="rounded-xl border bg-white p-4 shadow-sm hover:border-indigo-300"
                >
                    <p class="text-xs font-medium uppercase text-gray-500">
                        {{ $label }}
                    </p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ number_format((int) ($statusCounts[$status] ?? 0)) }}
                    </p>
                </a>
            @endforeach

            <a
                href="{{ route('orders.index') }}"
                class="rounded-xl border bg-white p-4 shadow-sm hover:border-indigo-300"
            >
                <p class="text-xs font-medium uppercase text-gray-500">
                    All
                </p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">
                    {{ number_format((int) $statusCounts->sum()) }}
                </p>
            </a>
        </div>

        <x-ui.card>
            <form
                method="GET"
                action="{{ route('orders.index') }}"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3"
            >
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Search
                    </label>

                    <input
                        id="order-search"
                        type="search"
                        name="q"
                        value="{{ request('q', request('search')) }}"
                        data-dynamic-search
                        placeholder="Order No, Customer Name Or Code..."
                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Status
                    </label>

                    <select
                        name="status"
                        data-dynamic-filter
                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                    >
                        <option value="">All</option>

                        @foreach($statuses as $status)
                            <option
                                value="{{ $status }}"
                                @selected(request('status') === $status)
                            >
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Salesperson
                    </label>

                    <select
                        name="salesperson_id"
                        data-dynamic-filter
                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                    >
                        <option value="">All</option>

                        @foreach($salespersons as $salesperson)
                            <option
                                value="{{ $salesperson->id }}"
                                @selected(request('salesperson_id') == $salesperson->id)
                            >
                                {{ $salesperson->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Area
                    </label>

                    <select
                        name="area_id"
                        data-dynamic-filter
                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                    >
                        <option value="">All</option>

                        @foreach($areas as $area)
                            <option
                                value="{{ $area->id }}"
                                @selected(request('area_id') == $area->id)
                            >
                                {{ $area->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <x-ui.button type="submit" variant="primary">
                        Filter
                    </x-ui.button>

                    <a
                        href="{{ route('orders.index') }}"
                        data-reset-filters
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Reset
                    </a>
                </div>

                <div class="md:col-span-2 lg:col-span-6 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From</label>
                        <input
                            name="date_from"
                            type="date"
                            data-dynamic-filter
                            value="{{ request('date_from') }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">To</label>
                        <input
                            name="date_to"
                            type="date"
                            data-dynamic-filter
                            value="{{ request('date_to') }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                        />
                    </div>
                </div>
            </form>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('orders.export', request()->query()) }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    CSV
                </a>

                <a
                    href="{{ route('orders.pdf', request()->query()) }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    PDF
                </a>

                <span class="text-xs text-gray-500">
                    Press <kbd>/</kbd> to search,
                    <kbd>Shift+A</kbd> to select all.
                </span>
            </div>
        </x-ui.card>

        <div
            x-show="selected.length > 0"
            x-cloak
            class="sticky top-2 z-20 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 shadow-sm"
        >
            <span class="text-sm font-medium text-indigo-900">
                <span x-text="selected.length"></span> selected
            </span>

            <div class="flex gap-2">
                @can('orders.approve')
                    <button
                        type="button"
                        @click="bulkAction('{{ route('orders.bulk.approve') }}')"
                        class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Approve Selected
                    </button>
                @endcan

                @can('orders.convert')
                    <button
                        type="button"
                        @click="bulkAction('{{ route('orders.bulk.convert') }}')"
                        class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                    >
                        Convert Selected
                    </button>
                @endcan

                <button
                    type="button"
                    @click="clearSelection()"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700"
                >
                    Clear
                </button>
            </div>
        </div>

        <x-ui.card>
            <div class="overflow-x-auto">
                <x-ui.table>
                    <x-slot name="head">
                        <tr>
                            <th class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    @change="toggleAll($event)"
                                    :checked="allVisibleSelected"
                                >
                            </th>

                            <x-ui.sortable-th column="order_no" :current-sort="request('sort', 'order_date')" :current-direction="request('direction', 'desc')">
                                Order
                            </x-ui.sortable-th>

                            <x-ui.sortable-th column="customer" :current-sort="request('sort', 'order_date')" :current-direction="request('direction', 'desc')">
                                Customer
                            </x-ui.sortable-th>

                            <x-ui.sortable-th column="order_date" :current-sort="request('sort', 'order_date')" :current-direction="request('direction', 'desc')" default-direction="desc">
                                Date
                            </x-ui.sortable-th>

                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">
                                Salesperson
                            </th>

                            <x-ui.sortable-th column="status" :current-sort="request('sort', 'order_date')" :current-direction="request('direction', 'desc')">
                                Status
                            </x-ui.sortable-th>

                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">
                                Items
                            </th>

                            <x-ui.sortable-th column="grand_total" :current-sort="request('sort', 'order_date')" :current-direction="request('direction', 'desc')" default-direction="desc" align="right">
                                Total
                            </x-ui.sortable-th>

                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 whitespace-nowrap">
                                Actions
                            </th>
                        </tr>
                    </x-slot>

                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50" id="order-row-{{ $order->id }}">
                            <td class="px-4 py-4">
                                <input
                                    type="checkbox"
                                    value="{{ $order->id }}"
                                    @change="toggle({{ $order->id }}, $event.target.checked)"
                                    :checked="selected.includes({{ $order->id }})"
                                >
                            </td>

                            <td class="px-6 py-4 text-sm font-medium">
                                {{ $order->order_no }}
                            </td>

                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium">
                                    {{ $order->customer->name }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $order->customer->code }}
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm">
                                {{ $order->order_date->format('d M Y') }}
                            </td>

                            <td class="px-6 py-4 text-sm">
                                {{ $order->salesperson?->name ?? '—' }}
                            </td>

                            <td class="px-6 py-4 text-sm">
                                <x-ui.badge variant="info">
                                    {{ ucfirst($order->status) }}
                                </x-ui.badge>
                            </td>

                            <td class="px-6 py-4 text-right text-sm">
                                {{ $order->items_count }}
                            </td>

                            <td class="px-6 py-4 text-right text-sm font-medium">
                                ₹{{ number_format($order->grand_total, 2) }}
                            </td>

                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <x-ui.button
                                        variant="ghost"
                                        size="sm"
                                        :href="route('orders.show', $order)"
                                    >
                                        View
                                    </x-ui.button>

                                    @php
                                        $isEditable = in_array($order->status, ['draft', 'pending', 'approved'], true) && ! $order->invoice;
                                        $canEdit = auth()->user()->hasRole('super-admin') || auth()->user()->can('orders.edit') || auth()->user()->can('orders.manage');
                                        $isDeletable = ! in_array($order->status, ['converted'], true) && ! $order->invoice;
                                        $canDelete = auth()->user()->hasRole('super-admin') || auth()->user()->can('orders.delete') || auth()->user()->can('orders.manage');
                                    @endphp

                                    @if($canEdit)
                                        @if($isEditable)
                                            <x-ui.button
                                                variant="secondary"
                                                size="sm"
                                                :href="route('orders.edit', $order)"
                                            >
                                                Edit
                                            </x-ui.button>
                                        @else
                                            <button
                                                type="button"
                                                disabled
                                                class="opacity-40 cursor-not-allowed inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-400"
                                                title="{{ $order->status === 'converted' || $order->invoice ? 'Invoiced orders cannot be edited' : ($order->status === 'cancelled' ? 'Cancelled orders cannot be edited' : 'Cannot be edited') }}"
                                            >
                                                Edit
                                            </button>
                                        @endif
                                    @endif

                                    @if($canDelete)
                                        @if($isDeletable)
                                            <button
                                                type="button"
                                                @click="promptDelete({{ $order->id }}, '{{ $order->order_no }}', '{{ addslashes($order->customer->name) }}', '{{ $order->status }}', '{{ number_format($order->grand_total, 2) }}')"
                                                class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition shadow-sm"
                                            >
                                                Delete
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                disabled
                                                class="opacity-40 cursor-not-allowed inline-flex items-center rounded-lg border border-red-200 bg-red-50/50 px-2.5 py-1.5 text-xs font-semibold text-red-300"
                                                title="Invoiced orders cannot be deleted"
                                            >
                                                Delete
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-10">
                                <x-ui.empty-state
                                    title="No orders found"
                                    description="Try changing your filters or book a new order."
                                />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </x-ui.card>

        <!-- Delete Confirmation Modal -->
        <div x-show="showDeleteModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title"
             role="dialog"
             aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showDeleteModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"
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
                                <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Order?</h3>
                                <div class="mt-2 space-y-3">
                                    <p class="text-xs text-slate-500">Are you sure you want to delete this order? Related stock reservations or transactions may be affected.</p>

                                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs text-left">
                                        <div class="flex justify-between items-center text-slate-600">
                                            <span class="font-medium">Order Number:</span>
                                            <span class="font-mono font-bold text-slate-900" x-text="deletingOrderNo"></span>
                                        </div>
                                        <div class="flex justify-between items-center text-slate-600">
                                            <span class="font-medium">Customer:</span>
                                            <span class="font-semibold text-slate-800" x-text="deletingCustomerName"></span>
                                        </div>
                                        <div class="flex justify-between items-center text-slate-600">
                                            <span class="font-medium">Status:</span>
                                            <span class="font-semibold uppercase" :class="deletingStatus === 'draft' || deletingStatus === 'pending' ? 'text-amber-700' : (deletingStatus === 'approved' ? 'text-indigo-700' : 'text-slate-700')" x-text="deletingStatus"></span>
                                        </div>
                                        <div class="flex justify-between items-center text-slate-600">
                                            <span class="font-medium">Grand Total:</span>
                                            <span class="font-bold text-slate-900">₹<span x-text="deletingGrandTotal"></span></span>
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
@endsection

@push('scripts')
<script>
function orderIndex() {
    return {
        selected: [],
        visibleIds: @json($orders->pluck('id')->values()),
        showDeleteModal: false,
        isDeleting: false,
        deletingId: null,
        deletingOrderNo: '',
        deletingCustomerName: '',
        deletingStatus: '',
        deletingGrandTotal: '',
        notification: {
            show: false,
            type: 'success',
            message: '',
        },

        promptDelete(id, orderNo, customerName, status, total) {
            this.deletingId = id;
            this.deletingOrderNo = orderNo;
            this.deletingCustomerName = customerName;
            this.deletingStatus = status;
            this.deletingGrandTotal = total;
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingId = null;
            this.deletingOrderNo = '';
            this.deletingCustomerName = '';
            this.deletingStatus = '';
            this.deletingGrandTotal = '';
        },

        showToast(message, type = 'success') {
            this.notification.message = message;
            this.notification.type = type;
            this.notification.show = true;
            setTimeout(() => {
                this.notification.show = false;
            }, 4000);
        },

        async executeDelete() {
            if (!this.deletingId || this.isDeleting) return;
            this.isDeleting = true;

            try {
                const response = await fetch(`/orders/${this.deletingId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });

                const data = await response.json().catch(() => null);

                if (response.ok && data?.success) {
                    const deletedId = this.deletingId;
                    const orderNo = this.deletingOrderNo;
                    this.showDeleteModal = false;
                    this.showToast(data.message || `Order ${orderNo} deleted successfully.`, 'success');

                    const row = document.getElementById(`order-row-${deletedId}`);
                    if (row) {
                        row.remove();
                    }
                    this.visibleIds = this.visibleIds.filter(id => id !== deletedId);
                    this.selected = this.selected.filter(id => id !== deletedId);

                    if (this.visibleIds.length === 0) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 800);
                    }
                } else {
                    const errorMsg = data?.message || 'Failed to delete order.';
                    this.showToast(errorMsg, 'error');
                }
            } catch (err) {
                this.showToast('Network error while deleting order.', 'error');
            } finally {
                this.isDeleting = false;
            }
        },

        get allVisibleSelected() {
            return this.visibleIds.length > 0 &&
                this.visibleIds.every(id => this.selected.includes(id));
        },

        init() {
            setInterval(() => {
                if (
                    !document.hidden &&
                    this.selected.length === 0
                ) {
                    window.location.reload();
                }
            }, 60000);
        },

        toggle(id, checked) {
            if (checked) {
                if (!this.selected.includes(id)) {
                    this.selected.push(id);
                }
            } else {
                this.selected = this.selected.filter(
                    selectedId => selectedId !== id
                );
            }
        },

        toggleAll(event) {
            if (event.target.checked) {
                this.selected = [...new Set([
                    ...this.selected,
                    ...this.visibleIds
                ])];
            } else {
                this.selected = this.selected.filter(
                    id => !this.visibleIds.includes(id)
                );
            }
        },

        clearSelection() {
            this.selected = [];
        },

        async bulkAction(url) {
            if (!this.selected.length) {
                return;
            }

            if (
                !confirm(
                    `Process ${this.selected.length} selected order(s)?`
                )
            ) {
                return;
            }

            const form = document.createElement('form');

            form.method = 'POST';
            form.action = url;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute('content');

            form.appendChild(csrf);

            this.selected.forEach(id => {
                const input = document.createElement('input');

                input.type = 'hidden';
                input.name = 'order_ids[]';
                input.value = id;

                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        },

        handleShortcut(event) {
            if (
                event.key === '/' &&
                !['INPUT', 'TEXTAREA', 'SELECT'].includes(
                    document.activeElement?.tagName
                )
            ) {
                event.preventDefault();

                document
                    .getElementById('order-search')
                    ?.focus();
            }

            if (
                event.shiftKey &&
                event.key.toLowerCase() === 'a' &&
                !['INPUT', 'TEXTAREA', 'SELECT'].includes(
                    document.activeElement?.tagName
                )
            ) {
                event.preventDefault();

                this.selected = this.allVisibleSelected
                    ? []
                    : [...new Set([
                        ...this.selected,
                        ...this.visibleIds
                    ])];
            }

            if (event.key === 'Escape') {
                this.clearSelection();
            }
        }
    };
}
</script>
@endpush