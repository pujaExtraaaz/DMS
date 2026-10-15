<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Models\Payment;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\BooksPoster;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Models\Party;
use Tally\Models\Voucher;
use Tally\Models\VoucherEntry;

/**
 * Links DMS cheques into Books:
 * - If linked to a Payment → PaymentSync handles the voucher; we only store the cheque↔voucher link.
 * - Cleared incoming without payment → create/post a Receipt voucher.
 * - Issued-to-vendor cleared/pending → create/post a Payment voucher.
 */
class ChequeSync
{
    public const KEY = 'cheque';

    public function __construct(private readonly CustomerSync $customers) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Cheque) {
            $this->push($model);
        }
    }

    public function push(Cheque $cheque): void
    {
        $cheque->loadMissing(['customer', 'payment']);
        $customer = $cheque->customer;
        if (! $customer?->company_id) {
            return;
        }

        // Prefer payment sync when a receipt/payment already exists.
        if ($cheque->payment_id && $cheque->payment) {
            app(PaymentSync::class)->push($cheque->payment);
            $voucherId = SyncLinks::acctId(PaymentSync::KEY, $cheque->payment);
            if ($voucherId) {
                $voucher = Voucher::query()->find($voucherId);
                if ($voucher) {
                    SyncLinks::store(self::KEY, $cheque, $voucher);
                }
            }

            return;
        }

        // Only clear/post money movement for cleared (or deposited) instruments.
        if (! in_array($cheque->status, ['cleared', 'deposited', 'pending'], true)) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $customer->company_id);
        $date = ($cheque->clearance_date ?? $cheque->cheque_date ?? now())->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId((int) $customer->company_id, $customer->branch_id ? (int) $customer->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Cheque '.$cheque->cheque_no.' has no books company/branch/year.');
        }

        $existing = Voucher::query()->find(SyncLinks::acctId(self::KEY, $cheque));
        if ($existing && $existing->status !== VoucherStatus::Draft) {
            SyncLinks::store(self::KEY, $cheque, $existing, 'conflict', 'Books voucher already posted.');

            return;
        }

        $this->customers->push($customer);
        $party = Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $customer));
        if (! $party) {
            throw new \RuntimeException('Party for cheque '.$cheque->cheque_no.' did not sync.');
        }

        $outgoing = $cheque->direction === 'issued_to_vendor';
        $type = $outgoing ? VoucherType::Payment : VoucherType::Receipt;
        $amount = number_format((float) $cheque->amount, 2, '.', '');
        $bankLedgerId = BooksCompany::bankLedger($company)->id;
        $shouldPost = in_array($cheque->status, ['cleared', 'deposited'], true);

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'voucher_type' => $type,
            'voucher_number' => mb_substr('CHQ-'.$cheque->cheque_no, 0, 30),
            'voucher_date' => $date,
            'reference_number' => $cheque->cheque_no,
            'narration' => trim('Cheque '.$cheque->cheque_no.' · '.($cheque->bank_name ?? '').' · '.$cheque->status.' '.($cheque->notes ?? '')),
            'status' => VoucherStatus::Draft,
            'total_debit' => $amount,
            'total_credit' => $amount,
        ];

        try {
            DB::transaction(function () use (&$existing, $attributes, $cheque, $party, $amount, $bankLedgerId, $outgoing, $shouldPost) {
                $existing ? $existing->update($attributes) : $existing = Voucher::query()->create($attributes);
                $existing->entries()->delete();

                if ($outgoing) {
                    // Payment: Dr Party, Cr Bank
                    VoucherEntry::query()->create([
                        'voucher_id' => $existing->id,
                        'ledger_id' => $party->ledger_id,
                        'line_number' => 1,
                        'debit' => $amount,
                        'credit' => 0,
                    ]);
                    VoucherEntry::query()->create([
                        'voucher_id' => $existing->id,
                        'ledger_id' => $bankLedgerId,
                        'line_number' => 2,
                        'debit' => 0,
                        'credit' => $amount,
                    ]);
                } else {
                    // Receipt: Dr Bank, Cr Party
                    VoucherEntry::query()->create([
                        'voucher_id' => $existing->id,
                        'ledger_id' => $bankLedgerId,
                        'line_number' => 1,
                        'debit' => $amount,
                        'credit' => 0,
                    ]);
                    VoucherEntry::query()->create([
                        'voucher_id' => $existing->id,
                        'ledger_id' => $party->ledger_id,
                        'line_number' => 2,
                        'debit' => 0,
                        'credit' => $amount,
                    ]);
                }

                SyncLinks::store(self::KEY, $cheque, $existing);
                if ($shouldPost) {
                    app(BooksPoster::class)->voucher($existing->fresh(), self::KEY);
                }
            });
        } catch (\Throwable $e) {
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $cheque, $e);
            throw $e;
        }
    }
}
