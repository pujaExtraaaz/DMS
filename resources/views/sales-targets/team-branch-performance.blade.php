@extends('layouts.dms')

@section('title', 'Team & Branch Target Performance')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Team &amp; Branch Performance</h1>
            <p class="text-xs sm:text-sm text-slate-500">Consolidated team and branch target achievements across DMS.</p>
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
        <form method="GET" action="{{ route('sales-targets.team-branch-performance') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Financial Year</label>
                <select name="financial_year_id" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">Current &amp; All FY</option>
                    @foreach($financialYears as $fy)
                        <option value="{{ $fy->id }}" @selected(request('financial_year_id') == $fy->id)>{{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Filter</button>
                <a href="{{ route('sales-targets.team-branch-performance') }}" class="rounded-lg border px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">↺</a>
            </div>
        </form>
    </div>

    {{-- Section 1: Sales Teams --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">
            Sales Teams Performance
        </h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase">
                    <tr>
                        <th class="px-4 py-3">Team Name</th>
                        <th class="px-4 py-3">Team Leader</th>
                        <th class="px-4 py-3 text-center">Members</th>
                        <th class="px-4 py-3 text-right">Target (₹)</th>
                        <th class="px-4 py-3 text-right">Achieved (₹)</th>
                        <th class="px-4 py-3 text-right">Pending (₹)</th>
                        <th class="px-4 py-3 text-center">Attainment %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($teamData as $tRow)
                        @php
                            $team = $tRow['team'];
                            $percent = $tRow['percent'];
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $team->name }} <span class="text-slate-400 font-mono text-[11px]">({{ $team->code }})</span></td>
                            <td class="px-4 py-3 text-slate-600">{{ $team->leader?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center"><span class="rounded bg-slate-100 px-2 py-0.5">{{ $tRow['members_count'] }}</span></td>
                            <td class="px-4 py-3 text-right font-semibold">₹ {{ number_format($tRow['target_amount'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-emerald-700">₹ {{ number_format($tRow['achieved_amount'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-rose-700">₹ {{ number_format($tRow['pending_amount'], 2) }}</td>
                            <td class="px-4 py-3 text-center w-36">
                                <span class="font-bold text-xs {{ $percent >= 100 ? 'text-emerald-700' : 'text-indigo-600' }}">{{ $percent }}%</span>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                    <div class="h-1.5 rounded-full {{ $percent >= 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ min(100, $percent) }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-400">No active team targets configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Section 2: Branch Consolidation --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">
            Branch Quota Consolidation
        </h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase">
                    <tr>
                        <th class="px-4 py-3">Branch Name</th>
                        <th class="px-4 py-3 text-right">Target (₹)</th>
                        <th class="px-4 py-3 text-right">Achieved (₹)</th>
                        <th class="px-4 py-3 text-right">Pending (₹)</th>
                        <th class="px-4 py-3 text-center">Attainment %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($branchData as $bRow)
                        @php
                            $branch = $bRow['branch'];
                            $percent = $bRow['percent'];
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $branch->name }}</td>
                            <td class="px-4 py-3 text-right font-semibold">₹ {{ number_format($bRow['target_amount'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-emerald-700">₹ {{ number_format($bRow['achieved_amount'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-rose-700">₹ {{ number_format($bRow['pending_amount'], 2) }}</td>
                            <td class="px-4 py-3 text-center w-36">
                                <span class="font-bold text-xs {{ $percent >= 100 ? 'text-emerald-700' : 'text-indigo-600' }}">{{ $percent }}%</span>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                    <div class="h-1.5 rounded-full {{ $percent >= 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ min(100, $percent) }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-400">No active branch targets configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

