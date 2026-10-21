<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document['title'] }} {{ $document['number'] }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", sans-serif; color: #1a2332; background: #ffffff; margin: 0; padding: 16px; font-size: 12px; line-height: 1.4; }
        h1 { font-size: 22px; margin: 0; letter-spacing: 0.02em; }
        h2 { font-size: 13px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #d3d7e0; text-align: left; padding: 6px 4px; vertical-align: top; }
        thead { display: table-header-group; }
        tr, .party, .totals, .terms { break-inside: avoid; page-break-inside: avoid; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.03em; color: #5c6778; }
        .money { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .muted { color: #5c6778; }
        .head { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #1a2332; padding-bottom: 12px; }
        .meta { margin-top: 16px; display: flex; justify-content: space-between; gap: 24px; }
        .party { min-width: 220px; }
        .totals { margin-top: 8px; margin-left: auto; width: 260px; }
        .totals td { border: 0; padding: 3px 0; }
        .grand td { border-top: 2px solid #1a2332; font-weight: 700; padding-top: 6px; }
        .terms { margin-top: 28px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 12px; }
        .toolbar-right { display: flex; align-items: center; gap: 8px; }
        .toolbar button, .toolbar a { font: inherit; padding: 6px 10px; border: 1px solid #cbd5e1; background: #fff; color: #1a2332; border-radius: 6px; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .toolbar .primary { background: #2563eb; border-color: #2563eb; color: #fff; }
        .toolbar .chevron { transition: transform 0.45s ease; }
        .toolbar .is-open .chevron { transform: rotate(180deg); }
        .shutter { display: grid; grid-template-rows: 0fr; transition: grid-template-rows 0.55s cubic-bezier(.22, .8, .2, 1); margin-bottom: 12px; }
        .shutter.is-open { grid-template-rows: 1fr; }
        .shutter-clip { overflow: hidden; min-height: 0; position: relative; }
        .shutter-slats { position: absolute; inset: 0; display: flex; flex-direction: column; z-index: 3; pointer-events: none; }
        .shutter-slats i { flex: 1; background: linear-gradient(#fff, #e2e8f0); border-bottom: 1px solid #94a3b8; transform-origin: top center; transform: scaleY(1); transition: transform 0.42s ease; }
        .shutter.is-open .shutter-slats i { transform: scaleY(0); }
        .shutter.is-open .shutter-slats i:nth-child(1) { transition-delay: 0.02s; }
        .shutter.is-open .shutter-slats i:nth-child(2) { transition-delay: 0.05s; }
        .shutter.is-open .shutter-slats i:nth-child(3) { transition-delay: 0.08s; }
        .shutter.is-open .shutter-slats i:nth-child(4) { transition-delay: 0.11s; }
        .shutter.is-open .shutter-slats i:nth-child(5) { transition-delay: 0.14s; }
        .shutter.is-open .shutter-slats i:nth-child(6) { transition-delay: 0.17s; }
        .shutter.is-open .shutter-slats i:nth-child(7) { transition-delay: 0.20s; }
        .shutter.is-open .shutter-slats i:nth-child(8) { transition-delay: 0.23s; }
        .print-fields { border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; background: #fff; }
        .print-fields h2 { font-size: 13px; margin: 0 0 4px; }
        .print-fields p { margin: 0 0 8px; color: #5c6778; font-size: 12px; }
        .print-fields .groups { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px 16px; }
        .print-fields h3 { margin: 0 0 4px; font-size: 10px; letter-spacing: 0.04em; text-transform: uppercase; color: #5c6778; }
        .print-fields label { display: flex; align-items: center; gap: 6px; margin: 2px 0; cursor: pointer; }
        .print-fields .actions { display: flex; gap: 8px; margin-top: 8px; }
        .special-instructions { margin: 0 0 12px; padding: 8px 10px; background: #fef3c7; border: 1px solid #d97706; border-left: 6px solid #b45309; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .special-instructions-label { font-size: 10px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #92400e; }
        .special-instructions-text { margin-top: 3px; font-size: 14px; font-weight: 800; white-space: pre-line; color: #1c1917; }
        @media (max-width: 720px) { .print-fields .groups { grid-template-columns: 1fr; } }
        @media print {
            .toolbar, .no-print, .actions { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <a href="{{ $back }}">Back</a>
        <div class="toolbar-right">
            <button type="button" id="print-options-toggle" aria-expanded="false" aria-controls="print-options-shutter">
                Print options
                <svg class="chevron" width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <button type="button" class="primary" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="shutter no-print" id="print-options-shutter">
        <div class="shutter-clip">
            <div class="shutter-slats" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
            <div class="print-fields">
                <h2>Print fields</h2>
                <p>Untick a field to leave it off this {{ strtolower($document['title']) }}. The choice is remembered on this browser. The document number, party, item, quantity, rate, amount, and total stay on the print.</p>
                <div class="groups">
                    <div>
                        <h3>Document</h3>
                        <label><input type="checkbox" data-print-field value="date" checked> Date</label>
                        <label><input type="checkbox" data-print-field value="reference" checked> Reference</label>
                        <label><input type="checkbox" data-print-field value="place" checked> Place of supply</label>
                        <label><input type="checkbox" data-print-field value="narration" checked> Special instructions</label>
                    </div>
                    <div>
                        <h3>Company</h3>
                        <label><input type="checkbox" data-print-field value="address" checked> Address</label>
                        <label><input type="checkbox" data-print-field value="gstin" checked> GSTIN</label>
                        <label><input type="checkbox" data-print-field value="branch" checked> Branch</label>
                    </div>
                    <div>
                        <h3>{{ $document['party_label'] }}</h3>
                        <label><input type="checkbox" data-print-field value="party-gstin" checked> GSTIN</label>
                        <label><input type="checkbox" data-print-field value="party-state" checked> State</label>
                    </div>
                    <div>
                        <h3>Line items</h3>
                        <label><input type="checkbox" data-print-field value="hsn" checked> HSN</label>
                        <label><input type="checkbox" data-print-field value="discount" checked> Discount</label>
                        <label><input type="checkbox" data-print-field value="taxable" checked> Taxable</label>
                        <label><input type="checkbox" data-print-field value="cgst" checked> CGST</label>
                        <label><input type="checkbox" data-print-field value="sgst" checked> SGST</label>
                        <label><input type="checkbox" data-print-field value="igst" checked> IGST</label>
                        <label><input type="checkbox" data-print-field value="cess" checked> Cess</label>
                    </div>
                    <div>
                        <h3>Footer</h3>
                        <label><input type="checkbox" data-print-field value="discount-total" checked> Discount total</label>
                        <label><input type="checkbox" data-print-field value="tax-total" checked> Tax total</label>
                        <label><input type="checkbox" data-print-field value="words" checked> Amount in words</label>
                        <label><input type="checkbox" data-print-field value="terms" checked> Terms</label>
                    </div>
                </div>
                <div class="actions">
                    <button type="button" id="print-fields-all">Show all</button>
                    <button type="button" id="print-fields-none">Hide optional fields</button>
                </div>
            </div>
        </div>
    </div>

    @if (! empty($document['narration']))
        <div class="special-instructions" data-col="narration">
            <div class="special-instructions-label">Order Notes / Special Instructions</div>
            <div class="special-instructions-text">{{ $document['narration'] }}</div>
        </div>
    @endif

    <header class="head">
        <div>
            <h1>{{ $document['company'] }}</h1>
            @foreach ($document['address'] as $line)
                <div class="muted" data-col="address">{{ $line }}</div>
            @endforeach
            @if ($document['gstin'])
                <div data-col="gstin">GSTIN {{ $document['gstin'] }}</div>
            @endif
            @if ($document['branch'])
                <div class="muted" data-col="branch">{{ $document['branch'] }}</div>
            @endif
        </div>
        <div>
            <h2>{{ $document['title'] }}</h2>
            <div><strong>{{ $document['number'] }}</strong></div>
            <div data-col="date">{{ $document['date'] }}</div>
            @if ($document['reference'])
                <div class="muted" data-col="reference">Ref {{ $document['reference'] }}</div>
            @endif
            @if ($document['place'])
                <div class="muted" data-col="place">Place of supply {{ $document['place'] }}</div>
            @endif
        </div>
    </header>

    <section class="meta">
        <div class="party">
            <h2>{{ $document['party_label'] }}</h2>
            <div>{{ $document['party'] ?: '—' }}</div>
            @if ($document['party_gstin'])
                <div data-col="party-gstin">GSTIN {{ $document['party_gstin'] }}</div>
            @endif
            @if ($document['party_state'])
                <div class="muted" data-col="party-state">{{ $document['party_state'] }}</div>
            @endif
        </div>
    </section>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th data-col="hsn">HSN</th>
                <th class="money">Qty</th>
                <th class="money">Rate</th>
                <th class="money" data-col="discount">Discount</th>
                <th class="money" data-col="taxable">Taxable</th>
                <th class="money" data-col="cgst">Cgst</th>
                <th class="money" data-col="sgst">Sgst</th>
                <th class="money" data-col="igst">Igst</th>
                <th class="money" data-col="cess">Cess</th>
                <th class="money">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($document['lines'] as $line)
                <tr>
                    <td>{{ $line['item'] }}</td>
                    <td data-col="hsn">{{ $line['hsn'] ?: '—' }}</td>
                    <td class="money">{{ $line['quantity'] }}</td>
                    <td class="money">{{ $line['rate'] }}</td>
                    <td class="money" data-col="discount">{{ $line['discount'] }}</td>
                    <td class="money" data-col="taxable">{{ $line['taxable'] }}</td>
                    <td class="money" data-col="cgst">{{ $line['cgst'] }}</td>
                    <td class="money" data-col="sgst">{{ $line['sgst'] }}</td>
                    <td class="money" data-col="igst">{{ $line['igst'] }}</td>
                    <td class="money" data-col="cess">{{ $line['cess'] }}</td>
                    <td class="money">{{ $line['amount'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="money">{{ $document['subtotal'] }}</td></tr>
        <tr data-col="discount-total"><td>Discount</td><td class="money">{{ $document['discount'] }}</td></tr>
        @if (! empty($document['delivery']) && $document['delivery'] !== '0.00' && $document['delivery'] !== '0')
            <tr><td>Delivery charges</td><td class="money">{{ $document['delivery'] }}</td></tr>
        @endif
        @if (! empty($document['purchase_ledger']))
            <tr><td>Purchase ledger</td><td>{{ $document['purchase_ledger'] }}</td></tr>
        @endif
        <tr data-col="tax-total"><td>Tax</td><td class="money">{{ $document['tax'] }}</td></tr>
        <tr class="grand"><td>Total</td><td class="money">{{ $document['grand'] }}</td></tr>
    </table>
    @if (! empty($document['words']))
        <p data-col="words">Amount in words: {{ $document['words'] }}</p>
    @endif

    @if ($document['terms'])
        <section class="terms" data-col="terms">
            <h2>Terms</h2>
            <p>{{ $document['terms'] }}</p>
        </section>
    @endif

    <script>
    (function () {
        var key = 'trading-print-fields';
        var boxes = document.querySelectorAll('[data-print-field]');
        function read() {
            try { return JSON.parse(localStorage.getItem(key) || '{}') || {}; }
            catch (e) { return {}; }
        }
        function apply() {
            var saved = read();
            boxes.forEach(function (box) { box.checked = saved[box.value] !== false; });
            document.querySelectorAll('[data-col]').forEach(function (el) {
                el.style.display = saved[el.getAttribute('data-col')] === false ? 'none' : '';
            });
        }
        function save() {
            var saved = {};
            boxes.forEach(function (box) { saved[box.value] = box.checked; });
            localStorage.setItem(key, JSON.stringify(saved));
            apply();
        }
        boxes.forEach(function (box) { box.addEventListener('change', save); });
        var showAll = document.getElementById('print-fields-all');
        var hideOptional = document.getElementById('print-fields-none');
        if (showAll) showAll.addEventListener('click', function () { boxes.forEach(function (box) { box.checked = true; }); save(); });
        if (hideOptional) hideOptional.addEventListener('click', function () { boxes.forEach(function (box) { box.checked = false; }); save(); });
        apply();
        var toggle = document.getElementById('print-options-toggle');
        var shutter = document.getElementById('print-options-shutter');
        if (toggle && shutter) {
            toggle.addEventListener('click', function () {
                var open = shutter.classList.toggle('is-open');
                toggle.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }
    })();
    </script>
</body>
</html>
