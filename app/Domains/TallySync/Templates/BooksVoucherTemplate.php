<?php

namespace App\Domains\TallySync\Templates;

use Tally\Accounting\VoucherType;
use Tally\Models\Voucher;

/**
 * Books voucher XML for TallyPrime, including cheque, bill, cost centre, and godown lines.
 * adjustment and transfer are sent as Journal.
 */
class BooksVoucherTemplate
{
    /** @var array<string, string> */
    public const TALLY_VOUCHER_NAMES = [
        'journal' => 'Journal',
        'adjustment' => 'Journal',
        'payment' => 'Payment',
        'receipt' => 'Receipt',
        'contra' => 'Contra',
        'transfer' => 'Journal',
        'sales' => 'Sales',
        'purchase' => 'Purchase',
        'credit_note' => 'Credit Note',
        'debit_note' => 'Debit Note',
    ];

    public function render(Voucher $voucher, string $action = 'Create'): string
    {
        $voucher->loadMissing([
            'entries.ledger',
            'entries.costCentre',
            'entries.businessUnit',
            'entries.department',
            'billAllocations.bill',
            'inventoryLines.product.primaryUnit',
            'inventoryLines.godown',
            'inventoryLines.destinationGodown',
            'entries.costCentre.category',
            'currency',
            'partyLedger',
        ]);

        $type = $voucher->voucher_type instanceof VoucherType
            ? $voucher->voucher_type->value
            : (string) $voucher->voucher_type;

        $billsByLedger = [];
        foreach ($voucher->billAllocations as $allocation) {
            $billsByLedger[(int) $allocation->ledger_id][] = [
                'name' => (string) ($allocation->reference_name ?: $allocation->bill?->bill_number ?: $voucher->voucher_number),
                'type' => $this->billType((string) $allocation->reference_type),
                'amount' => (float) $allocation->amount,
            ];
        }

        $lines = [];
        $party = $voucher->partyLedger?->name;
        $partyGstin = $voucher->partyLedger?->gstin;
        $gstType = $voucher->partyLedger?->gst_registration_type?->label();

        foreach ($voucher->entries as $entry) {
            $ledgerName = (string) ($entry->ledger?->name ?? 'Ledger');
            $isParty = (bool) ($entry->ledger?->isCustomer() || $entry->ledger?->isSupplier());
            if ($isParty && $party === null) {
                $party = $ledgerName;
                $partyGstin = $partyGstin ?: $entry->ledger?->gstin;
                $gstType = $gstType ?: $entry->ledger?->gst_registration_type?->label();
            }

            $bills = $billsByLedger[(int) $entry->ledger_id] ?? [];
            if ($bills === [] && $isParty && in_array($type, ['sales', 'purchase', 'credit_note', 'debit_note'], true)) {
                $bills = [[
                    'name' => (string) ($voucher->reference_number ?: $voucher->voucher_number),
                    'type' => 'New Ref',
                    'amount' => (float) ($entry->debit > 0 ? $entry->debit : $entry->credit),
                ]];
            }

            $lines[] = [
                'ledger' => $ledgerName,
                'debit' => (float) $entry->debit,
                'credit' => (float) $entry->credit,
                'is_party' => $isParty,
                'is_bank' => (bool) $entry->ledger?->isCashOrBank(),
                'is_tax' => $this->isTaxLedger($ledgerName),
                'cost_centre' => $entry->costCentre?->name ?? $entry->businessUnit?->name ?? $entry->department?->name,
                'bills' => $bills,
                'bank_transaction_type' => $entry->bank_transaction_type,
                'instrument_number' => $entry->instrument_number,
                'instrument_date' => $entry->instrument_date,
                'payment_favouring' => $entry->payment_favouring,
                'bank_name' => $entry->bank_name,
                'bank_account_number' => $entry->bank_account_number,
                'bank_ifsc' => $entry->bank_ifsc,
                'narration' => $entry->narration,
                'cost_category' => $entry->costCentre?->category?->name,
            ];
        }

        $goods = $this->goodsLedger($lines, $type);
        $inventory = $this->inventoryXml($voucher, $type, $goods);
        if ($inventory['xml'] !== '') {
            $lines = $this->withoutGoodsLine($lines, $goods, $inventory['total']);
        }

        return $this->wrap($type, (string) $voucher->voucher_number, $voucher->voucher_date?->format('Ymd') ?? now()->format('Ymd'), "books-{$type}-{$voucher->getKey()}", $lines, $action, [
            'party' => $party,
            'reference' => $voucher->reference_number,
            'narration' => $voucher->narration,
            'effective_date' => $voucher->effective_date,
            'reference_date' => $voucher->reference_date,
            'place_of_supply' => $voucher->place_of_supply,
            'party_gstin' => $partyGstin,
            'gst_registration_type' => $gstType,
            'is_optional' => (bool) $voucher->is_optional,
            'is_post_dated' => (bool) $voucher->is_post_dated,
            'is_memo' => (bool) $voucher->is_memo,
            'name_on_receipt' => $voucher->name_on_receipt,
            'nature_of_payment' => $voucher->nature_of_payment,
            'reverses_on' => $voucher->reverses_on,
            'exchange_rate' => $voucher->exchange_rate,
            'foreign_total' => $voucher->foreign_total,
            'currency' => $voucher->currency?->code ?? $voucher->currency?->name,
            'inventory' => $inventory['xml'],
            'is_invoice' => (bool) $voucher->is_invoice || $inventory['xml'] !== '' || in_array($type, ['sales', 'purchase'], true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function renderPayload(array $payload, string $action = 'Create'): string
    {
        $type = (string) $payload['type'];
        $date = XmlHelpers::ymd($payload['date'] ?? null) ?: now()->format('Ymd');
        $remoteId = 'books-'.$type.'-'.md5(((string) $payload['number']).$date);

        return $this->wrap($type, (string) $payload['number'], $date, $remoteId, $payload['entries'] ?? [], $action, [
            'party' => $payload['party'] ?? null,
            'reference' => $payload['reference'] ?? null,
            'narration' => $payload['narration'] ?? null,
            'effective_date' => $payload['effective_date'] ?? null,
            'reference_date' => $payload['reference_date'] ?? null,
            'place_of_supply' => $payload['place_of_supply'] ?? null,
            'party_gstin' => $payload['party_gstin'] ?? null,
            'gst_registration_type' => $payload['gst_registration_type'] ?? null,
            'is_optional' => (bool) ($payload['is_optional'] ?? false),
            'is_post_dated' => (bool) ($payload['is_post_dated'] ?? false),
            'inventory' => (string) ($payload['inventory'] ?? ''),
            'is_invoice' => (bool) ($payload['is_invoice'] ?? in_array($type, ['sales', 'purchase'], true)),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @param  array<string, mixed>  $extra
     */
    private function wrap(string $type, string $number, string $date, string $remoteId, array $lines, string $action, array $extra): string
    {
        $tallyName = self::TALLY_VOUCHER_NAMES[$type] ?? 'Journal';
        $body = XmlHelpers::voucherBody(
            $remoteId,
            $tallyName,
            $date,
            $number,
            XmlHelpers::accountingEntries($lines),
            $action,
            $extra['party'] ?? null,
            $extra['reference'] ?? null,
            $extra['narration'] ?? null,
            (bool) ($extra['is_invoice'] ?? false),
            $extra,
        );

        return XmlEnvelope::vouchers(strtoupper($type), $body);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function goodsLedger(array $lines, string $type): string
    {
        $preferCredit = in_array($type, ['sales', 'credit_note', 'receipt'], true);
        foreach ($lines as $line) {
            if (! empty($line['is_party']) || ! empty($line['is_bank']) || ! empty($line['is_tax'])) {
                continue;
            }
            $amount = $preferCredit ? (float) ($line['credit'] ?? 0) : (float) ($line['debit'] ?? 0);
            if ($amount > 0) {
                return (string) $line['ledger'];
            }
        }

        return $preferCredit
            ? (string) config('tally_sync.ledgers.sales', 'Sales')
            : (string) config('tally_sync.ledgers.purchase', 'Purchase');
    }

    /**
     * @return array{xml:string,total:float}
     */
    private function inventoryXml(Voucher $voucher, string $type, string $goodsLedger): array
    {
        $outward = in_array($type, ['sales', 'credit_note'], true);
        $xml = '';
        $total = 0.0;

        foreach ($voucher->inventoryLines as $line) {
            $amount = abs((float) $line->amount);
            if ($amount <= 0 && abs((float) $line->quantity) <= 0) {
                continue;
            }
            $total += $amount;
            $unit = $line->product?->primaryUnit?->symbol ?: $line->product?->primaryUnit?->name ?: 'Nos';
            $signed = $outward ? -$amount : $amount;
            $xml .= XmlHelpers::inventoryEntry(
                (string) ($line->product?->name ?: 'Item'),
                abs((float) $line->quantity),
                $unit,
                (float) $line->rate,
                $signed,
                (string) ($line->godown?->name ?: config('tally_sync.default_godown', 'Main Location')),
                $goodsLedger,
                (string) ($line->batch_name ?: 'Primary Batch'),
                [
                    'discount' => (float) $line->discount_amount,
                    'destination_godown' => $line->destinationGodown?->name,
                ],
            );
        }

        return ['xml' => $xml, 'total' => $total];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    private function withoutGoodsLine(array $lines, string $goodsLedger, float $inventoryTotal): array
    {
        $removed = false;

        return array_values(array_filter($lines, function (array $line) use ($goodsLedger, $inventoryTotal, &$removed) {
            if ($removed || (string) $line['ledger'] !== $goodsLedger) {
                return true;
            }
            $amount = max((float) ($line['debit'] ?? 0), (float) ($line['credit'] ?? 0));
            if (abs($amount - $inventoryTotal) > 0.05) {
                return true;
            }
            $removed = true;

            return false;
        }));
    }

    private function billType(string $type): string
    {
        return match ($type) {
            'agst_ref' => 'Agst Ref',
            'advance' => 'Advance',
            'on_account' => 'On Account',
            'new_ref' => 'New Ref',
            default => 'Agst Ref',
        };
    }

    private function isTaxLedger(string $name): bool
    {
        $name = strtoupper($name);

        return str_contains($name, 'CGST')
            || str_contains($name, 'SGST')
            || str_contains($name, 'IGST')
            || str_contains($name, 'CESS')
            || str_contains($name, 'ROUND');
    }
}