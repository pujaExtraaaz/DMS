@extends('layouts.dms')
@section('title', 'Targets')
@section('content')
<div id="listing-container" data-dynamic-container>
    {{-- Page Header with New Targets button --}}
    <x-ui.page-header title="Targets & Achievements">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('targets.periods.create')">+ New Targets</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    {{-- 1. Two Summary Cards Above the Search and Targets Table --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {{-- Card 1: Total Number of Targets --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Total Number of Targets
                    </p>
                    <p class="text-3xl font-bold text-slate-900 mt-2">
                        {{ number_format($summaryTotalTargets) }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ number_format($summaryTotalTargets) }} {{ Str::plural('target record', $summaryTotalTargets) }} saved
                        @if($summaryAssignedCount > 0)
                            · {{ number_format($summaryAssignedCount) }} assigned
                        @endif
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/25">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Card 2: Total Target Amount --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Total Target Amount
                    </p>
                    <p class="text-3xl font-bold text-emerald-700 mt-2">
                        {{ \Tally\Support\IndianCurrency::format($summaryTotalAmount) }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        Sum of assigned target amounts from database
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/25">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Search and Selection Option --}}
    <div
        class="mb-5 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm"
        x-data="{
            open: false,
            query: '{{ request('target_id') && $selectedTarget ? addslashes($selectedTarget->name) : '' }}',
            selectedId: '{{ request('target_id', '') }}',
            targets: {{ Js::from($allTargetsList->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'period_type' => ucfirst($t->period_type),
                'category' => $t->target_category_label ?? 'General',
                'entity' => $t->target_entity_name,
                'dates' => $t->starts_on->format('d M Y') . ' – ' . $t->ends_on->format('d M Y'),
                'targets_count' => $t->targets_count,
                'amount' => \Tally\Support\IndianCurrency::format($t->total_target_amount ?? 0),
                'status' => ucfirst($t->status),
            ])) }},
            get matches() {
                if (!this.query.trim()) return this.targets.slice(0, 8);
                const q = this.query.toLowerCase();
                return this.targets.filter(t => 
                    t.name.toLowerCase().includes(q) ||
                    (t.entity && t.entity.toLowerCase().includes(q)) ||
                    (t.category && t.category.toLowerCase().includes(q))
                ).slice(0, 10);
            },
            select(id, name) {
                this.selectedId = id;
                this.query = name;
                this.open = false;
                const url = new URL(window.location.href);
                url.searchParams.set('target_id', id);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            },
            clear() {
                this.selectedId = '';
                this.query = '';
                this.open = false;
                const url = new URL(window.location.href);
                url.searchParams.delete('target_id');
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }
        }"
        @click.outside="open = false"
    >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex-1 max-w-xl">
                <label class="block text-xs font-semibold tracking-wider text-slate-500 mb-1.5">
                    Target Search & Selection
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        x-model="query"
                        @focus="open = true"
                        @input="open = true"
                        placeholder="Search & Select Target By Name, Brand, Product, Or Supplier..."
                        class="block w-full rounded-xl border-slate-300 pl-9 pr-20 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition"
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                        <button
                            type="button"
                            x-show="query.length > 0 || selectedId"
                            x-cloak
                            @click="clear()"
                            class="p-1 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 transition"
                            title="Clear selection"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="open = !open"
                            class="p-1 text-slate-400 hover:text-slate-600 rounded transition"
                            title="Toggle target list"
                        >
                            <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>

                    {{-- Dynamic Suggestions Dropdown --}}
                    <div
                        x-show="open"
                        x-cloak
                        x-transition
                        class="absolute z-30 mt-1.5 w-full bg-white rounded-xl shadow-xl border border-slate-200 max-h-72 overflow-y-auto divide-y divide-slate-100"
                    >
                        <template x-for="t in matches" :key="t.id">
                            <button
                                type="button"
                                @click="select(t.id, t.name)"
                                class="w-full text-left px-3.5 py-2.5 text-xs hover:bg-indigo-50/70 transition flex items-center justify-between gap-3"
                                :class="{ 'bg-indigo-50 text-indigo-900 font-semibold': t.id == selectedId }"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-slate-900 truncate" x-text="t.name"></div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-medium" x-text="t.period_type"></span>
                                        <span x-show="t.entity" class="text-slate-600" x-text="t.category + ': ' + t.entity"></span>
                                        <span class="text-slate-400" x-text="t.dates"></span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-semibold text-slate-900" x-text="t.amount"></div>
                                    <div class="text-[10px] text-slate-500" x-text="t.targets_count + ' targets'"></div>
                                </div>
                            </button>
                        </template>
                        <div x-show="matches.length === 0" class="p-3 text-center text-xs text-slate-500">
                            No matching targets found
                        </div>
                    </div>
                </div>
            </div>

            @if(request('target_id') || request('search') || request('status') || request('period_type'))
                <div class="self-end pb-0.5">
                    <x-ui.button size="sm" variant="secondary" :href="route('targets.index')" class="text-xs">
                        Reset All Filters
                    </x-ui.button>
                </div>
            @endif
        </div>
    </div>

    {{-- Selected Target Details Panel --}}
    @if($selectedTarget)
        <div class="mb-5 rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50/70 via-white to-blue-50/40 p-5 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-4 border-b border-indigo-100">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold uppercase tracking-wider bg-indigo-100 text-indigo-700">Selected Target</span>
                        <h3 class="text-lg font-bold text-slate-900">{{ $selectedTarget->name }}</h3>
                        <x-ui.badge :variant="$selectedTarget->status === 'open' ? 'emerald' : 'gray'">
                            {{ ucfirst($selectedTarget->status) }}
                        </x-ui.badge>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Viewing saved parameters, targets assigned, and real-time achievements for this specific target.
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <x-ui.button size="sm" variant="secondary" :href="route('targets.index')" class="text-xs">
                        ✕ Clear Selection
                    </x-ui.button>
                    <x-ui.button size="sm" variant="primary" :href="route('targets.periods.show', $selectedTarget)" class="text-xs">
                        View Details & Assign →
                    </x-ui.button>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 pt-4">
                <div class="bg-white rounded-xl p-3 border border-slate-200 shadow-2xs">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Target Type</div>
                    <div class="text-sm font-bold text-slate-900 mt-0.5">{{ ucfirst($selectedTarget->period_type) }}</div>
                </div>

                <div class="bg-white rounded-xl p-3 border border-slate-200 shadow-2xs">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Target Category</div>
                    <div class="text-sm font-bold text-slate-900 mt-0.5">{{ $selectedTarget->target_category_label ?? 'General' }}</div>
                </div>

                <div class="bg-white rounded-xl p-3 border border-slate-200 shadow-2xs">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Selected Entity</div>
                    <div class="text-sm font-bold text-slate-900 mt-0.5 truncate" title="{{ $selectedTarget->target_entity_name ?? '—' }}">
                        {{ $selectedTarget->target_entity_name ?? '—' }}
                    </div>
                </div>

                <div class="bg-white rounded-xl p-3 border border-slate-200 shadow-2xs">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Date Range</div>
                    <div class="text-xs font-semibold text-slate-800 mt-1">
                        {{ $selectedTarget->starts_on->format('d M Y') }} – {{ $selectedTarget->ends_on->format('d M Y') }}
                    </div>
                </div>

                <div class="bg-white rounded-xl p-3 border border-slate-200 shadow-2xs">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Targets</div>
                    <div class="text-sm font-bold text-indigo-700 mt-0.5">
                        {{ number_format($selectedTarget->targets_count) }} Assigned
                    </div>
                </div>

                <div class="bg-white rounded-xl p-3 border border-slate-200 shadow-2xs">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Target Amount</div>
                    <div class="text-sm font-bold text-emerald-700 mt-0.5">
                        {{ \Tally\Support\IndianCurrency::format($selectedTarget->total_target_amount ?? 0) }}
                    </div>
                </div>
            </div>

            {{-- Progress & Achievement summary --}}
            @if($selectedTarget->total_target_amount > 0)
                <div class="mt-4 pt-3 border-t border-indigo-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-slate-600 font-medium">Achievement Progress:</span>
                        <span class="font-bold text-slate-900">{{ \Tally\Support\IndianCurrency::format($selectedTarget->total_achieved_amount) }}</span>
                        <span class="text-slate-400">/</span>
                        <span class="text-slate-600">{{ \Tally\Support\IndianCurrency::format($selectedTarget->total_target_amount) }}</span>
                        <span class="font-semibold px-2 py-0.5 rounded-full {{ $selectedTarget->overall_achievement_percent >= 100 ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ number_format($selectedTarget->overall_achievement_percent, 1) }}%
                        </span>
                    </div>
                    <div class="w-full sm:w-56 bg-slate-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-indigo-600 h-2.5 rounded-full transition-all" style="width: {{ min(100, $selectedTarget->overall_achievement_percent) }}%"></div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- 3. Target Listing Table Card with Toolbar Filters --}}
    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search Target Name, Category, Status..."
            :reset-url="route('targets.index')"
        >
            <x-slot name="filters">
                <select
                    name="period_type"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Type: All</option>
                    @foreach(['monthly', 'quarterly', 'annual'] as $pt)
                        <option value="{{ $pt }}" @selected(request('period_type') === $pt)>{{ ucfirst($pt) }}</option>
                    @endforeach
                </select>

                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Status: All</option>
                    @foreach(['open', 'closed'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Name</x-ui.sortable-th>
                        <x-ui.sortable-th column="period_type" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Type</x-ui.sortable-th>
                        <x-ui.sortable-th column="starts_on" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Range</x-ui.sortable-th>
                        <x-ui.sortable-th column="targets_count" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'" align="right">Total Number of Targets</x-ui.sortable-th>
                        <x-ui.sortable-th column="total_target_amount" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'" align="right">Total Target Amount</x-ui.sortable-th>
                        <x-ui.sortable-th column="status" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Status</x-ui.sortable-th>
                        <th class="px-3 py-2 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($periods as $period)
                        <tr class="hover:bg-slate-50 transition-colors {{ request('target_id') == $period->id ? 'bg-indigo-50/50' : '' }}">
                            <td class="px-3 py-2 font-medium text-slate-900">
                                <div>{{ $period->name }}</div>
                                @if($period->target_category_label && $period->target_entity_name)
                                    <div class="text-xs font-normal mt-1 flex flex-wrap gap-1">
                                        @if($period->target_category === 'brand')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-50 text-blue-700">Brand: {{ $period->target_entity_name }}</span>
                                        @elseif($period->target_category === 'product')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-purple-50 text-purple-700">Product: {{ $period->target_entity_name }}</span>
                                        @elseif($period->target_category === 'supplier')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-50 text-amber-700">Supplier: {{ $period->target_entity_name }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-600">{{ ucfirst($period->period_type) }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $period->starts_on->format('d M Y') }} – {{ $period->ends_on->format('d M Y') }}</td>
                            <td class="px-3 py-2 text-right font-medium text-slate-900">{{ number_format($period->targets_count) }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-slate-900">
                                {{ \Tally\Support\IndianCurrency::format($period->total_target_amount ?? 0) }}
                            </td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="$period->status === 'open' ? 'emerald' : 'gray'">
                                    {{ ucfirst($period->status) }}
                                </x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-ui.button size="sm" variant="secondary" :href="route('targets.index', array_merge(request()->query(), ['target_id' => $period->id]))">
                                        Select
                                    </x-ui.button>
                                    <x-ui.button size="sm" variant="primary" :href="route('targets.periods.show', $period)">
                                        Open
                                    </x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No targets found" description="Try adjusting your target search or filters." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($periods->count() > 0)
                    <tfoot class="bg-slate-50/80 font-semibold text-slate-900 border-t-2 border-slate-200">
                        <tr>
                            <td colspan="3" class="px-3 py-2.5 text-xs uppercase tracking-wider text-slate-600">
                                Filtered Total ({{ number_format($summaryTotalTargets) }} {{ Str::plural('Target', $summaryTotalTargets) }})
                            </td>
                            <td class="px-3 py-2.5 text-right font-bold text-slate-900">
                                {{ number_format($summaryAssignedCount) }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-bold text-emerald-700">
                                {{ \Tally\Support\IndianCurrency::format($summaryTotalAmount) }}
                            </td>
                            <td colspan="2" class="px-3 py-2.5"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="mt-4">{{ $periods->links() }}</div>
    </x-ui.card>
</div>
@endsection