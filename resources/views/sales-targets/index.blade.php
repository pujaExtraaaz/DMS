@extends('layouts.dms')

@section('title', 'Sales Target List')

@section('content')
<div class="space-y-6">
    {{-- Top Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Sales Targets</h1>
            <p class="text-xs sm:text-sm text-slate-500">Manage, assign, and track performance across all sales targets.</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <form action="{{ route('sales-targets.recalculate') }}" method="POST">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Recalculate All
                </button>
            </form>

            <a href="{{ route('sales-targets.create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Assign Target
            </a>
        </div>
    </div>

    {{-- Metrics Summary Bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <a href="{{ route('sales-targets.index') }}"
           class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-xs hover:border-slate-300 transition {{ !request('progress_status') ? 'ring-2 ring-slate-400 bg-slate-50/50' : '' }}">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Targets</span>
            <p class="text-xl font-bold text-slate-900 mt-1">{{ $totalTargetsCount }}</p>
            <span class="text-[10px] text-slate-400">All assigned targets</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['progress_status' => 'Not Started']) }}"
           class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-xs hover:border-slate-300 transition {{ request('progress_status') === 'Not Started' ? 'ring-2 ring-slate-400 bg-slate-50/50' : '' }}">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Not Started</span>
            <p class="text-xl font-bold text-slate-700 mt-1">{{ $notStartedCount }}</p>
            <span class="text-[10px] text-slate-400">Future start date</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['progress_status' => 'In Progress']) }}"
           class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-xs hover:border-indigo-300 transition {{ request('progress_status') === 'In Progress' ? 'ring-2 ring-indigo-500 bg-indigo-50/30' : '' }}">
            <span class="text-[11px] font-semibold text-indigo-600 uppercase tracking-wider">In Progress</span>
            <p class="text-xl font-bold text-indigo-700 mt-1">{{ $inProgressCount }}</p>
            <span class="text-[10px] text-slate-400">Active timeframe</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['progress_status' => 'Achieved']) }}"
           class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-xs hover:border-emerald-300 transition {{ request('progress_status') === 'Achieved' ? 'ring-2 ring-emerald-500 bg-emerald-50/30' : '' }}">
            <span class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider">Achieved</span>
            <p class="text-xl font-bold text-emerald-700 mt-1">{{ $achievedCount }}</p>
            <span class="text-[10px] text-slate-400">Goal met 100%+</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['progress_status' => 'Period Expired']) }}"
           class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-xs hover:border-rose-300 transition {{ request('progress_status') === 'Period Expired' ? 'ring-2 ring-rose-500 bg-rose-50/30' : '' }}">
            <span class="text-[11px] font-semibold text-rose-600 uppercase tracking-wider">Period Expired</span>
            <p class="text-xl font-bold text-rose-700 mt-1">{{ $periodExpiredCount }}</p>
            <span class="text-[10px] text-slate-400">Unachieved past end</span>
        </a>
    </div>

    {{-- Filter Toolbar --}}
    <div class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-xs">
        <form method="GET" action="{{ route('sales-targets.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-7 gap-3">
            <div>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search target, party..."
                       class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <select name="progress_status" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Progress Statuses</option>
                    <option value="Not Started" @selected(request('progress_status') === 'Not Started')>Not Started</option>
                    <option value="In Progress" @selected(request('progress_status') === 'In Progress')>In Progress</option>
                    <option value="Achieved" @selected(request('progress_status') === 'Achieved')>Achieved</option>
                    <option value="Period Expired" @selected(request('progress_status') === 'Period Expired')>Period Expired</option>
                </select>
            </div>

            <div>
                <select name="owner_type" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Owner Types</option>
                    <option value="party" @selected(request('owner_type') === 'party')>Party / Customer</option>
                    <option value="individual" @selected(request('owner_type') === 'individual')>Individual Salesman</option>
                    <option value="team" @selected(request('owner_type') === 'team')>Team</option>
                    <option value="branch" @selected(request('owner_type') === 'branch')>Branch</option>
                </select>
            </div>

            <div>
                <select name="customer_id" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Parties</option>
                    @foreach($parties as $p)
                        <option value="{{ $p->id }}" @selected(request('customer_id') == $p->id)>
                            {{ $p->name }} {{ $p->code ? '('.$p->code.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="target_type" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Target Types</option>
                    <option value="value" @selected(request('target_type') === 'value')>Value-Wise</option>
                    <option value="product" @selected(request('target_type') === 'product')>Product-Wise</option>
                    <option value="sku" @selected(request('target_type') === 'sku')>SKU-Wise</option>
                    <option value="brand" @selected(request('target_type') === 'brand')>Brand-Wise</option>
                    <option value="category" @selected(request('target_type') === 'category')>Category-Wise</option>
                    <option value="combined" @selected(request('target_type') === 'combined')>Combined</option>
                </select>
            </div>

            <div>
                <select name="status" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Admin Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="pending_approval" @selected(request('status') === 'pending_approval')>Pending Approval</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 transition">
                    Filter
                </button>
                <a href="{{ route('sales-targets.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition" title="Reset">
                    ↺
                </a>
            </div>
        </form>
    </div>

    {{-- Targets Table --}}
    <div class="overflow-x-auto rounded-xl border border-slate-200/90 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
            <thead class="bg-slate-50/80 text-slate-700 font-semibold tracking-wider uppercase">
                <tr>
                    <th class="px-4 py-3">Party Name</th>
                    <th class="px-4 py-3 text-right">Target Amount</th>
                    <th class="px-4 py-3 text-center">Start Date</th>
                    <th class="px-4 py-3 text-center">End Date</th>
                    <th class="px-4 py-3 text-right">Achieved Target</th>
                    <th class="px-4 py-3 text-right">Shortfall</th>
                    <th class="px-4 py-3 text-center">Achievement %</th>
                    <th class="px-4 py-3 text-center">Remaining Days</th>
                    <th class="px-4 py-3 text-center">Target Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($targets as $t)
                    @php
                        $ach = $t->achievement;
                        $targetAmt = (float) $t->target_amount;
                        $achAmount = $ach ? (float) $ach->achieved_amount : 0.0;
                        $shortfall = $ach ? (float) $ach->pending_amount : max(0, $targetAmt - $achAmount);
                        $percent = $ach ? (float) $ach->achievement_percent : ($targetAmt > 0 ? round(($achAmount / $targetAmt) * 100, 1) : 0);
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        {{-- 1. Party Name / Details --}}
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-900">
                                {{ $t->customer?->name ?? $t->owner_name }}
                            </div>
                            <div class="flex items-center gap-1.5 mt-0.5 text-[11px] text-slate-500">
                                <a href="{{ route('sales-targets.show', $t) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    {{ $t->name }}
                                </a>
                                @if($t->customer?->code)
                                    <span>• Code: {{ $t->customer->code }}</span>
                                @elseif($t->owner_type !== 'party')
                                    <span>• {{ ucfirst($t->owner_type) }}</span>
                                @endif
                                @if($t->revision_number > 0)
                                    <span class="rounded bg-slate-100 px-1 text-[10px] text-slate-600">Rev #{{ $t->revision_number }}</span>
                                @endif
                            </div>
                        </td>

                        {{-- 2. Target Amount --}}
                        <td class="px-4 py-3 text-right font-semibold text-slate-900 whitespace-nowrap">
                            ₹ {{ number_format($targetAmt, 2) }}
                            @if($t->target_quantity > 0)
                                <div class="text-[11px] text-slate-400 font-normal">{{ number_format($t->target_quantity, 0) }} Units</div>
                            @endif
                        </td>

                        {{-- 3. Target Start Date --}}
                        <td class="px-4 py-3 text-center text-slate-600 whitespace-nowrap">
                            {{ $t->starts_on->format('d M Y') }}
                        </td>

                        {{-- 4. Target End Date --}}
                        <td class="px-4 py-3 text-center text-slate-600 whitespace-nowrap">
                            {{ $t->ends_on->format('d M Y') }}
                        </td>

                        {{-- 5. Achieved Target --}}
                        <td class="px-4 py-3 text-right font-bold {{ $percent >= 100 ? 'text-emerald-700' : 'text-slate-900' }} whitespace-nowrap">
                            ₹ {{ number_format($achAmount, 2) }}
                            @if($ach && $ach->achieved_quantity > 0)
                                <div class="text-[11px] text-slate-500 font-normal">{{ number_format($ach->achieved_quantity, 0) }} Units</div>
                            @endif
                        </td>

                        {{-- 6. Shortfall --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if($t->progress_status === 'Period Expired' && $shortfall > 0)
                                <div class="font-bold text-rose-600">₹ {{ number_format($shortfall, 2) }}</div>
                                <div class="text-[10px] text-rose-500 font-semibold uppercase tracking-wider">Unachieved</div>
                            @elseif($shortfall > 0)
                                <span class="font-semibold text-rose-600">₹ {{ number_format($shortfall, 2) }}</span>
                            @else
                                <span class="font-semibold text-emerald-600">₹ 0.00</span>
                            @endif
                        </td>

                        {{-- 7. Achievement Percentage --}}
                        <td class="px-4 py-3 text-center w-28 whitespace-nowrap">
                            <span class="text-xs font-bold {{ $percent >= 100 ? 'text-emerald-700' : ($percent >= 75 ? 'text-indigo-600' : 'text-amber-600') }}">
                                {{ $percent }}%
                            </span>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                <div class="h-1.5 rounded-full {{ $percent >= 100 ? 'bg-emerald-500' : ($percent >= 60 ? 'bg-indigo-500' : 'bg-amber-500') }}"
                                     style="width: {{ min(100, $percent) }}%"></div>
                            </div>
                        </td>

                        {{-- 8. Remaining Days --}}
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @php
                                $remLabel = $t->remaining_days_label;
                            @endphp
                            @if($t->progress_status === 'Achieved')
                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                                    <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ $remLabel }}
                                </span>
                            @elseif($t->progress_status === 'Period Expired')
                                <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700">
                                    {{ $remLabel }}
                                </span>
                            @elseif($t->progress_status === 'Not Started')
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                                    {{ $remLabel }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700">
                                    {{ $remLabel }}
                                </span>
                            @endif
                        </td>

                        {{-- 9. Target Status --}}
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if($t->progress_status === 'Achieved')
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Achieved</span>
                            @elseif($t->progress_status === 'Period Expired')
                                <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">Period Expired</span>
                            @elseif($t->progress_status === 'Not Started')
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-400/20">Not Started</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">In Progress</span>
                            @endif
                        </td>

                        {{-- 10. Actions Dropdown / Links --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="{{ route('sales-targets.show', $t) }}"
                                   class="p-1 text-slate-400 hover:text-indigo-600 transition"
                                   title="View Details &amp; Drilldown">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>

                                <a href="{{ route('sales-targets.edit', $t) }}"
                                   class="p-1 text-slate-400 hover:text-indigo-600 transition"
                                   title="Revise / Edit Target">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>

                                <a href="{{ route('sales-targets.create', ['copy_from' => $t->id]) }}"
                                   class="p-1 text-slate-400 hover:text-indigo-600 transition"
                                   title="Copy Target to Next Period">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                </a>

                                @if($t->status === 'pending_approval')
                                    <form action="{{ route('sales-targets.approve', $t) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1 text-emerald-600 hover:text-emerald-800 transition" title="Approve Target">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="py-12 text-center text-xs text-slate-400">
                            No targets found matching the current search criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div>
        {{ $targets->links() }}
    </div>
</div>
@endsection

