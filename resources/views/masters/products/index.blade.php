@extends('layouts.dms')
@section('title', 'Products')
@section('content')
<x-ui.page-header title="Products">
    <x-slot name="actions">
        <form method="GET" class="flex min-w-0 flex-1 gap-2 sm:max-w-md">
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search name or SKU or Serial No."
                class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm"
            >

            <select
                name="sort"
                class="rounded-lg border-gray-300 text-sm"
            >
                <option value="serial_no" @selected(request('sort', 'created_at') === 'serial_no')>
                    Serial No.
                </option>
                <option value="name" @selected(request('sort') === 'name')>
                    Name
                </option>
                <option value="sku" @selected(request('sort') === 'sku')>
                    SKU
                </option>
            </select>

            <select
                name="direction"
                class="rounded-lg border-gray-300 text-sm"
            >
                <option value="asc" @selected(request('direction') === 'asc')>
                    ASC
                </option>
                <option value="desc" @selected(request('direction', 'desc') === 'desc')>
                    DESC
                </option>
            </select>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        </form>
        <x-ui.button variant="primary" :href="route('masters.products.create')">Add Product</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Serial No.</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">SKU</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Unit</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Tax</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 font-mono text-xs text-slate-600">{{ $item->serial_no }}</td>
                        <td class="px-3 py-2 font-medium text-slate-900">{{ $item->name }}</td>
                        <td class="px-3 py-2 font-mono text-xs text-slate-600">{{ $item->sku }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $item->baseUom?->code ?? '—' }}</td>
                        <td class="px-3 py-2 text-right text-slate-700">{{ $item->tax_rate }}%</td>
                        <td class="px-3 py-2 text-right">
                            <x-ui.button variant="secondary" size="sm" :href="route('masters.products.edit', $item)">Edit</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan=6" class="px-3 py-8 text-center text-slate-500">
                            No products found. Click <strong>Add Product</strong> to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($items->hasPages())
        <div class="mt-4">{{ $items->links() }}</div>
    @endif
</x-ui.card>
@endsection
