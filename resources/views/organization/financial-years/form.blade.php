@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Financial Year' : 'Create Financial Year')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Financial Year' : 'Create Financial Year'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('organization.financial-years.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
    <form method="POST"
          action="{{ $item->exists ? route('organization.financial-years.update', $item) : route('organization.financial-years.store') }}"
          class="space-y-6 max-w-2xl">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div>
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Financial Year</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <x-ui.select name="company_id" label="Company" required>
                        <option value="">Select company</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}" @selected(old('company_id', $item->company_id)==$c->id)>{{ $c->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="md:col-span-2">
                    <x-ui.input name="name" label="Financial Year Name" value="{{ old('name', $item->name) }}" required maxlength="50" placeholder="e.g. FY 2026–27" />
                </div>
                <x-ui.input name="starts_on" label="Starts On" type="date" value="{{ old('starts_on', optional($item->starts_on)->format('Y-m-d')) }}" required />
                <x-ui.input name="ends_on" label="Ends On" type="date" value="{{ old('ends_on', optional($item->ends_on)->format('Y-m-d')) }}" required />
            </div>
            <p class="mt-2 text-[11px] text-slate-500">End date must be later than start date. Years for the same company cannot overlap.</p>
        </div>

        @if($item->exists)
            <div>
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Status &amp; Controls</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div>
                        <p class="text-xs text-slate-500">Current FY</p>
                        <p class="font-medium text-slate-900">{{ $item->is_current ? 'Yes' : 'No' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Status</p>
                        <p class="font-medium text-slate-900">{{ $item->is_closed ? 'Closed' : 'Open' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Closed (Block Posting)</p>
                        <p class="font-medium text-slate-900">{{ $item->is_closed ? 'Yes' : 'No' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Closing Date</p>
                        <p class="font-medium text-slate-900">{{ $item->closed_at?->format('d M Y H:i') ?: '—' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs text-slate-500">Closed By</p>
                        <p class="font-medium text-slate-900">{{ $item->closedByUser?->name ?: '—' }}</p>
                    </div>
                </div>
                <p class="mt-2 text-[11px] text-slate-500">Use Set Current / Close / Re-open from the list. Closed years cannot be marked current. Closing blocks new postings but historical view/print remains available.</p>
            </div>
        @endif

        <x-ui.button type="submit" variant="primary">Save</x-ui.button>
    </form>
</x-ui.card>
@endsection
