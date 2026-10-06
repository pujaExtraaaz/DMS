@extends('layouts.dms')
@section('title', 'Parties')
@section('content')
<x-ui.page-header title="Parties">
    <x-slot name="actions">
        <x-ui.button variant="primary" :href="route('masters.customers.create')">Add Party</x-ui.button>
    </x-slot>
</x-ui.page-header>
<x-ui.card>
    <form method="GET" class="flex flex-wrap items-end gap-4 mb-6">
        <div class="flex-1 min-w-[200px]">
            <x-ui.input name="search" label="Search" :value="request('search')" placeholder="Name, code, phone, GSTIN..." />
        </div>
        <div class="w-48">
            <x-ui.select name="party_type" label="Party Type" placeholder="All">
                <option value=""></option>
                @foreach([\App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_DEBTORS => 'Sundry Debtors', \App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_CREDITORS => 'Sundry Creditors', \App\Domains\Master\Models\Customer::PARTY_TYPE_BOTH => 'Both'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('party_type')===$val)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="w-44">
            <x-ui.select name="area_id" label="Area" placeholder="All">
                <option value=""></option>
                @foreach($areas ?? [] as $a)
                    <option value="{{ $a->id }}" @selected(request('area_id')==$a->id)>{{ $a->name }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="w-44">
            <x-ui.select name="customer_type_id" label="Category" placeholder="All">
                <option value=""></option>
                @foreach($customerTypes ?? [] as $ct)
                    <option value="{{ $ct->id }}" @selected(request('customer_type_id')==$ct->id)>{{ $ct->name }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.button type="submit" variant="secondary" class="whitespace-nowrap">Filter</x-ui.button>
            @if(request()->hasAny(['search', 'party_type', 'area_id', 'customer_type_id']))
                <a href="{{ route('masters.customers.index') }}" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Reset</a>
            @endif
        </div>
    </form>
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Party Name</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Code</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Party Type</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Phone</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">GSTIN</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Area</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
            </tr>
        </x-slot>
        @forelse($items as $item)
            <tr>
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
                <td colspan="7" class="px-6 py-8">
                    <x-ui.empty-state title="No parties found" />
                </td>
            </tr>
        @endforelse
    </x-ui.table>
    <div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection