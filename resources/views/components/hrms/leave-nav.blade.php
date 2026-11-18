@props([
    'active' => 'requests', // requests | balances | types
])

@php
    $tabs = [
        'requests' => [
            'label' => 'Leave Requests',
            'route' => route('hrms.leave-requests.index'),
            'match' => request()->routeIs('hrms.leave-requests.*'),
        ],
        'balances' => [
            'label' => 'Leave Balances',
            'route' => route('hrms.leave-balances.index'),
            'match' => request()->routeIs('hrms.leave-balances.*'),
        ],
        'types' => [
            'label' => 'Leave Types',
            'route' => route('hrms.leave-types.index'),
            'match' => request()->routeIs('hrms.leave-types.*'),
        ],
    ];

    $creates = [
        'requests' => ['label' => 'New Request', 'href' => route('hrms.leave-requests.create'), 'title' => 'Create leave request'],
        'balances' => ['label' => 'Allocate', 'href' => route('hrms.leave-balances.create'), 'title' => 'Allocate / adjust balance'],
        'types' => ['label' => 'Add Type', 'href' => route('hrms.leave-types.create'), 'title' => 'Create leave type'],
    ];

    $current = collect($tabs)->search(fn ($tab, $key) => $active === $key || ($tab['match'] ?? false));
    if ($current === false) {
        $current = 'requests';
    }
    $createBtn = $creates[$current] ?? $creates['requests'];
@endphp

<div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex w-full items-center justify-end border-b border-slate-100 bg-slate-50/80 px-4 py-3">
        <a href="{{ $createBtn['href'] }}"
           title="{{ $createBtn['title'] }}"
           class="inline-flex items-center gap-1.5 rounded-full border-2 border-indigo-600 bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-500">
            <span class="flex h-5 w-5 items-center justify-center rounded-full border border-white/80 text-[11px] leading-none">+</span>
            {{ $createBtn['label'] }}
        </a>
    </div>

    <nav class="flex flex-wrap gap-1 px-2 py-2 sm:px-3" aria-label="Leave management">
        @foreach($tabs as $key => $tab)
            @php $isActive = $key === $current; @endphp
            <a href="{{ $tab['route'] }}"
               class="rounded-full border px-4 py-2 text-sm font-medium transition
                      {{ $isActive
                          ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm'
                          : 'border-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-900' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
