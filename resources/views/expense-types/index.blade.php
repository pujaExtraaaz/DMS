@extends('layouts.dms')
@section('title', 'Expense Types')
@section('content')
<x-ui.page-header title="Expense Types">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('deals.index')">Deals</x-ui.button>
        <x-ui.button variant="primary" :href="route('expense-types.create')">Add</x-ui.button>
    </x-slot>
</x-ui.page-header>
<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search expense types by name, code..."
            :searchValue="request('search')"
            :resetUrl="route('expense-types.index')"
        />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50"><tr>
                    <x-ui.sortable-th column="name" label="Name" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="code" label="Code" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="accounting_treatment" label="Treatment" :currentSort="$sort" :currentDirection="$direction" />
                    <x-ui.sortable-th column="is_active" label="Status" :currentSort="$sort" :currentDirection="$direction" />
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ $item->name }}</td>
                            <td class="px-3 py-2 font-mono text-xs text-slate-600">{{ $item->code }}</td>
                            <td class="px-3 py-2">{{ str_replace('_',' ', $item->accounting_treatment) }}</td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="$item->is_active ? 'success' : 'secondary'">{{ $item->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <x-ui.button variant="secondary" size="sm" :href="route('expense-types.edit', $item)">Edit</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No expense types found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
