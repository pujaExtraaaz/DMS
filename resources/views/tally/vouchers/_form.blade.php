@php
    $row = [
        'ledger_id' => '',
        'debit' => '',
        'credit' => '',
        'narration' => '',
        'reference' => '',
    ];
@endphp

<form method="POST" action="{{ $action }}" id="voucher-form">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="form-grid">
        <x-tally::form.field name="voucher_date" label="Voucher date" required>
            <x-tally::form.input name="voucher_date" type="date" value="{{ old('voucher_date', optional($voucher->voucher_date)->toDateString()) }}" required />
        </x-form.field>
        <x-tally::form.field name="voucher_number" label="Voucher number">
            <input class="input" value="{{ $nextNumber }}" readonly>
            @unless ($voucher->exists)
                <p class="form-note">Assigned when you save. This preview does not reserve the number.</p>
            @endunless
        </x-form.field>
        <x-tally::form.field name="reference_number" label="Reference">
            <x-tally::form.input name="reference_number" value="{{ old('reference_number', $voucher->reference_number) }}" maxlength="50" />
        </x-form.field>
        <x-tally::form.field name="narration" label="Narration" class="span-2">
            <x-tally::form.textarea name="narration" maxlength="1000" :value="old('narration', $voucher->narration) ?? ''" />
        </x-form.field>
    </div>

    @foreach ($errors->getMessages() as $field => $messages)
        @if ($field === 'entries' || str_starts_with($field, 'entries.') || in_array($field, ['status', 'branch_id', 'financial_year_id'], true))
            @foreach ($messages as $message)
                <p class="field-error">{{ $message }}</p>
            @endforeach
        @endif
    @endforeach

    <div class="table-wrap">
        <table class="data voucher-lines">
            <thead>
                <tr>
                    <th>Ledger</th>
                    <th class="money">Debit</th>
                    <th class="money">Credit</th>
                    <th>Line narration</th>
                    <th>Reference</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="voucher-lines">
                @foreach ($entries as $index => $entry)
                    @include('tally::vouchers._line', ['index' => $index, 'entry' => $entry, 'ledgers' => $ledgers])
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th class="money" id="total-debit">0.00</th>
                    <th class="money" id="total-credit">0.00</th>
                    <th colspan="3" id="voucher-difference">Difference 0.00</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <p class="form-note">Each line is either a debit or a credit. Totals here are a guide; the server rejects an unbalanced post.</p>

    <div class="form-actions">
        <button class="btn" type="button" id="add-voucher-line">Add row</button>
        <button class="btn" type="submit" name="action" value="draft">Save draft</button>
        <button class="btn btn-primary" type="submit" name="action" value="post">Post voucher</button>
        <a class="btn" href="{{ tally_route('books.tally.vouchers.index') }}">Cancel</a>
    </div>
</form>

<template id="voucher-line-template">
    @include('tally::vouchers._line', ['index' => '__INDEX__', 'entry' => $row, 'ledgers' => $ledgers])
</template>

<script>
(function () {
    const body = document.getElementById('voucher-lines');
    const template = document.getElementById('voucher-line-template');
    let nextIndex = body.querySelectorAll('tr').length;

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

    function format(amount) {
        const negative = amount < 0n;
        const abs = negative ? -amount : amount;
        return (negative ? '-' : '') + (abs / 100n).toString() + '.' + (abs % 100n).toString().padStart(2, '0');
    }

    function refresh() {
        let debit = 0n;
        let credit = 0n;
        let invalid = false;

        body.querySelectorAll('tr').forEach(function (row) {
            const debitCents = cents(row.querySelector('[data-amount="debit"]').value);
            const creditCents = cents(row.querySelector('[data-amount="credit"]').value);
            if (debitCents === null || creditCents === null) {
                invalid = true;
                return;
            }
            debit += debitCents;
            credit += creditCents;
        });

        document.getElementById('total-debit').textContent = invalid ? '—' : format(debit);
        document.getElementById('total-credit').textContent = invalid ? '—' : format(credit);
        const difference = document.getElementById('voucher-difference');
        if (invalid) {
            difference.textContent = 'Check the amounts';
            difference.className = 'is-bad';
            return;
        }
        const gap = debit - credit;
        difference.textContent = gap === 0n ? 'Balanced' : 'Difference ' + format(gap < 0n ? -gap : gap);
        difference.className = gap === 0n ? 'is-ok' : 'is-bad';
    }

    body.addEventListener('input', refresh);

    document.getElementById('add-voucher-line').addEventListener('click', function () {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
        nextIndex += 1;
        body.insertAdjacentHTML('beforeend', html);
        refresh();
    });

    body.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-line]');
        if (!button || body.querySelectorAll('tr').length < 2) {
            return;
        }
        button.closest('tr').remove();
        refresh();
    });

    refresh();
})();
</script>
