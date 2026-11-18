@extends('layouts.dms')
@section('title', $item->exists ? 'Payroll · Edit Pay Head' : 'Payroll · Create Pay Head')
@section('content')
<x-hrms.payroll-nav active="heads" />

<x-ui.card :title="$item->exists ? 'Edit Pay Head' : 'Create Pay Head'">
    <div class="mb-4">
        <a href="{{ route('hrms.pay-heads.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">← Back to pay heads</a>
    </div>

    @if($item->exists && $item->is_system)
        <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
            This is a standard pay head. Code and type are locked; you can still rename it or deactivate it.
        </p>
    @endif

    <form method="POST" action="{{ $item->exists ? route('hrms.pay-heads.update', $item) : route('hrms.pay-heads.store') }}" class="space-y-4 max-w-xl">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <x-ui.select name="company_id" label="Company" :disabled="$item->exists && $item->is_system">
            <option value="">—</option>
            @foreach($companies as $c)
                <option value="{{ $c->id }}" @selected(old('company_id', $item->company_id)==$c->id)>{{ $c->name }}</option>
            @endforeach
        </x-ui.select>

        <x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />

        <x-ui.input
            name="code"
            label="Code"
            :value="old('code', $item->code)"
            :disabled="$item->exists && $item->is_system"
            help="Optional short code, e.g. CONV for conveyance."
        />

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select name="type"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @disabled($item->exists && $item->is_system)>
                @foreach(['earning' => 'Earning', 'deduction' => 'Deduction'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $item->type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <x-ui.input name="sort_order" label="Sort Order" type="number" :value="old('sort_order', $item->sort_order ?? 100)" />

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600">
            Active
        </label>

        <x-ui.button type="submit" variant="primary">Save Pay Head</x-ui.button>
    </form>
</x-ui.card>
@endsection
