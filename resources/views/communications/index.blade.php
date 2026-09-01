@extends('layouts.dms')
@section('title', 'Communications')
@section('content')
<x-ui.page-header title="Communication Log" />
<x-ui.card>
    <form method="GET" class="flex flex-col gap-4 md:flex-row md:items-end mb-6">
        <div class="flex-1 md:flex-none md:w-80">
            <x-ui.select name="type" label="Type" placeholder="All"><option value=""></option><option value="whatsapp_invoice">WhatsApp Invoice</option><option value="payment_link">Payment Link</option><option value="payment_reminder">Reminder</option></x-ui.select>
        </div>
        <x-ui.button type="submit" variant="secondary" class="w-full md:w-auto">Filter</x-ui.button>
    </form>
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Type</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Recipient</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th></tr></x-slot>
@forelse($logs as $log)<tr><td class="px-6 py-4 text-sm">{{ $log->created_at->format('d M Y H:i') }}</td><td class="px-6 py-4 text-sm">{{ str_replace('_', ' ', $log->type) }}</td><td class="px-6 py-4 text-sm">{{ $log->customer?->name ?? '—' }}</td><td class="px-6 py-4 text-sm">{{ $log->recipient }}</td><td class="px-6 py-4 text-sm"><x-ui.badge variant="success">{{ $log->status }}</x-ui.badge></td></tr>@empty<tr><td colspan="5" class="px-6 py-8"><x-ui.empty-state title="No messages" /></td></tr>@endforelse</x-ui.table>
<div class="mt-4">{{ $logs->links() }}</div></x-ui.card>
@endsection
