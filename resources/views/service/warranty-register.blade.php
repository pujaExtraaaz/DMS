@extends('layouts.dms')
@section('title', 'OEM Warranty Register')
@section('content')
<div class="space-y-4">
    <x-ui.page-header title="OEM Warranty Register" />
    <x-ui.card>
        @include('service.partials.filter-bar', [
            'action' => route('service.warranty-register'),
            'resetUrl' => route('service.warranty-register'),
            'filters' => $filters ?? [],
            'showDates' => true,
            'showSearch' => true,
            'showOemStatus' => true,
        ])
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2 text-left">Ticket</th>
                        <th class="px-3 py-2 text-left">Created</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-left">Brand / Model</th>
                        <th class="px-3 py-2 text-left">Serial</th>
                        <th class="px-3 py-2 text-left">OEM</th>
                        <th class="px-3 py-2 text-left">Claim / OEM ticket</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">Tech</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="px-3 py-2"><a href="{{ route('service.tickets.show', $t) }}" class="text-indigo-600">{{ $t->ticket_no }}</a></td>
                            <td class="px-3 py-2 text-slate-500">{{ $t->created_at?->format('d M Y') }}</td>
                            <td class="px-3 py-2">{{ $t->customer_name }}</td>
                            <td class="px-3 py-2">{{ $t->brand }} {{ $t->model_number }}</td>
                            <td class="px-3 py-2">{{ $t->serial_no ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $t->warranty?->oemCentre?->name ?? $t->oemCentre?->name ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $t->warranty?->warranty_claim_no ?: '—' }} / {{ $t->warranty?->oem_ticket_no ?: '—' }}</td>
                            <td class="px-3 py-2 capitalize">{{ $t->warranty?->oem_status ?? $t->warranty_status }}</td>
                            <td class="px-3 py-2">{{ $t->assignedEmployee?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-3 py-6 text-center text-slate-500">No warranty records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $tickets->links() }}</div>
    </x-ui.card>
</div>
@endsection
