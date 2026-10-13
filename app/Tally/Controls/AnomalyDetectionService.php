<?php

namespace Tally\Controls;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Models\AnomalyFlag;
use Tally\Models\Company;
use Tally\Models\Voucher;

class AnomalyDetectionService
{
    /**
     * Flag duplicate payments by same party + amount + date.
     *
     * @return list<AnomalyFlag>
     */
    public function flagDuplicatePayments(Company $company, ?string $from = null, ?string $to = null): array
    {
        $paymentVouchers = Voucher::query()
            ->where('company_id', $company->id)
            ->where('voucher_type', VoucherType::Payment->value)
            ->where('status', VoucherStatus::Posted->value)
            ->when($from, fn ($q) => $q->whereDate('voucher_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('voucher_date', '<=', $to))
            ->with('entries')
            ->get();

        $buckets = [];
        foreach ($paymentVouchers as $voucher) {
            foreach ($voucher->entries as $entry) {
                if (Money::cents((string) $entry->debit) <= 0) {
                    continue;
                }
                $key = $entry->ledger_id.'|'.$entry->debit.'|'.$voucher->voucher_date->toDateString();
                $buckets[$key][] = $voucher;
            }
        }

        $created = [];
        foreach ($buckets as $key => $vouchers) {
            if (count($vouchers) < 2) {
                continue;
            }
            [$ledgerId, $amount, $date] = explode('|', $key, 3);
            foreach ($vouchers as $voucher) {
                $created[] = AnomalyFlag::query()->firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'flag_type' => 'duplicate_payment',
                        'document_type' => 'voucher',
                        'document_id' => $voucher->id,
                    ],
                    [
                        'amount' => $amount,
                        'severity' => 'medium',
                        'message' => "Duplicate payment of {$amount} on {$date} for party ledger #{$ledgerId}.",
                        'status' => 'open',
                    ]
                );
            }
        }

        return $created;
    }

    /**
     * @return list<AnomalyFlag>
     */
    public function scan(Company $company): array
    {
        return $this->flagDuplicatePayments($company);
    }
}
