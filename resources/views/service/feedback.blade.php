@extends('layouts.dms')
@section('title', 'Customer Feedback')
@section('content')
<div class="space-y-4">
    <x-ui.page-header title="Customer Feedback" />
    <x-ui.card>
        @include('service.partials.filter-bar', [
            'action' => route('service.feedback'),
            'resetUrl' => route('service.feedback'),
            'filters' => $filters ?? [],
            'showDates' => true,
            'showSearch' => true,
            'showRating' => true,
        ])
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2 text-left">Ticket</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-left">Rating</th>
                        <th class="px-3 py-2 text-left">Comments</th>
                        <th class="px-3 py-2 text-left">Technician</th>
                        <th class="px-3 py-2 text-left">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="px-3 py-2"><a href="{{ route('service.tickets.show', $t) }}" class="text-indigo-600">{{ $t->ticket_no }}</a></td>
                            <td class="px-3 py-2">{{ $t->customer_name }}</td>
                            <td class="px-3 py-2 capitalize">{{ $t->request_type }}</td>
                            <td class="px-3 py-2 font-semibold text-amber-700">{{ $t->feedback_rating }}/5</td>
                            <td class="px-3 py-2">{{ $t->feedback_comments }}</td>
                            <td class="px-3 py-2">{{ $t->assignedEmployee?->name ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $t->feedback_at?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">No feedback yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $tickets->links() }}</div>
    </x-ui.card>
</div>
@endsection
