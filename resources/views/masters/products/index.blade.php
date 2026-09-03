@extends('layouts.dms')
@section('title', 'Products')
@section('content')
<x-ui.page-header title="Products">
    <x-slot name="actions">
        <x-ui.button variant="primary" :href="route('masters.products.create')">Add Product</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    {{-- Search Filter --}}
    <form method="GET" class="flex flex-col gap-4 md:flex-row md:items-end mb-6">
        <div class="flex-1">
            <x-ui.input name="search" label="Search" placeholder="Search by name or SKU..." :value="request('search')" />
        </div>
        <x-ui.button type="submit" variant="secondary" class="w-full md:w-auto">Search</x-ui.button>
    </form>

    {{-- Products Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Name</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">SKU</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Unit</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Tax Rate</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-900 font-medium">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-slate-600 text-xs font-mono">{{ $item->sku }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->baseUom?->code ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ $item->tax_rate }}%</td>
                        <td class="px-4 py-3 text-center">
                            <x-ui.button variant="ghost" size="sm" :href="route('masters.products.edit', $item)">Edit</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center">
                            <x-ui.empty-state title="No products found" description="Add a new product to get started" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($items->hasPages())
        <div class="mt-4">
            {{ $items->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
