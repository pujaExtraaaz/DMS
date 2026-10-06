@extends('layouts.dms')
@section('title', 'Expense Claims')
@section('content')
<x-ui.page-header title="Expense Claims">
<x-slot name="actions">
    <x-ui.button variant="primary" :href="route('hrms.expense-claims.create')">+ New Claim</x-ui.button>
</x-slot>
</x-ui.page-header>

<x-ui.card class="mb-6">
    <form method="GET" action="{{ route('hrms.expense-claims.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Status</label>
            <select name="status" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
                <option value="">All Statuses</option>
                @foreach(['pending', 'approved', 'settled', 'rejected'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Employee</label>
            <select name="employee_id" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
                <option value="">All Employees</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" @selected(request('employee_id') == $e->id)>{{ $e->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-ui.button type="submit" variant="secondary" class="w-full">Filter</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Employee</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Type</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Claim Amount</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold uppercase text-slate-500">Receipt</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold uppercase text-slate-500">Status</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Actions / Settlement</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-3 py-2 font-medium text-slate-700 whitespace-nowrap">{{ $item->claim_date->format('d M Y') }}</td>
                        <td class="px-3 py-2">
                            <a href="{{ route('hrms.employees.show', $item->employee_id) }}" class="font-medium text-indigo-600 hover:underline">{{ $item->employee?->name }}</a>
                            <div class="text-xs text-slate-400 font-mono">{{ $item->employee?->employee_code }}</div>
                        </td>
                        <td class="px-3 py-2">
                            <span class="font-medium text-slate-800">{{ $item->claim_type }}</span>
                            @if($item->description)
                                <div class="text-xs text-slate-500 truncate max-w-xs" title="{{ $item->description }}">{{ $item->description }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right font-mono font-semibold text-slate-800">₹{{ number_format($item->amount, 2) }}</td>
                        <td class="px-3 py-2 text-center">
                            @if($item->receipt_path)
                                <a href="{{ route('hrms.expense-claims.receipt', $item) }}" class="inline-flex items-center text-xs font-medium text-indigo-600 hover:underline">
                                    View Receipt
                                </a>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            @php
                                $badgeCls = match($item->status) {
                                    'approved' => 'bg-indigo-100 text-indigo-800',
                                    'settled' => 'bg-emerald-100 text-emerald-800',
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    default => 'bg-slate-100 text-slate-800',
                                };
                            @endphp
                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $badgeCls }}">
                                {{ ucfirst($item->status) }}
                            </span>
                            @if($item->status === 'settled')
                                <div class="text-[10px] text-slate-500 mt-0.5">₹{{ number_format($item->settlement_amount, 2) }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right whitespace-nowrap space-x-1">
                            @if($item->status === 'pending')
                                <form method="POST" action="{{ route('hrms.expense-claims.approve', $item) }}" class="inline">
                                    @csrf
                                    <x-ui.button type="submit" size="sm" variant="primary">Approve</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('hrms.expense-claims.reject', $item) }}" class="inline">
                                    @csrf
                                    <x-ui.button type="submit" size="sm" variant="secondary">Reject</x-ui.button>
                                </form>
                            @elseif($item->status === 'approved')
                                <details class="inline-block text-left">
                                    <summary class="cursor-pointer text-xs font-semibold text-emerald-600 hover:text-emerald-800 py-1 px-2 rounded border border-emerald-300 bg-emerald-50">Settle</summary>
                                    <div class="absolute right-4 mt-2 w-72 p-4 bg-white rounded-lg shadow-lg border border-slate-200 z-20 text-left">
                                        <form method="POST" action="{{ route('hrms.expense-claims.settle', $item) }}" class="space-y-3">
                                            @csrf
                                            <div class="text-xs font-semibold text-slate-700">Record Settlement</div>
                                            <div>
                                                <label class="block text-[11px] font-medium text-slate-600 mb-0.5">Amount (₹) *</label>
                                                <input type="number" step="0.01" name="settlement_amount" value="{{ $item->amount }}" required class="block w-full text-xs rounded border-gray-300">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-medium text-slate-600 mb-0.5">Reference / UTR</label>
                                                <input type="text" name="settlement_reference" placeholder="e.g. TXN123456" class="block w-full text-xs rounded border-gray-300">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-medium text-slate-600 mb-0.5">Notes</label>
                                                <input type="text" name="settlement_notes" placeholder="Optional notes" class="block w-full text-xs rounded border-gray-300">
                                            </div>
                                            <x-ui.button type="submit" size="sm" variant="primary" class="w-full bg-emerald-600 hover:bg-emerald-700">Confirm Settlement</x-ui.button>
                                        </form>
                                    </div>
                                </details>
                            @elseif($item->status === 'settled')
                                <span class="text-xs text-slate-400">Ref: {{ $item->settlement_reference ?: '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-slate-500">No expense claims found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
