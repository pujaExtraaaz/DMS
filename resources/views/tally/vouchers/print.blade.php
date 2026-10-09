<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $voucher->voucher_number }} — {{ $voucher->voucher_type->label() }}</title>
    <style>
        body { font-family: "Segoe UI", sans-serif; color: #1a2332; margin: 24px; font-size: 13px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #d3d7e0; text-align: left; padding: 6px 4px; }
        .money { text-align: right; font-variant-numeric: tabular-nums; }
        .muted { color: #5c6778; }
        .actions { margin-top: 16px; }
        button { font: inherit; padding: 6px 10px; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <p class="muted">{{ $voucher->company->name }} · {{ $voucher->branch->code }} · {{ $voucher->financialYear->name }}</p>
    <h1>{{ $voucher->voucher_type->label() }} {{ $voucher->voucher_number }}</h1>
    <p>{{ $voucher->voucher_date->format('d M Y') }} · {{ $voucher->status->label() }}</p>
    <p>Reference: {{ $voucher->reference_number ?: '—' }}</p>
    @if ($voucher->name_on_receipt)
        <p>Name on Receipt: {{ $voucher->name_on_receipt }}</p>
    @endif
    @if ($voucher->is_post_dated)
        <p>Post-Dated</p>
    @endif
    @if ($voucher->is_optional)
        <p>Optional</p>
    @endif
    @if ($voucher->is_memo)
        <p>Memorandum</p>
    @endif
    @if ($voucher->nature_of_payment)
        <p>Nature of Payment: {{ $voucher->nature_of_payment }}</p>
    @endif
    @if ($voucher->reverses_on)
        <p>Reverses on {{ $voucher->reverses_on->format('d M Y') }}</p>
    @endif
    <p>Narration: {{ $voucher->narration ?: '—' }}</p>
    <table>
        <thead>
            <tr>
                <th>Ledger</th>
                <th class="money">Debit</th>
                <th class="money">Credit</th>
                <th>Narration</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($voucher->entries as $entry)
                <tr>
                    <td>{{ $entry->ledger->name }}</td>
                    <td class="money">{{ $entry->debit }}</td>
                    <td class="money">{{ $entry->credit }}</td>
                    <td>{{ $entry->narration ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th class="money">{{ $voucher->total_debit }}</th>
                <th class="money">{{ $voucher->total_credit }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
    <p class="muted">Created {{ $voucher->created_at?->format('d M Y H:i') }} by {{ $voucher->creator->name ?? '—' }}
        @if ($voucher->posted_at) · Posted {{ $voucher->posted_at->format('d M Y H:i') }} @endif
        @if ($voucher->cancelled_at) · Cancelled {{ $voucher->cancelled_at->format('d M Y H:i') }} @endif
    </p>
    <div class="actions">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ tally_route('books.tally.vouchers.show', $voucher) }}">Back</a>
    </div>
</body>
</html>
