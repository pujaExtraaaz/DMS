@extends('layouts.dms')
@section('title', 'Lead Segments (Targeted Campaigns)')
@section('content')
<div x-data="segmentListManager()" class="space-y-4">
    <!-- Live notification banner -->
    <div x-show="notification.show"
         x-transition
         x-cloak
         :class="notification.type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-800 border-red-200'"
         class="rounded-lg border p-3 text-sm flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2">
            <span class="font-bold shrink-0" x-text="notification.type === 'success' ? '✓' : '⚠'"></span>
            <span x-text="notification.message"></span>
        </div>
        <button type="button" @click="notification.show = false" class="text-xs font-semibold opacity-70 hover:opacity-100">Dismiss</button>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="font-bold">✓</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <x-ui.page-header title="Lead Segments &amp; Targeted Groups">
        <x-slot name="actions">
            <x-ui.button :href="route('crm.leads.index')" variant="secondary">
                <svg class="w-4 h-4 mr-1.5 -ml-0.5 inline-block text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                All CRM Leads
            </x-ui.button>
            <x-ui.button :href="route('crm.leads.segments.create')" variant="primary">
                + Create Segment
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search segment name or description..."
            :reset-url="route('crm.leads.segments.index')"
        />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Segment</x-ui.sortable-th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Criteria Rules</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Matching Leads</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Created By</th>
                        <x-ui.sortable-th column="updated_at" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Last Updated</x-ui.sortable-th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($segments as $segment)
                        @php
                            $matchCount = $segment->matchingLeadsCount();
                            $summaries = $segment->criteria_summary;
                        @endphp
                        <tr class="hover:bg-slate-50" id="segment-row-{{ $segment->id }}">
                            <td class="px-3 py-3 font-medium text-slate-900 max-w-xs">
                                <a href="{{ route('crm.leads.segments.show', $segment) }}" class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <span>{{ $segment->name }}</span>
                                </a>
                                @if($segment->description)
                                    <div class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $segment->description }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-700 max-w-md">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($summaries as $rule)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ $rule }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <a href="{{ route('crm.leads.segments.show', $segment) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition"
                                   title="View matching leads">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <span>{{ number_format($matchCount) }} Leads</span>
                                </a>
                            </td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap text-xs">
                                <div>{{ $segment->creator?->name ?: 'System' }}</div>
                                @if($segment->branch)
                                    <div class="text-[11px] text-slate-400">{{ $segment->branch->name }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-500 whitespace-nowrap text-xs">
                                <div>{{ $segment->updated_at->format('d M Y') }}</div>
                                <div class="text-[11px] text-slate-400">{{ $segment->updated_at->format('H:i') }}</div>
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-ui.button size="sm" variant="secondary" :href="route('crm.leads.segments.show', $segment)" class="!px-2.5 !py-1.5" title="View Matching Leads" aria-label="View Matching Leads">
                                        <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </x-ui.button>
                                    <x-ui.button size="sm" variant="secondary" :href="route('crm.leads.segments.edit', $segment)" class="!px-2.5 !py-1.5" title="Edit Segment" aria-label="Edit Segment">
                                        <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </x-ui.button>
                                    <button type="button"
                                            @click="confirmDelete({{ $segment->id }}, '{{ addslashes($segment->name) }}', {{ $matchCount }})"
                                            class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 shadow-xs hover:bg-red-50 hover:text-red-700 !px-2.5 !py-1.5 transition"
                                            title="Delete Segment"
                                            aria-label="Delete Segment">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <p class="text-sm font-medium text-slate-600 mb-1">No lead segments created yet.</p>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto mb-4">Create targeted segments by Industry, Region, Purchase History, or Engagement Level to run campaigns efficiently.</p>
                                <x-ui.button :href="route('crm.leads.segments.create')" variant="primary">
                                    + Create First Segment
                                </x-ui.button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-t border-slate-100">
            {{ $segments->links() }}
        </div>
    </x-ui.card>

    <!-- Delete Confirmation Modal -->
    <div x-show="deleteModal.show"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
         @keydown.escape.window="deleteModal.show = false">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4 border border-slate-100"
             @click.away="deleteModal.show = false">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Delete Lead Segment</h3>
                    <p class="text-xs text-slate-500">Confirm deletion of segment definition</p>
                </div>
            </div>

            <p class="text-xs text-slate-600">
                Are you sure you want to delete <strong class="text-slate-800" x-text="deleteModal.name"></strong>?
            </p>

            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-800 flex items-center gap-2">
                <span>✓</span>
                <span><strong>Leads are preserved:</strong> Deleting this segment will only delete the saved group definition. All <strong x-text="deleteModal.count"></strong> matching leads will remain completely untouched.</span>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <x-ui.button type="button" variant="secondary" @click="deleteModal.show = false">
                    Cancel
                </x-ui.button>
                <button type="button"
                        @click="executeDelete()"
                        :disabled="isDeleting"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-xs hover:bg-red-700 transition">
                    <span x-show="!isDeleting">Delete Segment</span>
                    <span x-show="isDeleting" x-cloak>Deleting...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function segmentListManager() {
    return {
        notification: { show: false, message: '', type: 'success' },
        deleteModal: { show: false, id: null, name: '', count: 0 },
        isDeleting: false,

        confirmDelete(id, name, count) {
            this.deleteModal = { show: true, id, name, count };
        },

        async executeDelete() {
            if (!this.deleteModal.id) return;
            this.isDeleting = true;

            try {
                const res = await fetch(`{{ url('crm/leads/segments') }}/${this.deleteModal.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    }
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    const row = document.getElementById(`segment-row-${this.deleteModal.id}`);
                    if (row) row.remove();

                    this.notification = {
                        show: true,
                        message: data.message || 'Segment deleted successfully.',
                        type: 'success'
                    };
                    this.deleteModal.show = false;
                } else {
                    this.notification = {
                        show: true,
                        message: data.message || 'Failed to delete segment.',
                        type: 'error'
                    };
                }
            } catch (err) {
                this.notification = {
                    show: true,
                    message: 'Network error deleting segment.',
                    type: 'error'
                };
            } finally {
                this.isDeleting = false;
            }
        }
    };
}
</script>
@endsection

