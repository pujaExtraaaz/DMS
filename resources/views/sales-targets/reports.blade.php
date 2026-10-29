@extends('layouts.dms')

@section('title', 'Sales Target Reports & Export')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Sales Target Reports</h1>
            <p class="text-xs sm:text-sm text-slate-500">Comprehensive target attainment reports with live invoice reconciliation and export capabilities.</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            {{-- Export to Excel --}}
            <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-800 shadow-xs hover:bg-emerald-100 transition">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel (.xlsx)
            </a>

            {{-- Export to CSV --}}
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <form method="GET" action="{{ route('sales-targets.reports') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Owner Type</label>
                <select name="owner_type" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Owners</option>
                    <option value="party" @selected(request('owner_type') === 'party')>Party / Customer</option>
                    <option value="individual" @selected(request('owner_type') === 'individual')>Individual Salesman</option>
                    <option value="team" @selected(request('owner_type') === 'team')>Sales Team</option>
                    <option value="branch" @selected(request('owner_type') === 'branch')>Branch</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Party / Customer</label>
                <select name="customer_id" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Parties</option>
                    @foreach($parties as $p)
                        <option value="{{ $p->id }}" @selected(request('customer_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Salesperson</label>
                <select name="salesperson_id" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Salespeople</option>
                    @foreach($salespeople as $sp)
                        <option value="{{ $sp->id }}" @selected(request('salesperson_id') == $sp->id)>{{ $sp->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Target Type</label>
                <select name="target_type" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Types</option>
                    <option value="value" @selected(request('target_type') === 'value')>Value-Wise</option>
                    <option value="product" @selected(request('target_type') === 'product')>Product-Wise</option>
                    <option value="sku" @selected(request('target_type') === 'sku')>SKU-Wise</option>
                    <option value="brand" @selected(request('target_type') === 'brand')>Brand-Wise</option>
                    <option value="category" @selected(request('target_type') === 'category')>Category-Wise</option>
                    <option value="combined" @selected(request('target_type') === 'combined')>Combined</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    <option value="pending_approval" @selected(request('status') === 'pending_approval')>Pending Approval</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border-gray-300 text-xs">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Filter</button>
                <a href="{{ route('sales-targets.reports') }}" class="rounded-lg border px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">↺</a>
            </div>
        </form>
    </div>

    {{-- Report Table --}}
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
            <thead class="bg-slate-50 text-slate-700 font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-3.5 py-3">Ref ID</th>
                    <th class="px-3.5 py-3">Target Name</th>
                    <th class="px-3.5 py-3">Owner</th>
                    <th class="px-3.5 py-3">Period</th>
                    <th class="px-3.5 py-3 text-right">Target (₹)</th>
                    <th class="px-3.5 py-3 text-right">Achieved (₹)</th>
                    <th class="px-3.5 py-3 text-right">Pending (₹)</th>
                    <th class="px-3.5 py-3 text-center">Attainment %</th>
                    <th class="px-3.5 py-3 text-center">Target Qty</th>
                    <th class="px-3.5 py-3 text-center">Achieved Qty</th>
                    <th class="px-3.5 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @php
                    $sumTarget = 0;
                    $sumAchieved = 0;
                    $sumPending = 0;
                @endphp
                @forelse($targets as $t)
                    @php
                        $ach = $t->achievement;
                        $tAmt = (float) $t->target_amount;
                        $aAmt = $ach ? (float) $ach->achieved_amount : 0.0;
                        $pAmt = $ach ? (float) $ach->pending_amount : max(0, $tAmt - $aAmt);
                        $pct = $ach ? (float) $ach->achievement_percent : 0.0;

                        $sumTarget += $tAmt;
                        $sumAchieved += $aAmt;
                        $sumPending += $pAmt;
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-3.5 py-2.5 font-mono text-[11px] text-slate-500">TGT-{{ str_pad((string)$t->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-3.5 py-2.5 font-bold text-slate-900">
                            <a href="{{ route('sales-targets.show', $t) }}" class="text-indigo-600 hover:underline">
                                {{ $t->name }}
                            </a>
                        </td>
                        <td class="px-3.5 py-2.5">
                            <span class="font-medium text-slate-800">{{ $t->owner_name }}</span>
                            <span class="text-[10px] text-slate-400 block">{{ ucfirst($t->owner_type) }}</span>
                        </td>
                        <td class="px-3.5 py-2.5 text-slate-600">
                            {{ $t->starts_on->format('d M y') }} &rarr; {{ $t->ends_on->format('d M y') }}
                        </td>
                        <td class="px-3.5 py-2.5 text-right font-semibold">₹ {{ number_format($tAmt, 2) }}</td>
                        <td class="px-3.5 py-2.5 text-right font-bold text-emerald-700">₹ {{ number_format($aAmt, 2) }}</td>
                        <td class="px-3.5 py-2.5 text-right font-semibold text-rose-700">₹ {{ number_format($pAmt, 2) }}</td>
                        <td class="px-3.5 py-2.5 text-center font-bold {{ $pct >= 100 ? 'text-emerald-700' : 'text-indigo-600' }}">{{ $pct }}%</td>
                        <td class="px-3.5 py-2.5 text-center">{{ $t->target_quantity > 0 ? number_format($t->target_quantity, 0) : '—' }}</td>
                        <td class="px-3.5 py-2.5 text-center text-emerald-700 font-semibold">{{ $ach && $ach->achieved_quantity > 0 ? number_format($ach->achieved_quantity, 0) : '—' }}</td>
                        <td class="px-3.5 py-2.5 text-center">
                            <span class="rounded px-2 py-0.5 text-[10px] font-semibold {{ $t->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ ucfirst($t->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="py-12 text-center text-xs text-slate-400">No report records found.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($targets->isNotEmpty())
                <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-200">
                    <tr>
                        <td colspan="4" class="px-3.5 py-2.5 text-right uppercase">Page Totals:</td>
                        <td class="px-3.5 py-2.5 text-right font-bold">₹ {{ number_format($sumTarget, 2) }}</td>
                        <td class="px-3.5 py-2.5 text-right font-bold text-emerald-700">₹ {{ number_format($sumAchieved, 2) }}</td>
                        <td class="px-3.5 py-2.5 text-right font-bold text-rose-700">₹ {{ number_format($sumPending, 2) }}</td>
                        <td class="px-3.5 py-2.5 text-center font-bold text-indigo-700">
                            {{ $sumTarget > 0 ? round(($sumAchieved / $sumTarget) * 100, 1) : 0 }}%
                        </td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <div>
        {{ $targets->links() }}
    </div>
</div>
@endsection

