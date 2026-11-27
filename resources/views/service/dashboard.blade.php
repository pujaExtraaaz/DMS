@extends('layouts.dms')
@section('title', 'Service Dashboard')
@section('content')
<div class="space-y-4">
    <x-ui.page-header title="Service & Support Dashboard">
        <x-slot name="actions">
            <form method="POST" action="{{ route('service.escalate') }}">@csrf
                <x-ui.button type="submit" variant="secondary">Escalate overdue</x-ui.button>
            </form>
            <x-ui.button :href="route('service.tickets.create')" variant="primary">+ New Ticket</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-ui.card><p class="text-xs text-slate-500">Open tickets</p><p class="text-2xl font-semibold">{{ $openCount }}</p></x-ui.card>
        <x-ui.card><p class="text-xs text-slate-500">Overdue SLA</p><p class="text-2xl font-semibold text-red-600">{{ $overdueCount }}</p></x-ui.card>
        <x-ui.card><p class="text-xs text-slate-500">Avg resolution (hrs)</p><p class="text-2xl font-semibold">{{ $avgResolutionHours ?? '—' }}</p></x-ui.card>
        <x-ui.card>
            <p class="text-xs text-slate-500 mb-1">By type</p>
            @foreach($byType as $type => $c)
                <div class="flex justify-between text-sm capitalize"><span>{{ $type }}</span><span>{{ $c }}</span></div>
            @endforeach
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-ui.card>
            <h3 class="text-sm font-semibold mb-2">Open by priority</h3>
            @forelse($byPriority as $p => $c)
                <div class="flex justify-between text-sm capitalize py-1 border-b border-slate-50"><span>{{ $p }}</span><span>{{ $c }}</span></div>
            @empty
                <p class="text-sm text-slate-500">None</p>
            @endforelse
        </x-ui.card>
        <x-ui.card>
            <h3 class="text-sm font-semibold mb-2">Warranty split</h3>
            @forelse($warrantySplit as $w => $c)
                <div class="flex justify-between text-sm capitalize py-1 border-b border-slate-50"><span>{{ str_replace('_',' ',$w) }}</span><span>{{ $c }}</span></div>
            @empty
                <p class="text-sm text-slate-500">None</p>
            @endforelse
        </x-ui.card>
    </div>

    <x-ui.card>
        <h3 class="text-sm font-semibold mb-3">Recent tickets</h3>
        <table class="min-w-full text-sm">
            <thead class="text-xs uppercase text-slate-500">
                <tr>
                    <th class="py-1 text-left">Ticket</th>
                    <th class="text-left">Created</th>
                    <th class="text-left">Customer</th>
                    <th class="text-left">Status</th>
                    <th class="text-left">Tech</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recent as $t)
                    <tr class="border-t border-slate-50">
                        <td class="py-2"><a href="{{ route('service.tickets.show', $t) }}" class="text-indigo-600">{{ $t->ticket_no }}</a></td>
                        <td class="text-slate-600">{{ $t->created_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td>{{ $t->customer_name }}</td>
                        <td class="capitalize">{{ $t->status }}</td>
                        <td>{{ $t->assignedEmployee?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-slate-500">No tickets yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.card>
</div>
@endsection
