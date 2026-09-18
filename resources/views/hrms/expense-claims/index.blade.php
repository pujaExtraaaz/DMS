@extends('layouts.dms')
@section('title', 'Expense Claims')
@section('content')
<x-ui.page-header title="Expense Claims"><x-slot name="actions"><x-ui.button variant="primary" :href="route('hrms.expense-claims.create')">New Claim</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Employee</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Type</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Amount</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th><th></th></tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr>
<td class="px-3 py-2">{{ $item->claim_date->format('d M Y') }}</td>
<td class="px-3 py-2">{{ $item->employee?->name }}</td>
<td class="px-3 py-2">{{ $item->claim_type }}</td>
<td class="px-3 py-2">₹{{ number_format($item->amount, 2) }}</td>
<td class="px-3 py-2"><x-ui.badge>{{ ucfirst($item->status) }}</x-ui.badge></td>
<td class="px-3 py-2 text-right whitespace-nowrap">
@if($item->status==='pending')
<form method="POST" action="{{ route('hrms.expense-claims.approve', $item) }}" class="inline">@csrf<x-ui.button type="submit" size="sm" variant="primary">Approve</x-ui.button></form>
<form method="POST" action="{{ route('hrms.expense-claims.reject', $item) }}" class="inline">@csrf<x-ui.button type="submit" size="sm" variant="secondary">Reject</x-ui.button></form>
@endif
</td>
</tr>
@empty<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No claims.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $items->links() }}</div></x-ui.card>
@endsection
