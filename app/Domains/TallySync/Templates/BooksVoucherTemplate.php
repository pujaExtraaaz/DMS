<?php

namespace App\Domains\TallySync\Templates;

use Tally\Accounting\VoucherType;
use Tally\Models\Voucher;

/**
 * Canonical Books → TallyPrime voucher XML for every VoucherType.
 * Kept for future sync; not used by the live connector while tally_sync.enabled is false.
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
        $voucher->loadMissing(['entries.ledger', 'entries.costCentre', 'entries.businessUnit']);

        $type = $voucher->voucher_type instanceof VoucherType
            ? $voucher->voucher_type->value
            : (string) $voucher->voucher_type;

        $tallyName = self::TALLY_VOUCHER_NAMES[$type] ?? 'Journal';
        $date = $voucher->voucher_date?->format('Ymd') ?? now()->format('Ymd');
        $remoteId = "books-{$type}-{$voucher->getKey()}";

        $lines = [];
        $party = null;

        foreach ($voucher->entries as $entry) {
            $ledgerName = (string) ($entry->ledger?->name ?? 'Ledger');
            $isParty = (bool) ($entry->ledger?->isCustomer() || $entry->ledger?->isSupplier());

            if ($isParty && $party === null) {
                $party = $ledgerName;
            }

            $lines[] = [
                'ledger' => $ledgerName,
                'debit' => (float) $entry->debit,
                'credit' => (float) $entry->credit,
                'is_party' => $isParty,
                'cost_centre' => $entry->costCentre?->name ?? $entry->businessUnit?->name,
            ];
        }

        $entries = XmlHelpers::accountingEntries($lines);
        $body = XmlHelpers::voucherBody(
            $remoteId,
            $tallyName,
            $date,
            (string) $voucher->voucher_number,
            $entries,
            $action,
            $party,
            $voucher->reference_number,
            $voucher->narration,
            in_array($type, ['sales', 'purchase'], true),
        );

        return XmlEnvelope::vouchers(strtoupper($type), $body);
    }

    /**
     * Build XML from a DTO without persisting a voucher (tests / future sync).
     *
     * @param  array{
     *   type:string,
     *   number:string,
     *   date:string,
     *   narration?:?string,
     *   reference?:?string,
     *   party?:?string,
     *   entries:list<array{ledger:string,debit?:float,credit?:float,is_party?:bool,cost_centre?:?string}>
     * }  $payload
     */
    public function renderPayload(array $payload, string $action = 'Create'): string
    {
        $type = $payload['type'];
        $tallyName = self::TALLY_VOUCHER_NAMES[$type] ?? 'Journal';
        $date = preg_replace('/\D/', '', $payload['date']) ?: now()->format('Ymd');
        if (strlen($date) === 8) {
            // already Ymd
        } else {
            $date = date('Ymd', strtotime($payload['date']));
        }

        $remoteId = 'books-'.$type.'-'.md5($payload['number'].$date);
        $entries = XmlHelpers::accountingEntries($payload['entries'] ?? []);
        $body = XmlHelpers::voucherBody(
            $remoteId,
            $tallyName,
            $date,
            (string) $payload['number'],
            $entries,
            $action,
            $payload['party'] ?? null,
            $payload['reference'] ?? null,
            $payload['narration'] ?? null,
            in_array($type, ['sales', 'purchase'], true),
        );

        return XmlEnvelope::vouchers(strtoupper($type), $body);
    }
}
