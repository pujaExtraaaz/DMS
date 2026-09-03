@extends('layouts.dms')

@section('title', 'Orders')

@section('content')
    <div
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
                        value="{{ request('q') }}"
                        placeholder="Order no, customer name or code..."
                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Status
                    </label>

                    <select
                        name="status"
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

                    <x-ui.button
                        type="button"
                        variant="secondary"
                        :href="route('orders.index')"
                    >
                        Reset
                    </x-ui.button>
                </div>

                <div class="md:col-span-2 lg:col-span-6 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <x-ui.input
                        name="date_from"
                        label="From"
                        type="date"
                        :value="request('date_from')"
                    />

                    <x-ui.input
                        name="date_to"
                        label="To"
                        type="date"
                        :value="request('date_to')"
                    />
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

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Order
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Customer
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Date
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Salesperson
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Status
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                Items
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                Total
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </x-slot>

                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50">
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

                            <td class="px-6 py-4 text-right">
                                <x-ui.button
                                    variant="ghost"
                                    size="sm"
                                    :href="route('orders.show', $order)"
                                >
                                    View
                                </x-ui.button>
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
    </div>
@endsection

@push('scripts')
<script>
function orderIndex() {
    return {
        selected: [],
        visibleIds: @json($orders->pluck('id')->values()),

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