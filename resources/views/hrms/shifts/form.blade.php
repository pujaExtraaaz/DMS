@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Shift' : 'Create Shift')
@section('content')
<x-ui.card :title="$item->exists ? 'Edit Shift' : 'Create Shift'">
<form method="POST" action="{{ $item->exists ? route('hrms.shifts.update', $item) : route('hrms.shifts.store') }}" class="max-w-xl space-y-4">
@csrf @if($item->exists) @method('PUT') @endif
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.input name="code" label="Code" :value="old('code', $item->code)" />
<div class="grid grid-cols-2 gap-4">
<x-ui.input name="start_time" label="Start" type="time" :value="old('start_time', substr((string)$item->start_time,0,5))" required />
<x-ui.input name="end_time" label="End" type="time" :value="old('end_time', substr((string)$item->end_time,0,5))" required />
<x-ui.input name="grace_minutes" label="Grace minutes" type="number" :value="old('grace_minutes', $item->grace_minutes)" />
<x-ui.input name="break_minutes" label="Break minutes" type="number" :value="old('break_minutes', $item->break_minutes)" />
<x-ui.input name="half_day_minutes" label="Half-day minutes" type="number" :value="old('half_day_minutes', $item->half_day_minutes)" />
<x-ui.input name="full_day_minutes" label="Full-day minutes" type="number" :value="old('full_day_minutes', $item->full_day_minutes)" />
</div>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))> Active</label>
<x-ui.button type="submit" variant="primary">Save</x-ui.button>
</form>
</x-ui.card>
@endsection
