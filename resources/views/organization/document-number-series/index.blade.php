@extends('layouts.dms')
@section('title', 'Voucher Numbering')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Voucher Numbering">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('organization.document-number-series.create')">+ Add Series</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search document type, prefix..."
            :reset-url="route('organization.document-number-series.index')"
        />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="document_type" :current-sort="$sort ?? 'document_type'" :current-direction="$direction ?? 'asc'">Document</x-ui.sortable-th>
                        <x-ui.sortable-th column="company" :current-sort="$sort ?? 'document_type'" :current-direction="$direction ?? 'asc'">Company</x-ui.sortable-th>
                        <x-ui.sortable-th column="prefix" :current-sort="$sort ?? 'document_type'" :current-direction="$direction ?? 'asc'">Format</x-ui.sortable-th>
                        <x-ui.sortable-th column="numbering_method" :current-sort="$sort ?? 'document_type'" :current-direction="$direction ?? 'asc'">Method</x-ui.sortable-th>
                        <x-ui.sortable-th column="is_active" :current-sort="$sort ?? 'document_type'" :current-direction="$direction ?? 'asc'">Status</x-ui.sortable-th>
                        <th class="px-3 py-2 text-right text-xs font-semibold tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-3 py-2">
                                <div class="font-medium text-slate-900">{{ $documentTypes[$item->document_type] ?? $item->document_type }}</div>
                                <div class="text-xs text-slate-500">{{ $item->document_type }}</div>
                            </td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->company?->name ?? 'All companies' }}</td>
                            <td class="px-3 py-2">
                                <code class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-800">{{ $item->preview() }}</code>
                            </td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="$item->isManual() ? 'warning' : 'info'">
                                    {{ $item->isManual() ? 'Manual' : 'Automatic' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="$item->is_active ? 'success' : 'neutral'">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-ui.button variant="secondary" size="sm" :href="route('organization.document-number-series.edit', $item)">Edit</x-ui.button>
                                    <form method="POST" action="{{ route('organization.document-number-series.destroy', $item) }}" class="inline" onsubmit="return confirm('Delete this numbering series?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-red-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-red-700">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-slate-500" data-empty>
                                <x-ui.empty-state title="No voucher series configured" description="Create a series to control prefix, financial year, and automatic or manual numbering." />
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
</div>
@endsection
