@extends('layouts.dms')
@section('title', $calendar->name)
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('hrms.holidays.index') }}" class="text-sm text-indigo-600 hover:underline">← Calendars</a>
        <h1 class="text-xl font-bold text-slate-900">{{ $calendar->name }} ({{ $calendar->year }})</h1>
        <p class="text-sm text-slate-500">{{ $calendar->branch?->name ?? 'All branches' }} · {{ $calendar->is_published ? 'Published' : 'Draft' }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('hrms.holidays.edit', $calendar) }}" class="rounded-lg border px-3 py-2 text-sm">Edit</a>
        @unless($calendar->is_published)
            <form method="POST" action="{{ route('hrms.holidays.publish', $calendar) }}">@csrf<button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">Publish to ESS</button></form>
        @endunless
    </div>
</div>

<div class="mb-6 grid gap-6 lg:grid-cols-2">
    <x-ui.card title="Add holiday">
        <form method="POST" action="{{ route('hrms.holidays.items.store', $calendar) }}" class="space-y-3">
            @csrf
            <x-ui.input name="holiday_date" label="Date" type="date" required />
            <x-ui.input name="name" label="Name" required />
            <x-ui.select name="category" label="Category">
                @foreach(['national','state','festival','other'] as $cat)
                    <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="kind" label="Kind">
                <option value="compulsory">Compulsory</option>
                <option value="optional">Optional</option>
                <option value="restricted">Restricted</option>
            </x-ui.select>
            <x-ui.button type="submit" variant="primary">Add</x-ui.button>
        </form>
    </x-ui.card>
    <x-ui.card title="Holiday list">
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse($calendar->holidays as $h)
                <li class="flex items-center justify-between gap-2 py-2">
                    <div>
                        <div class="font-medium">{{ $h->name }}</div>
                        <div class="text-xs text-slate-500">{{ $h->holiday_date->format('d M Y') }} · {{ $h->category }} · {{ $h->kind }}</div>
                    </div>
                    <form method="POST" action="{{ route('hrms.holidays.items.destroy', $h) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')
                        <button class="text-xs text-rose-600">Delete</button>
                    </form>
                </li>
            @empty
                <li class="py-6 text-center text-slate-500">No holidays yet.</li>
            @endforelse
        </ul>
    </x-ui.card>
</div>
@endsection
