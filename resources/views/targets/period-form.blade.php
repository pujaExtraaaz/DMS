@extends('layouts.dms')
@section('title', 'Create Target Period')
@section('content')
<x-ui.page-header title="Create Target Period"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('targets.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('targets.periods.store') }}" class="space-y-4 max-w-xl">@csrf
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.select name="period_type" label="Type" required>
@foreach(['monthly','quarterly','annual'] as $t)
<option value="{{ $t }}" @selected(old('period_type', $item->period_type)==$t)>{{ ucfirst($t) }}</option>
@endforeach
</x-ui.select>
<div class="grid grid-cols-2 gap-3">
<x-ui.input name="starts_on" type="date" label="Starts" :value="old('starts_on', optional($item->starts_on)->format('Y-m-d') ?? $item->starts_on)" required />
<x-ui.input name="ends_on" type="date" label="Ends" :value="old('ends_on', optional($item->ends_on)->format('Y-m-d') ?? $item->ends_on)" required />
</div>
<x-ui.button type="submit" variant="primary">Save</x-ui.button>
</form>
</x-ui.card>
@endsection
