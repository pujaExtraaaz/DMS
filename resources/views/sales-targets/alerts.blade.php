@extends('layouts.dms')

@section('title', 'Target Threshold & Underperformance Alerts')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Target Alerts &amp; Notifications</h1>
                @if($unreadCount > 0)
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">
                        {{ $unreadCount }} Unread
                    </span>
                @endif
            </div>
            <p class="text-xs sm:text-sm text-slate-500">Threshold crossings (50%, 75%, 90%, 100%) and pacing underperformance notifications.</p>
        </div>

        <div class="flex items-center gap-2">
            @if($unreadCount > 0)
                <form action="{{ route('sales-targets.alerts.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        ✓ Mark All as Read
                    </button>
                </form>
            @endif

            <a href="{{ route('sales-targets.dashboard') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                Target Dashboard
            </a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-xs">
        <form method="GET" action="{{ route('sales-targets.alerts') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Alert Type</label>
                <select name="alert_type" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Alert Types</option>
                    <option value="threshold_50" @selected(request('alert_type') === 'threshold_50')>50% Milestone Reached</option>
                    <option value="threshold_75" @selected(request('alert_type') === 'threshold_75')>75% Milestone Reached</option>
                    <option value="threshold_90" @selected(request('alert_type') === 'threshold_90')>90% Critical Milestone</option>
                    <option value="threshold_100" @selected(request('alert_type') === 'threshold_100')>100% Target Achieved</option>
                    <option value="underperformance" @selected(request('alert_type') === 'underperformance')>Underperformance Warning</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status</label>
                <select name="is_read" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All (Read &amp; Unread)</option>
                    <option value="0" @selected(request('is_read') === '0')>Unread Only</option>
                    <option value="1" @selected(request('is_read') === '1')>Read Only</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Filter</button>
                <a href="{{ route('sales-targets.alerts') }}" class="rounded-lg border px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">↺</a>
            </div>
        </form>
    </div>

    {{-- Alerts List --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-xs divide-y divide-slate-100 overflow-hidden">
        @forelse($alerts as $alert)
            <div class="p-4 sm:p-5 flex items-start justify-between gap-4 {{ $alert->is_read ? 'bg-white opacity-85' : 'bg-slate-50/50' }} hover:bg-slate-50 transition">
                <div class="flex items-start gap-3.5 min-w-0">
                    {{-- Icon Badge --}}
                    <span class="p-2 rounded-lg mt-0.5 shrink-0 {{ str_contains($alert->alert_type, '100') ? 'bg-emerald-100 text-emerald-700' : (str_contains($alert->alert_type, 'underperformance') ? 'bg-rose-100 text-rose-700' : (str_contains($alert->alert_type, '90') ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700')) }}">
                        @if(str_contains($alert->alert_type, '100'))
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif(str_contains($alert->alert_type, 'underperformance'))
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        @endif
                    </span>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-bold text-slate-900">{{ $alert->title }}</h3>
                            @if(!$alert->is_read)
                                <span class="rounded bg-indigo-600 px-1.5 py-0.2 text-[10px] font-bold text-white uppercase tracking-wider">New</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-600 mt-1">{{ $alert->message }}</p>
                        <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-2">
                            <span>Target: <a href="{{ route('sales-targets.show', $alert->target_id) }}" class="font-semibold text-indigo-600 hover:underline">{{ $alert->target?->name }}</a></span>
                            <span>&bull;</span>
                            <span>{{ $alert->created_at->format('d M Y, h:i A') }} ({{ $alert->created_at->diffForHumans() }})</span>
                        </div>
                    </div>
                </div>

                {{-- Action --}}
                <div class="shrink-0 flex items-center gap-2">
                    <a href="{{ route('sales-targets.show', $alert->target_id) }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                        View Target
                    </a>
                    @if(!$alert->is_read)
                        <form action="{{ route('sales-targets.alerts.read', $alert) }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                                Mark Read
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-xs text-slate-400">
                No alerts or notifications recorded.
            </div>
        @endforelse
    </div>

    <div>
        {{ $alerts->links() }}
    </div>
</div>
@endsection

