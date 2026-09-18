@extends('layouts.dms')
@section('title', 'Lead '.$lead->name)
@section('content')
<x-ui.page-header :title="$lead->name">
<x-slot name="actions"><x-ui.button variant="secondary" :href="route('crm.leads.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
<x-ui.card title="Lead details" class="xl:col-span-2">
<dl class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
<div><dt class="text-slate-500">Mobile</dt><dd class="font-medium">{{ $lead->mobile ?: '—' }}</dd></div>
<div><dt class="text-slate-500">Email</dt><dd class="font-medium">{{ $lead->email ?: '—' }}</dd></div>
<div><dt class="text-slate-500">Organization</dt><dd class="font-medium">{{ $lead->organization ?: '—' }}</dd></div>
<div><dt class="text-slate-500">City / State</dt><dd class="font-medium">{{ trim(($lead->city ?? '').' / '.($lead->state ?? ''), ' /') ?: '—' }}</dd></div>
<div><dt class="text-slate-500">Status</dt><dd class="font-medium">{{ ucfirst($lead->status) }}</dd></div>
<div><dt class="text-slate-500">Priority</dt><dd class="font-medium">{{ ucfirst($lead->priority ?? 'normal') }}</dd></div>
<div><dt class="text-slate-500">Source</dt><dd class="font-medium">{{ $lead->source?->name ?: '—' }}</dd></div>
<div><dt class="text-slate-500">Campaign</dt><dd class="font-medium">{{ $lead->campaign?->name ?: '—' }}</dd></div>
<div class="md:col-span-2"><dt class="text-slate-500">Notes</dt><dd class="font-medium">{{ $lead->notes ?: '—' }}</dd></div>
</dl>
</x-ui.card>
<div class="space-y-6">
<x-ui.card title="Assign">
<form method="POST" action="{{ route('crm.leads.assign', $lead) }}" class="space-y-3">@csrf
<x-ui.select name="assigned_to" label="Salesperson" required>
<option value="">Select</option>
@foreach($users as $u)<option value="{{ $u->id }}" @selected($lead->assigned_to==$u->id)>{{ $u->name }}</option>@endforeach
</x-ui.select>
<textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm" placeholder="Notes"></textarea>
<x-ui.button type="submit" variant="primary">Assign</x-ui.button>
</form>
</x-ui.card>
@if(!$lead->converted_customer_id)
<x-ui.card title="Convert to Party">
<form method="POST" action="{{ route('crm.leads.convert', $lead) }}" class="space-y-3">@csrf
<x-ui.button type="submit" variant="primary">Convert to Customer</x-ui.button>
</form>
</x-ui.card>
@else
<x-ui.card title="Converted"><p class="text-sm">Customer #{{ $lead->converted_customer_id }} on {{ optional($lead->converted_at)->format('d M Y') }}</p></x-ui.card>
@endif
</div>
</div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
<x-ui.card title="Follow-up">
<form method="POST" action="{{ route('crm.leads.followup', $lead) }}" class="space-y-3">@csrf
<x-ui.select name="channel" label="Channel">
@foreach(['call','whatsapp','email','meeting','note'] as $c)<option value="{{ $c }}">{{ ucfirst($c) }}</option>@endforeach
</x-ui.select>
<x-ui.input name="due_at" type="datetime-local" label="Next follow-up" required />
<textarea name="notes" rows="3" class="block w-full rounded-lg border-gray-300 text-sm" placeholder="Follow-up notes"></textarea>
<x-ui.button type="submit" variant="primary">Log follow-up</x-ui.button>
</form>
<ul class="mt-4 space-y-2 text-sm">
@forelse($lead->followups as $f)
<li class="border-b border-slate-100 pb-2"><span class="font-medium">{{ ucfirst($f->channel ?? 'note') }}</span> · {{ $f->user?->name }} · {{ $f->created_at?->format('d M Y H:i') }}<div class="text-slate-600">{{ $f->notes }}</div></li>
@empty
<li class="text-slate-500">No follow-ups yet.</li>
@endforelse
</ul>
</x-ui.card>
<x-ui.card title="Activity">
<ul class="space-y-2 text-sm">
@forelse($lead->activities as $a)
<li class="border-b border-slate-100 pb-2"><span class="font-medium">{{ $a->activity_type }}</span> · {{ $a->user?->name }} · {{ $a->created_at?->format('d M Y H:i') }}<div class="text-slate-600">{{ $a->body }}</div></li>
@empty
<li class="text-slate-500">No activity.</li>
@endforelse
</ul>
</x-ui.card>
</div>
@endsection
