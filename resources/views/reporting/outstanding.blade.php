@extends('layouts.dms')
@section('title', 'Outstanding Report')
@section('content')
<x-ui.page-header title="Outstanding Report" />
<x-ui.card><x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Balance</th></tr></x-slot>
@forelse($balances as $row)<tr><td class="px-6 py-4 text-sm">{{ $row['customer']->name }}</td><td class="px-6 py-4 text-sm text-right font-medium">₹{{ number_format($row['balance'], 2) }}</td></tr>@empty<tr><td colspan="2" class="px-6 py-8"><x-ui.empty-state title="No outstanding balances" /></td></tr>@endforelse</x-ui.table></x-ui.card>
@endsection
