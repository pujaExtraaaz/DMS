@extends('layouts.dms')
@section('title', 'Assign Target')
@section('content')
<x-ui.page-header :title="'Assign Target — '.$period->name"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('targets.periods.show', $period)">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('targets.store', $period) }}" class="space-y-4 max-w-xl">@csrf
<x-ui.select name="customer_id" label="Party"><option value="">Optional</option>@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="salesperson_id" label="Salesperson"><option value="">Optional</option>@foreach($salespeople as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="brand_id" label="Brand"><option value="">Optional</option>@foreach($brands as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="amount" type="number" step="0.01" label="Target Amount" required />
<x-ui.button type="submit" variant="primary">Save</x-ui.button>
</form>
</x-ui.card>
@endsection
