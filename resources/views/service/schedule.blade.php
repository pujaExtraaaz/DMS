@extends('layouts.dms')
@section('title', 'Daily Visit Schedule')
@section('content')
<div class="space-y-4">
    <x-ui.page-header title="Daily Visit Schedule" />
    <x-ui.card>
        <form method="GET" action="{{ route('service.schedule') }}" class="mb-4 flex flex-wrap gap-2 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Date</label>
                <input type="date" name="date" value="{{ $date }}" class="rounded-lg border-slate-300 text-sm" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="rounded-lg border-slate-300 text-sm" placeholder="Ticket, customer…">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Type</label>
                <select name="request_type" class="rounded-lg border-slate-300 text-sm">
                    <option value="">All</option>
                    @foreach($requestTypes ?? ['complaint','installation','service'] as $t)
                        <option value="{{ $t }}" @selected(($filters['request_type'] ?? '') === $t)>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                <select name="status" class="rounded-lg border-slate-300 text-sm">
                    <option value="">All</option>
                    @foreach($statuses ?? \App\Domains\Service\Models\ServiceTicket::STATUSES as $s)
                        @continue(in_array($s, ['closed','cancelled'], true))
                        <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Priority</label>
                <select name="priority" class="rounded-lg border-slate-300 text-sm">
                    <option value="">All</option>
                    @foreach($priorities ?? ['critical','high','normal','low'] as $p)
                        <option value="{{ $p }}" @selected(($filters['priority'] ?? '') === $p)>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Party</label>
                <select name="party_type" class="rounded-lg border-slate-300 text-sm">
                    <option value="">All</option>
                    @foreach($partyTypes ?? ['dealer','end_user','company'] as $p)
                        <option value="{{ $p }}" @selected(($filters['party_type'] ?? '') === $p)>{{ str_replace('_',' ',ucfirst($p)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Technician</label>
                <select name="employee_id" class="rounded-lg border-slate-300 text-sm">
                    <option value="">All</option>
                    @foreach($employees ?? [] as $emp)
                        <option value="{{ $emp->id }}" @selected(($filters['employee_id'] ?? '') == $emp->id)>{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">OEM centre</label>
                <select name="oem_centre_id" class="rounded-lg border-slate-300 text-sm">
                    <option value="">All</option>
                    @foreach($oemCentres ?? [] as $o)
                        <option value="{{ $o->id }}" @selected(($filters['oem_centre_id'] ?? '') == $o->id)>{{ $o->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Brand</label>
                <select name="brand" class="rounded-lg border-slate-300 text-sm">
                    <option value="">All</option>
                    @foreach($brands ?? [] as $b)
                        <option value="{{ $b }}" @selected(($filters['brand'] ?? '') === $b)>{{ $b }}</option>
                    @endforeach
                </select>
            </div>
            <x-ui.button type="submit" variant="secondary">Show</x-ui.button>
            <a href="{{ route('service.schedule') }}" class="text-xs text-slate-500 underline pb-2">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr>
                    <th class="px-3 py-2 text-left">Time</th>
                    <th class="px-3 py-2 text-left">Ticket</th>
                    <th class="px-3 py-2 text-left">Type</th>
                    <th class="px-3 py-2 text-left">Customer</th>
                    <th class="px-3 py-2 text-left">Priority</th>
                    <th class="px-3 py-2 text-left">Technician</th>
                    <th class="px-3 py-2 text-left">OEM</th>
                    <th class="px-3 py-2 text-left">Status</th>
                </tr></thead>
                <tbody>
                    @forelse($tickets as $t)
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium">{{ $t->expected_visit_at?->format('H:i') ?? '—' }}</td>
                            <td class="px-3 py-2"><a href="{{ route('service.tickets.show', $t) }}" class="text-indigo-600">{{ $t->ticket_no }}</a></td>
                            <td class="px-3 py-2 capitalize">{{ $t->request_type }}</td>
                            <td class="px-3 py-2">{{ $t->customer_name }}</td>
                            <td class="px-3 py-2 capitalize">{{ $t->priority }}</td>
                            <td class="px-3 py-2">{{ $t->assignedEmployee?->name ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $t->oemCentre?->name ?? '—' }}</td>
                            <td class="px-3 py-2 capitalize">{{ $t->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-3 py-6 text-center text-slate-500">No visits scheduled for this day.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection
