@extends('layouts.dms')
@section('title', 'OEM Centres')
@section('content')
<div class="space-y-4">
    <x-ui.page-header title="Service Master Settings" />

    <x-ui.card>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold mb-1">Service ticket numbering</h3>
                <p class="text-xs text-slate-500 mb-2">Customize prefix / FY / padding for ticket numbers (e.g. AVITSRV-2627-0001) from Voucher Numbering.</p>
                @if($srvSeries)
                    <p class="text-sm text-slate-700">
                        Series: <strong>{{ $srvSeries->name }}</strong>
                        · Type <code class="text-xs bg-slate-100 px-1 rounded">SRV</code>
                        · Next preview:
                        <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded font-semibold">{{ $srvPreview ?? $srvSeries->preview() }}</code>
                    </p>
                @else
                    <p class="text-sm text-amber-700">No active SRV series found. Create one under Voucher Numbering with document type <strong>Service Ticket (SRV)</strong>.</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                @if($srvSeries)
                    <x-ui.button variant="primary" :href="route('organization.document-number-series.edit', $srvSeries)">Edit SRV series</x-ui.button>
                @endif
                <x-ui.button variant="secondary" :href="route('organization.document-number-series.create', ['document_type' => 'SRV'])">+ Add SRV series</x-ui.button>
                <x-ui.button variant="secondary" :href="route('organization.document-number-series.index', ['document_type' => 'SRV'])">Voucher numbering</x-ui.button>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card>
        <h3 class="text-sm font-semibold mb-3">SLA by priority</h3>
        <p class="text-xs text-slate-500 mb-3">Priorities: Critical, High, Normal, Low. Resolution hours drive ticket SLA due dates.</p>
        <form method="POST" action="{{ route('service.sla.update') }}" class="space-y-2">
            @csrf
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500"><tr>
                    <th class="text-left py-1">Priority</th>
                    <th class="text-left py-1">Resolution hrs</th>
                    <th class="text-left py-1">Escalate after hrs</th>
                    <th class="text-left py-1">Active</th>
                </tr></thead>
                <tbody>
                    @foreach($slaPolicies as $i => $policy)
                        <tr class="border-t border-slate-50">
                            <td class="py-2 capitalize">
                                {{ $policy->priority }}
                                <input type="hidden" name="policies[{{ $i }}][id]" value="{{ $policy->id }}">
                            </td>
                            <td class="py-2"><input type="number" name="policies[{{ $i }}][resolution_hours]" value="{{ $policy->resolution_hours }}" min="1" class="w-24 rounded-lg border-slate-300 text-sm" required></td>
                            <td class="py-2"><input type="number" name="policies[{{ $i }}][escalate_after_hours]" value="{{ $policy->escalate_after_hours }}" min="1" class="w-24 rounded-lg border-slate-300 text-sm"></td>
                            <td class="py-2"><input type="checkbox" name="policies[{{ $i }}][is_active]" value="1" @checked($policy->is_active)></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <x-ui.button type="submit" variant="primary">Save SLA</x-ui.button>
        </form>
    </x-ui.card>

    <div class="grid lg:grid-cols-2 gap-4">
        <x-ui.card>
            <h3 class="text-sm font-semibold mb-3">Add OEM centre</h3>
            <form method="POST" action="{{ route('service.oem-centres.store') }}" class="space-y-3">
                @csrf
                <x-ui.input name="name" label="Name *" required />
                <x-ui.input name="brand" label="Brand" />
                <x-ui.input name="contact_person" label="Contact person" />
                <x-ui.input name="mobile" label="Mobile" />
                <x-ui.input name="email" label="Email" type="email" />
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Address</label>
                    <textarea name="address" rows="2" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
                </div>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <x-ui.button type="submit" variant="primary">Save</x-ui.button>
            </form>
        </x-ui.card>
        <x-ui.card>
            <h3 class="text-sm font-semibold mb-3">Centres</h3>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse($items as $item)
                    <li class="py-3">
                        <form method="POST" action="{{ route('service.oem-centres.update', $item) }}" class="space-y-2">
                            @csrf @method('PUT')
                            <div class="flex justify-between items-start gap-2">
                                <input type="text" name="name" value="{{ $item->name }}" class="rounded-lg border-slate-300 text-sm font-medium flex-1" required>
                                <label class="text-xs flex items-center gap-1"><input type="checkbox" name="is_active" value="1" @checked($item->is_active)> Active</label>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" name="brand" value="{{ $item->brand }}" placeholder="Brand" class="rounded-lg border-slate-300 text-xs">
                                <input type="text" name="contact_person" value="{{ $item->contact_person }}" placeholder="Contact" class="rounded-lg border-slate-300 text-xs">
                                <input type="text" name="mobile" value="{{ $item->mobile }}" placeholder="Mobile" class="rounded-lg border-slate-300 text-xs">
                                <input type="email" name="email" value="{{ $item->email }}" placeholder="Email" class="rounded-lg border-slate-300 text-xs">
                            </div>
                            <textarea name="address" rows="1" class="w-full rounded-lg border-slate-300 text-xs" placeholder="Address">{{ $item->address }}</textarea>
                            <x-ui.button type="submit" variant="secondary">Update</x-ui.button>
                        </form>
                    </li>
                @empty
                    <li class="py-4 text-slate-500">No OEM centres yet.</li>
                @endforelse
            </ul>
            <div class="mt-3">{{ $items->links() }}</div>
        </x-ui.card>
    </div>
</div>
@endsection
