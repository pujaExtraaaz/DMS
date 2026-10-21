@extends('layouts.dms')
@section('title', 'Guide')
@section('content')
@php
    $tones = [
        1 => '#4f46e5',
        2 => '#7c3aed',
        3 => '#0284c7',
        4 => '#0f766e',
        5 => '#059669',
        6 => '#d97706',
        7 => '#e11d48',
        8 => '#ea580c',
        9 => '#c026d3',
        10 => '#0f172a',
    ];
@endphp

<style>
    .gd { --gd-ink: #0f172a; --gd-muted: #64748b; color: var(--gd-ink); }
    .gd-hero {
        background:
            radial-gradient(1200px 220px at 100% 0%, rgba(99, 102, 241, .35), transparent 55%),
            linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        border-radius: 18px;
        color: #fff;
        padding: 28px 28px 22px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, .12);
    }
    .gd-kicker {
        margin: 0;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .22em;
        text-transform: uppercase;
        color: #a5b4fc;
    }
    .gd-hero h2 { margin: 8px 0 0; font-size: 28px; line-height: 1.2; font-weight: 650; letter-spacing: -.02em; }
    .gd-lead, .gd-purpose, .gd-step p {
        margin: 0;
        text-align: justify;
        text-justify: inter-word;
        hyphens: auto;
        line-height: 1.75;
    }
    .gd-lead { margin-top: 12px; max-width: 78ch; color: #e2e8f0; font-size: 14.5px; }
    .gd-nav-grid { display: grid; gap: 8px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .gd-nav-grid a {
        display: flex; align-items: center; gap: 8px; min-height: 44px;
        border-radius: 12px; text-decoration: none; color: inherit;
    }
    .gd-nav {
        position: sticky; top: 0; z-index: 20;
        background: rgba(255,255,255,.94);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 8px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
        backdrop-filter: blur(8px);
    }
    .gd-nav-grid a {
        flex-direction: column; justify-content: center; gap: 1px;
        background: #f8fafc; color: #334155;
        padding: 6px 4px; text-align: center;
    }
    .gd-nav-grid a small { font-size: 10px; font-weight: 700; letter-spacing: .08em; color: var(--gd); }
    .gd-nav-grid a span { font-size: 11px; font-weight: 650; line-height: 1.25; }
    .gd-nav-grid a.is-on { background: var(--gd); color: #fff; box-shadow: 0 6px 14px rgba(15, 23, 42, .12); }
    .gd-nav-grid a.is-on small { color: rgba(255,255,255,.8); }
    .gd-stack { display: flex; flex-direction: column; gap: 16px; }
    .gd-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        scroll-margin-top: 92px;
    }
    .gd-card-head {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 14px;
        align-items: center;
        padding: 18px 22px;
        background: linear-gradient(90deg, color-mix(in srgb, var(--gd) 12%, white), #fff 42%);
        border-bottom: 1px solid #f1f5f9;
        border-left: 5px solid var(--gd);
    }
    .gd-badge {
        width: 52px; height: 52px; border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        background: var(--gd); color: #fff; font-size: 18px; font-weight: 750;
        box-shadow: 0 8px 16px color-mix(in srgb, var(--gd) 35%, transparent);
    }
    .gd-card-head p { margin: 0; font-size: 11px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--gd); }
    .gd-card-head h3 { margin: 2px 0 0; font-size: 20px; line-height: 1.2; font-weight: 700; }
    .gd-count {
        font-size: 12px; font-weight: 650; color: #475569;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 999px;
        padding: 6px 10px; white-space: nowrap;
    }
    .gd-body { padding: 18px 22px 20px; }
    .gd-purpose { font-size: 14.5px; color: #334155; }
    .gd-label {
        margin: 18px 0 0;
        font-size: 11px; font-weight: 700; letter-spacing: .16em;
        text-transform: uppercase; color: #94a3b8;
    }
    .gd-steps { list-style: none; margin: 14px 0 0; padding: 0; }
    .gd-step { display: grid; grid-template-columns: 32px minmax(0, 1fr); gap: 12px; }
    .gd-rail { display: flex; flex-direction: column; align-items: center; }
    .gd-dot {
        width: 32px; height: 32px; border-radius: 999px;
        display: flex; align-items: center; justify-content: center;
        background: var(--gd); color: #fff; font-size: 13px; font-weight: 700; flex: none;
    }
    .gd-line { width: 2px; flex: 1; min-height: 12px; margin: 4px 0; background: color-mix(in srgb, var(--gd) 28%, white); }
    .gd-step p { padding-bottom: 14px; font-size: 14.5px; color: #1e293b; }
    .gd-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; padding: 0; list-style: none; }
    .gd-chips li {
        border-radius: 999px;
        padding: 5px 12px;
        font-size: 12px; font-weight: 650;
        color: var(--gd);
        background: color-mix(in srgb, var(--gd) 10%, white);
        border: 1px solid color-mix(in srgb, var(--gd) 22%, white);
    }
    @media (min-width: 720px) {
        .gd-nav-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    }
    @media (min-width: 1180px) {
        .gd-nav-grid { grid-template-columns: repeat(10, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .gd-hero, .gd-body { padding-left: 16px; padding-right: 16px; }
        .gd-card-head { grid-template-columns: auto 1fr; padding: 16px; }
        .gd-count { grid-column: 2; justify-self: start; }
        .gd-hero h2 { font-size: 22px; }
    }
</style>

<div class="gd" id="guide-root" x-data="{ active: 1 }">
    <div class="gd-stack">
        <section class="gd-hero">
            <p class="gd-kicker">Section 12</p>
            <h2>How the whole project runs</h2>
            <p class="gd-lead">Work moves in one direction. Set up the company, build masters, purchase into stock, sell, deliver, collect, then read the same activity in Accounting Books. Reports only show what those steps already posted. A posted invoice or stock document is cancelled or reversed. It is never deleted.</p>
        </section>

        <nav class="gd-nav" aria-label="Guide modules">
            <div class="gd-nav-grid">
                @foreach ($modules as $module)
                    <a href="#module-{{ $module['number'] }}"
                       style="--gd: {{ $tones[$module['number']] }}"
                       @click="active = {{ $module['number'] }}"
                       :class="active === {{ $module['number'] }} ? 'is-on' : ''">
                        <small>{{ str_pad((string) $module['number'], 2, '0', STR_PAD_LEFT) }}</small>
                        <span>{{ $module['title'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>

        @foreach ($modules as $module)
            <section class="gd-card" id="module-{{ $module['number'] }}" data-guide-module="{{ $module['number'] }}" style="--gd: {{ $tones[$module['number']] }}">
                <header class="gd-card-head">
                    <span class="gd-badge">{{ str_pad((string) $module['number'], 2, '0', STR_PAD_LEFT) }}</span>
                    <div>
                        <p>Module {{ $module['number'] }}</p>
                        <h3>{{ $module['title'] }}</h3>
                    </div>
                    <span class="gd-count">{{ count($module['screens']) }} screens</span>
                </header>
                <div class="gd-body">
                    <p class="gd-purpose">{{ $module['purpose'] }}</p>
                    <p class="gd-label">Workflow</p>
                    <ol class="gd-steps">
                        @foreach ($module['flow'] as $index => $step)
                            <li class="gd-step">
                                <div class="gd-rail">
                                    <span class="gd-dot">{{ $index + 1 }}</span>
                                    @if (! $loop->last)
                                        <span class="gd-line"></span>
                                    @endif
                                </div>
                                <p>{{ $step }}</p>
                            </li>
                        @endforeach
                    </ol>
                    <p class="gd-label">Open these screens</p>
                    <ul class="gd-chips">
                        @foreach ($module['screens'] as $screen)
                            <li>{{ $screen }}</li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
    function bindGuideNav() {
        if (window.__guideNavBound) return;
        const root = document.getElementById('guide-root');
        const sections = document.querySelectorAll('[data-guide-module]');
        if (!root || !sections.length || !window.Alpine || !('IntersectionObserver' in window)) return;
        const scroller = document.querySelector('main.dms-content');
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                Alpine.$data(root).active = Number(entry.target.getAttribute('data-guide-module'));
            });
        }, { root: scroller, rootMargin: '-20% 0px -60% 0px', threshold: 0.15 });
        sections.forEach(function (section) { observer.observe(section); });
        window.__guideNavBound = true;
    }
    document.addEventListener('alpine:initialized', bindGuideNav);
    if (window.Alpine) bindGuideNav();
</script>
@endpush
