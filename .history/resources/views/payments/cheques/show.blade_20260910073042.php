@extends('layouts.dms')
@section('title', 'Cheque '.$item->cheque_no)
@section('content')
<x-ui.page-header :title="'Cheque '.$item->cheque_no" :description="$item->customer?->name">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('cheques.index')">Back</x-ui.button>
        @if($item->status === 'pending')
            <form method="POST" action="{{ route('cheques.deposit', $item) }}">@csrf<x-ui.button type="submit" variant="primary">Deposit</x-ui.button></form>
        @endif
        @if(in_array($item->status, ['pending','deposited']))
            <form method="POST" action="{{ route('cheques.clear', $item) }}">@csrf<x-ui.button type="submit" variant="primary">Clear</x-ui.button></form>
            <form method="POST" action="{{ route('cheques.bounce', $item) }}" class="flex gap-2 items-center">
                @csrf
                <input type="text" name="reason" placeholder="Bounce reason" class="rounded-lg border-gray-300 text-sm">
                <x-ui.button type="submit" variant="secondary">Bounce</x-ui.button>
            </form>
        @endif
        @if(!in_array($item->status, ['cleared','bounced','cancelled']))
            <form method="POST" action="{{ route('cheques.cancel', $item) }}">@csrf<x-ui.button type="submit" variant="secondary">Cancel</x-ui.button></form>
        @endif
    </x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <x-ui.card title="Details">
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd>{{ ucfirst($item->status) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Purpose</dt><dd>{{ strtoupper($item->purpose) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Direction</dt><dd>{{ str_replace('_',' ', $item->direction) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Amount</dt><dd>₹{{ number_format($item->amount, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Bank</dt><dd>{{ $item->bank_name ?: '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Cheque Date</dt><dd>{{ $item->cheque_date?->format('d M Y') ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Party bounce count</dt><dd>{{ $item->customer?->cheque_bounce_count ?? 0 }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Credit status</dt><dd>{{ $item->customer?->credit_status }}</dd></div>
        </dl>
    </x-ui.card>
    <x-ui.card title="Bounce History">
        <div class="space-y-3 text-sm">
            @forelse($item->bounces as $bounce)
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="font-medium">#{{ $bounce->bounce_number }} · {{ $bounce->bounced_on?->format('d M Y') }}</div>
                    <div class="text-slate-500">{{ $bounce->reason ?: 'No reason' }}</div>
                    @if($bounce->triggered_freeze)
                        <div class="text-red-600 text-xs mt-1">Triggered freeze</div>
                    @endif
                </div>
            @empty
                <p class="text-slate-500">No bounce history.</p>
            @endforelse
        </div>
    </x-ui.card>
</div>
@endsection
