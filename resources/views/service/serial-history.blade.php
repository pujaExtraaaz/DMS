@extends('layouts.dms')
@section('title', 'Serial History')
@section('content')
<div class="space-y-4">
    <x-ui.page-header title="Serial service history" />
    <x-ui.card>
        @include('service.partials.filter-bar', [
            'action' => route('service.serial-history'),
            'resetUrl' => route('service.serial-history'),
            'filters' => array_merge($filters ?? [], ['serial_no' => $serial ?? ($filters['serial_no'] ?? '')]),
            'showDates' => true,
            'showSearch' => false,
        ])
        @if(($serial ?? '') !== '')
            <h3 class="text-sm font-semibold mb-2">Tickets matching “{{ $serial }}” ({{ $tickets->count() }})</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr>
                        <th class="px-3 py-2 text-left">Ticket</th>
                        <th class="px-3 py-2 text-left">Date</th>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-left">Tech</th>
                        <th class="px-3 py-2 text-left">Serial</th>
                    </tr></thead>
                    <tbody>
                        @forelse($tickets as $t)
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="px-3 py-2"><a href="{{ route('service.tickets.show', $t) }}" class="text-indigo-600">{{ $t->ticket_no }}</a></td>
                                <td class="px-3 py-2">{{ $t->created_at?->format('d M Y') }}</td>
                                <td class="px-3 py-2 capitalize">{{ $t->request_type }}</td>
                                <td class="px-3 py-2 capitalize">{{ $t->status }}</td>
                                <td class="px-3 py-2">{{ $t->customer_name }}</td>
                                <td class="px-3 py-2">{{ $t->assignedEmployee?->name ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $t->serial_no }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-4 text-slate-500 text-center">No tickets for this serial.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-500">Enter a serial number (and optional filters) then click Filter.</p>
        @endif
    </x-ui.card>
</div>
@endsection
