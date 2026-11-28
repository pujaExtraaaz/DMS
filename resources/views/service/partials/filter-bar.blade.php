@props([
    'action',
    'resetUrl',
    'filters' => [],
    'showDates' => true,
    'showSearch' => true,
    'showFlags' => false,
    'showPayment' => false,
    'showOemStatus' => false,
    'showRating' => false,
    'showApproved' => false,
    'extra' => null,
])
@php
    $f = $filters ?? [];
@endphp
<form method="GET" action="{{ $action }}" class="mb-4 space-y-3">
    <div class="flex flex-wrap gap-2 items-end">
        @if($showSearch)
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Search</label>
                <input type="text" name="search" value="{{ $f['search'] ?? '' }}" class="rounded-lg border-slate-300 text-sm min-w-[12rem]" placeholder="Ticket, customer, serial…">
            </div>
        @endif
        @if($showDates)
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">From</label>
                <input type="date" name="date_from" value="{{ $f['date_from'] ?? '' }}" class="rounded-lg border-slate-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">To</label>
                <input type="date" name="date_to" value="{{ $f['date_to'] ?? '' }}" class="rounded-lg border-slate-300 text-sm">
            </div>
        @endif
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Type</label>
            <select name="request_type" class="rounded-lg border-slate-300 text-sm">
                <option value="">All</option>
                @foreach($requestTypes ?? ['complaint','installation','service'] as $t)
                    <option value="{{ $t }}" @selected(($f['request_type'] ?? '') === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
            <select name="status" class="rounded-lg border-slate-300 text-sm">
                <option value="">All</option>
                @foreach($statuses ?? \App\Domains\Service\Models\ServiceTicket::STATUSES as $s)
                    <option value="{{ $s }}" @selected(($f['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Priority</label>
            <select name="priority" class="rounded-lg border-slate-300 text-sm">
                <option value="">All</option>
                @foreach($priorities ?? ['critical','high','normal','low'] as $p)
                    <option value="{{ $p }}" @selected(($f['priority'] ?? '') === $p)>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Party</label>
            <select name="party_type" class="rounded-lg border-slate-300 text-sm">
                <option value="">All</option>
                @foreach($partyTypes ?? ['dealer','end_user','company'] as $p)
                    <option value="{{ $p }}" @selected(($f['party_type'] ?? '') === $p)>{{ str_replace('_',' ',ucfirst($p)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Technician</label>
            <select name="employee_id" class="rounded-lg border-slate-300 text-sm">
                <option value="">All</option>
                @foreach($employees ?? [] as $emp)
                    <option value="{{ $emp->id }}" @selected(($f['employee_id'] ?? '') == $emp->id)>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">OEM centre</label>
            <select name="oem_centre_id" class="rounded-lg border-slate-300 text-sm">
                <option value="">All</option>
                @foreach($oemCentres ?? [] as $o)
                    <option value="{{ $o->id }}" @selected(($f['oem_centre_id'] ?? '') == $o->id)>{{ $o->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Brand</label>
            <select name="brand" class="rounded-lg border-slate-300 text-sm">
                <option value="">All</option>
                @foreach($brands ?? [] as $b)
                    <option value="{{ $b }}" @selected(($f['brand'] ?? '') === $b)>{{ $b }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Model</label>
            <select name="model_number" class="rounded-lg border-slate-300 text-sm">
                <option value="">All</option>
                @foreach($models ?? [] as $m)
                    <option value="{{ $m }}" @selected(($f['model_number'] ?? '') === $m)>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Warranty</label>
            <select name="warranty_status" class="rounded-lg border-slate-300 text-sm">
                <option value="">All</option>
                @foreach($warrantyStatuses ?? ['in_warranty','out_of_warranty','unknown'] as $w)
                    <option value="{{ $w }}" @selected(($f['warranty_status'] ?? '') === $w)>{{ str_replace('_',' ',ucfirst($w)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Handler</label>
            <select name="handler_type" class="rounded-lg border-slate-300 text-sm">
                <option value="">All</option>
                @foreach($handlerTypes ?? ['avit','oem','both'] as $h)
                    <option value="{{ $h }}" @selected(($f['handler_type'] ?? '') === $h)>{{ strtoupper($h) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Serial</label>
            <input type="text" name="serial_no" value="{{ $f['serial_no'] ?? '' }}" class="rounded-lg border-slate-300 text-sm">
        </div>
        @if($showPayment)
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Payment</label>
                <select name="payment_status" class="rounded-lg border-slate-300 text-sm">
                    <option value="">All</option>
                    @foreach($paymentStatuses ?? ['pending','partial','paid','waived'] as $ps)
                        <option value="{{ $ps }}" @selected(($f['payment_status'] ?? '') === $ps)>{{ ucfirst($ps) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        @if($showOemStatus)
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">OEM status</label>
                <select name="oem_status" class="rounded-lg border-slate-300 text-sm">
                    <option value="">All</option>
                    @foreach($oemStatuses ?? ['pending','submitted','approved','rejected','closed','in_progress'] as $os)
                        <option value="{{ $os }}" @selected(($f['oem_status'] ?? '') === $os)>{{ ucfirst($os) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        @if($showRating)
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Rating</label>
                <select name="rating" class="rounded-lg border-slate-300 text-sm">
                    <option value="">All</option>
                    @for($r = 5; $r >= 1; $r--)
                        <option value="{{ $r }}" @selected(($f['rating'] ?? '') == $r)>{{ $r }} / 5</option>
                    @endfor
                </select>
            </div>
        @endif
        @if($showApproved)
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Approved</label>
                <select name="approved" class="rounded-lg border-slate-300 text-sm">
                    <option value="">All</option>
                    <option value="1" @selected(($f['approved'] ?? '') === '1')>Yes</option>
                    <option value="0" @selected(($f['approved'] ?? '') === '0')>No</option>
                </select>
            </div>
        @endif
        {{ $extra ?? '' }}
        @if($showFlags)
            <label class="flex items-center gap-1 text-xs text-slate-700 pb-2">
                <input type="checkbox" name="pending_only" value="1" @checked(!empty($f['pending_only']))> Open only
            </label>
            <label class="flex items-center gap-1 text-xs text-slate-700 pb-2">
                <input type="checkbox" name="unassigned" value="1" @checked(!empty($f['unassigned']))> Unassigned
            </label>
            <label class="flex items-center gap-1 text-xs text-slate-700 pb-2">
                <input type="checkbox" name="overdue" value="1" @checked(!empty($f['overdue']))> Overdue
            </label>
        @endif
        <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        <a href="{{ $resetUrl }}" class="text-xs text-slate-500 underline pb-2">Reset</a>
    </div>
</form>
