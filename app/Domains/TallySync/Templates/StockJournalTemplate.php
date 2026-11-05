<?php

namespace App\Domains\TallySync\Templates;

use Tally\Inventory\StockTransactionType;
use Tally\Models\StockTransaction;

/**
 * Books stock journal / transfer → TallyPrime Stock Journal voucher.
 */
class StockJournalTemplate
{
    public function render(StockTransaction $txn, string $action = 'Create'): string
    {
        $txn->loadMissing([
            'lines.product.primaryUnit',
            'lines.godown',
            'sourceGodown',
            'destinationGodown',
        ]);

        $date = $txn->transaction_date?->format('Ymd') ?? now()->format('Ymd');
        $number = (string) ($txn->number ?: 'STA-'.$txn->getKey());
        $remote = 'books_stock_journal-'.$txn->getKey();
        $type = $txn->type instanceof StockTransactionType ? $txn->type : StockTransactionType::tryFrom((string) $txn->type);
        $narration = XmlHelpers::xml($txn->narration ?: ($type?->label() ?: 'Stock Journal').' '.$number);
        $destDefault = (string) ($txn->destinationGodown?->name ?: '');
        $sourceDefault = (string) ($txn->sourceGodown?->name ?: config('tally_sync.default_godown', 'Main Location'));

        $inventory = '';
        foreach ($txn->lines as $line) {
            $qty = (float) $line->quantity;
            if (abs($qty) < 0.0001) {
                continue;
            }
            $unit = $line->product?->primaryUnit?->symbol
                ?: $line->product?->primaryUnit?->name
                ?: 'Nos';
            $rate = (float) $line->rate;
            $amount = (float) $line->value;
            if (abs($amount) < 0.01) {
                $amount = round(abs($qty) * $rate, 2);
            }
            $godown = (string) ($line->godown?->name ?: $sourceDefault);
            $batch = trim((string) ($line->batch_number ?: 'Primary Batch'));

            $signedQty = $qty;
            $destination = '';
            if ($type === StockTransactionType::Transfer) {
                $destination = $destDefault !== '' ? $destDefault : $godown;
                $godown = $sourceDefault;
                $signedAmount = -abs($amount);
            } elseif ($qty < 0 || $type === StockTransactionType::Out || $type === StockTransactionType::RejectionOut) {
                $signedAmount = -abs($amount);
                $signedQty = -abs($qty);
            } else {
                $signedAmount = abs($amount);
                $signedQty = abs($qty);
            }

            $inventory .= XmlHelpers::inventoryEntry(
                (string) ($line->product?->name ?: 'Item'),
                abs($signedQty),
                $unit,
                $rate,
                $signedAmount,
                $godown,
                '',
                $batch !== '' ? $batch : 'Primary Batch',
                array_filter([
                    'destination_godown' => $destination !== '' ? $destination : null,
                    'manufactured_on' => $line->manufactured_on,
                    'expires_on' => $line->expires_on,
                ], fn ($value) => $value !== null && $value !== ''),
            );
        }

        // Pure inventory vouchers: strip empty accounting allocations that confuse Tally.
        $inventory = preg_replace(
            '/<ACCOUNTINGALLOCATIONS\.LIST><LEDGERNAME><\/LEDGERNAME><ISDEEMEDPOSITIVE>[^<]*<\/ISDEEMEDPOSITIVE><AMOUNT>[^<]*<\/AMOUNT><\/ACCOUNTINGALLOCATIONS\.LIST>/',
            '',
            $inventory
        ) ?? $inventory;

        $body = '<VOUCHER REMOTEID="'.XmlHelpers::xml($remote).'" VCHTYPE="Stock Journal" ACTION="'.XmlHelpers::xml($action).'" OBJVIEW="Inventory Voucher View">'
            .'<DATE>'.$date.'</DATE>'
            .'<EFFECTIVEDATE>'.$date.'</EFFECTIVEDATE>'
            .'<VOUCHERTYPENAME>Stock Journal</VOUCHERTYPENAME>'
            .'<VOUCHERNUMBER>'.XmlHelpers::xml($number).'</VOUCHERNUMBER>'
            .'<PERSISTEDVIEW>Inventory Voucher View</PERSISTEDVIEW>'
            .'<ISINVOICE>No</ISINVOICE>'
            .'<NARRATION>'.$narration.'</NARRATION>'
            .$inventory
            .'</VOUCHER>';

        return XmlEnvelope::vouchers('STOCK_JOURNAL', $body);
    }
}
