@props([
    'active' => 'runs',
])

@php
    $tabs = [
        'runs' => [
            'label' => 'Payroll Runs',
            'route' => route('hrms.payroll-runs.index'),
            'match' => request()->routeIs('hrms.payroll-runs.*'),
        ],
        'structures' => [
            'label' => 'Salary Structures',
            'route' => route('hrms.salary-structures.index'),
            'match' => request()->routeIs('hrms.salary-structures.*'),
        ],
        'heads' => [
            'label' => 'Pay Heads',
            'route' => route('hrms.pay-heads.index'),
            'match' => request()->routeIs('hrms.pay-heads.*'),
        ],
        'compoff' => [
            'label' => 'Comp-off',
            'route' => route('hrms.comp-off.index'),
            'match' => request()->routeIs('hrms.comp-off.*'),
        ],
    ];

    $creates = [
        'runs' => ['label' => 'New Run', 'href' => route('hrms.payroll-runs.create'), 'title' => 'Create payroll run'],
        'structures' => ['label' => 'New Structure', 'href' => route('hrms.salary-structures.create'), 'title' => 'Create salary structure'],
        'heads' => ['label' => 'Add Pay Head', 'href' => route('hrms.pay-heads.create'), 'title' => 'Create pay head'],
        'compoff' => ['label' => 'Credit Comp-off', 'href' => route('hrms.comp-off.index'), 'title' => 'Credit compensatory off'],
    ];

    $current = collect($tabs)->search(fn ($tab, $key) => $active === $key || ($tab['match'] ?? false));
    if ($current === false) {
        $current = 'runs';
    }
    $createBtn = $creates[$current] ?? $creates['runs'];
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

    <nav class="flex flex-wrap gap-1 px-2 py-2 sm:px-3" aria-label="Payroll">
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
