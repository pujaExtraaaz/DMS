@php
    $warrantyStatus = $serial->warrantyStatus();
    $warrantyStatusClass = match ($warrantyStatus) {
        'Under Warranty' => 'bg-emerald-50 text-emerald-700',
        'Out of Warranty' => 'bg-rose-50 text-rose-700',
        default => 'bg-slate-100 text-slate-600',
    };
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
    <div>
        <p class="text-xs text-slate-500">1. Product Name</p>
        <p class="font-semibold text-slate-900">{{ $serial->product?->name ?? '—' }}</p>
    </div>
    <div>
        <p class="text-xs text-slate-500">2. Serial Number</p>
        <p class="font-semibold text-slate-900">{{ $serial->serial_number }}</p>
    </div>
    <div>
        <p class="text-xs text-slate-500">3. Customer Name</p>
        <p class="font-semibold text-slate-900">{{ $serial->customer?->name ?? '—' }}</p>
    </div>
    <div>
        <p class="text-xs text-slate-500">4. Sales Invoice Number and Date</p>
        <p class="font-semibold text-slate-900">
            @if($serial->salesInvoice)
                {{ $serial->salesInvoice->invoice_no }}
                <span class="font-normal text-slate-500">{{ $serial->salesInvoice->invoice_date?->format('d-m-Y') }}</span>
            @else
                —
            @endif
        </p>
    </div>
    <div>
        <p class="text-xs text-slate-500">5. Warranty Period</p>
        <p class="font-semibold text-slate-900">{{ $serial->warranty_label ?: ($serial->warranty_months ? $serial->warranty_months.' months' : '—') }}</p>
    </div>
    <div>
        <p class="text-xs text-slate-500">6. Warranty Start Date and Expiry Date</p>
        <p class="font-semibold text-slate-900">
            {{ $serial->warranty_start_on?->format('d-m-Y') ?? '—' }}
            <span class="font-normal text-slate-500">to</span>
            {{ $serial->warranty_expires_on?->format('d-m-Y') ?? '—' }}
        </p>
    </div>
    <div>
        <p class="text-xs text-slate-500">7. Current Warranty Status</p>
        <p class="mt-1"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $warrantyStatusClass }}">{{ $warrantyStatus ?? 'No warranty' }}</span></p>
    </div>
</div>
