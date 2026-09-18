@extends('layouts.dms')
@section('title', 'Bulk Import')
@section('content')
<x-ui.page-header title="Bulk Import" description="Upload Products, Parties or Price Master data. Download the template, fill it, upload — the system validates, updates existing rows by key, and shows a downloadable error report." />

@if(session('status'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
@endif

@if($summary)
    <x-ui.card class="mb-4" title="Last import result — {{ ucfirst(str_replace('-', ' ', $summary['type'])) }}">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3 text-sm">
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-3 py-2"><span class="text-xs uppercase text-slate-500">Created</span><div class="text-lg font-semibold text-emerald-700">{{ $summary['created'] }}</div></div>
            <div class="rounded-lg bg-indigo-50 border border-indigo-100 px-3 py-2"><span class="text-xs uppercase text-slate-500">Updated</span><div class="text-lg font-semibold text-indigo-700">{{ $summary['updated'] }}</div></div>
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-3 py-2"><span class="text-xs uppercase text-slate-500">Skipped</span><div class="text-lg font-semibold text-amber-700">{{ $summary['skipped'] }}</div></div>
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2"><span class="text-xs uppercase text-slate-500">Errors</span><div class="text-lg font-semibold text-red-700">{{ count($summary['errors']) }}</div></div>
        </div>
        @if(! empty($summary['errors']))
            @if($summary['errors_file'])
                <a href="{{ route('masters.bulk-import.errors', ['filename' => basename($summary['errors_file'])]) }}" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                    Download error CSV
                </a>
            @endif
            <div class="mt-3 overflow-x-auto max-h-60 border rounded-lg">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50 sticky top-0">
                        <tr>
                            <th class="px-3 py-1.5 text-left">Row</th>
                            <th class="px-3 py-1.5 text-left">Field</th>
                            <th class="px-3 py-1.5 text-left">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($summary['errors'] as $err)
                            <tr>
                                <td class="px-3 py-1.5">{{ $err['row'] }}</td>
                                <td class="px-3 py-1.5 font-mono text-slate-600">{{ $err['field'] }}</td>
                                <td class="px-3 py-1.5">{{ $err['message'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach([
        'products' => ['Products', 'Match on SKU; UOM code must exist. Missing brand / category auto-created.'],
        'parties' => ['Parties (Customers &amp; Suppliers)', 'Match on Code; classification (customer type) auto-created if new. Contact + billing address created inline.'],
        'price-master' => ['Price Master', 'Match on Customer Type + SKU + UOM + Min Qty; updates rate.'],
    ] as $type => [$label, $note])
        <x-ui.card :title="$label">
            <p class="text-xs text-slate-500 mb-3">{!! $note !!}</p>
            <div class="flex flex-col gap-2">
                <a href="{{ route('masters.bulk-import.template', $type) }}"
                   class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    ⬇ Download template CSV
                </a>
                <form method="POST" enctype="multipart/form-data" action="{{ route('masters.bulk-import.upload', $type) }}" class="space-y-2">
                    @csrf
                    <input type="file" name="file" required accept=".csv,.xlsx,.xls" class="block w-full text-xs">
                    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        Upload &amp; import
                    </button>
                </form>
            </div>
        </x-ui.card>
    @endforeach
</div>
@endsection
