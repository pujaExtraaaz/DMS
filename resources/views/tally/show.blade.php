@extends('layouts.dms')

@section('title', 'Tally Queue #'.$item->id)

@section('content')

<x-ui.page-header :title="'Tally Queue #'.$item->id">
    <x-slot name="actions">
        <div class="flex items-center gap-2">
            @if(in_array($item->status, ['failed', 'pending', 'skipped'], true))
                <form method="POST" action="{{ route('tally.queue.retry', $item) }}">
                    @csrf
                    <x-ui.button type="submit" variant="primary">
                        Retry Sync
                    </x-ui.button>
                </form>
            @endif

            <x-ui.button
                variant="secondary"
                :href="route('tally.queue.index')"
            >
                Back
            </x-ui.button>
        </div>
    </x-slot>
</x-ui.page-header>

{{-- Sync Summary --}}
<x-ui.card class="mb-4">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-base font-semibold text-slate-900">
                Sync Status
            </h3>
            <p class="text-sm text-slate-500">
                Current Tally synchronization state
            </p>
        </div>

        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold
            {{ $item->status === 'sent'
                ? 'bg-emerald-100 text-emerald-700'
                : ($item->status === 'failed'
                    ? 'bg-red-100 text-red-700'
                    : 'bg-amber-100 text-amber-700') }}">
            {{ ucfirst($item->status) }}
        </span>
    </div>

    <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">

        <div>
            <dt class="text-slate-500">Queue ID</dt>
            <dd class="mt-1 font-medium text-slate-900">
                #{{ $item->id }}
            </dd>
        </div>

        <div>
            <dt class="text-slate-500">Document</dt>
            <dd class="mt-1 font-medium text-slate-900">
                {{ $item->document_type }}
                #{{ $item->document_id }}
            </dd>
        </div>

        <div>
            <dt class="text-slate-500">Attempts</dt>
            <dd class="mt-1 font-medium text-slate-900">
                {{ $item->attempts }}
            </dd>
        </div>

        <div>
            <dt class="text-slate-500">Created</dt>
            <dd class="mt-1 text-slate-700">
                {{ optional($item->created_at)->format('d M Y, h:i A') }}
            </dd>
        </div>

        <div>
            <dt class="text-slate-500">Last Updated</dt>
            <dd class="mt-1 text-slate-700">
                {{ optional($item->updated_at)->format('d M Y, h:i A') }}
            </dd>
        </div>

        <div>
            <dt class="text-slate-500">Sent At</dt>
            <dd class="mt-1 text-slate-700">
                {{ optional($item->sent_at)->format('d M Y, h:i A') ?: '—' }}
            </dd>
        </div>

    </dl>
</x-ui.card>

{{-- Error --}}
@if($item->last_error)
    <x-ui.card class="mb-4">
        <h3 class="text-base font-semibold text-red-700 mb-2">
            Last Error
        </h3>

        <div class="rounded-lg bg-red-50 border border-red-200 p-4">
            <pre class="text-sm text-red-800 whitespace-pre-wrap break-words">{{ $item->last_error }}</pre>
        </div>
    </x-ui.card>
@endif

{{-- Tally Response --}}
@if($item->last_response)
    <x-ui.card class="mb-4">
        <details>
            <summary class="cursor-pointer text-base font-semibold text-slate-900">
                Tally Response
            </summary>

            <div class="mt-4">
                <pre class="bg-slate-900 text-slate-100 text-xs p-4 rounded-lg overflow-x-auto whitespace-pre-wrap">{{ $item->last_response }}</pre>
            </div>
        </details>
    </x-ui.card>
@endif

{{-- Tally Request --}}
<x-ui.card>
    <details>
        <summary class="cursor-pointer text-base font-semibold text-slate-900">
            Tally Request XML
        </summary>

        <div class="mt-4">
            <pre class="bg-slate-900 text-slate-100 text-xs p-4 rounded-lg overflow-x-auto whitespace-pre-wrap">{{ $item->payload }}</pre>
        </div>
    </details>
</x-ui.card>

@endsection