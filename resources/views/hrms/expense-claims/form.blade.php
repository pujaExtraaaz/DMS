@extends('layouts.dms')
@section('title', 'New Expense Claim')
@section('content')
<x-ui.page-header title="New Expense Claim">
<x-slot name="actions">
    <x-ui.button variant="secondary" :href="route('hrms.expense-claims.index')">Back to Claims</x-ui.button>
</x-slot>
</x-ui.page-header>

<x-ui.card>
<form method="POST" action="{{ route('hrms.expense-claims.store') }}" enctype="multipart/form-data" class="space-y-4 max-w-xl">
    @csrf

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 p-4 border border-red-200">
            <h4 class="text-sm font-semibold text-red-800">Please correct the following errors:</h4>
            <ul class="mt-2 list-disc list-inside text-xs text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-ui.select name="employee_id" label="Employee" required>
        <option value="">Select Employee</option>
        @foreach($employees as $e)
            <option value="{{ $e->id }}" @selected(old('employee_id') == $e->id)>{{ $e->name }} ({{ $e->employee_code }})</option>
        @endforeach
    </x-ui.select>

    <div class="grid grid-cols-2 gap-4">
        <x-ui.input name="claim_date" label="Claim Date" type="date" :value="old('claim_date', now()->toDateString())" required />
        <x-ui.input name="amount" label="Amount (₹)" type="number" step="0.01" :value="old('amount')" required placeholder="0.00" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Expense Type / Category *</label>
        <input type="text" name="claim_type" value="{{ old('claim_type') }}" required placeholder="e.g. Travel, Food, Lodging, Client Meeting, Fuel" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Attach Receipt / Bill (PDF, JPG, PNG up to 10MB)</label>
        <input type="file" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description / Purpose</label>
        <textarea name="description" rows="3" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Details of the incurred expense...">{{ old('description') }}</textarea>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-ui.button type="submit" variant="primary">Submit Expense Claim</x-ui.button>
        <x-ui.button variant="secondary" :href="route('hrms.expense-claims.index')">Cancel</x-ui.button>
    </div>
</form>
</x-ui.card>
@endsection
