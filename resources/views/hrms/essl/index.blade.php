@extends('layouts.dms')
@section('title', 'eSSL Sync')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-xl font-bold">eSSL Biometric Sync</h1>
        <p class="text-sm text-slate-500">Status: {{ $enabled ? 'Enabled' : 'Disabled (set ESSL_ENABLED=true)' }}</p>
    </div>
    <form method="POST" action="{{ route('hrms.essl.pull') }}">@csrf
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Pull now</button>
    </form>
</div>
<div class="grid gap-6 lg:grid-cols-2">
<x-ui.card title="Sync logs">
<ul class="divide-y text-sm">@forelse($logs as $log)<li class="py-2"><span class="font-medium">{{ $log->status }}</span> · {{ $log->started_at }} · fetched {{ $log->fetched }} / imported {{ $log->imported }} / unmatched {{ $log->unmatched }}<div class="text-xs text-slate-500">{{ $log->message }}</div></li>@empty<li class="py-6 text-center text-slate-500">No syncs yet.</li>@endforelse</ul>
</x-ui.card>
<x-ui.card title="Recent punches">
<ul class="divide-y text-sm">@forelse($punches as $p)<li class="py-2">{{ $p->punched_at }} · {{ $p->employee?->name ?? $p->biometric_code }} · {{ $p->source }}</li>@empty<li class="py-6 text-center text-slate-500">No punches.</li>@endforelse</ul>
</x-ui.card>
</div>
<p class="mt-4 text-xs text-slate-500">Office agent can POST punches to <code>/api/essl/punches</code> with Bearer token (ESSL_TOKEN).</p>
@endsection
