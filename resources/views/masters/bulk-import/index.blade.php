@extends('layouts.dms')
@section('title', 'Bulk Import')
@section('content')
<x-ui.page-header title="Bulk Import" description="Import Setup, Catalog, Products, Parties and Price List from CSV/Excel. Download each template, fill it, then upload in the order shown — the system validates, updates by key, and provides an error report." />

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

<p class="mb-4 text-xs text-slate-500">
    Recommended order: Companies → Branches → Warehouses → Financial Years → UOMs → Brands → Categories → Sub Categories → Product Types → Products → Parties → Price List.
</p>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach([
        'companies' => ['Companies', 'Match on Code. Creates/updates company profile fields.'],
        'branches' => ['Branches', 'Match on Company Code + Branch Code. Company must exist first.'],
        'warehouses' => ['Warehouses', 'Match on Company + Warehouse Code. Branch must exist first.'],
        'financial-years' => ['Financial Years', 'Match on Company + date range. Can mark one year as current.'],
        'uoms' => ['UOMs', 'Match on Code. Required before Products / Price List imports.'],
        'brands' => ['Brands', 'Match on Company + Name. Optional company_code uses your login company when blank.'],
        'categories' => ['Categories', 'Match on Brand + Name. Import Brands first.'],
        'sub-categories' => ['Sub Categories', 'Match on Brand + Category + Name. Import Categories first.'],
        'product-types' => ['Product Types', 'Match on Company + Name/Code. Sets track_serial and description.'],
        'products' => ['Products', 'Match on Part Number, or on Name and Model when the part number is blank. A second import updates changed rows and skips identical ones, so nothing is duplicated. A UOM already in masters is used; a UOM that is only on the sheet is created, then the product is imported. Brand, category, sub-category and type are left blank when they are not already in masters.'],
        'parties' => ['Parties (Customers &amp; Suppliers)', 'Match on Code, else GSTIN, else Name — re-import updates instead of duplicating. Classification, area and route auto-created.'],
        'price-master' => ['Price List', 'Match on Customer Type + Part Number + UOM + Min Qty. Supports rate_includes_gst.'],
        'leads' => ['CRM Leads', 'Match on Mobile or Email. Maps Contact Name, Company, Title, Mobiles, Address, etc.'],
    ] as $type => [$label, $note])
        <x-ui.card :title="$label">
            <p class="text-xs text-slate-500 mb-3">{!! $note !!}</p>
            <div class="flex flex-col gap-2">
                <a href="{{ route('masters.bulk-import.template', $type) }}"
                   class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Download template CSV
                </a>
                <form method="POST" enctype="multipart/form-data" action="{{ route('masters.bulk-import.upload', $type) }}" class="space-y-2" @if($type === 'products') data-live-import data-progress-url="{{ route('masters.bulk-import.progress', ['id' => 'PROGRESS']) }}" data-cancel-url="{{ route('masters.bulk-import.cancel', ['id' => 'PROGRESS']) }}" @endif>
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

<div id="import-progress" hidden class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/50 p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white px-6 py-6 text-center shadow-xl">
        <div id="import-ring" class="mx-auto grid h-28 w-28 place-items-center rounded-full" style="background: conic-gradient(#4f46e5 0%, #e2e8f0 0%);">
            <div class="grid h-20 w-20 place-items-center rounded-full bg-white">
                <span id="import-percent" class="text-xl font-semibold text-slate-800">0%</span>
            </div>
        </div>
        <p id="import-status" class="mt-4 text-sm font-semibold text-slate-800">Importing products…</p>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
            <div id="import-bar" class="h-full rounded-full bg-indigo-600" style="width: 0%"></div>
        </div>
        <p id="import-count" class="mt-2 text-xs text-slate-500">Preparing the file</p>
        <button type="button" id="import-cancel" class="relative z-10 mt-5 inline-flex items-center justify-center rounded-lg border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
            Cancel
        </button>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById('import-progress');
    const percentEl = document.getElementById('import-percent');
    const bar = document.getElementById('import-bar');
    const ring = document.getElementById('import-ring');
    const statusEl = document.getElementById('import-status');
    const countEl = document.getElementById('import-count');
    const cancelBtn = document.getElementById('import-cancel');
    let active = null;

    const paint = (percent, processed, total, label) => {
        if (active && active.cancelled) {
            return;
        }
        const safe = Math.max(0, Math.min(100, Number(percent) || 0));
        percentEl.textContent = safe + '%';
        bar.style.width = safe + '%';
        ring.style.background = 'conic-gradient(#4f46e5 ' + safe + '%, #e2e8f0 ' + safe + '%)';
        statusEl.textContent = label;
        countEl.textContent = total > 0 ? (processed + ' of ' + total + ' rows') : 'Preparing the file';
    };

    const finishCancelled = () => {
        statusEl.textContent = 'Import cancelled';
        countEl.textContent = 'Nothing was saved';
        setTimeout(() => { overlay.hidden = true; }, 900);
    };

    cancelBtn.addEventListener('click', () => {
        if (!active || active.cancelled || active.finished) {
            return;
        }
        active.cancelled = true;
        active.polling = false;
        cancelBtn.disabled = true;
        statusEl.textContent = 'Cancelling…';
        countEl.textContent = 'Stopping the import';
        active.request.abort();
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch(active.cancelUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            keepalive: true,
        }).catch(() => {});
        finishCancelled();
    });

    document.querySelectorAll('form[data-live-import]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const file = form.querySelector('input[type="file"]');
            if (!file || !file.files.length) {
                form.reportValidity();
                return;
            }

            const progressId = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(16).slice(2)).replace(/[^A-Za-z0-9\-]/g, '').slice(0, 80);
            active = {
                cancelled: false,
                finished: false,
                polling: true,
                request: new AbortController(),
                cancelUrl: (form.dataset.cancelUrl || '').replace('PROGRESS', progressId),
            };
            const run = active;
            const body = new FormData(form);
            body.set('progress_id', progressId);
            overlay.hidden = false;
            cancelBtn.disabled = false;
            paint(0, 0, 0, 'Importing products…');

            const progressUrl = form.dataset.progressUrl.replace('PROGRESS', progressId);
            const poll = async () => {
                while (run.polling && !run.cancelled) {
                    try {
                        const res = await fetch(progressUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                        if (res.ok && !run.cancelled) {
                            const data = await res.json();
                            paint(data.percent || 0, data.processed || 0, data.total || 0, data.status === 'done' ? 'Finishing…' : 'Importing products…');
                        }
                    } catch (e) {}
                    await new Promise((resolve) => setTimeout(resolve, 700));
                }
            };
            poll();

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(form.action, {
                    method: 'POST',
                    body,
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token || '' },
                    credentials: 'same-origin',
                    signal: run.request.signal,
                });
                const data = await res.json().catch(() => ({}));
                run.polling = false;
                if (run.cancelled || data.cancelled) {
                    run.cancelled = true;
                    finishCancelled();
                    return;
                }
                if (!res.ok) {
                    paint(0, 0, 0, data.message || 'Import failed');
                    setTimeout(() => { overlay.hidden = true; }, 1600);
                    return;
                }
                run.finished = true;
                cancelBtn.disabled = true;
                paint(100, data.created + data.updated + data.skipped, data.created + data.updated + data.skipped, 'Import complete');
                countEl.textContent = data.created + ' created, ' + data.updated + ' updated, ' + data.skipped + ' skipped';
                setTimeout(() => window.location.reload(), 900);
            } catch (e) {
                run.polling = false;
                if (run.cancelled || (e && e.name === 'AbortError')) {
                    run.cancelled = true;
                    finishCancelled();
                    return;
                }
                paint(0, 0, 0, 'Import failed');
                setTimeout(() => { overlay.hidden = true; }, 1600);
            }
        });
    });
})();
</script>
@endsection
