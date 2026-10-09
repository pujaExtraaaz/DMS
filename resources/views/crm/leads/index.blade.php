@extends('layouts.dms')
@section('title', 'CRM Leads')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="CRM Leads">
        <x-slot name="actions">
            <x-ui.button :href="route('crm.leads.create')" variant="primary">+ New Lead</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search name, mobile, email, organization..."
            :reset-url="route('crm.leads.index')"
        >
            <x-slot name="filters">
                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Status: All</option>
                    @foreach(['new','contacted','qualified','unqualified','converted','lost'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Lead</x-ui.sortable-th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Contact</th>
                        <x-ui.sortable-th column="source" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Source</x-ui.sortable-th>
                        <x-ui.sortable-th column="assignee" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Assignee</x-ui.sortable-th>
                        <x-ui.sortable-th column="status" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Status</x-ui.sortable-th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">
                                {{ $item->name }}
                                @if($item->organization)
                                    <div class="text-xs text-slate-500">{{ $item->organization }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-600">
                                {{ $item->mobile ?? '—' }}
                                @if($item->email)
                                    <div class="text-xs text-slate-400">{{ $item->email }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->source?->name ?: 'Meta' }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->assignee?->name ?: '—' }}</td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="in_array($item->status, ['qualified', 'converted']) ? 'emerald' : ($item->status === 'lost' ? 'rose' : 'amber')">
                                    {{ ucfirst($item->status) }}
                                </x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <x-ui.button size="sm" variant="secondary" :href="route('crm.leads.show', $item)">Open</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No leads found" description="Try adjusting your search or filters." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
