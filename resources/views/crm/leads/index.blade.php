@extends('layouts.dms')
@section('title', 'CRM Leads')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="CRM Leads">
        <x-slot name="actions">
            <x-ui.button :href="route('crm.leads.bulk-upload')" variant="secondary">
                <svg class="w-4 h-4 mr-1.5 -ml-0.5 inline-block text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Bulk Upload
            </x-ui.button>
            <x-ui.button :href="route('crm.leads.create')" variant="primary">+ New Lead</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if(session('bulk_errors_file'))
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
                <div>
                    <h4 class="text-xs font-semibold text-amber-900">Some rows could not be imported</h4>
                    <p class="text-xs text-amber-700">A detailed error report CSV is available for inspection, correction, and re-import.</p>
                </div>
            </div>
            <a href="{{ route('crm.leads.bulk-upload.errors', ['filename' => session('bulk_errors_file')]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download Error Report CSV
            </a>
        </div>
    @endif

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search contact name, company, mobile, email, tag..."
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
                                @if($item->company_name ?: $item->organization)
                                    <div class="text-xs text-slate-500">{{ $item->company_name ?: $item->organization }}</div>
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
                            <td class="px-3 py-2 text-right whitespace-nowrap space-x-1">
                                <x-ui.button size="sm" variant="secondary" :href="route('crm.leads.show', $item)">Open</x-ui.button>
                                <x-ui.button size="sm" variant="secondary" :href="route('crm.leads.edit', $item)">Edit</x-ui.button>
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
