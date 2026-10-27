<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $comparison->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { margin-bottom: 12px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; vertical-align: top; }
        th { background: #1e3a5f; color: #fff; text-align: left; }
        .lowest { background: #bbf7d0; font-weight: bold; }
        .muted { color: #64748b; }
        .flag { color: #b45309; font-size: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    @if(empty($forPdf))
        <p class="no-print"><button onclick="window.print()">Print</button></p>
    @endif
    <h1>Purchase comparison {{ $comparison->number }}</h1>
    <div class="meta">
        Date: {{ $comparison->comparison_date?->format('d M Y') }}
        @if($comparison->indent_no) · Indent: {{ $comparison->indent_no }} @endif
        @if($comparison->customer_project) · {{ $comparison->customer_project }} @endif
        @if($comparison->required_delivery_date) · Required by {{ $comparison->required_delivery_date->format('d M Y') }} @endif
        <br>Status: {{ str_replace('_', ' ', $comparison->status) }}
        @if($comparison->preparer) · Prepared by {{ $comparison->preparer?->name }} @endif
        @if($comparison->approver) · {{ $comparison->status === 'rejected' ? 'Rejected' : 'Approved' }} by {{ $comparison->approver->name }} {{ $comparison->approved_at?->format('d M Y') }} @endif
        @if($comparison->remarks)<br>Remarks: {{ $comparison->remarks }}@endif
    </div>
    <table>
        <thead>
            <tr>
                <th>Vendor</th>
                <th>Quotation</th>
                <th>Commercial terms</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matrix['vendors'] as $vendor)
                <tr>
                    <td>
                        <strong>{{ $vendor->supplier?->name }}</strong><br>
                        {{ $vendor->contact_person }} {{ $vendor->mobile }}<br>
                        {{ $vendor->email }}<br>
                        GSTIN {{ $vendor->gstin ?: '—' }}
                    </td>
                    <td>
                        {{ $vendor->quotation_number ?: '—' }}<br>
                        {{ $vendor->quotation_date?->format('d M Y') ?: '—' }} to {{ $vendor->valid_until?->format('d M Y') ?: '—' }}
                    </td>
                    <td>
                        Credit {{ $vendor->credit_period ?: '—' }} · Advance {{ $vendor->advance_payment ?: '—' }}<br>
                        Replacement {{ $vendor->replacement_policy ?: '—' }} · Returns {{ $vendor->return_policy ?: '—' }}<br>
                        Support {{ $vendor->installation_support ?: '—' }} · Transporter {{ $vendor->transporter ?: '—' }}<br>
                        Freight paid by {{ $vendor->freight_paid_by === 'company' ? 'AVIT Digital' : 'Vendor' }}
                        · Partial {{ $vendor->partial_delivery ? 'Yes' : 'No' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <br>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                @foreach($matrix['vendors'] as $vendor)
                    <th>{{ $vendor->supplier?->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($matrix['rows'] as $row)
                <tr>
                    <td>
                        <strong>{{ $row['item']->item_name }}</strong>
                        @if($row['item']->model_sku)<br><span class="muted">{{ $row['item']->model_sku }}</span>@endif
                    </td>
                    <td>{{ rtrim(rtrim(number_format((float) $row['item']->required_quantity, 2, '.', ''), '0'), '.') }}</td>
                    @foreach($matrix['vendors'] as $vendor)
                        @php($cell = $row['cells'][$vendor->id])
                        <td class="{{ $cell['is_lowest'] ? 'lowest' : '' }}">
                            @if(!$cell['math'])
                                <span class="muted">Not quoted</span>
                            @else
                                Basic {{ number_format($cell['math']['basic_rate'], 2) }}
                                @if($cell['is_lowest_basic']) · lowest @endif
                                @if($cell['is_highest_basic']) · highest @endif
                                <br>Net {{ number_format($cell['math']['net_rate'], 2) }} @if($cell['is_lowest_net']) · lowest @endif
                                <br>Freight {{ number_format($cell['math']['freight'], 2) }} · Other {{ number_format($cell['math']['charges'], 2) }}
                                <br>Pre-GST {{ number_format($cell['math']['pre_gst'], 2) }}
                                <br>GST {{ number_format($cell['math']['gst_amount'], 2) }}
                                <br>Landed {{ number_format($cell['math']['landed'], 2) }} @if($cell['is_lowest']) · lowest @endif
                                <br>Per unit {{ number_format($cell['math']['per_unit'], 2) }}
                                <br>Gap {{ number_format($cell['gap'], 2) }} · Saving {{ number_format($cell['saving'], 2) }}%
                                <br>Warranty {{ $cell['quote']->warranty_period ?: '—' }} · Delivery {{ $cell['quote']->delivery_time ?: '—' }}
                                @if($cell['flags'])<br><span class="flag">Not identical: {{ implode(', ', $cell['flags']) }}</span>@endif
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
