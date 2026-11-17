<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Note - {{ $receiptNote->receipt_note_no }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.4;
        }

        .toolbar {
            max-width: 820px;
            margin: 14px auto 10px;
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .toolbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #4f46e5;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .toolbar-btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .sheet {
            max-width: 820px;
            margin: 0 auto 30px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .disclaimer-banner {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 16px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .company-name {
            font-size: 18px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .doc-title {
            font-size: 18px;
            font-weight: 800;
            color: #4f46e5;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-no {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            text-align: right;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            background: #f8fafc;
        }

        .details-block h4 {
            margin: 0 0 6px 0;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 3px;
        }

        .detail-label {
            width: 140px;
            color: #64748b;
            font-weight: 500;
        }

        .detail-value {
            flex: 1;
            color: #0f172a;
            font-weight: 600;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            font-size: 10.5px;
            text-transform: uppercase;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        .items-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: 700;
        }

        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .serial-badge {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 9.5px;
            margin: 1px;
            font-family: monospace;
        }

        .signatures {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            text-align: center;
        }

        .sign-box {
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            font-weight: 600;
            color: #475569;
            font-size: 11px;
        }

        @media print {
            body {
                background: #ffffff;
            }
            .toolbar {
                display: none;
            }
            .sheet {
                border: none;
                box-shadow: none;
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>

@if(!($isPdf ?? false))
<div class="toolbar">
    <div>
        <a href="{{ route('purchasing.receipt-notes.show', $receiptNote) }}" class="toolbar-btn toolbar-btn-secondary">
            &larr; Back
        </a>
    </div>
    <div style="display: flex; gap: 8px;">
        <button onclick="window.print()" class="toolbar-btn">
            Print Document
        </button>
        <a href="{{ route('purchasing.receipt-notes.export', [$receiptNote, 'pdf']) }}" class="toolbar-btn toolbar-btn-secondary">
            Download PDF
        </a>
    </div>
</div>
@endif

<div class="sheet">
    {{-- Prominent Mandatory Disclaimer --}}
    <div class="disclaimer-banner">
        THIS DOCUMENT CONFIRMS PHYSICAL RECEIPT OF GOODS. IT IS NOT A PURCHASE INVOICE OR TAX INVOICE.
    </div>

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td style="vertical-align: top; width: 60%;">
                <div class="company-name">{{ $company->name ?? config('app.name', 'DMS ERP') }}</div>
                @if($company->address ?? false)
                    <div style="color: #475569;">{{ $company->address }}</div>
                @endif
                @if($company->gstin ?? $company->gst_number ?? false)
                    <div style="color: #475569;">GSTIN: <strong>{{ $company->gstin ?? $company->gst_number }}</strong></div>
                @endif
                @if($company->phone ?? false)
                    <div style="color: #475569;">Phone: {{ $company->phone }}</div>
                @endif
            </td>
            <td style="vertical-align: top; width: 40%; text-align: right;">
                <div class="doc-title">Goods Receipt Note</div>
                <div class="doc-no">{{ $receiptNote->receipt_note_no }}</div>
                <div style="color: #64748b; margin-top: 4px;">Date: <strong>{{ $receiptNote->receipt_date->format('d M Y') }}</strong></div>
                <div style="color: #64748b;">Status: <strong>{{ strtoupper($receiptNote->status_label) }}</strong></div>
            </td>
        </tr>
    </table>

    {{-- Details Grid --}}
    <div class="details-grid">
        <div class="details-block">
            <h4>Supplier Details</h4>
            <div style="font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                {{ $receiptNote->supplier?->name }}
            </div>
            @if($receiptNote->supplier?->address)
                <div style="color: #475569; margin-bottom: 2px;">{{ $receiptNote->supplier->address }}</div>
            @endif
            @if($receiptNote->supplier?->gst_number)
                <div style="color: #475569;">GSTIN: <strong>{{ $receiptNote->supplier->gst_number }}</strong></div>
            @endif
            @if($receiptNote->supplier?->phone)
                <div style="color: #475569;">Phone: {{ $receiptNote->supplier->phone }}</div>
            @endif
        </div>

        <div class="details-block">
            <h4>Receipt & Delivery Info</h4>
            <div class="detail-row">
                <span class="detail-label">Warehouse:</span>
                <span class="detail-value">{{ $receiptNote->warehouse?->name ?? 'Default' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Supplier Challan No:</span>
                <span class="detail-value">{{ $receiptNote->supplier_challan_no ?: '—' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Supplier Challan Date:</span>
                <span class="detail-value">{{ $receiptNote->supplier_challan_date ? $receiptNote->supplier_challan_date->format('d/m/Y') : '—' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">PO Reference:</span>
                <span class="detail-value">{{ $receiptNote->purchaseOrder?->po_no ?: 'Direct Receipt' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Received By:</span>
                <span class="detail-value">{{ $receiptNote->received_by ?: '—' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Vehicle / Ref No:</span>
                <span class="detail-value">{{ $receiptNote->reference_no ?: '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">#</th>
                <th>Item Description</th>
                <th style="width: 50px;" class="text-center">UOM</th>
                @if($receiptNote->purchase_order_id)
                    <th style="width: 70px;" class="text-right">PO Qty</th>
                @endif
                <th style="width: 75px;" class="text-right">Challan Qty</th>
                <th style="width: 80px;" class="text-right">Received Qty</th>
                <th style="width: 100px;">Batch / Expiry</th>
                <th style="width: 120px;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receiptNote->items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <div class="font-bold">{{ $item->product?->name }}</div>
                        @if($item->product?->sku)
                            <div style="font-size: 9.5px; color: #64748b;">SKU: {{ $item->product->sku }}</div>
                        @endif

                        @if($item->serials->isNotEmpty())
                            <div style="margin-top: 4px;">
                                <span style="font-size: 9px; color: #64748b; font-weight: 700;">SERIALS:</span>
                                @foreach($item->serials as $ser)
                                    <span class="serial-badge">{{ $ser->serial_number }}</span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->uom?->short_name ?? $item->product?->unit ?? 'PCS' }}</td>
                    @if($receiptNote->purchase_order_id)
                        <td class="text-right">{{ number_format((float) $item->ordered_qty, 2) }}</td>
                    @endif
                    <td class="text-right">{{ number_format((float) $item->challan_qty, 2) }}</td>
                    <td class="text-right font-bold">{{ number_format((float) $item->received_qty, 2) }}</td>
                    <td>
                        @if($item->batch_no)
                            <div class="font-mono font-bold">{{ $item->batch_no }}</div>
                            @if($item->expiry_date)
                                <div style="font-size: 9px; color: #64748b;">Exp: {{ $item->expiry_date->format('d/m/Y') }}</div>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $item->remarks ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: 700;">
                <td colspan="{{ $receiptNote->purchase_order_id ? 3 : 2 }}" class="text-right">TOTAL:</td>
                @if($receiptNote->purchase_order_id)
                    <td class="text-right">{{ number_format($receiptNote->items->sum('ordered_qty'), 2) }}</td>
                @endif
                <td class="text-right">{{ number_format($receiptNote->items->sum('challan_qty'), 2) }}</td>
                <td class="text-right font-bold">{{ number_format($receiptNote->total_received_qty, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    @if($receiptNote->remarks)
        <div style="margin-bottom: 24px; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
            <div style="font-weight: 700; color: #475569; margin-bottom: 2px;">Remarks / Gate Inspection:</div>
            <div style="color: #334155; white-space: pre-line;">{{ $receiptNote->remarks }}</div>
        </div>
    @endif

    {{-- Signatures --}}
    <div class="signatures">
        <div class="sign-box">
            Received By<br>
            <span style="font-weight: 400; font-size: 10px; color: #64748b;">({{ $receiptNote->received_by ?: 'Storekeeper' }})</span>
        </div>
        <div class="sign-box">
            Inspected & Verified By<br>
            <span style="font-weight: 400; font-size: 10px; color: #64748b;">(Quality / Store In-charge)</span>
        </div>
        <div class="sign-box">
            Authorized Signatory<br>
            <span style="font-weight: 400; font-size: 10px; color: #64748b;">(For {{ $company->name ?? 'Company' }})</span>
        </div>
    </div>
</div>

</body>
</html>
