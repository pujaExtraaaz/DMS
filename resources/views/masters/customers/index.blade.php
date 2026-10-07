@extends('layouts.dms')
@section('title', 'Parties')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Parties">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('masters.customers.create')">+ Add Party</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search name, code, phone, GSTIN..."
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
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Area</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
                </tr>
            </x-slot>
            @forelse($items as $item)
                <tr class="hover:bg-slate-50/75 transition-colors">
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">
                        <a href="{{ route('masters.customers.edit', $item) }}" class="text-indigo-600 hover:text-indigo-800 hover:underline">
                            {{ $item->name }}
                        </a>
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
                        <x-ui.button variant="ghost" size="sm" :href="route('masters.customers.edit', $item)">Edit</x-ui.button>
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
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection