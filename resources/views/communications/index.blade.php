@extends('layouts.dms')
@section('title', 'Communications')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Communication Log" />

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search customer, recipient, status..."
            :reset-url="route('communications.index')"
        >
            <x-slot name="filters">
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span>From:</span>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    />
                </div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span>To:</span>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    />
                </div>

                <select
                    name="type"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Type: All</option>
                    <option value="whatsapp_invoice" @selected(request('type') === 'whatsapp_invoice')>WhatsApp Invoice</option>
                    <option value="payment_link" @selected(request('type') === 'payment_link')>Payment Link</option>
                    <option value="payment_reminder" @selected(request('type') === 'payment_reminder')>Reminder</option>
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="created_at" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Date</x-ui.sortable-th>
                        <x-ui.sortable-th column="type" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Type</x-ui.sortable-th>
                        <x-ui.sortable-th column="customer" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Customer</x-ui.sortable-th>
                        <x-ui.sortable-th column="recipient" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Recipient</x-ui.sortable-th>
                        <x-ui.sortable-th column="status" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Status</x-ui.sortable-th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-sm text-slate-600 whitespace-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm text-slate-700">{{ str_replace('_', ' ', $log->type) }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $log->customer?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600 font-mono text-xs">{{ $log->recipient }}</td>
                            <td class="px-6 py-4 text-sm">
                                <x-ui.badge :variant="in_array($log->status, ['sent', 'delivered', 'read']) ? 'emerald' : ($log->status === 'failed' ? 'rose' : 'amber')">
                                    {{ ucfirst($log->status) }}
                                </x-ui.badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No messages found" description="Try adjusting your search or filters." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </x-ui.card>
</div>
@endsection
