@extends('layouts.dms')
@section('title', 'Holiday Calendar')
@section('content')
@include('ess.layout-nav')
<form method="GET" class="mb-4"><input type="number" name="year" value="{{ $year }}" class="rounded-lg border-slate-300 text-sm"><button class="ml-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">Year</button></form>
@forelse($calendars as $calendar)
<x-ui.card :title="$calendar->name" class="mb-4">
<ul class="divide-y text-sm">@foreach($calendar->holidays as $h)<li class="flex justify-between py-2"><span>{{ $h->name }} <span class="text-slate-400">({{ $h->kind }})</span></span><span>{{ $h->holiday_date->format('d M Y') }}</span></li>@endforeach</ul>
</x-ui.card>
@empty
<p class="text-slate-500">No published holiday calendar for {{ $year }}.</p>
@endforelse
@endsection
