@extends('layouts.dms')
@section('title', 'Service Quotation & Collection')
@section('content')
<div class="space-y-4">
    <x-ui.page-header title="Service Quotation & Approval / Collection" />
    <x-ui.card>
        @include('service.partials.filter-bar', [
            'action' => route('service.quotations'),
            'resetUrl' => route('service.quotations'),
            'filters' => $filters ?? [],
            'showDates' => true,
            'showSearch' => true,
            'showPayment' => true,
            'showApproved' => true,
        ])
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm" id="collection">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2 text-left">Ticket</th>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-left">Quote / Charges</th>
                        <th class="px-3 py-2 text-left">Approved</th>
                        <th class="px-3 py-2 text-left">Payment</th>
                        <th class="px-3 py-2 text-left">Tech</th>
                        <th class="px-3 py-2 text-left">Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                        @php $ch = $t->charges; @endphp
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="px-3 py-2"><a href="{{ route('service.tickets.show', $t) }}" class="text-indigo-600">{{ $t->ticket_no }}</a></td>
                            <td class="px-3 py-2 capitalize">{{ $t->request_type }}</td>
                            <td class="px-3 py-2">{{ $t->customer_name }}</td>
                            <td class="px-3 py-2">
                                @if($t->installation?->quotation_amount)
                                    Install quote: ₹{{ number_format((float)$t->installation->quotation_amount, 2) }}<br>
                                @endif
                                @if($ch)
                                    ₹{{ number_format((float)$ch->grand_total, 2) }}
                                @elseif(!$t->installation?->quotation_amount)
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ ($ch?->customer_approved || $t->installation?->customer_approved) ? 'Yes' : 'No' }}</td>
                            <td class="px-3 py-2 capitalize">{{ $ch?->payment_status ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $t->assignedEmployee?->name ?? '—' }}</td>
                            <td class="px-3 py-2">
                                @if($t->invoice_id)
                                    <a href="{{ route('invoices.show', $t->invoice_id) }}" class="text-indigo-600">View</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-3 py-6 text-center text-slate-500">No chargeable / quotation tickets.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $tickets->links() }}</div>
    </x-ui.card>
</div>
@endsection
