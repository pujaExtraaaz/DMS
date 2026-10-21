@extends('layouts.dms')
@section('title', $comparison->number)
@section('content')
<div class="space-y-4">
    <x-ui.page-header :title="$comparison->number">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('purchasing.comparisons.index')">Back</x-ui.button>
            <x-ui.button variant="secondary" :href="route('purchasing.comparisons.edit', $comparison)">Edit</x-ui.button>
            <x-ui.button variant="secondary" :href="route('purchasing.comparisons.print', $comparison)" target="_blank">Print</x-ui.button>
            <x-ui.button variant="secondary" :href="route('purchasing.comparisons.pdf', $comparison)">PDF</x-ui.button>
            <x-ui.button variant="secondary" :href="route('purchasing.comparisons.excel', $comparison)">Excel</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <div class="grid gap-3 text-sm md:grid-cols-4">
            <div><span class="text-slate-500">Date</span><div>{{ $comparison->comparison_date?->format('d M Y') }}</div></div>
            <div><span class="text-slate-500">Indent</span><div>{{ $comparison->indent_no ?: '—' }}</div></div>
            <div><span class="text-slate-500">Customer / project</span><div>{{ $comparison->customer_project ?: '—' }}</div></div>
            <div><span class="text-slate-500">Required delivery</span><div>{{ $comparison->required_delivery_date?->format('d M Y') ?: '—' }}</div></div>
            <div><span class="text-slate-500">Status</span><div>{{ str_replace('_', ' ', ucfirst($comparison->status)) }}</div></div>
            <div><span class="text-slate-500">Prepared by</span><div>{{ $comparison->preparer?->name ?: '—' }}</div></div>
            <div><span class="text-slate-500">Last changed by</span><div>{{ $comparison->editor?->name ?: '—' }}</div></div>
            <div><span class="text-slate-500">Approved / rejected by</span><div>{{ $comparison->approver?->name ?: '—' }} @if($comparison->approved_at)<span class="text-slate-500">{{ $comparison->approved_at->format('d M Y H:i') }}</span>@endif</div></div>
        </div>
        @if($comparison->remarks)
            <p class="mt-3 text-sm text-slate-700">{{ $comparison->remarks }}</p>
        @endif
        @if($comparison->rejection_reason)
            <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-800">Rejected: {{ $comparison->rejection_reason }}</p>
        @endif
    </x-ui.card>

    <x-ui.card>
        <h2 class="mb-3 text-sm font-semibold text-slate-800">Vendors</h2>
        <div class="grid gap-3 md:grid-cols-2">
            @foreach($matrix['vendors'] as $vendor)
                <div class="rounded-lg border border-slate-200 p-3 text-sm">
                    <div class="font-semibold">{{ $vendor->supplier?->name }}</div>
                    <div class="text-slate-600">{{ $vendor->billing_address ?: 'No billing address' }}</div>
                    <div class="text-slate-600">Dispatch: {{ $vendor->dispatch_address ?: '—' }}</div>
                    <div class="mt-1 text-slate-600">{{ $vendor->contact_person ?: 'No contact' }} · {{ $vendor->mobile ?: '—' }} · {{ $vendor->email ?: '—' }}</div>
                    <div class="text-slate-600">GSTIN {{ $vendor->gstin ?: '—' }}</div>
                    <div class="text-slate-600">Quotation {{ $vendor->quotation_number ?: '—' }} · {{ $vendor->quotation_date?->format('d M Y') ?: 'no date' }} · valid {{ $vendor->valid_until?->format('d M Y') ?: '—' }}</div>
                    <div class="mt-1 text-slate-600">Credit {{ $vendor->credit_period ?: '—' }} · Advance {{ $vendor->advance_payment ?: '—' }} · Credit limit {{ $vendor->credit_limit_note ?: '—' }}</div>
                    <div class="text-slate-600">Replacement {{ $vendor->replacement_policy ?: '—' }} · Returns {{ $vendor->return_policy ?: '—' }} · Support {{ $vendor->installation_support ?: '—' }}</div>
                    <div class="text-slate-600">Transporter {{ $vendor->transporter ?: '—' }} · Freight paid by {{ $vendor->freight_paid_by === 'company' ? 'AVIT Digital' : 'Vendor' }} · Delivery {{ $vendor->delivery_location ?: '—' }} · Partial {{ $vendor->partial_delivery ? 'Yes' : 'No' }}</div>
                    @if($vendor->files->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($vendor->files as $file)
                                <a class="text-indigo-700 underline" href="{{ route('purchasing.comparisons.files', [$comparison, $file]) }}">{{ $file->original_name }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-ui.card>

    <x-ui.card>
        <h2 class="mb-3 text-sm font-semibold text-slate-800">Item comparison</h2>
        <p class="mb-3 text-xs text-slate-500">Green is the lowest landed cost. Amber means quoted quantity, GST, or delivery is not the same across vendors.</p>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-2 py-2">Item</th>
                        @foreach($matrix['vendors'] as $vendor)
                            <th class="px-2 py-2">{{ $vendor->supplier?->name }}</th>
                        @endforeach
                        <th class="px-2 py-2">Award</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($matrix['rows'] as $row)
                        <tr class="border-t border-slate-100 align-top">
                            <td class="px-2 py-2">
                                <div class="font-medium">{{ $row['item']->item_name }}</div>
                                <div class="text-xs text-slate-500">{{ $row['item']->category_name }} {{ $row['item']->subcategory_name }}</div>
                                <div class="text-xs text-slate-500">{{ $row['item']->brand_name }} {{ $row['item']->model_sku }}</div>
                                <div class="text-xs text-slate-500">Required qty {{ rtrim(rtrim(number_format((float) $row['item']->required_quantity, 2, '.', ''), '0'), '.') }}</div>
                                @if($row['item']->description)
                                    <div class="text-xs text-slate-500">{{ $row['item']->description }}</div>
                                @endif
                                <div class="mt-2 text-xs text-slate-700">
                                    Lowest basic {{ $row['min_basic'] !== null ? number_format($row['min_basic'], 2) : '—' }}
                                    · Highest basic {{ $row['max_basic'] !== null ? number_format($row['max_basic'], 2) : '—' }}
                                    <br>Lowest net {{ $row['min_net'] !== null ? number_format($row['min_net'], 2) : '—' }}
                                    <br>Lowest landed vendor: {{ $row['lowest_vendor'] ?: '—' }}
                                </div>
                            </td>
                            @foreach($matrix['vendors'] as $vendor)
                                @php($cell = $row['cells'][$vendor->id])
                                <td class="px-2 py-2 {{ $cell['is_lowest'] ? 'bg-emerald-100' : '' }}">
                                    @if(!$cell['math'])
                                        <span class="text-slate-400">Not quoted</span>
                                    @else
                                        <div>Quoted qty {{ rtrim(rtrim(number_format((float) $cell['quote']->quoted_quantity, 2, '.', ''), '0'), '.') }}</div>
                                        <div>Basic {{ number_format($cell['math']['basic_rate'], 2) }}
                                            @if($cell['is_lowest_basic'])<span class="text-emerald-800">lowest</span>@endif
                                            @if($cell['is_highest_basic'])<span class="text-rose-700">highest</span>@endif
                                        </div>
                                        <div>Discount {{ number_format($cell['math']['discount_per_unit'], 2) }} · Net {{ number_format($cell['math']['net_rate'], 2) }} @if($cell['is_lowest_net'])<span class="text-emerald-800">lowest</span>@endif</div>
                                        <div>Freight {{ number_format($cell['math']['freight'], 2) }} · Other {{ number_format($cell['math']['charges'], 2) }}</div>
                                        <div>Pre-GST {{ number_format($cell['math']['pre_gst'], 2) }}</div>
                                        <div>GST {{ number_format($cell['math']['gst_amount'], 2) }} ({{ rtrim(rtrim(number_format($cell['math']['gst_rate'], 2, '.', ''), '0'), '.') }}%)</div>
                                        <div class="font-semibold">Landed {{ number_format($cell['math']['landed'], 2) }} @if($cell['is_lowest'])<span class="text-emerald-800">lowest</span>@endif</div>
                                        <div>Per unit {{ number_format($cell['math']['per_unit'], 2) }}</div>
                                        <div class="text-xs text-slate-600">Gap {{ number_format($cell['gap'], 2) }} · Saving vs highest {{ number_format($cell['saving'], 2) }}%</div>
                                        <div class="text-xs text-slate-600">Warranty {{ $cell['quote']->warranty_period ?: '—' }} {{ $cell['quote']->warranty_type ? '('.str_replace('_', ' ', $cell['quote']->warranty_type).')' : '' }}</div>
                                        <div class="text-xs text-slate-600">Delivery {{ $cell['quote']->delivery_time ?: '—' }} {{ $cell['quote']->delivery_date?->format('d M Y') }}</div>
                                        <div class="text-xs text-slate-600">Stock {{ $cell['quote']->stock_availability ? str_replace('_', ' ', $cell['quote']->stock_availability) : '—' }} · {{ $cell['quote']->payment_terms ?: 'No payment terms' }}</div>
                                        @if($cell['quote']->price_validity || $cell['quote']->country_of_origin)
                                            <div class="text-xs text-slate-600">Validity {{ $cell['quote']->price_validity ?: '—' }} · Origin {{ $cell['quote']->country_of_origin ?: '—' }}</div>
                                        @endif
                                        @if($cell['flags'])
                                            <div class="text-xs font-semibold text-amber-700">Not identical: {{ implode(', ', $cell['flags']) }}</div>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-2 py-2">
                                <div>{{ $row['item']->selectedVendor?->supplier?->name ?: 'Not chosen' }}</div>
                                @if($row['item']->selection_reason)
                                    <div class="text-xs text-slate-600">{{ $row['item']->selection_reason }}</div>
                                @endif
                                @if($row['item']->final_rate !== null)
                                    <div class="text-xs">Final rate {{ number_format((float) $row['item']->final_rate, 2) }}</div>
                                @endif
                                @if($row['item']->final_freight !== null)
                                    <div class="text-xs">Final freight {{ number_format((float) $row['item']->final_freight, 2) }}</div>
                                @endif
                                @if($row['item']->final_amount !== null)
                                    <div class="text-xs">Final amount {{ number_format((float) $row['item']->final_amount, 2) }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.card>
            <h2 class="mb-3 text-sm font-semibold text-slate-800">Approval</h2>
            @php($chosen = collect($matrix['rows'])->map(fn ($row) => $row['item']->selectedVendor?->supplier?->name)->filter()->unique())
            <p class="mb-2 text-sm text-slate-700">Recommended vendor: {{ $chosen->count() === 1 ? $chosen->first() : ($chosen->isEmpty() ? 'Not chosen' : 'Different vendor per item') }}</p>
            @if($comparison->isEditable())
                <form method="POST" action="{{ route('purchasing.comparisons.submit', $comparison) }}">
                    @csrf
                    <button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Send for approval</button>
                </form>
            @elseif($comparison->status === 'pending_approval')
                <form method="POST" action="{{ route('purchasing.comparisons.approve', $comparison) }}" class="space-y-2">
                    @csrf
                    <textarea name="executive_remarks" rows="2" placeholder="Executive remarks" class="w-full rounded-lg border-slate-300 text-sm">{{ $comparison->executive_remarks }}</textarea>
                    <button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">Approve</button>
                </form>
                <form method="POST" action="{{ route('purchasing.comparisons.reject', $comparison) }}" class="mt-3 space-y-2">
                    @csrf
                    <textarea name="rejection_reason" rows="2" required placeholder="Reason for rejection" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
                    <button class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white">Reject</button>
                </form>
            @else
                <p class="text-sm text-slate-600">{{ $comparison->executive_remarks ?: 'No executive remarks.' }}</p>
            @endif
        </x-ui.card>

        <x-ui.card>
            <h2 class="mb-3 text-sm font-semibold text-slate-800">Purchase orders</h2>
            @if($comparison->orders->isNotEmpty())
                <ul class="space-y-1 text-sm">
                    @foreach($comparison->orders as $link)
                        <li>
                            <a class="text-indigo-700 underline" href="{{ route('purchasing.orders.show', $link->purchase_order_id) }}">{{ $link->purchaseOrder?->po_no }}</a>
                            <span class="text-slate-500">{{ $link->vendor?->supplier?->name }}</span>
                        </li>
                    @endforeach
                </ul>
            @elseif($comparison->status === 'approved')
                <form method="POST" action="{{ route('purchasing.comparisons.convert', $comparison) }}">
                    @csrf
                    <p class="mb-2 text-xs text-slate-500">Items awarded to the same supplier become one purchase order. A line without a product stays on the sheet until you link one.</p>
                    <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Create purchase orders</button>
                </form>
            @else
                <p class="text-sm text-slate-600">Approve the sheet before creating purchase orders.</p>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card>
        <h2 class="mb-3 text-sm font-semibold text-slate-800">History</h2>
        <ul class="space-y-1 text-sm text-slate-700">
            @foreach($comparison->events as $event)
                <li>{{ $event->created_at?->format('d M Y H:i') }} · {{ $event->user?->name ?: 'System' }} · {{ str_replace('_', ' ', $event->action) }} @if($event->note)<span class="text-slate-500">{{ $event->note }}</span>@endif</li>
            @endforeach
        </ul>
    </x-ui.card>
</div>
@endsection
