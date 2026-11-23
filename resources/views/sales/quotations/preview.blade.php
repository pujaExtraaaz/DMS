<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $item->quotation_no }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 12mm 14mm;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background: #e8eef5;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            font-size: 11px;
            line-height: 1.35;
        }

        .toolbar {
            max-width: 794px;
            margin: 14px auto;
            padding: 8px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            border: 1px solid #d0d7de;
            border-radius: 8px;
        }

        .toolbar-left, .toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toolbar a, .toolbar button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
        }

        .toolbar .btn-primary {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #fff;
        }

        .sheet {
            width: 794px;
            max-width: 100%;
            margin: 0 auto 28px;
            background: #fff;
            border: 1px solid #d0d7de;
            padding: 28px 32px 36px;
            min-height: 1040px;
        }

        .letterhead {
            display: grid;
            grid-template-columns: 1fr 1.4fr 1fr;
            gap: 12px;
            align-items: start;
            padding-bottom: 14px;
            border-bottom: 2px solid #111;
            margin-bottom: 16px;
        }

        .letterhead-left {
            min-height: 8px;
        }

        .letterhead-center {
            text-align: center;
        }

        .letterhead-logo {
            max-height: 64px;
            max-width: 180px;
            object-fit: contain;
            margin: 0 auto 6px;
            display: block;
        }

        .letterhead-company {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.02em;
            line-height: 1.25;
            color: #0a0a0a;
        }

        .letterhead-right {
            text-align: right;
            font-size: 11px;
            line-height: 1.55;
            color: #111;
        }

        .letterhead-right .contact-line {
            margin: 1px 0;
        }

        .letterhead-right .contact-label {
            font-weight: 700;
        }

        .doc-title {
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin: 10px 0 14px;
            color: #0a0a0a;
            padding-bottom: 8px;
            border-bottom: 1px solid #222;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #cbd5e1;
        }

        .meta-left, .meta-right {
            font-size: 12px;
            line-height: 1.55;
        }

        .meta-right {
            text-align: right;
        }

        .meta-row .lbl {
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .party-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            margin-bottom: 18px;
            padding: 12px 0;
            border-bottom: 1px solid #cbd5e1;
        }

        .party-head {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 8px;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .party-line {
            margin: 2px 0;
            font-size: 12px;
        }

        .party-line .lbl {
            font-weight: 700;
            display: inline-block;
            min-width: 68px;
        }

        .instructions {
            white-space: pre-line;
            font-size: 12px;
            line-height: 1.5;
            color: #222;
        }

        .quote-heading {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            margin: 14px 0 8px;
            letter-spacing: 0.02em;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 2px solid #111;
        }

        table.items th,
        table.items td {
            border: 1px solid #222;
            padding: 5px 4px;
            vertical-align: top;
        }

        table.items th {
            background: #f3f4f6;
            font-size: 10.5px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            border-bottom: 2px solid #111;
        }

        table.items td {
            font-size: 10.5px;
        }

        .col-sr { width: 28px; text-align: center; }
        .col-img { width: 58px; text-align: center; }
        .col-desc { width: auto; text-align: left; }
        .col-hsn { width: 46px; text-align: center; word-break: break-all; }
        .col-qty { width: 36px; text-align: center; }
        .col-uom { width: 44px; text-align: center; }
        .col-mrp { width: 72px; text-align: right; white-space: nowrap; }
        .col-price { width: 72px; text-align: right; white-space: nowrap; }
        .col-tax { width: 40px; text-align: center; }
        .col-total { width: 78px; text-align: right; white-space: nowrap; }

        .item-desc {
            white-space: pre-line;
            font-size: 10.5px;
            line-height: 1.35;
            color: #111;
        }

        .item-desc .name {
            font-weight: 700;
            display: block;
            margin-bottom: 2px;
        }

        .thumb {
            width: 48px;
            height: 48px;
            object-fit: contain;
            background: #fff;
        }

        .thumb-empty {
            width: 48px;
            height: 48px;
            display: inline-block;
            background: #f3f4f6;
            border: 1px solid #ddd;
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1.4fr 0.9fr;
            gap: 24px;
            margin-top: 16px;
            align-items: start;
        }

        .terms-title {
            font-weight: 800;
            margin-bottom: 6px;
            font-size: 12px;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .terms-body {
            white-space: pre-line;
            font-size: 11px;
            line-height: 1.5;
            color: #111;
        }

        .totals {
            margin-top: 4px;
            padding-left: 8px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 3px 0;
            font-size: 12px;
            font-weight: 700;
        }

        .totals-row.final {
            margin-top: 4px;
            font-size: 13px;
        }

        .sign-block {
            margin-top: 40px;
            font-size: 12px;
        }

        .sign-note {
            margin-bottom: 48px;
            color: #222;
        }

        .sign-line {
            display: inline-block;
            min-width: 200px;
            border-top: 1px solid #333;
            padding-top: 6px;
            font-weight: 600;
        }

        @media print {
            html, body { background: #fff; }
            .toolbar, .no-print { display: none !important; }
            .sheet {
                width: auto;
                margin: 0;
                border: none;
                padding: 0;
                min-height: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
@php
    $customer = $item->customer;
    $company = $company ?? $item->resolveCompany();
    $companyName = trim((string) ($company?->name ?: ''));
    $companyLogo = $company?->logo_url;
    $companyPhone = trim((string) ($company?->phone ?: ''));
    $companyWebsite = trim((string) ($company?->website ?: ''));
    $companyEmail = trim((string) ($company?->email ?: ''));
    $address = trim((string) ($customer?->address ?: ''));
    $pincode = trim((string) ($customer?->pincode ?: ''));
    $phone = trim((string) ($customer?->phone ?: ''));
    $email = trim((string) ($customer?->email ?: ''));

    $indianMoney = function ($value) {
        $amount = round((float) $value, 0);
        $negative = $amount < 0;
        $amount = abs($amount);
        $int = (string) (int) $amount;
        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);
        if ($rest !== '') {
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $formatted = $rest.','.$last3;
        } else {
            $formatted = $last3;
        }

        return ($negative ? '-₹ ' : '₹ ').$formatted;
    };

    $qtyLabel = function ($qty) {
        $n = (float) $qty;
        if (abs($n - round($n)) < 0.00001) {
            return (string) (int) round($n);
        }

        return rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.');
    };
@endphp

<div class="toolbar no-print">
    <div class="toolbar-left">
        <a href="{{ route('quotations.show', $item) }}">Back</a>
        @if($item->isEditable())
            <a href="{{ route('quotations.edit', $item) }}">Edit</a>
        @endif
    </div>
    <div class="toolbar-right">
        <a href="{{ route('quotations.export', ['quotation' => $item, 'format' => 'pdf']) }}" title="Download PDF">Export PDF</a>
        <a href="{{ route('quotations.export', ['quotation' => $item, 'format' => 'xlsx']) }}" title="Download Excel">Export Excel</a>
        <a href="{{ route('quotations.export', ['quotation' => $item, 'format' => 'csv']) }}" title="Download CSV">Export CSV</a>
        <button type="button" class="btn-primary" onclick="window.print()">Print</button>
    </div>
</div>

<div class="sheet">
    <div class="letterhead">
        <div class="letterhead-left"></div>
        <div class="letterhead-center">
            @if($companyLogo)
                <img src="{{ $companyLogo }}" alt="{{ $companyName !== '' ? $companyName : 'Company' }}" class="letterhead-logo">
            @endif
            <div class="letterhead-company">{{ $companyName !== '' ? $companyName : 'Company' }}</div>
        </div>
        <div class="letterhead-right">
            @if($companyPhone !== '')
                <div class="contact-line"><span class="contact-label">Tel:</span> {{ $companyPhone }}</div>
            @endif
            @if($companyWebsite !== '')
                <div class="contact-line"><span class="contact-label">Web:</span> {{ $companyWebsite }}</div>
            @endif
            @if($companyEmail !== '')
                <div class="contact-line"><span class="contact-label">Email:</span> {{ $companyEmail }}</div>
            @endif
        </div>
    </div>

    <div class="meta-row">
        <div class="meta-left">
            <div><span class="lbl">No:</span> {{ $item->quotation_no }}</div>
        </div>
        <div class="meta-right">
            <div><span class="lbl">Date:</span> {{ $item->quotation_date?->format('d/m/Y') }}</div>
            <div><span class="lbl">Valid Until:</span> {{ $item->valid_until?->format('d/m/Y') ?: '—' }}</div>
        </div>
    </div>

    <div class="party-grid">
        <div>
            <div class="party-head">Customer</div>
            <div class="party-line"><span class="lbl">Name:</span> {{ $customer?->name ?: '—' }}</div>
            <div class="party-line"><span class="lbl">Address:</span> {{ $address !== '' ? $address : '—' }}</div>
            <div class="party-line"><span class="lbl">Pin code:</span> {{ $pincode !== '' ? $pincode : '—' }}</div>
            <div class="party-line"><span class="lbl">Phone:</span> {{ $phone !== '' ? $phone : '—' }}</div>
            <div class="party-line"><span class="lbl">E-mail:</span> {{ $email !== '' ? $email : '—' }}</div>
        </div>
        <div>
            <div class="party-head">Special Instructions:</div>
            <div class="instructions">{{ $item->special_instructions ?: "Review this quotation carefully.\nAdjustments or clarifications can be made\nbefore approval." }}</div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th class="col-sr">Sr.</th>
                <th class="col-img">Image</th>
                <th class="col-desc">Description</th>
                <th class="col-hsn">HSN</th>
                <th class="col-qty">Qty</th>
                <th class="col-uom">UOM</th>
                <th class="col-mrp">MRP</th>
                <th class="col-price">PRICE</th>
                <th class="col-tax">Tax</th>
                <th class="col-total">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($item->items as $line)
                @php
                    $img = $line->imageUrl();
                    $productName = trim((string) ($line->product?->name ?: ''));
                    $desc = trim((string) ($line->description ?: ''));
                    $taxLabel = $line->tax_rate !== null
                        ? rtrim(rtrim(number_format((float) $line->tax_rate, 2, '.', ''), '0'), '.').'%'
                        : '—';
                @endphp
                <tr>
                    <td class="col-sr">{{ $line->sort_order ?: $loop->iteration }}</td>
                    <td class="col-img">
                        @if($img)
                            <img src="{{ $img }}" alt="" class="thumb">
                        @else
                            <span class="thumb-empty"></span>
                        @endif
                    </td>
                    <td class="col-desc">
                        <div class="item-desc">
                            @if($desc !== '')
                                @if($productName !== '' && ! str_starts_with(mb_strtolower($desc), mb_strtolower($productName)))
                                    <span class="name">{{ $productName }}</span>
                                @endif
                                {{ $desc }}
                            @else
                                <span class="name">{{ $productName !== '' ? $productName : 'Item' }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="col-hsn">{{ $line->hsn_code ?: '—' }}</td>
                    <td class="col-qty">{{ $qtyLabel($line->quantity) }}</td>
                    <td class="col-uom">{{ $line->uom?->code ?: '—' }}</td>
                    <td class="col-mrp">{{ $line->mrp !== null ? $indianMoney($line->mrp) : '—' }}</td>
                    <td class="col-price">{{ $indianMoney($line->unit_price) }}</td>
                    <td class="col-tax">{{ $taxLabel }}</td>
                    <td class="col-total">{{ $indianMoney($line->taxableDisplayTotal()) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="bottom-grid">
        <div>
            <div class="terms-title">Terms & Conditions: -</div>
            <div class="terms-body">{{ $item->terms_conditions ?: '—' }}</div>
        </div>
        <div class="totals">
            <div class="totals-row"><span>SUBTOTAL:</span><span>{{ $indianMoney($item->subtotal) }}</span></div>
            <div class="totals-row"><span>TAX:</span><span>{{ $indianMoney($item->tax_amount) }}</span></div>
            <div class="totals-row"><span>DISCOUNT (IF ANY):</span><span>{{ $indianMoney($item->discount_amount) }}</span></div>
            <div class="totals-row final"><span>FINAL AMOUNT:</span><span>{{ $indianMoney($item->grand_total) }}</span></div>
        </div>
    </div>

    <div class="sign-block">
        <div class="sign-note">Please sign below to confirm acceptance of this quotation.</div>
        <div class="sign-line">Customer Signature</div>
    </div>
</div>
</body>
</html>
