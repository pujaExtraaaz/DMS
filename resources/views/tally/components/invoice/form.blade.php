@props([
    'action',
    'method' => 'POST',
    'kind',
    'invoice',
    'lines',
    'partyLedgers',
    'accountLedgers',
    'nextNumber',
    'products' => [],
    'godowns' => [],
    'taxRates' => [],
    'hsnSacs' => [],
    'priceLists' => [],
])
<form class="tally-voucher tally-vch tally-co" method="POST" action="{{ $action }}" id="invoice-form" data-company-state="{{ app(\Tally\Context\WorkspaceContext::class)->company()?->state }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    @if (old('sales_order_id', $invoice->sales_order_id))
        <input type="hidden" name="sales_order_id" value="{{ old('sales_order_id', $invoice->sales_order_id) }}">
    @endif
    <div class="tally-co-sheet">
    <div class="tally-co-title">{{ $kind->label() }}</div>
    @if (in_array($kind->value, ['sales', 'purchase'], true))
        @include('tally::companies._yesno', [
            'name' => 'reverse_charge',
            'label' => 'Reverse charge',
            'value' => old('reverse_charge', $invoice->reverse_charge) ? '1' : '0',
            'options' => ['No', 'Yes'],
            'values' => ['0', '1'],
        ])
    @endif
    <x-tally::invoice.header :kind="$kind" :invoice="$invoice" :next-number="$nextNumber" />
    <x-tally::invoice.party :kind="$kind" :invoice="$invoice" :party-ledgers="$partyLedgers" :account-ledgers="$accountLedgers" />
    @if (count($priceLists) > 0)
        <label class="tally-co-row">
            <span>Price level</span>
            <span>:</span>
            <select id="price_level" class="input" data-price-level>
                <option value="">Standard</option>
                @foreach ($priceLists as $list)
                    <option value="{{ $list->id }}" data-rates='@json($list->lines->mapWithKeys(fn ($line) => [(string) $line->product_id => (string) $line->rate]))'>{{ $list->name }}</option>
                @endforeach
            </select>
        </label>
    @endif
    <label class="tally-co-row">
        <span>Scan barcode</span>
        <span>:</span>
        <span class="gstin-fetch">
            <input class="input" name="barcode_scan" data-barcode-scan data-barcode-scope="invoice" data-barcode-url="{{ tally_route('books.tally.barcodes.lookup') }}" placeholder="Scan, then Enter" autocomplete="off">
            <button class="btn" type="button" data-barcode-camera>Camera</button>
        </span>
    </label>

    @foreach ($errors->getMessages() as $field => $messages)
        @if (in_array($field, ['lines', 'status', 'invoice', 'branch_id', 'financial_year_id'], true))
            @foreach ($messages as $message)
                <p class="field-error">{{ $message }}</p>
            @endforeach
        @endif
    @endforeach

    <x-tally::invoice.lines :lines="$lines" :products="$products" :godowns="$godowns" :tax-rates="$taxRates" :hsn-sacs="$hsnSacs" :purchase="in_array($kind->value, ['purchase', 'debit_note'], true)" />
    <x-tally::invoice.totals :invoice="$invoice" live />
    <x-tally::invoice.narration :invoice="$invoice" />
    </div>
    <x-tally::invoice.actions :kind="$kind" />
</form>
<script>
(function () {
    const root = document.getElementById('invoice-lines');
    if (!root) {
        return;
    }

    function cents(value) {
        const raw = String(value ?? '').trim();
        if (raw === '') {
            return 0n;
        }
        if (!/^\d+(\.\d{1,2})?$/.test(raw)) {
            return null;
        }
        const parts = raw.split('.');
        const fraction = (parts[1] || '').padEnd(2, '0').slice(0, 2);
        return (BigInt(parts[0]) * 100n) + BigInt(fraction);
    }

    function millis(value) {
        const raw = String(value ?? '').trim();
        if (raw === '') {
            return 0n;
        }
        if (!/^\d+(\.\d{1,4})?$/.test(raw)) {
            return null;
        }
        const parts = raw.split('.');
        const fraction = (parts[1] || '').padEnd(4, '0').slice(0, 4);
        return (BigInt(parts[0]) * 10000n) + BigInt(fraction);
    }

    function format(amount) {
        const negative = amount < 0n;
        const abs = negative ? -amount : amount;
        return (negative ? '-' : '') + (abs / 100n).toString() + '.' + (abs % 100n).toString().padStart(2, '0');
    }

    function refresh() {
        let subtotal = 0n;
        let discount = 0n;
        let tax = 0n;
        let invalid = false;

        root.querySelectorAll('tbody tr').forEach(function (row) {
            const qty = millis(row.querySelector('input[data-qty]')?.value);
            const rate = cents(row.querySelector('input[data-rate]')?.value);
            const disc = cents(row.querySelector('input[data-discount]')?.value);
            const taxCents = cents(row.querySelector('input[data-tax]')?.value);
            const cell = row.querySelector('[data-line-total]');
            if (qty === null || rate === null || disc === null || taxCents === null) {
                invalid = true;
                if (cell) {
                    cell.textContent = '—';
                }
                return;
            }
            const amount = ((qty * rate) + 5000n) / 10000n;
            const line = amount - disc + taxCents;
            if (cell) {
                cell.textContent = format(line);
            }
            subtotal += amount;
            discount += disc;
            tax += taxCents;
        });

        const grand = subtotal - discount + tax;
        const write = function (id, value) {
            const node = document.getElementById(id);
            if (node) {
                node.textContent = invalid ? '—' : format(value);
            }
        };
        write('invoice-subtotal', subtotal);
        write('invoice-discount', discount);
        write('invoice-tax', tax);
        write('invoice-grand', grand < 0n ? 0n : grand);
    }

    function nextIndex() {
        let next = 0;
        root.querySelectorAll('[name^="lines["]').forEach(function (input) {
            const match = input.name.match(/^lines\[(\d+)\]/);
            if (match) {
                next = Math.max(next, Number(match[1]) + 1);
            }
        });
        return next;
    }

    function priceFor(productId) {
        const level = document.querySelector('[data-price-level]');
        const rates = level?.selectedOptions?.[0]?.dataset.rates;
        if (!productId || !rates) {
            return '';
        }
        try {
            return JSON.parse(rates)[String(productId)] || '';
        } catch (error) {
            return '';
        }
    }

    function hundredths(value) {
        const raw = String(value ?? '').trim();
        if (raw === '' || !/^\d+(\.\d+)?$/.test(raw)) {
            return 0n;
        }
        const parts = raw.split('.');
        return (BigInt(parts[0]) * 100n) + BigInt((parts[1] || '').padEnd(2, '0').slice(0, 2));
    }

    function fillTax(row) {
        const select = row.querySelector('[name$="[tax_rate_id]"]');
        const option = select?.selectedOptions?.[0];
        const taxInput = row.querySelector('input[data-tax]');
        if (!option?.value || !taxInput || option.dataset.igst === undefined) {
            return;
        }
        const qty = millis(row.querySelector('input[data-qty]')?.value);
        const rate = cents(row.querySelector('input[data-rate]')?.value);
        const disc = cents(row.querySelector('input[data-discount]')?.value);
        if (qty === null || rate === null || disc === null) {
            return;
        }
        const taxable = ((qty * rate) + 5000n) / 10000n - disc;
        const party = document.querySelector('[name="party_ledger_id"]')?.selectedOptions?.[0]?.dataset.state || '';
        const company = document.getElementById('invoice-form')?.dataset.companyState || '';
        const inter = party !== '' && company !== '' && party.toLowerCase() !== company.toLowerCase();
        const rateHundredths = inter
            ? hundredths(option.dataset.igst) + hundredths(option.dataset.cess)
            : hundredths(option.dataset.cgst) + hundredths(option.dataset.sgst) + hundredths(option.dataset.cess);
        const taxCents = taxable > 0n ? (taxable * rateHundredths + 5000n) / 10000n : 0n;
        taxInput.value = format(taxCents);
    }

    root.addEventListener('change', function (event) {
        const row = event.target.closest('tr');
        if (!row) {
            if (event.target.name === 'party_ledger_id') {
                root.querySelectorAll('tbody tr').forEach(fillTax);
                refresh();
            }
            return;
        }
        if (event.target.name?.endsWith('[product_id]')) {
            const option = event.target.selectedOptions?.[0];
            const item = row.querySelector('[data-item]');
            if (option?.value && item) {
                item.value = option.textContent.trim();
            }
            const rate = row.querySelector('input[data-rate]');
            const levelRate = priceFor(option?.value);
            if (rate && levelRate) {
                rate.value = levelRate;
            } else if (option?.dataset.rate && rate && rate.value === '') {
                rate.value = option.dataset.rate;
            }
            const hsn = row.querySelector('[data-hsn-store]');
            if (option?.dataset.hsn && hsn) {
                hsn.value = option.dataset.hsn;
                const query = hsn.closest('[data-picker]')?.querySelector('[data-picker-query]');
                if (query) {
                    query.value = option.dataset.hsn ? (hsn.selectedOptions[0]?.textContent.trim() || '') : '';
                }
                hsn.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
        if (event.target.name?.endsWith('[tax_rate_id]') || event.target.name?.endsWith('[hsn_sac_id]')) {
            fillTax(row);
        }
        refresh();
    });
    root.addEventListener('input', function (event) {
        if (event.target.matches('input[data-qty], input[data-rate], input[data-discount]')) {
            const row = event.target.closest('tr');
            if (row?.querySelector('[name$="[tax_rate_id]"]')?.value) {
                fillTax(row);
            }
        }
        refresh();
    });
    document.querySelector('[data-price-level]')?.addEventListener('change', function () {
        root.querySelectorAll('tbody tr').forEach(function (row) {
            const product = row.querySelector('[name$="[product_id]"]');
            const rate = row.querySelector('input[data-rate]');
            const levelRate = priceFor(product?.value);
            if (rate && levelRate) {
                rate.value = levelRate;
                fillTax(row);
            }
        });
        refresh();
    });
    document.getElementById('invoice-form').addEventListener('click', function (event) {
        if (!event.target.closest('[data-add-line]')) {
            return;
        }
        const template = document.getElementById('invoice-line');
        root.querySelector('tbody').insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(nextIndex())));
        refresh();
    });
    root.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-line]');
        if (!button) {
            return;
        }
        const body = root.querySelector('tbody');
        if (body.querySelectorAll('tr').length < 2) {
            return;
        }
        button.closest('tr').remove();
        refresh();
    });
    refresh();
})();
</script>
