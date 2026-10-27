@extends('layouts.dms')
@section('title', 'Sister Concern Management · Dashboard')

@section('content')
<div class="max-w-7xl mx-auto pb-12">
    {{-- Header Banner & Navigation Tabs --}}
    @include('organization.sister-concerns.partials.header', [
        'activeTab' => 'dashboard',
        'tabCounts' => $tabCounts,
    ])

    {{-- C. Summary Cards (8 Cards in Responsive Grid) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 mb-8">
        {{-- Card 1: Total Business Groups --}}
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-100 hover:shadow-md transition-shadow flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Business Groups</span>
                <div class="h-10 w-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['business_groups_count']) }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700">Groups</span>
            </div>
        </div>

        {{-- Card 2: Total Sister Concerns --}}
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-100 hover:shadow-md transition-shadow flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Sister Concerns</span>
                <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['companies_count']) }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">Entities</span>
            </div>
        </div>

        {{-- Card 3: Total Head Offices --}}
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-100 hover:shadow-md transition-shadow flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Head Offices</span>
                <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['head_offices_count']) }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">HQ Sites</span>
            </div>
        </div>

        {{-- Card 4: Total Turnover --}}
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-100 hover:shadow-md transition-shadow flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Turnover</span>
                <div class="h-10 w-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-slate-900" title="₹{{ number_format($stats['total_turnover'], 2) }}">{{ $stats['turnover_formatted'] }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-violet-50 text-violet-700">All Entities</span>
            </div>
        </div>

        {{-- Card 5: Total Employees --}}
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-100 hover:shadow-md transition-shadow flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Employees</span>
                <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['employees_count']) }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Workforce</span>
            </div>
        </div>

        {{-- Card 6: Active Transactions --}}
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-100 hover:shadow-md transition-shadow flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Transactions</span>
                <div class="h-10 w-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['active_transactions_count']) }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-50 text-cyan-700">In Progress</span>
            </div>
        </div>

        {{-- Card 7: Total Transaction Value --}}
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-100 hover:shadow-md transition-shadow flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Transaction Value</span>
                <div class="h-10 w-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-slate-900" title="₹{{ number_format($stats['total_transaction_value'], 2) }}">{{ $stats['transaction_value_formatted'] }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700">Volume</span>
            </div>
        </div>

        {{-- Card 8: Settled Transactions --}}
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-100 hover:shadow-md transition-shadow flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Settled Transactions</span>
                <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['settled_transactions_count']) }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Completed</span>
            </div>
        </div>
    </div>

    {{-- D. Dashboard Charts (2 Charts in Responsive Grid) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Chart 1: Group-wise Turnover (₹ Lakhs) --}}
        <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-100 flex flex-col justify-between">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Group-wise Turnover (₹ Lakhs)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Turnover performance grouped by business group</p>
                </div>
                <span class="inline-flex items-center rounded-lg bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700">
                    Live Data
                </span>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="groupTurnoverChart"></canvas>
            </div>
        </div>

        {{-- Chart 2: Entity Type Distribution --}}
        <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-100 flex flex-col justify-between">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Entity Type Distribution</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Distribution of Head Office, Branches, and Warehouses</p>
                </div>
                <span class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                    Infrastructure
                </span>
            </div>
            <div class="relative h-72 w-full flex items-center justify-center">
                <canvas id="entityDistributionChart"></canvas>
            </div>
        </div>
    </div>

    {{-- E. Quick Overview Table --}}
    <div class="bg-white rounded-2xl shadow-xs border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Sister Concerns Summary</h3>
                <p class="text-xs text-slate-500 mt-0.5">Active business groups and affiliated entities</p>
            </div>
            <a href="{{ route('organization.sister-concerns.companies') }}" class="text-xs font-semibold text-purple-700 hover:text-purple-800 flex items-center gap-1 transition">
                <span>View all companies</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/75">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Business Group</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Companies</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Turnover (₹ Lakhs)</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $overviewGroups = \App\Domains\Organization\Models\BusinessGroup::with(['companies'])->get();
                    @endphp
                    @forelse($overviewGroups as $idx => $grp)
                        @php
                            $grpTurnover = $groupTurnoverValues[$idx] ?? 0;
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-3.5 font-medium text-slate-900">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-lg bg-purple-50 text-purple-700 font-bold flex items-center justify-center text-xs">
                                        {{ substr($grp->name, 0, 1) }}
                                    </div>
                                    <span>{{ $grp->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 font-mono text-xs text-slate-600">{{ $grp->code }}</td>
                            <td class="px-6 py-3.5 text-slate-600">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $grp->companies->count() }} company(ies)
                                </span>
                            </td>
                            <td class="px-6 py-3.5 font-semibold text-slate-900">
                                ₹{{ number_format($grpTurnover, 2) }} L
                            </td>
                            <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('organization.business-groups.edit', $grp) }}" class="text-xs font-semibold text-purple-600 hover:text-purple-800">
                                    Configure →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                No business groups found. Click "+ Add Group" to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const groupLabels = @json($groupTurnoverLabels);
    const groupValues = @json($groupTurnoverValues);
    const entityLabels = @json($entityDistribution['labels']);
    const entityData = @json($entityDistribution['data']);

    // 1. Group-wise Turnover Bar Chart
    const ctxTurnover = document.getElementById('groupTurnoverChart');
    if (ctxTurnover && typeof Chart !== 'undefined') {
        new Chart(ctxTurnover, {
            type: 'bar',
            data: {
                labels: groupLabels,
                datasets: [{
                    label: 'Turnover (₹ Lakhs)',
                    data: groupValues,
                    backgroundColor: [
                        'rgba(147, 51, 234, 0.85)',
                        'rgba(99, 102, 241, 0.85)',
                        'rgba(59, 130, 246, 0.85)',
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(245, 158, 11, 0.85)'
                    ],
                    borderColor: [
                        '#7e22ce',
                        '#4f46e5',
                        '#2563eb',
                        '#059669',
                        '#d97706'
                    ],
                    borderWidth: 1.5,
                    borderRadius: 8,
                    maxBarThickness: 48,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e1b4b',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                return ' Turnover: ₹' + context.parsed.y + ' Lakhs';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 12, weight: '500' }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { family: 'Inter', size: 12 },
                            color: '#64748b',
                            callback: function (val) {
                                return '₹' + val + ' L';
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Entity Type Distribution Doughnut Chart
    const ctxEntity = document.getElementById('entityDistributionChart');
    if (ctxEntity && typeof Chart !== 'undefined') {
        new Chart(ctxEntity, {
            type: 'doughnut',
            data: {
                labels: entityLabels,
                datasets: [{
                    data: entityData,
                    backgroundColor: [
                        '#7c3aed', // Purple: Head Office
                        '#4f46e5', // Indigo: Branches
                        '#06b6d4'  // Cyan: Warehouses
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Inter', size: 12, weight: '500' },
                            padding: 18,
                            color: '#334155',
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e1b4b',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const val = context.parsed;
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ' ' + context.label + ': ' + val + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush

