@extends('layouts.dms')
@section('title', 'Service Reports')
@section('content')
@php
    $filters = $filters ?? [];
    $from = $from ?? ($filters['date_from'] ?? now()->startOfMonth()->toDateString());
    $to = $to ?? ($filters['date_to'] ?? now()->toDateString());
    $kpis = $kpis ?? ['total' => 0, 'open' => 0, 'overdue' => 0, 'closed' => 0, 'avg_rating' => null, 'charges' => 0, 'paid' => 0, 'outstanding' => 0];

    $drill = function (array $extra = []) use ($filters, $from, $to) {
        return route('service.tickets.index', array_filter(array_merge([
            'date_from' => $filters['date_from'] ?? $from,
            'date_to' => $filters['date_to'] ?? $to,
            'request_type' => $filters['request_type'] ?? null,
            'status' => $filters['status'] ?? null,
            'priority' => $filters['priority'] ?? null,
            'party_type' => $filters['party_type'] ?? null,
            'employee_id' => $filters['employee_id'] ?? null,
            'oem_centre_id' => $filters['oem_centre_id'] ?? null,
            'brand' => $filters['brand'] ?? null,
            'model_number' => $filters['model_number'] ?? null,
            'warranty_status' => $filters['warranty_status'] ?? null,
            'handler_type' => $filters['handler_type'] ?? null,
        ], $extra), fn ($v) => $v !== null && $v !== ''));
    };

    $maxOf = function ($rows) {
        $m = (int) collect($rows)->max('c');

        return max(1, $m);
    };

    $sumOf = fn ($rows) => max(1, (int) collect($rows)->sum('c'));

    $warrantyIn = (int) collect($warrantyVsOut ?? [])->firstWhere('warranty_status', 'in_warranty')?->c;
    $warrantyOut = (int) collect($warrantyVsOut ?? [])->firstWhere('warranty_status', 'out_of_warranty')?->c;
    $warrantyUnk = (int) collect($warrantyVsOut ?? [])->reject(fn ($r) => in_array($r->warranty_status, ['in_warranty', 'out_of_warranty'], true))->sum('c');
    $warrantyTotal = max(1, $warrantyIn + $warrantyOut + $warrantyUnk);
    $wInPct = round(($warrantyIn / $warrantyTotal) * 100);
    $wOutPct = round(($warrantyOut / $warrantyTotal) * 100);

    $statusOrder = ['new', 'assigned', 'visit', 'pending', 'completed', 'confirmed', 'closed', 'cancelled'];
    $statusMap = collect($statusWise ?? [])->keyBy('status');
@endphp

<div class="space-y-6" x-data="{ showFilters: true }">
    <x-ui.page-header title="Service Reports & Analytics">
        <x-slot name="actions">
            <button type="button" @click="showFilters = !showFilters"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                <span x-text="showFilters ? 'Hide filters' : 'Show filters'"></span>
            </button>
            <x-ui.button variant="secondary" :href="route('service.tickets.index', ['date_from' => $from, 'date_to' => $to])">Open register</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    {{-- 1. Filters --}}
    <x-ui.card x-show="showFilters" x-cloak>
        <div class="mb-3 flex items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">1 · Period & filters</h2>
                <p class="text-xs text-slate-500">All cards below follow these filters. Click any card or bar to drill into Ticket Register.</p>
            </div>
            <p class="text-xs font-medium text-slate-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} → {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
        </div>
        @include('service.partials.filter-bar', [
            'action' => route('service.reports'),
            'resetUrl' => route('service.reports'),
            'filters' => array_merge($filters, ['date_from' => $from, 'date_to' => $to]),
            'showDates' => true,
            'showSearch' => true,
            'showFlags' => false,
        ])
    </x-ui.card>

    {{-- 2. Snapshot --}}
    <section class="space-y-3">
        <div class="flex items-end justify-between gap-2">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">2 · Period snapshot</h2>
                <p class="text-xs text-slate-500">Volume, SLA risk, closures, and money for the selected window.</p>
            </div>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <a href="{{ $drill() }}" class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total tickets</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ number_format($kpis['total']) }}</p>
                <p class="mt-2 text-xs text-indigo-600 opacity-0 group-hover:opacity-100 transition">View register →</p>
            </a>
            <a href="{{ $drill(['pending_only' => 1]) }}" class="group relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-indigo-600/80">Open</p>
                <p class="mt-2 text-3xl font-semibold text-indigo-700">{{ number_format($kpis['open']) }}</p>
                <p class="mt-2 text-xs text-indigo-600 opacity-0 group-hover:opacity-100 transition">Open only →</p>
            </a>
            <a href="{{ $drill(['overdue' => 1, 'pending_only' => 1]) }}" class="group relative overflow-hidden rounded-2xl border border-red-100 bg-gradient-to-br from-red-50 to-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-red-300 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-red-600/80">Overdue SLA</p>
                <p class="mt-2 text-3xl font-semibold text-red-600">{{ number_format($kpis['overdue']) }}</p>
                <p class="mt-2 text-xs text-red-600 opacity-0 group-hover:opacity-100 transition">Overdue list →</p>
            </a>
            <a href="{{ $drill(['status' => 'closed']) }}" class="group relative overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700/80">Closed</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-700">{{ number_format($kpis['closed']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Avg rating {{ $kpis['avg_rating'] ?? '—' }}</p>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Chargeable (period)</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">₹{{ number_format($kpis['charges'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Collected</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700">₹{{ number_format($kpis['paid'], 2) }}</p>
            </div>
            <a href="{{ route('service.quotations', array_filter(['date_from' => $from, 'date_to' => $to, 'payment_status' => 'pending'])) }}"
               class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 shadow-sm transition hover:border-amber-400 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-800/80">Outstanding</p>
                <p class="mt-2 text-2xl font-semibold text-amber-800">₹{{ number_format($kpis['outstanding'], 2) }}</p>
                <p class="mt-1 text-xs text-amber-700">Open collection →</p>
            </a>
        </div>
    </section>

    {{-- 3. Pipeline --}}
    <section class="space-y-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">3 · Status pipeline</h2>
            <p class="text-xs text-slate-500">Ticket flow from New through Closed. Click a stage to filter the register.</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-2">
            @foreach($statusOrder as $st)
                @php $count = (int) ($statusMap[$st]->c ?? 0); @endphp
                <a href="{{ $drill(['status' => $st]) }}"
                   class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-400 hover:shadow-md {{ $count === 0 ? 'opacity-60' : '' }}">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $st }}</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900">{{ $count }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- 4. Mix --}}
    <section class="space-y-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">4 · Work mix</h2>
            <p class="text-xs text-slate-500">Type, priority, party, and warranty split for the period.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            {{-- Type --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-800">Request type</h3>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($typeWise ?? [] as $row)
                        @php $pct = round(($row->c / $maxOf($typeWise)) * 100); @endphp
                        <a href="{{ $drill(['request_type' => $row->request_type]) }}" class="block group">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="capitalize text-slate-700 group-hover:text-indigo-700">{{ $row->request_type }}</span>
                                <span class="font-semibold">{{ $row->c }}</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">No data</p>
                    @endforelse
                </div>
            </div>

            {{-- Priority --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-800">Priority</h3>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($priorityWise ?? [] as $row)
                        @php
                            $pct = round(($row->c / $maxOf($priorityWise)) * 100);
                            $bar = match ($row->priority) {
                                'critical' => 'bg-red-500',
                                'high' => 'bg-amber-500',
                                'low' => 'bg-slate-400',
                                default => 'bg-indigo-400',
                            };
                        @endphp
                        <a href="{{ $drill(['priority' => $row->priority]) }}" class="block group">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="capitalize text-slate-700 group-hover:text-indigo-700">{{ $row->priority }}</span>
                                <span class="font-semibold">{{ $row->c }}</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">No data</p>
                    @endforelse
                </div>
            </div>

            {{-- Party --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-800">Party type</h3>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($partyWise ?? [] as $row)
                        @php $pct = round(($row->c / $maxOf($partyWise)) * 100); @endphp
                        <a href="{{ $drill(['party_type' => $row->party_type]) }}" class="block group">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="capitalize text-slate-700 group-hover:text-indigo-700">{{ str_replace('_', ' ', $row->party_type) }}</span>
                                <span class="font-semibold">{{ $row->c }}</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-violet-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">No data</p>
                    @endforelse
                </div>
            </div>

            {{-- Warranty visual --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-800">Warranty split</h3>
                </div>
                <div class="p-4 flex items-center gap-4">
                    <div class="relative h-24 w-24 shrink-0 rounded-full"
                         style="background: conic-gradient(#10b981 0 {{ $wInPct }}%, #f43f5e {{ $wInPct }}% {{ $wInPct + $wOutPct }}%, #94a3b8 {{ $wInPct + $wOutPct }}% 100%);">
                        <div class="absolute inset-3 rounded-full bg-white flex items-center justify-center">
                            <span class="text-xs font-semibold text-slate-700">{{ $warrantyIn + $warrantyOut + $warrantyUnk }}</span>
                        </div>
                    </div>
                    <div class="space-y-2 flex-1 min-w-0">
                        <a href="{{ $drill(['warranty_status' => 'in_warranty']) }}" class="flex items-center justify-between text-sm hover:text-indigo-700">
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> In warranty</span>
                            <span class="font-semibold">{{ $warrantyIn }}</span>
                        </a>
                        <a href="{{ $drill(['warranty_status' => 'out_of_warranty']) }}" class="flex items-center justify-between text-sm hover:text-indigo-700">
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span> Out of warranty</span>
                            <span class="font-semibold">{{ $warrantyOut }}</span>
                        </a>
                        <a href="{{ $drill(['warranty_status' => 'unknown']) }}" class="flex items-center justify-between text-sm hover:text-indigo-700">
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span> Unknown</span>
                            <span class="font-semibold">{{ $warrantyUnk }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 5. Leaders --}}
    <section class="space-y-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">5 · Brand · model · technician</h2>
            <p class="text-xs text-slate-500">Top volumes in the period. Click a row to open matching tickets.</p>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            @foreach([
                ['title' => 'Brand-wise', 'rows' => $brandWise ?? [], 'key' => 'brand', 'param' => 'brand', 'color' => 'bg-sky-500'],
                ['title' => 'Model-wise', 'rows' => $modelWise ?? [], 'key' => 'model_number', 'param' => 'model_number', 'color' => 'bg-cyan-500'],
            ] as $board)
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-800">{{ $board['title'] }}</h3>
                        <span class="text-[11px] text-slate-400">Top {{ count($board['rows']) }}</span>
                    </div>
                    <div class="p-3 space-y-1 max-h-80 overflow-y-auto">
                        @forelse($board['rows'] as $i => $row)
                            @php
                                $label = $row->{$board['key']} ?: '—';
                                $pct = round(($row->c / $maxOf($board['rows'])) * 100);
                            @endphp
                            <a href="{{ $drill([$board['param'] => $row->{$board['key']}]) }}"
                               class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-slate-50 transition">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-semibold text-slate-600">{{ $i + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex justify-between gap-2 text-sm">
                                        <span class="truncate text-slate-800">{{ $label }}</span>
                                        <span class="font-semibold shrink-0">{{ $row->c }}</span>
                                    </div>
                                    <div class="mt-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full {{ $board['color'] }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500 px-2 py-4">No data</p>
                        @endforelse
                    </div>
                </div>
            @endforeach

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-800">Technician-wise</h3>
                    <span class="text-[11px] text-slate-400">Assigned load</span>
                </div>
                <div class="p-3 space-y-1 max-h-80 overflow-y-auto">
                    @forelse($techWise ?? [] as $i => $row)
                        @php $pct = round(($row->c / $maxOf($techWise)) * 100); @endphp
                        <a href="{{ $drill(['employee_id' => $row->assigned_employee_id]) }}"
                           class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-slate-50 transition">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-xs font-semibold text-indigo-700">{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex justify-between gap-2 text-sm">
                                    <span class="truncate text-slate-800">{{ $row->assignedEmployee?->name ?? '#'.$row->assigned_employee_id }}</span>
                                    <span class="font-semibold shrink-0">{{ $row->c }}</span>
                                </div>
                                <div class="mt-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-indigo-600" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500 px-2 py-4">No assignments in period</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    {{-- 6. Follow-ups --}}
    <section class="space-y-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">6 · Follow-ups</h2>
            <p class="text-xs text-slate-500">Money still due, installation mix, and repeat serials needing attention.</p>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden lg:col-span-1">
                <div class="border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-800">Installation types</h3>
                </div>
                <div class="p-4 space-y-2">
                    @forelse($installTypes ?? [] as $row)
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 text-sm">
                            <span class="capitalize text-slate-700">{{ str_replace('_', ' ', $row->installation_type) }}</span>
                            <span class="font-semibold">{{ $row->c }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No installation tickets in period</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden lg:col-span-1">
                <div class="border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-800">Pending payments</h3>
                    <a href="{{ route('service.quotations', ['payment_status' => 'pending']) }}" class="text-[11px] font-medium text-indigo-600 hover:underline">Collection</a>
                </div>
                <div class="divide-y divide-slate-50 max-h-80 overflow-y-auto">
                    @forelse($pendingPayments ?? [] as $pay)
                        <a href="{{ route('service.tickets.show', $pay->ticket) }}" class="flex items-center justify-between px-4 py-3 text-sm hover:bg-amber-50/50 transition">
                            <div class="min-w-0">
                                <p class="font-medium text-indigo-700 truncate">{{ $pay->ticket?->ticket_no }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ $pay->ticket?->customer_name }}</p>
                            </div>
                            <span class="shrink-0 font-semibold text-amber-700">₹{{ number_format((float) $pay->grand_total - (float) $pay->paid_amount, 2) }}</span>
                        </a>
                    @empty
                        <p class="px-4 py-6 text-sm text-slate-500">None outstanding</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden lg:col-span-1">
                <div class="border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-800">Repeat serials</h3>
                    <span class="text-[11px] text-slate-400">&gt; 1 ticket</span>
                </div>
                <div class="p-3 grid gap-2 max-h-80 overflow-y-auto">
                    @forelse($repeatSerials ?? [] as $row)
                        <a href="{{ route('service.serial-history', ['serial_no' => $row->serial_no]) }}"
                           class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-2.5 text-sm hover:border-indigo-300 hover:bg-indigo-50/40 transition">
                            <span class="font-medium text-indigo-700 truncate pr-2">{{ $row->serial_no }}</span>
                            <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">{{ $row->c }}</span>
                        </a>
                    @empty
                        <p class="px-1 py-6 text-sm text-slate-500">No repeats in period</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
