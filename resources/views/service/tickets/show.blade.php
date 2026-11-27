@extends('layouts.dms')
@section('title', $ticket->ticket_no)
@section('content')
@php
    $isOverdue = $ticket->sla_due_at && $ticket->sla_due_at->isPast() && !in_array($ticket->status, ['closed','cancelled']);
@endphp
<div class="space-y-4">
    <x-ui.page-header :title="$ticket->ticket_no">
        <x-slot name="actions">
            <x-ui.button :href="route('service.tickets.edit', $ticket)" variant="secondary">Edit</x-ui.button>
            <x-ui.button :href="route('service.tickets.index')" variant="secondary">Register</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <x-ui.card>
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <div class="text-xs text-slate-500 uppercase tracking-wide">{{ ucfirst($ticket->request_type) }} · {{ str_replace('_',' ',$ticket->party_type) }}</div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $ticket->customer_name }}</h2>
                        <p class="text-sm text-slate-600">{{ $ticket->contact_person }} {{ $ticket->contact_mobile }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium bg-slate-100 capitalize">{{ $ticket->status }}</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $ticket->priority === 'critical' ? 'bg-red-100 text-red-800' : 'bg-amber-50 text-amber-800' }}">{{ ucfirst($ticket->priority) }}</span>
                        @if($isOverdue)<span class="rounded-full px-2.5 py-1 text-xs font-medium bg-red-600 text-white">SLA Overdue</span>@endif
                    </div>
                </div>
                <dl class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-500">Brand / Model</dt><dd>{{ $ticket->brand }} {{ $ticket->model_number }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Serial</dt><dd>{{ $ticket->serial_no ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Warranty</dt><dd class="capitalize">{{ str_replace('_',' ',$ticket->warranty_status) }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Invoice</dt><dd>{{ $ticket->invoice_no ?: '—' }} {{ $ticket->invoice_date?->format('d M Y') }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Technician</dt><dd>{{ $ticket->assignedEmployee?->name ?? 'Unassigned' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">OEM</dt><dd>{{ $ticket->oemCentre?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Expected visit</dt><dd>{{ $ticket->expected_visit_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">SLA due</dt><dd class="{{ $isOverdue ? 'text-red-600 font-semibold' : '' }}">{{ $ticket->sla_due_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Handler</dt><dd class="uppercase">{{ $ticket->handler_type }}</dd></div>
                </dl>
                @if($ticket->problem_description)
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <h4 class="text-xs font-semibold text-slate-500 uppercase mb-1">Problem / Request</h4>
                        <p class="text-sm text-slate-800 whitespace-pre-wrap">{{ $ticket->problem_description }}</p>
                    </div>
                @endif
                @if($ticket->site_address || $ticket->billing_address)
                    <div class="mt-3 grid md:grid-cols-2 gap-3 text-sm">
                        @if($ticket->billing_address)<div><span class="text-xs text-slate-500">Billing</span><p>{{ $ticket->billing_address }}</p></div>@endif
                        @if($ticket->site_address)<div><span class="text-xs text-slate-500">Site</span><p>{{ $ticket->site_address }}</p></div>@endif
                    </div>
                @endif
            </x-ui.card>

            {{-- Assign --}}
            <x-ui.card>
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Technician / OEM Assignment</h3>
                <form method="POST" action="{{ route('service.tickets.assign', $ticket) }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-medium text-slate-600">Avit technician</label>
                            <div class="flex items-center gap-2">
                                <button type="button" id="btn-create-technician" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">+ Create</button>
                                <button type="button" id="btn-delete-technician" class="text-xs text-red-600 hover:text-red-800 font-semibold">Delete</button>
                            </div>
                        </div>
                        <select name="employee_id" id="assign-technician-select" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">—</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" @selected($ticket->assigned_employee_id == $emp->id)>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-medium text-slate-600">OEM centre</label>
                            <div class="flex items-center gap-2">
                                <button type="button" id="btn-create-oem" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">+ Create</button>
                                <button type="button" id="btn-delete-oem" class="text-xs text-red-600 hover:text-red-800 font-semibold">Delete</button>
                            </div>
                        </div>
                        <select name="oem_centre_id" id="assign-oem-select" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">—</option>
                            @foreach($oemCentres as $o)
                                <option value="{{ $o->id }}" @selected($ticket->oem_centre_id == $o->id)>{{ $o->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ui.input name="expected_visit_at" label="Expected visit" type="datetime-local" :value="$ticket->expected_visit_at?->format('Y-m-d\TH:i')" />
                    <x-ui.input name="notes" label="Notes" />
                    <div class="md:col-span-2"><x-ui.button type="submit" variant="primary">Assign</x-ui.button></div>
                </form>
            </x-ui.card>

            {{-- Status --}}
            <x-ui.card>
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Status workflow</h3>
                <form method="POST" action="{{ route('service.tickets.transition', $ticket) }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Move to</label>
                        <select name="status" class="w-full rounded-lg border-slate-300 text-sm" required>
                            @foreach(\App\Domains\Service\Models\ServiceTicket::STATUSES as $s)
                                <option value="{{ $s }}" @selected($ticket->status === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ui.input name="notes" label="Notes" />
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Completion remarks (required to close)</label>
                        <textarea name="completion_remarks" rows="2" class="w-full rounded-lg border-slate-300 text-sm">{{ $ticket->completion_remarks }}</textarea>
                    </div>
                    <div><x-ui.button type="submit" variant="primary">Update status</x-ui.button></div>
                </form>
            </x-ui.card>

            {{-- Visit report --}}
            <x-ui.card>
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Visit reports</h3>
                @forelse($ticket->visits as $visit)
                    <div class="mb-3 rounded-lg border border-slate-100 p-3 text-sm">
                        <div class="flex justify-between text-xs text-slate-500 mb-1">
                            <span>{{ $visit->visited_at?->format('d M Y H:i') }} · {{ $visit->employee?->name }}</span>
                            @if($visit->next_visit_required)<span class="text-amber-600">Next visit {{ $visit->next_visit_at?->format('d M Y') }}</span>@endif
                        </div>
                        <p><strong>Action:</strong> {{ $visit->action_taken }}</p>
                        @if($visit->problem_identified)<p class="text-slate-600">{{ $visit->problem_identified }}</p>@endif
                        @if($visit->parts_replaced)<p class="text-xs mt-1">Parts: {{ $visit->parts_replaced }}</p>@endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500 mb-3">No visits logged yet.</p>
                @endforelse
                <form method="POST" action="{{ route('service.tickets.visits.store', $ticket) }}" enctype="multipart/form-data" class="space-y-3 border-t border-slate-100 pt-3">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Technician</label>
                            <select name="employee_id" class="w-full rounded-lg border-slate-300 text-sm">
                                <option value="">—</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" @selected($ticket->assigned_employee_id == $emp->id)>{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-ui.input name="visited_at" label="Visited at" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" />
                    </div>
                    <textarea name="customer_complaint" rows="2" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Customer complaint"></textarea>
                    <textarea name="problem_identified" rows="2" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Problem identified"></textarea>
                    <textarea name="action_taken" rows="2" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Action taken *" required></textarea>
                    <textarea name="parts_replaced" rows="1" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Parts replaced"></textarea>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="text-xs text-slate-600">Before photo</label><input type="file" name="before_photo" accept="image/*" class="text-xs"></div>
                        <div><label class="text-xs text-slate-600">After photo</label><input type="file" name="after_photo" accept="image/*" class="text-xs"></div>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="next_visit_required" value="1"> Next visit required</label>
                    <x-ui.input name="next_visit_at" label="Next visit at" type="datetime-local" />
                    <textarea name="customer_signature" rows="1" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Customer signature (base64 or name)"></textarea>
                    <x-ui.button type="submit" variant="primary">Save visit report</x-ui.button>
                </form>
            </x-ui.card>

            @if($ticket->request_type === 'installation')
            <x-ui.card>
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Installation details</h3>
                <form method="POST" action="{{ route('service.tickets.installation', $ticket) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Type</label>
                        <select name="installation_type" class="w-full rounded-lg border-slate-300 text-sm" required>
                            @foreach(['free','chargeable','oem','avit_digital'] as $it)
                                <option value="{{ $it }}" @selected(($ticket->installation?->installation_type ?? 'free') === $it)>{{ str_replace('_',' ',ucfirst($it)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ui.input name="project_name" label="Project" :value="$ticket->installation?->project_name" />
                    <x-ui.input name="quotation_amount" label="Quotation" type="number" step="0.01" :value="$ticket->installation?->quotation_amount" />
                    <x-ui.input name="installation_charges" label="Charges" type="number" step="0.01" :value="$ticket->installation?->installation_charges" />
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Site address</label>
                        <textarea name="site_address" rows="2" class="w-full rounded-lg border-slate-300 text-sm">{{ $ticket->installation?->site_address ?? $ticket->site_address }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Material</label>
                        <textarea name="material_consumed" rows="2" class="w-full rounded-lg border-slate-300 text-sm">{{ $ticket->installation?->material_consumed }}</textarea>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="customer_approved" value="1" @checked($ticket->installation?->customer_approved)> Customer approved quote</label>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="customer_acknowledged" value="1" @checked($ticket->installation?->customer_acknowledged)> Customer acknowledgement (required to close)</label>
                    <div><label class="text-xs text-slate-600">Completion certificate</label><input type="file" name="completion_certificate" class="text-xs"></div>
                    <textarea name="customer_signature" rows="1" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Sign-off / signature">{{ $ticket->installation?->customer_signature }}</textarea>
                    <div class="md:col-span-2"><x-ui.button type="submit" variant="primary">Save installation</x-ui.button></div>
                </form>
            </x-ui.card>
            @endif

            {{-- Charges --}}
            <x-ui.card>
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Charges &amp; approval</h3>
                @php $ch = $ticket->charges; @endphp
                <form method="POST" action="{{ route('service.tickets.charges', $ticket) }}" class="space-y-3">
                    @csrf
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_paid_service" value="1" @checked($ch?->is_paid_service)> Paid / chargeable service</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <x-ui.input name="technician_charges" label="Technician charges" type="number" step="0.01" :value="$ch?->technician_charges" />
                        <x-ui.input name="transportation_charges" label="Transport" type="number" step="0.01" :value="$ch?->transportation_charges" />
                        <x-ui.input name="paid_amount" label="Paid amount" type="number" step="0.01" :value="$ch?->paid_amount" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Payment status</label>
                            <select name="payment_status" class="w-full rounded-lg border-slate-300 text-sm">
                                @foreach(['pending','partial','paid','waived'] as $ps)
                                    <option value="{{ $ps }}" @selected(($ch?->payment_status ?? 'pending') === $ps)>{{ ucfirst($ps) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm pt-6"><input type="checkbox" name="customer_approved" value="1" @checked($ch?->customer_approved)> Customer approved charges</label>
                    </div>
                    <div class="space-y-2">
                        <p class="text-xs font-semibold text-slate-600">Spare / labour lines</p>
                        @for($i = 0; $i < 3; $i++)
                            @php $line = $ch?->lines[$i] ?? null; @endphp
                            <div class="grid grid-cols-4 gap-2">
                                <select name="lines[{{ $i }}][line_type]" class="rounded-lg border-slate-300 text-sm">
                                    @foreach(['spare','labour','other'] as $lt)
                                        <option value="{{ $lt }}" @selected(($line->line_type ?? 'spare') === $lt)>{{ ucfirst($lt) }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="lines[{{ $i }}][item_name]" value="{{ $line->item_name ?? '' }}" placeholder="Item" class="rounded-lg border-slate-300 text-sm">
                                <input type="number" step="0.01" name="lines[{{ $i }}][quantity]" value="{{ $line->quantity ?? '' }}" placeholder="Qty" class="rounded-lg border-slate-300 text-sm">
                                <input type="number" step="0.01" name="lines[{{ $i }}][rate]" value="{{ $line->rate ?? '' }}" placeholder="Rate" class="rounded-lg border-slate-300 text-sm">
                            </div>
                        @endfor
                    </div>
                    @if($ch)
                        <p class="text-sm text-slate-600">Grand total: <strong>₹{{ number_format((float)$ch->grand_total, 2) }}</strong>
                            · Outstanding: <strong>₹{{ number_format(max(0, (float)$ch->grand_total - (float)$ch->paid_amount), 2) }}</strong>
                        </p>
                    @endif
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button type="submit" variant="primary">Save charges</x-ui.button>
                    </div>
                </form>
                @if($ch?->is_paid_service && $ch?->customer_approved)
                    <form method="POST" action="{{ route('service.tickets.invoice', $ticket) }}" class="mt-3">
                        @csrf
                        @if($ticket->invoice_id)
                            <a href="{{ route('invoices.show', $ticket->invoice_id) }}" class="text-sm text-indigo-600 underline">Open linked invoice</a>
                        @else
                            <x-ui.button type="submit" variant="secondary">Create draft service invoice</x-ui.button>
                        @endif
                    </form>
                @endif
            </x-ui.card>

            {{-- Warranty --}}
            <x-ui.card>
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Warranty / OEM claim</h3>
                <form method="POST" action="{{ route('service.tickets.warranty', $ticket) }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">OEM centre</label>
                        <select name="oem_centre_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">—</option>
                            @foreach($oemCentres as $o)
                                <option value="{{ $o->id }}" @selected(($ticket->warranty?->oem_centre_id ?? $ticket->oem_centre_id) == $o->id)>{{ $o->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">OEM status</label>
                        <select name="oem_status" class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach(['pending','approved','rejected','closed'] as $os)
                                <option value="{{ $os }}" @selected(($ticket->warranty?->oem_status ?? 'pending') === $os)>{{ ucfirst($os) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ui.input name="warranty_claim_no" label="Claim no" :value="$ticket->warranty?->warranty_claim_no" />
                    <x-ui.input name="oem_ticket_no" label="OEM ticket no" :value="$ticket->warranty?->oem_ticket_no" />
                    <div class="md:col-span-2"><textarea name="parts_covered" rows="2" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Parts covered">{{ $ticket->warranty?->parts_covered }}</textarea></div>
                    <div class="md:col-span-2"><textarea name="notes" rows="2" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Notes">{{ $ticket->warranty?->notes }}</textarea></div>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="chargeable" value="1" @checked($ticket->warranty?->chargeable)> Chargeable to customer</label>
                    <div class="md:col-span-2"><x-ui.button type="submit" variant="primary">Save warranty</x-ui.button></div>
                </form>
            </x-ui.card>

            {{-- Attachments --}}
            <x-ui.card>
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Attachments</h3>
                <ul class="text-sm space-y-1 mb-3">
                    @forelse($ticket->attachments as $att)
                        <li><a href="{{ $att->url() }}" target="_blank" class="text-indigo-600 hover:underline">{{ $att->original_name ?? basename($att->path) }}</a> <span class="text-xs text-slate-400">({{ $att->kind }})</span></li>
                    @empty
                        <li class="text-slate-500">None yet.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('service.tickets.attachments', $ticket) }}" enctype="multipart/form-data" class="flex flex-wrap gap-2 items-end">
                    @csrf
                    <input type="file" name="attachment" required class="text-sm">
                    <select name="kind" class="rounded-lg border-slate-300 text-sm">
                        @foreach(['photo','video','invoice','other'] as $k)
                            <option value="{{ $k }}">{{ ucfirst($k) }}</option>
                        @endforeach
                    </select>
                    <x-ui.button type="submit" variant="secondary">Upload</x-ui.button>
                </form>
            </x-ui.card>

            @if($ticket->status === 'closed')
            <x-ui.card>
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Customer feedback</h3>
                @if($ticket->feedback_rating)
                    <p class="text-sm">Rating: <strong>{{ $ticket->feedback_rating }}/5</strong></p>
                    <p class="text-sm text-slate-600">{{ $ticket->feedback_comments }}</p>
                @else
                    <form method="POST" action="{{ route('service.tickets.feedback', $ticket) }}" class="flex flex-wrap gap-3 items-end">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Rating 1–5</label>
                            <select name="feedback_rating" class="rounded-lg border-slate-300 text-sm" required>
                                @for($r=1;$r<=5;$r++)<option value="{{ $r }}">{{ $r }}</option>@endfor
                            </select>
                        </div>
                        <x-ui.input name="feedback_comments" label="Comments" />
                        <x-ui.button type="submit" variant="primary">Submit</x-ui.button>
                    </form>
                @endif
            </x-ui.card>
            @endif
        </div>

        <div class="space-y-4">
            <x-ui.card>
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Timeline</h3>
                <ul class="space-y-3 text-sm">
                    @forelse($ticket->activities as $act)
                        <li class="border-l-2 border-indigo-200 pl-3">
                            <div class="text-xs text-slate-400">{{ $act->created_at?->format('d M Y H:i') }} · {{ $act->user?->name ?? 'System' }}</div>
                            <div class="font-medium capitalize">{{ str_replace('_',' ',$act->event) }}</div>
                            @if($act->from_status || $act->to_status)
                                <div class="text-xs text-slate-500">{{ $act->from_status }} → {{ $act->to_status }}</div>
                            @endif
                            @if($act->notes)<div class="text-slate-600">{{ $act->notes }}</div>@endif
                        </li>
                    @empty
                        <li class="text-slate-500">No activity yet.</li>
                    @endforelse
                </ul>
            </x-ui.card>
            <x-ui.card>
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Assignment history</h3>
                <ul class="text-sm space-y-2">
                    @forelse($ticket->assignments as $a)
                        <li>{{ $a->created_at?->format('d M Y') }} — {{ $a->employee?->name ?? '—' }} / {{ $a->oemCentre?->name ?? '—' }}</li>
                    @empty
                        <li class="text-slate-500">None</li>
                    @endforelse
                </ul>
            </x-ui.card>
        </div>
    </div>
</div>

{{-- Theme-matched quick-create modal --}}
<div id="service-quick-create-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" data-modal-dismiss></div>
    <div class="relative z-10 flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="sqc-title">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h3 id="sqc-title" class="text-base font-semibold text-slate-800">Create</h3>
                    <p id="sqc-subtitle" class="mt-0.5 text-xs text-slate-500">Add a new record without leaving this ticket.</p>
                </div>
                <button type="button" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" data-modal-dismiss aria-label="Close">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form id="sqc-form" class="space-y-4 px-5 py-4">
                <div id="sqc-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"></div>
                <div>
                    <label for="sqc-name" id="sqc-name-label" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-700">Name *</label>
                    <input type="text" id="sqc-name" name="name" required autocomplete="off"
                           class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Enter name">
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" data-modal-dismiss
                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" id="sqc-submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                        <span data-label>Create</span>
                        <span data-busy class="hidden">Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Theme-matched quick-delete confirm modal --}}
<div id="service-quick-delete-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" data-delete-dismiss></div>
    <div class="relative z-10 flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="sqd-title">
            <div class="flex items-start gap-3 px-5 py-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="sqd-title" class="text-base font-semibold text-slate-800">Delete</h3>
                    <p id="sqd-body" class="mt-1 text-sm text-slate-600">Are you sure you want to delete this record?</p>
                    <p id="sqd-error" class="mt-2 hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"></p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                <button type="button" data-delete-dismiss
                        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" id="sqd-confirm"
                        class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                    <span data-label>Delete</span>
                    <span data-busy class="hidden">Deleting…</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const csrf = @json(csrf_token());
    const techUrl = @json(route('service.technicians.quick-store'));
    const oemUrl = @json(route('service.oem-centres.quick-store'));
    const techDestroyTpl = @json(url('service/technicians/__ID__/quick-destroy'));
    const oemDestroyTpl = @json(url('service/oem-centres/__ID__/quick-destroy'));

    const modal = document.getElementById('service-quick-create-modal');
    const form = document.getElementById('sqc-form');
    const nameInput = document.getElementById('sqc-name');
    const titleEl = document.getElementById('sqc-title');
    const subtitleEl = document.getElementById('sqc-subtitle');
    const labelEl = document.getElementById('sqc-name-label');
    const errorEl = document.getElementById('sqc-error');
    const submitBtn = document.getElementById('sqc-submit');

    const delModal = document.getElementById('service-quick-delete-modal');
    const delTitle = document.getElementById('sqd-title');
    const delBody = document.getElementById('sqd-body');
    const delError = document.getElementById('sqd-error');
    const delConfirm = document.getElementById('sqd-confirm');

    if (!modal || !form || !nameInput || !delModal || !delConfirm) return;

    let active = null;
    let deleteActive = null;

    function setBusy(busy) {
        submitBtn.disabled = busy;
        submitBtn.querySelector('[data-label]').classList.toggle('hidden', busy);
        submitBtn.querySelector('[data-busy]').classList.toggle('hidden', !busy);
    }

    function setDeleteBusy(busy) {
        delConfirm.disabled = busy;
        delConfirm.querySelector('[data-label]').classList.toggle('hidden', busy);
        delConfirm.querySelector('[data-busy]').classList.toggle('hidden', !busy);
    }

    function showError(msg) {
        if (!msg) {
            errorEl.classList.add('hidden');
            errorEl.textContent = '';
            return;
        }
        errorEl.textContent = msg;
        errorEl.classList.remove('hidden');
    }

    function showDeleteError(msg) {
        if (!msg) {
            delError.classList.add('hidden');
            delError.textContent = '';
            return;
        }
        delError.textContent = msg;
        delError.classList.remove('hidden');
    }

    function openModal(config) {
        active = config;
        titleEl.textContent = config.title;
        subtitleEl.textContent = config.subtitle;
        labelEl.textContent = config.label;
        nameInput.placeholder = config.placeholder;
        nameInput.value = '';
        showError('');
        setBusy(false);
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => nameInput.focus(), 50);
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        if (delModal.classList.contains('hidden')) {
            document.body.classList.remove('overflow-hidden');
        }
        active = null;
        setBusy(false);
        showError('');
    }

    function openDeleteModal(config) {
        deleteActive = config;
        delTitle.textContent = config.title;
        delBody.textContent = config.body;
        showDeleteError('');
        setDeleteBusy(false);
        delModal.classList.remove('hidden');
        delModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    }

    function closeDeleteModal() {
        delModal.classList.add('hidden');
        delModal.setAttribute('aria-hidden', 'true');
        if (modal.classList.contains('hidden')) {
            document.body.classList.remove('overflow-hidden');
        }
        deleteActive = null;
        setDeleteBusy(false);
        showDeleteError('');
    }

    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', closeModal);
    });
    delModal.querySelectorAll('[data-delete-dismiss]').forEach((el) => {
        el.addEventListener('click', closeDeleteModal);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (!delModal.classList.contains('hidden')) closeDeleteModal();
        else if (!modal.classList.contains('hidden')) closeModal();
    });

    document.getElementById('btn-create-technician')?.addEventListener('click', () => {
        openModal({
            title: 'New Avit technician',
            subtitle: 'Creates an employee record and selects them for this ticket.',
            label: 'Technician name *',
            placeholder: 'e.g. Ravi Kumar',
            url: techUrl,
            selectId: 'assign-technician-select',
            failLabel: 'Could not create technician',
        });
    });

    document.getElementById('btn-create-oem')?.addEventListener('click', () => {
        openModal({
            title: 'New OEM centre',
            subtitle: 'Creates an OEM centre and selects it for this ticket.',
            label: 'OEM centre name *',
            placeholder: 'e.g. Sony Authorised Service',
            url: oemUrl,
            selectId: 'assign-oem-select',
            failLabel: 'Could not create OEM centre',
        });
    });

    document.getElementById('btn-delete-technician')?.addEventListener('click', () => {
        const sel = document.getElementById('assign-technician-select');
        const id = sel?.value;
        if (!id) {
            openDeleteModal({
                title: 'Delete technician',
                body: 'Select a technician in the dropdown first, then click Delete.',
                url: null,
                selectId: 'assign-technician-select',
            });
            return;
        }
        const name = sel.options[sel.selectedIndex]?.text || 'this technician';
        openDeleteModal({
            title: 'Delete technician',
            body: `Delete "${name}"? They will be unassigned from open service tickets.`,
            url: techDestroyTpl.replace('__ID__', id),
            selectId: 'assign-technician-select',
            id,
        });
    });

    document.getElementById('btn-delete-oem')?.addEventListener('click', () => {
        const sel = document.getElementById('assign-oem-select');
        const id = sel?.value;
        if (!id) {
            openDeleteModal({
                title: 'Delete OEM centre',
                body: 'Select an OEM centre in the dropdown first, then click Delete.',
                url: null,
                selectId: 'assign-oem-select',
            });
            return;
        }
        const name = sel.options[sel.selectedIndex]?.text || 'this OEM centre';
        openDeleteModal({
            title: 'Delete OEM centre',
            body: `Delete "${name}"? It will be cleared from linked service tickets.`,
            url: oemDestroyTpl.replace('__ID__', id),
            selectId: 'assign-oem-select',
            id,
        });
    });

    delConfirm.addEventListener('click', async () => {
        if (!deleteActive) return;
        if (!deleteActive.url) {
            closeDeleteModal();
            return;
        }
        setDeleteBusy(true);
        showDeleteError('');
        try {
            const res = await fetch(deleteActive.url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
            });
            const data = await res.json();
            if (!data.success) {
                showDeleteError(data.message || 'Could not delete.');
                setDeleteBusy(false);
                return;
            }
            const sel = document.getElementById(deleteActive.selectId);
            if (sel) {
                const opt = Array.from(sel.options).find((o) => o.value === String(deleteActive.id));
                if (opt) opt.remove();
                sel.value = '';
            }
            closeDeleteModal();
        } catch (err) {
            showDeleteError('Could not delete.');
            setDeleteBusy(false);
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!active) return;
        const name = nameInput.value.trim();
        if (!name) {
            showError('Name is required.');
            nameInput.focus();
            return;
        }
        setBusy(true);
        showError('');
        try {
            const res = await fetch(active.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ name }),
            });
            const data = await res.json();
            if (!data.success || !data.item) {
                showError(data.message || active.failLabel);
                setBusy(false);
                return;
            }
            const sel = document.getElementById(active.selectId);
            if (sel) {
                let opt = Array.from(sel.options).find((o) => o.value === String(data.item.id));
                if (!opt) {
                    opt = new Option(data.item.name, data.item.id, true, true);
                    sel.add(opt);
                }
                sel.value = String(data.item.id);
            }
            closeModal();
        } catch (err) {
            showError(active.failLabel);
            setBusy(false);
        }
    });
})();
</script>
@endpush
