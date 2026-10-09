@props([
    'action',
    'method' => 'POST',
    'voucher',
    'entries',
    'ledgers',
    'cashLedgers',
    'otherLedgers',
    'nextNumber',
    'costCentres' => [],
    'openBills' => [],
    'currencies' => [],
    'paymentRequests' => [],
    'merchants' => [],
    'company' => null,
    'balances' => [],
    'voucherClasses' => [],
    'deductionSections' => [],
    'postOnly' => false,
    'back' => null,
])

@php
    use Tally\Accounting\VoucherType;

    $blank = ['ledger_id' => '', 'debit' => '', 'credit' => '', 'narration' => '', 'reference' => '', 'cost_centre_id' => ''];
    $doubleEntry = in_array($voucher->voucher_type, [VoucherType::Journal, VoucherType::Receipt], true);
    $lineCentres = $voucher->voucher_type === VoucherType::Journal ? $costCentres : [];
    $classOptions = collect($voucherClasses)->filter(fn ($class) => $class->voucher_type === $voucher->voucher_type->value);
    $dateLabel = 'Date';
    $openLedger = match (true) {
        $voucher->voucher_type === VoucherType::Contra => 'account',
        $voucher->voucher_type === VoucherType::Payment && request()->boolean('stat') => 'particulars',
        $voucher->voucher_type === VoucherType::Payment => 'account',
        default => null,
    };
    $backUrl = $back ?: tally_route('books.tally.vouchers.index', ['voucher_type' => $voucher->voucher_type->value]);
    $voucherDate = old('voucher_date', optional($voucher->voucher_date)->toDateString());
    $sections = match ($voucher->voucher_type) {
        VoucherType::Payment => [
            ['side' => 'credit', 'title' => 'Account', 'note' => 'Cash or bank', 'ledgers' => $cashLedgers],
            ['side' => 'debit', 'title' => 'Particulars', 'note' => 'Party or expense', 'ledgers' => $otherLedgers],
        ],
        VoucherType::Receipt => [
            ['side' => 'debit', 'title' => 'Account', 'note' => 'Cash or bank', 'ledgers' => $cashLedgers],
            ['side' => 'credit', 'title' => 'Particulars', 'note' => 'Party or income', 'ledgers' => $otherLedgers],
        ],
        VoucherType::Contra => [
            ['side' => 'credit', 'title' => 'Account', 'note' => 'Account the money leaves', 'ledgers' => $cashLedgers],
            ['side' => 'debit', 'title' => 'Particulars', 'note' => 'Account that receives the money', 'ledgers' => $cashLedgers],
        ],
        default => [],
    };

    $amount = function (array $entry, string $side): string {
        $value = $entry[$side] ?? '';

        return ($value === '0' || $value === '0.00' || $value === 0) ? '' : (string) $value;
    };

    $rowsFor = function (string $side) use ($entries, $blank): array {
        $rows = [];

        foreach ($entries as $entry) {
            $debit = (float) ($entry['debit'] ?? 0);
            $credit = (float) ($entry['credit'] ?? 0);

            if ($side === 'debit' && $debit > 0) {
                $rows[] = $entry;
            }

            if ($side === 'credit' && $credit > 0) {
                $rows[] = $entry;
            }
        }

        return $rows === [] ? [$blank] : $rows;
    };

    $indexedSections = [];
    $lineIndex = 0;

    foreach ($sections as $section) {
        $rows = [];

        foreach ($rowsFor($section['side']) as $entry) {
            $rows[] = [
                'index' => $lineIndex,
                'entry' => $entry,
                'amount' => $amount($entry, $section['side']),
            ];
            $lineIndex++;
        }

        $indexedSections[] = $section + ['rows' => $rows];
    }
@endphp

<form class="tally-voucher tally-vch tally-co {{ $doubleEntry ? '' : 'is-single' }}" method="POST" action="{{ $action }}" id="voucher-form" data-balances='@json($balances)' @if ($openLedger) data-open-ledger="{{ $openLedger }}" @endif>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    @if (request('return') === 'day-book')
        <input type="hidden" name="return" value="day-book">
    @endif

    <div class="tally-co-sheet">
        <div class="tally-co-title">Accounting Voucher Creation</div>
        <div class="tally-co-head">
            <label class="tally-co-row">
                <span>{{ $voucher->voucher_type->label() }}</span>
                <span>:</span>
                <span class="tally-co-value">No. {{ $nextNumber }}</span>
            </label>
            <label class="tally-co-row">
                <span>Date</span>
                <span>:</span>
                <input class="input" name="voucher_date" type="date" value="{{ $voucherDate }}" required data-voucher-date aria-label="{{ $dateLabel }}">
            </label>
            <label class="tally-co-row">
                <span>Company</span>
                <span>:</span>
                <span class="tally-co-value">{{ $company->name ?? '' }}</span>
            </label>
            <label class="tally-co-row">
                <span>Voucher class</span>
                <span>:</span>
                <select class="input" name="voucher_class_id" data-voucher-class>
                    <option value="">Not Applicable</option>
                    @foreach ($classOptions as $class)
                        <option value="{{ $class->id }}" data-ledger="{{ $class->default_ledger_id }}" @selected((string) old('voucher_class_id', $voucher->voucher_class_id) === (string) $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <span data-voucher-weekday hidden>
            @if ($voucherDate)
                {{ \Illuminate\Support\Carbon::parse($voucherDate)->format('j-M-Y') }} {{ \Illuminate\Support\Carbon::parse($voucherDate)->format('l') }}
            @endif
        </span>
        <span data-voucher-flags hidden></span>
    <input type="hidden" name="is_post_dated" value="{{ old('is_post_dated', $voucher->is_post_dated) ? '1' : '0' }}" data-post-dated>
    <input type="hidden" name="is_optional" value="{{ old('is_optional', $voucher->is_optional) ? '1' : '0' }}" data-optional>
    <input type="hidden" name="is_memo" value="{{ old('is_memo', $voucher->is_memo || request()->boolean('memo')) ? '1' : '0' }}" data-memo>

    @if ($voucher->voucher_type === VoucherType::Payment)
        <label class="tally-co-row">
            <span>Nature of Payment</span>
            <span>:</span>
            <select class="input" id="nature_of_payment" name="nature_of_payment">
                <option value="">Not Applicable</option>
                @foreach ($deductionSections as $section)
                    <option value="{{ $section->section_code }}" @selected(old('nature_of_payment', $voucher->nature_of_payment) === $section->section_code)>{{ $section->section_code }} · {{ $section->name }}</option>
                @endforeach
            </select>
        </label>
        @if (request()->boolean('stat'))
            <p class="form-note">Stat Payment lists duty and tax ledgers in Particulars.</p>
        @endif
    @endif
    @if ($voucher->voucher_type === VoucherType::Journal)
        <label class="tally-co-row">
            <span>Reverses on</span>
            <span>:</span>
            <input class="input" type="date" name="reverses_on" value="{{ old('reverses_on', optional($voucher->reverses_on)->toDateString()) }}">
        </label>
        <label class="tally-co-row">
            <span>Memorandum</span>
            <span>:</span>
            <input type="checkbox" data-memo-toggle @checked(old('is_memo', $voucher->is_memo || request()->boolean('memo')))>
        </label>
    @endif
    <details class="tally-co-more">
        <summary>More details</summary>
        <div class="form-grid">
        <x-tally::form.field name="reference_number" label="Reference">
            <x-tally::form.input name="reference_number" value="{{ old('reference_number', $voucher->reference_number) }}" maxlength="50" />
        </x-form.field>
        @if (count($currencies) > 0)
            <x-tally::form.field name="currency_id" label="Currency">
                @include('tally::masters._picker', [
                    'name' => 'currency_id',
                    'options' => collect($currencies)->mapWithKeys(fn ($currency) => [$currency->id => $currency->code.' · '.$currency->name])->all(),
                    'current' => old('currency_id', $voucher->currency_id),
                    'meta' => collect($currencies)->mapWithKeys(fn ($currency) => [$currency->id => ['rate' => $currency->exchange_rate]])->all(),
                    'placeholder' => 'Company currency',
                    'optional' => true,
                ])
            </x-form.field>
            <x-tally::form.field name="exchange_rate" label="Exchange rate">
                <x-tally::form.input name="exchange_rate" data-exchange-rate value="{{ old('exchange_rate', $voucher->exchange_rate) }}" inputmode="decimal" />
            </x-form.field>
        @endif
        @if ($voucher->voucher_type === VoucherType::Payment && count($paymentRequests) > 0)
            <x-tally::form.field name="payment_request_id" label="Payment request">
                @include('tally::masters._picker', [
                    'name' => 'payment_request_id',
                    'options' => collect($paymentRequests)->mapWithKeys(fn ($request) => [$request->id => $request->reference.' · '.$request->ledger?->name.' · '.$request->amount])->all(),
                    'current' => old('payment_request_id', $voucher->payment_request_id),
                    'meta' => collect($paymentRequests)->mapWithKeys(fn ($request) => [$request->id => ['ledger' => $request->ledger_id, 'amount' => (string) ($request->amount - $request->paid_amount)]])->all(),
                    'placeholder' => 'Payment request',
                    'optional' => true,
                    'create' => tally_route('books.tally.payment-requests.create'),
                ])
            </x-form.field>
        @endif
        @if ($voucher->voucher_type === VoucherType::Payment && count($merchants) > 0)
            <x-tally::form.field name="merchant_profile_id" label="Merchant">
                @include('tally::masters._picker', [
                    'name' => 'merchant_profile_id',
                    'options' => collect($merchants)->mapWithKeys(fn ($merchant) => [$merchant->id => $merchant->name])->all(),
                    'current' => old('merchant_profile_id', $voucher->merchant_profile_id),
                    'meta' => collect($merchants)->mapWithKeys(fn ($merchant) => [$merchant->id => ['ledger' => $merchant->settlement_ledger_id]])->all(),
                    'placeholder' => 'Merchant',
                    'optional' => true,
                    'create' => tally_route('books.tally.merchant-profiles.create'),
                ])
            </x-form.field>
        @endif
        </div>
    </details>

    @foreach ($errors->getMessages() as $field => $messages)
        @if ($field === 'entries' || str_starts_with($field, 'entries.') || in_array($field, ['status', 'branch_id', 'financial_year_id'], true))
            @foreach ($messages as $message)
                <p class="field-error">{{ $message }}</p>
            @endforeach
        @endif
    @endforeach

    <div id="voucher-lines">
        @if ($doubleEntry)
            <div class="table-wrap">
                        <table class="data voucher-lines">
                            <colgroup>
                                <col>
                                <col class="col-money">
                                <col class="col-money">
                                <col class="col-remove">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Particulars</th>
                                    <th class="money">Debit</th>
                                    <th class="money">Credit</th>
                                    <th></th>
                                </tr>
                            </thead>
                    <tbody data-section="journal">
                        @foreach ($entries as $index => $entry)
                            @include('tally::vouchers._line', ['index' => $index, 'entry' => $entry, 'ledgers' => $ledgers, 'mode' => 'journal', 'costCentres' => $lineCentres, 'refLabel' => $voucher->voucher_type === VoucherType::Receipt ? 'Agst Ref' : 'New Ref'])
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            @foreach ($indexedSections as $section)
                <section class="voucher-section {{ $section['title'] === 'Account' ? 'is-account' : 'is-particulars' }}">
                    <h2>{{ $section['title'] }}</h2>
                    @if ($section['title'] === 'Account')
                        <p class="current-balance">Cur Bal <strong data-current-balance></strong></p>
                    @endif
                    <div class="table-wrap">
                        <table class="data voucher-lines">
                            <colgroup>
                                <col>
                                <col class="col-money">
                                <col class="col-remove">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>{{ $section['title'] === 'Account' ? '' : 'Particulars' }}</th>
                                    <th class="money">{{ $section['title'] === 'Account' ? '' : 'Amount' }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody data-section="{{ $section['side'] }}">
                                @foreach ($section['rows'] as $row)
                                    @include('tally::vouchers._line', ['index' => $row['index'], 'entry' => $row['entry'] + ['amount' => $row['amount']], 'ledgers' => $section['ledgers'], 'mode' => $section['side'], 'costCentres' => $lineCentres, 'account' => $section['title'] === 'Account', 'autofocus' => $loop->parent->first && $loop->first])
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @unless ($section['title'] === 'Account')
                        <button class="btn" type="button" data-add-line="{{ $section['side'] }}">Add row</button>
                    @endunless
                </section>
            @endforeach
        @endif
    </div>

    <div class="balance-bar">
        <span>Debit <strong id="total-debit">0.00</strong></span>
        <span>Credit <strong id="total-credit">0.00</strong></span>
        <strong id="voucher-difference">Difference 0.00</strong>
    </div>
    <p class="form-note">Totals here are a guide. The server rejects an unbalanced post and a ledger that does not belong on this voucher.</p>

    @if (in_array($voucher->voucher_type, [\Tally\Accounting\VoucherType::Receipt, \Tally\Accounting\VoucherType::Payment], true))
        <details class="tally-more">
        <summary>Adjust against bills</summary>
        <p class="form-note">Optional. Enter an amount against a bill for a partial or full settlement. Any remainder is kept as an advance.</p>
        <div class="table-wrap">
            <table class="data bill-allocate">
                <colgroup>
                    <col class="col-bill">
                    <col class="col-due">
                    <col class="col-outstanding">
                    <col class="col-allocate">
                </colgroup>
                <thead><tr><th>Bill</th><th>Due</th><th class="money">Outstanding</th><th class="money">Allocate</th></tr></thead>
                <tbody>
                    @forelse ($openBills as $index => $bill)
                        <tr>
                            <td>{{ $bill->ledger?->name }} · {{ $bill->bill_number }}<input type="hidden" name="allocations[{{ $index }}][bill_id]" value="{{ $bill->id }}"></td>
                            <td>{{ $bill->due_date?->format('d M Y') }}</td>
                            <td class="money">{{ number_format(($bill->outstanding_cents ?? 0) / 100, 2) }}</td>
                            <td><input class="input money" name="allocations[{{ $index }}][amount]" inputmode="decimal" value="{{ old('allocations.'.$index.'.amount') }}"></td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No open bills for this company yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </details>
    @endif

    @if ($voucher->voucher_type === VoucherType::Receipt)
        <label class="tally-co-row">
            <span>Name on Receipt</span>
            <span>:</span>
            <input class="input" name="name_on_receipt" data-name-on-receipt value="{{ old('name_on_receipt', $voucher->name_on_receipt) }}" maxlength="160">
        </label>
    @endif
    <label class="tally-co-row is-address">
        <span>Narration</span>
        <span>:</span>
        <textarea class="input tally-narration-box" name="narration" maxlength="1000">{{ old('narration', $voucher->narration) }}</textarea>
    </label>
    <div class="tally-co-rule"></div>
    <label class="tally-co-row">
        <span>Amount</span>
        <span>:</span>
        <strong class="tally-co-value" data-screen-total>0.00</strong>
    </label>
    </div>

    <div class="form-actions tally-co-actions">
        @if ($doubleEntry)
            <button class="btn" type="button" data-add-line="journal">Add row</button>
        @endif
        <a class="btn" data-esc href="{{ $backUrl }}">Q: Quit</a>
        <button class="btn btn-primary" type="button" data-ask-accept data-accept>A: Accept</button>
        @unless ($postOnly)
            <button class="btn" type="submit" name="action" value="draft">Save draft</button>
        @endunless
        @if ($voucher->exists && $voucher->isDraft())
            <button class="btn" type="submit" form="delete-voucher" data-delete>D: Delete</button>
        @endif
    </div>
    <dialog id="accept-ask" class="accept-ask">
        <p>Accept ?</p>
        <button class="btn" type="submit" name="action" value="post">Yes</button>
        <button class="btn" type="button" data-close-accept>No</button>
    </dialog>
</form>

@if ($doubleEntry)
    <template id="line-journal">
        @include('tally::vouchers._line', ['index' => '__INDEX__', 'entry' => $blank, 'ledgers' => $ledgers, 'mode' => 'journal', 'costCentres' => $lineCentres, 'refLabel' => $voucher->voucher_type === VoucherType::Receipt ? 'Agst Ref' : 'New Ref'])
    </template>
@else
    @foreach ($sections as $section)
        <template id="line-{{ $section['side'] }}">
            @include('tally::vouchers._line', ['index' => '__INDEX__', 'entry' => $blank, 'ledgers' => $section['ledgers'], 'mode' => $section['side'], 'costCentres' => $lineCentres, 'account' => false])
        </template>
    @endforeach
@endif

<script>
(function () {
    const root = document.getElementById('voucher-lines');

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

    const balanceMap = JSON.parse(document.getElementById('voucher-form')?.dataset.balances || '{}');
    const balanceLabel = document.querySelector('[data-current-balance]');
    const dateInput = document.querySelector('[data-voucher-date]');
    const weekday = document.querySelector('[data-voucher-weekday]');

    function showBalance() {
        if (!balanceLabel) {
            return;
        }

        const selected = root.querySelector('.is-account .picker-store');
        balanceLabel.textContent = selected ? (balanceMap[selected.value] || '') : '';
    }

    function showWeekday() {
        if (!dateInput || !weekday || !dateInput.value) {
            return;
        }

        const parsed = new Date(dateInput.value + 'T00:00:00');

        if (Number.isNaN(parsed.getTime())) {
            return;
        }

        const day = parsed.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }).replace(/ /g, '-');
        const week = parsed.toLocaleDateString('en-GB', { weekday: 'long' });
        weekday.innerHTML = day + '<br>' + week;
    }

    root.addEventListener('change', function (event) {
        if (event.target?.closest?.('.is-account')) {
            showBalance();
        }
    });
    dateInput?.addEventListener('change', showWeekday);
    showBalance();

    function mirrorAccount() {
        const account = root.querySelector('[data-account-amount]');

        if (!account) {
            return;
        }

        const other = account.getAttribute('data-amount') === 'debit' ? 'credit' : 'debit';
        let total = 0n;

        root.querySelectorAll('[data-amount="' + other + '"]').forEach(function (input) {
            const value = cents(input.value);

            if (value !== null) {
                total += value;
            }
        });

        account.value = format(total);
    }

    function lineMarks() {
        root.querySelectorAll('tr').forEach(function (row) {
            const store = row.querySelector('.picker-store');
            const balance = row.querySelector('[data-line-balance]');

            if (store && balance) {
                const text = balanceMap[store.value] || '';
                balance.textContent = text ? 'Cur Bal: ' + text : '';
            }

            const mark = row.querySelector('[data-by-to]');

            if (!mark) {
                return;
            }

            const debit = cents(row.querySelector('[data-amount="debit"]')?.value);
            const credit = cents(row.querySelector('[data-amount="credit"]')?.value);
            mark.textContent = credit > 0n && !(debit > 0n) ? 'To' : 'By';
        });
    }

    function refresh() {
        mirrorAccount();
        lineMarks();
        let debit = 0n;
        let credit = 0n;
        let invalid = false;

        root.querySelectorAll('tr').forEach(function (row) {
            const debitInput = row.querySelector('[data-amount="debit"]');
            const creditInput = row.querySelector('[data-amount="credit"]');
            const debitCents = debitInput ? cents(debitInput.value) : 0n;
            const creditCents = creditInput ? cents(creditInput.value) : 0n;
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
        const screen = document.querySelector('[data-screen-total]');

        if (screen) {
            screen.textContent = format(debit > credit ? debit : credit);
        }
    }

    function nextIndex() {
        let next = 0;
        root.querySelectorAll('[name^="entries["]').forEach(function (input) {
            const match = input.name.match(/^entries\[(\d+)\]/);
            if (match) {
                next = Math.max(next, Number(match[1]) + 1);
            }
        });
        return next;
    }

    document.getElementById('voucher-form').addEventListener('change', function (event) {
        const store = event.target;

        if (store.name === 'currency_id') {
            const rate = store.selectedOptions[0]?.dataset.rate;
            const field = document.querySelector('[data-exchange-rate]');

            if (rate && field) {
                field.value = rate;
            }
        }

        if (store.name === 'voucher_class_id') {
            const ledger = store.selectedOptions[0]?.dataset.ledger;
            const picker = root.querySelector('.picker-store');

            if (ledger && picker) {
                picker.value = ledger;
                const label = picker.querySelector('option[value="' + ledger + '"]')?.textContent || '';
                const query = picker.closest('[data-picker]')?.querySelector('[data-picker-query]');

                if (query) {
                    query.value = label.trim();
                }
            }
        }

        if (store.name === 'payment_request_id' || store.name === 'merchant_profile_id') {
            const option = store.selectedOptions[0];
            const ledger = option?.dataset.ledger;
            const particulars = root.querySelector('section:not(.is-account) .picker-store');

            if (ledger && particulars) {
                particulars.value = ledger;
                const label = particulars.querySelector('option[value="' + ledger + '"]')?.textContent || '';
                particulars.closest('[data-picker]')?.querySelector('[data-picker-query]')?.setAttribute('value', label.trim());
                const query = particulars.closest('[data-picker]')?.querySelector('[data-picker-query]');

                if (query) {
                    query.value = label.trim();
                }
            }

            if (store.name === 'payment_request_id' && option?.dataset.amount) {
                const amount = root.querySelector('section:not(.is-account) [data-amount]');

                if (amount) {
                    amount.value = option.dataset.amount;
                    refresh();
                }
            }
        }
    });

    root.addEventListener('input', refresh);

    document.getElementById('voucher-form').addEventListener('click', function (event) {
        const add = event.target.closest('[data-add-line]');
        if (!add) {
            return;
        }
        const template = document.getElementById('line-' + add.getAttribute('data-add-line'));
        const body = root.querySelector('tbody[data-section="' + add.getAttribute('data-add-line') + '"]');
        body.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(nextIndex())));
        refresh();
    });

    root.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-line]');
        if (!button) {
            return;
        }
        const body = button.closest('tbody');
        if (body.querySelectorAll('tr').length < 2) {
            return;
        }
        button.closest('tr').remove();
        refresh();
    });

    const ask = document.getElementById('accept-ask');
    document.querySelector('[data-ask-accept]')?.addEventListener('click', function () {
        ask?.showModal();
    });
    document.querySelector('[data-close-accept]')?.addEventListener('click', function () {
        ask?.close();
    });

    function paintFlags() {
        const flags = document.querySelector('[data-voucher-flags]');
        const posted = document.querySelector('[data-post-dated]');
        const optional = document.querySelector('[data-optional]');

        if (!flags) {
            return;
        }

        const memo = document.querySelector('[data-memo]');
        flags.textContent = [posted?.value === '1' ? 'Post-Dated' : '', optional?.value === '1' ? 'Optional' : '', memo?.value === '1' ? 'Memorandum' : ''].filter(Boolean).join(' · ');
    }

    document.querySelector('[data-memo-toggle]')?.addEventListener('change', function (event) {
        const field = document.querySelector('[data-memo]');

        if (field) {
            field.value = event.target.checked ? '1' : '0';
        }

        paintFlags();
    });

    document.addEventListener('voucher-flag', paintFlags);
    paintFlags();
    refresh();
})();
</script>
