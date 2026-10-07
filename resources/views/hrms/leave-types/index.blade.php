@extends('layouts.dms')
@section('title', 'Leave Types')
@section('content')
<x-ui.page-header title="Leave Types"><x-slot name="actions"><x-ui.button variant="primary" :href="route('hrms.leave-types.create')">Add</x-ui.button></x-slot></x-ui.page-header>
<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search leave types by name..."
            :searchValue="request('search')"
            :resetUrl="route('hrms.leave-types.index')"
        />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="name" label="Name" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="default_days" label="Default Days" align="right" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="is_paid" label="Paid" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ $item->name }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->default_days }}</td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="$item->is_paid ? 'success' : 'secondary'">{{ $item->is_paid ? 'Yes' : 'No' }}</x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <x-ui.button variant="secondary" size="sm" :href="route('hrms.leave-types.edit', $item)">Edit</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No leave types found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
