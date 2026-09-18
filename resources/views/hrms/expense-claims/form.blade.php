@extends('layouts.dms')
@section('title', 'New Expense Claim')
@section('content')
<x-ui.page-header title="New Expense Claim"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('hrms.expense-claims.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ route('hrms.expense-claims.store') }}" class="space-y-4 max-w-xl">@csrf
<x-ui.select name="employee_id" label="Employee" required>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="claim_date" label="Date" type="date" :value="old('claim_date', now()->toDateString())" required />
<x-ui.input name="claim_type" label="Claim Type" :value="old('claim_type')" required />
<x-ui.input name="amount" label="Amount" type="number" step="0.01" :value="old('amount')" required />
<div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('description') }}</textarea></div>
<x-ui.button type="submit" variant="primary">Submit</x-ui.button></form></x-ui.card>
@endsection
