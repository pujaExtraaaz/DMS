@extends('layouts.dms')
@section('title', 'Segment: ' . $segment->name)
@section('content')
<div class="space-y-6">
    <x-ui.page-header :title="'Segment: ' . $segment->name">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('crm.leads.segments.index')">
                <svg class="w-4 h-4 mr-1.5 -ml-0.5 inline-block text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Segments List
            </x-ui.button>
            <x-ui.button variant="secondary" :href="route('crm.leads.segments.edit', $segment)">
                <svg class="w-4 h-4 mr-1.5 -ml-0.5 inline-block text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                Edit Segment
            </x-ui.button>
            <a href="{{ route('crm.leads.segments.export', $segment) }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export Audience (CSV)
            </a>
        </x-slot>
    </x-ui.page-header>

    <!-- Overview Card -->
    <x-ui.card>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
            <div class="md:col-span-2 space-y-2">
                @if($segment->description)
                    <p class="text-xs text-slate-600">{{ $segment->description }}</p>
                @endif
                <div>
                    <h4 class="text-[11px] font-semibold text-slate-400 tracking-wider mb-1.5">Configured Criteria</h4>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($segment->criteria_summary as $rule)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $rule }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 grid grid-cols-3 gap-3 text-center">
                <div class="p-3 rounded-xl bg-indigo-50 border border-indigo-100">
                    <span class="text-[11px] font-semibold text-indigo-600 uppercase tracking-wider">Matching Leads</span>
                    <div class="text-2xl font-bold text-indigo-900 mt-1">{{ number_format($stats['total_count']) }}</div>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100">
                    <span class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider">With Mobile</span>
                    <div class="text-2xl font-bold text-emerald-900 mt-1">{{ number_format($stats['with_mobile_count']) }}</div>
                </div>
                <div class="p-3 rounded-xl bg-purple-50 border border-purple-100">
                    <span class="text-[11px] font-semibold text-purple-600 uppercase tracking-wider">With Email</span>
                    <div class="text-2xl font-bold text-purple-900 mt-1">{{ number_format($stats['with_email_count']) }}</div>
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- Matching Leads Listing -->
    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search Within Matching Audience..."
            :reset-url="route('crm.leads.segments.show', $segment)"
        />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Lead Name</x-ui.sortable-th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Contact</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Location</th>
                        <x-ui.sortable-th column="status" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Status</x-ui.sortable-th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Purchase History</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Engagement Level</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($leads as $item)
                        @php
                            $purchase = $item->purchase_summary;
                            $engagement = $item->engagement_summary;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2.5 font-medium text-slate-900">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <a href="{{ route('crm.leads.show', $item) }}" class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline">
                                        {{ $item->name }}
                                    </a>
                                    <x-ui.badge :variant="match($item->category) { 'customer' => 'success', 'prospect' => 'info', 'partner' => 'primary', default => 'neutral' }" class="text-[10px] px-1.5 py-0.2 leading-none">
                                        {{ $item->category_label }}
                                    </x-ui.badge>
                                </div>
                                @if($item->company_name ?: $item->organization)
                                    <div class="text-xs text-slate-500">{{ $item->company_name ?: $item->organization }}</div>
                                @endif
                                @if($item->industry ?: $item->tag)
                                    <div class="text-[10px] text-slate-400 mt-0.5">Industry: {{ $item->industry ?: $item->tag }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-xs text-slate-600">
                                <div>{{ $item->mobile ?: ($item->secondary_mobile ?: ($item->phone ?: '—')) }}</div>
                                @if($item->email)
                                    <div class="text-[11px] text-slate-400">{{ $item->email }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-xs text-slate-600">
                                <div>{{ trim(($item->city ? $item->city . ', ' : '') . ($item->state ?? '')) ?: '—' }}</div>
                                @if($item->branch)
                                    <div class="text-[10px] text-slate-400">{{ $item->branch->name }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 capitalize">
                                    {{ $item->status === 'follow_up' ? 'Follow-up' : $item->status }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-xs">
                                <x-ui.badge :variant="$purchase['badge_variant']" class="text-[11px]">
                                    {{ $purchase['status_label'] }}
                                </x-ui.badge>
                                @if($purchase['last_order_date'])
                                    <div class="text-[10px] text-slate-400 mt-0.5">Last: {{ \Illuminate\Support\Carbon::parse($purchase['last_order_date'])->format('d M Y') }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-xs">
                                <x-ui.badge :variant="$engagement['badge_variant']" class="text-[11px]">
                                    {{ $engagement['level_label'] }}
                                </x-ui.badge>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    {{ $engagement['followups_count'] }} follow-ups · {{ $engagement['activities_count'] }} activities
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                <x-ui.button size="sm" variant="secondary" :href="route('crm.leads.show', $item)" class="!px-2.5 !py-1.5" title="Open Lead">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-xs">
                                No leads currently match this segment's criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-t border-slate-100">
            {{ $leads->links() }}
        </div>
    </x-ui.card>
</div>
@endsection

