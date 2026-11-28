@extends('layouts.dms')
@section('title', $title)
@section('content')
<div class="space-y-4">
    <x-ui.page-header :title="$title">
        <x-slot name="actions">
            <form method="POST" action="{{ route('service.escalate') }}">@csrf
                <x-ui.button type="submit" variant="secondary">Escalate overdue</x-ui.button>
            </form>
            <x-ui.button :href="route('service.tickets.create')" variant="primary">+ New Ticket</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if(!empty($showDashboard))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <x-ui.card><p class="text-xs text-slate-500">Open tickets</p><p class="text-2xl font-semibold">{{ $openCount }}</p></x-ui.card>
            <x-ui.card><p class="text-xs text-slate-500">Overdue SLA</p><p class="text-2xl font-semibold text-red-600">{{ $overdueCount }}</p></x-ui.card>
            <x-ui.card><p class="text-xs text-slate-500">Avg resolution (hrs)</p><p class="text-2xl font-semibold">{{ $avgResolutionHours ?? '—' }}</p></x-ui.card>
            <x-ui.card>
                <p class="text-xs text-slate-500 mb-1">By type</p>
                @forelse($byType as $type => $c)
                    <div class="flex justify-between text-sm capitalize"><span>{{ $type }}</span><span>{{ $c }}</span></div>
                @empty
                    <p class="text-sm text-slate-500">—</p>
                @endforelse
            </x-ui.card>
        </div>
    @endif

    <x-ui.card>
        @include('service.partials.filter-bar', [
            'action' => route('service.tickets.index'),
            'resetUrl' => route('service.tickets.index'),
            'filters' => $filters ?? [],
            'showDates' => true,
            'showSearch' => true,
            'showFlags' => true,
        ])

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Ticket</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Customer</th>
                        <th class="px-3 py-2">Serial / Model</th>
                        <th class="px-3 py-2">Priority</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Assignee</th>
                        <th class="px-3 py-2">SLA</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $ticket)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium">
                                <a href="{{ route('service.tickets.show', $ticket) }}" class="text-indigo-600 hover:underline">{{ $ticket->ticket_no }}</a>
                                <div class="text-[11px] text-slate-400">{{ $ticket->created_at?->format('d M Y') }}</div>
                            </td>
                            <td class="px-3 py-2 capitalize">{{ $ticket->request_type }}</td>
                            <td class="px-3 py-2">
                                <div>{{ $ticket->customer_name }}</div>
                                <div class="text-[11px] text-slate-400 capitalize">{{ str_replace('_',' ',$ticket->party_type) }}</div>
                            </td>
                            <td class="px-3 py-2">
                                <div>{{ $ticket->serial_no ?: '—' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $ticket->brand }} {{ $ticket->model_number }}</div>
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium
                                    {{ $ticket->priority === 'critical' ? 'bg-red-100 text-red-800' : ($ticket->priority === 'high' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 capitalize">{{ $ticket->status }}</td>
                            <td class="px-3 py-2">{{ $ticket->assignedEmployee?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs {{ $ticket->sla_due_at && $ticket->sla_due_at->isPast() && !in_array($ticket->status, ['closed','cancelled']) ? 'text-red-600 font-semibold' : 'text-slate-500' }}">
                                {{ $ticket->sla_due_at?->format('d M H:i') ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('service.tickets.edit', $ticket) }}" class="text-xs text-slate-500 hover:text-indigo-600">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-8 text-center text-slate-500">
                                @if(!empty($filters['assignment']))
                                    No open tickets to assign.
                                @elseif(!empty($filters['unassigned']))
                                    No unassigned open tickets.
                                @elseif(!empty($filters['overdue']))
                                    No pending / overdue tickets.
                                @else
                                    No tickets found.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
