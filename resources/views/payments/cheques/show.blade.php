@extends('layouts.dms')
@section('title', 'Cheque '.$item->cheque_no)

@section('content')
<div class="space-y-6">
    <x-ui.page-header :title="'Cheque #'.$item->cheque_no" :description="$item->customer?->name ?: 'Party Cheque'">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('cheques.index')">
                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Back to Register
            </x-ui.button>

            @if(in_array($item->status, ['pending', \App\Domains\Payment\Models\Cheque::STATUS_ISSUED, \App\Domains\Payment\Models\Cheque::STATUS_RECEIVED]))
                <form method="POST" action="{{ route('cheques.deposit', $item) }}" class="inline">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" class="bg-cyan-50 border-cyan-200 text-cyan-800 hover:bg-cyan-100">
                        Deposit in Bank
                    </x-ui.button>
                </form>
            @endif

            @if(in_array($item->status, ['pending', \App\Domains\Payment\Models\Cheque::STATUS_DEPOSITED, \App\Domains\Payment\Models\Cheque::STATUS_ISSUED, \App\Domains\Payment\Models\Cheque::STATUS_RECEIVED]))
                <form method="POST" action="{{ route('cheques.clear', $item) }}" class="inline">
                    @csrf
                    <x-ui.button type="submit" variant="primary">Clear Cheque</x-ui.button>
                </form>

                <form method="POST" action="{{ route('cheques.bounce', $item) }}" class="inline flex items-center gap-1.5" onsubmit="return confirm('Confirm marking this cheque as bounced?')">
                    @csrf
                    <input type="text" name="reason" placeholder="Bounce reason..." class="rounded-lg border-gray-300 text-xs py-1.5 px-2.5 w-44">
                    <x-ui.button type="submit" variant="secondary" class="bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100">
                        Bounce
                    </x-ui.button>
                </form>
            @endif

            @if(!in_array($item->status, [\App\Domains\Payment\Models\Cheque::STATUS_CLEARED, \App\Domains\Payment\Models\Cheque::STATUS_BOUNCED, \App\Domains\Payment\Models\Cheque::STATUS_CANCELLED]))
                <form method="POST" action="{{ route('cheques.cancel', $item) }}" class="inline" onsubmit="return confirm('Cancel this cheque?')">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" class="text-rose-600 hover:text-rose-800">
                        Cancel
                    </x-ui.button>
                </form>
            @endif

            @if(!in_array($item->status, [\App\Domains\Payment\Models\Cheque::STATUS_CLEARED, \App\Domains\Payment\Models\Cheque::STATUS_BOUNCED, \App\Domains\Payment\Models\Cheque::STATUS_CANCELLED, \App\Domains\Payment\Models\Cheque::STATUS_STALE]))
                <form method="POST" action="{{ route('cheques.mark-stale', $item) }}" class="inline" onsubmit="return confirm('Mark this cheque as stale / expired?')">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" class="text-amber-700 hover:text-amber-900">
                        Mark Stale
                    </x-ui.button>
                </form>
            @endif
        </x-slot>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-ui.card title="Cheque Details">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Cheque Number</dt>
                        <dd class="mt-1 font-mono font-bold text-base text-slate-900">{{ $item->cheque_no }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border {{ $item->statusBadgeClass() }}">
                                {{ $item->status_label }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Cheque Type</dt>
                        <dd class="mt-1">
                            @if($item->isIssued())
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200">
                                    Issued (Outgoing)
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 border border-indigo-200">
                                    Received (Incoming)
                                </span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Amount</dt>
                        <dd class="mt-1 font-mono font-bold text-lg text-slate-900">₹{{ number_format($item->amount, 2) }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Party Name</dt>
                        <dd class="mt-1 font-medium text-slate-900">
                            @if($item->customer)
                                <a href="{{ route('masters.customers.show', $item->customer) }}" class="text-indigo-600 hover:underline">
                                    {{ $item->customer->name }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Cheque Date</dt>
                        <dd class="mt-1 font-mono text-slate-800">{{ $item->cheque_date ? $item->cheque_date->format('d M Y') : '—' }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Associated Bank Account</dt>
                        <dd class="mt-1 text-slate-800">
                            <span class="font-medium">{{ $item->bank_name ?: '—' }}</span>
                            @if($item->bank_account_no)
                                <span class="font-mono text-slate-500 block">A/C: {{ $item->bank_account_no }}</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Cheque Book Reference</dt>
                        <dd class="mt-1 text-slate-800">
                            @if($item->chequeBook)
                                <span class="font-medium">{{ $item->chequeBook->cheque_book_name ?: 'Cheque Book' }}</span>
                                <span class="font-mono text-slate-500 block">({{ $item->chequeBook->from_number }} - {{ $item->chequeBook->to_number }})</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Purpose</dt>
                        <dd class="mt-1 uppercase font-mono text-slate-800">{{ $item->purpose ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Reference Voucher</dt>
                        <dd class="mt-1 text-slate-800">
                            @if($item->payment)
                                <a href="{{ route('payments.show', $item->payment) }}" class="text-indigo-600 hover:underline font-mono">
                                    {{ $item->payment->payment_no }}
                                </a>
                            @elseif($item->reference_voucher)
                                <span class="font-mono">{{ $item->reference_voucher }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Deposit Date</dt>
                        <dd class="mt-1 font-mono text-slate-800">{{ $item->deposit_date ? $item->deposit_date->format('d M Y') : '—' }}</dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Clearance Date</dt>
                        <dd class="mt-1 font-mono text-slate-800">{{ $item->clearance_date ? $item->clearance_date->format('d M Y') : '—' }}</dd>
                    </div>

                    @if($item->cancelled_at)
                    <div class="sm:col-span-2 rounded-lg bg-rose-50 border border-rose-200 p-3">
                        <dt class="font-semibold text-rose-800 uppercase tracking-wider text-[11px]">Cancellation Information</dt>
                        <dd class="mt-1 text-rose-900">
                            <span>Cancelled on {{ $item->cancelled_at->format('d M Y H:i') }}</span>
                            @if($item->cancelled_reason)
                                <p class="font-medium mt-0.5">Reason: {{ $item->cancelled_reason }}</p>
                            @endif
                        </dd>
                    </div>
                    @endif

                    @if($item->notes)
                    <div class="sm:col-span-2">
                        <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[11px]">Remarks / Notes</dt>
                        <dd class="mt-1 text-slate-700 whitespace-pre-line">{{ $item->notes }}</dd>
                    </div>
                    @endif
                </dl>
            </x-ui.card>
        </div>

        <div class="space-y-6">
            {{-- Bounce & Risk History Card --}}
            <x-ui.card title="Bounce & Risk History">
                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Party Bounce Count:</span>
                        <span class="font-bold font-mono {{ ($item->customer?->cheque_bounce_count ?? 0) > 0 ? 'text-rose-600' : 'text-slate-800' }}">
                            {{ $item->customer?->cheque_bounce_count ?? 0 }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Party Credit Status:</span>
                        <span class="font-semibold uppercase tracking-wider {{ ($item->customer?->credit_status ?? '') === 'frozen' ? 'text-rose-600' : 'text-slate-800' }}">
                            {{ $item->customer?->credit_status ?: 'Normal' }}
                        </span>
                    </div>

                    @forelse($item->bounces as $bounce)
                        <div class="rounded-lg border border-rose-200 bg-rose-50/50 p-3 space-y-1">
                            <div class="flex items-center justify-between font-semibold text-rose-900">
                                <span>Bounce #{{ $bounce->bounce_number }}</span>
                                <span class="font-mono text-[11px]">{{ $bounce->bounced_on ? $bounce->bounced_on->format('d M Y') : '' }}</span>
                            </div>
                            <div class="text-rose-800">{{ $bounce->reason ?: 'No reason stated' }}</div>
                            @if($bounce->charges > 0)
                                <div class="text-[11px] text-rose-700 font-mono">Charges: ₹{{ number_format($bounce->charges, 2) }}</div>
                            @endif
                            @if($bounce->triggered_freeze)
                                <div class="font-bold text-rose-600 text-[11px]">⚠️ Triggered automatic account freeze</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-slate-400 italic">No bounce records for this cheque.</p>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
