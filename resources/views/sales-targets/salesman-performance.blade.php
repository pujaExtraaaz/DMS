@extends('layouts.dms')

@section('title', 'Salesman Target Performance')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Salesman Performance Matrix</h1>
            <p class="text-xs sm:text-sm text-slate-500">Comparative quota attainment, shortfalls, and rankings calculated from invoices.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('sales-targets.dashboard') }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                Target Dashboard
            </a>
            <a href="{{ route('sales-targets.reports') }}" class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                Export Reports
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-xs">
        <form method="GET" action="{{ route('sales-targets.salesman-performance') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Financial Year</label>
                <select name="financial_year_id" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">Current &amp; All FY</option>
                    @foreach($financialYears as $fy)
                        <option value="{{ $fy->id }}" @selected(request('financial_year_id') == $fy->id)>{{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border-gray-300 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border-gray-300 text-xs">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Filter</button>
                <a href="{{ route('sales-targets.salesman-performance') }}" class="rounded-lg border px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">↺</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
            <thead class="bg-slate-50/80 text-slate-700 font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-center w-16">Rank</th>
                    <th class="px-4 py-3">Salesman</th>
                    <th class="px-4 py-3 text-center">Active Targets</th>
                    <th class="px-4 py-3 text-right">Target Value (₹)</th>
                    <th class="px-4 py-3 text-right">Achieved Value (₹)</th>
                    <th class="px-4 py-3 text-right">Pending / Shortfall (₹)</th>
                    <th class="px-4 py-3 text-center">Attainment %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($performanceData as $idx => $row)
                    @php
                        $user = $row['user'];
                        $percent = $row['achievement_percent'];
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold {{ $idx === 0 ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-400' : ($idx === 1 ? 'bg-slate-200 text-slate-700' : ($idx === 2 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600')) }}">
                                {{ $idx + 1 }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-900">{{ $user->name }}</div>
                            <span class="text-[11px] text-slate-400">{{ $user->email }}</span>
                        </td>
                        <td class="px-4 py-3 text-center font-medium">
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ $row['targets_count'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">₹ {{ number_format($row['target_amount'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-700">₹ {{ number_format($row['achieved_amount'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-rose-700">₹ {{ number_format($row['pending_amount'], 2) }}</td>
                        <td class="px-4 py-3 text-center w-36">
                            <span class="font-bold text-xs {{ $percent >= 100 ? 'text-emerald-700' : ($percent >= 75 ? 'text-indigo-600' : 'text-amber-600') }}">
                                {{ $percent }}%
                            </span>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                <div class="h-1.5 rounded-full {{ $percent >= 100 ? 'bg-emerald-500' : ($percent >= 60 ? 'bg-indigo-500' : 'bg-amber-500') }}"
                                     style="width: {{ min(100, $percent) }}%"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-xs text-slate-400">
                            No performance records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

