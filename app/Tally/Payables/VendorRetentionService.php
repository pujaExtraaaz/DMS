<?php

namespace Tally\Payables;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Invoice;
use Tally\Models\Ledger;
use Tally\Models\VendorRetention;

class VendorRetentionService
{
    public function __construct(
        private readonly VoucherEngine $vouchers,
    ) {}

    /**
     * Hold retention from an invoice or a manual amount.
     *
     * @param  array{
     *   invoice_id?: ?int,
     *   supplier_ledger_id?: ?int,
     *   retention_percent?: mixed,
     *   retention_amount?: mixed,
     *   retention_date?: string,
     *   release_due_date?: ?string,
     *   kind?: string,
     *   retention_ledger_id?: ?int,
     *   post_journal?: bool
     * }  $data
     */
    public function hold(Company $company, User $user, array $data): VendorRetention
    {
        $invoice = null;
        if (! empty($data['invoice_id'])) {
            $invoice = Invoice::query()
                ->where('company_id', $company->id)
                ->whereKey((int) $data['invoice_id'])
                ->first();
            if (! $invoice) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Invoice not found for this company.',
                ]);
            }
        }

        $supplierId = (int) ($data['supplier_ledger_id'] ?? $invoice?->party_ledger_id ?? 0);
        if ($supplierId <= 0) {
            throw ValidationException::withMessages([
                'supplier_ledger_id' => 'Supplier ledger is required.',
            ]);
        }

        $percent = (float) ($data['retention_percent'] ?? 0);
        $amountCents = Money::cents((string) ($data['retention_amount'] ?? 0));
        if ($amountCents <= 0 && $invoice && $percent > 0) {
            $amountCents = (int) round(Money::cents((string) $invoice->grand_total) * ($percent / 100));
        }

        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'retention_amount' => 'Retention amount must be greater than zero.',
            ]);
        }

        $retentionLedgerId = (int) ($data['retention_ledger_id'] ?? $company->retention_ledger_id ?? 0);
        if ($retentionLedgerId <= 0) {
            throw ValidationException::withMessages([
                'retention_ledger_id' => 'Retention ledger is required (on the hold or company default).',
            ]);
        }

        $date = (string) ($data['retention_date'] ?? now()->toDateString());
        $postJournal = (bool) ($data['post_journal'] ?? true);

        return DB::transaction(function () use (
            $company, $user, $invoice, $supplierId, $percent, $amountCents,
            $retentionLedgerId, $date, $data, $postJournal
        ) {
            $retention = VendorRetention::query()->create([
                'company_id' => $company->id,
                'supplier_ledger_id' => $supplierId,
                'invoice_id' => $invoice?->id,
                'retention_percent' => $percent,
                'retention_amount' => Money::format($amountCents),
                'released_amount' => '0.00',
                'retention_date' => $date,
                'release_due_date' => $data['release_due_date'] ?? null,
                'kind' => $data['kind'] ?? 'retention',
                'status' => 'held',
                'retention_ledger_id' => $retentionLedgerId,
            ]);

            if ($postJournal) {
                [$branch, $year] = $this->postingContext($company, $date);
                $amount = Money::format($amountCents);
                $voucher = $this->vouchers->save(
                    $company,
                    $branch,
                    $year,
                    $user,
                    VoucherType::Journal,
                    [
                        'voucher_date' => $date,
                        'narration' => 'Vendor retention hold #'.$retention->id,
                        'entries' => [
                            ['ledger_id' => $supplierId, 'debit' => $amount, 'credit' => '0.00'],
                            ['ledger_id' => $retentionLedgerId, 'debit' => '0.00', 'credit' => $amount],
                        ],
                    ],
                    true
                );
                $retention->update(['hold_voucher_id' => $voucher->id]);
            }

            return $retention->fresh(['supplier', 'invoice', 'retentionLedger']);
        });
    }

    /**
     * Release retained amount: Payment (Dr retention Cr bank) or Journal (Dr retention Cr supplier).
     */
    public function release(
        VendorRetention $retention,
        mixed $amount,
        User $user,
        ?int $bankLedgerId = null,
        ?string $date = null,
    ): VendorRetention {
        $retention->loadMissing('company');
        $company = $retention->company;

        if (in_array($retention->status, ['released', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'retention' => 'Retention is already fully released.',
            ]);
        }

        $held = Money::cents((string) $retention->retention_amount);
        $already = Money::cents((string) $retention->released_amount);
        $remaining = max(0, $held - $already);
        $releaseCents = Money::cents((string) $amount);

        if ($releaseCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Release amount must be greater than zero.',
            ]);
        }

        if ($releaseCents > $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'Release amount exceeds remaining retention.',
            ]);
        }

        $retentionLedgerId = (int) ($retention->retention_ledger_id ?: $company->retention_ledger_id);
        if ($retentionLedgerId <= 0) {
            throw ValidationException::withMessages([
                'retention_ledger_id' => 'Retention ledger is required to release.',
            ]);
        }

        $date ??= $retention->release_due_date?->toDateString()
            ?? $retention->retention_date?->toDateString()
            ?? now()->toDateString();
        [$branch, $year] = $this->postingContext($company, $date);
        $formatted = Money::format($releaseCents);

        return DB::transaction(function () use (
            $retention, $company, $branch, $year, $user, $date,
            $formatted, $releaseCents, $already, $held, $retentionLedgerId, $bankLedgerId
        ) {
            if ($bankLedgerId) {
                $bank = Ledger::query()
                    ->where('company_id', $company->id)
                    ->whereKey($bankLedgerId)
                    ->first();
                if (! $bank) {
                    throw ValidationException::withMessages([
                        'bank_ledger_id' => 'Bank ledger not found.',
                    ]);
                }

                $voucher = $this->vouchers->save(
                    $company,
                    $branch,
                    $year,
                    $user,
                    VoucherType::Payment,
                    [
                        'voucher_date' => $date,
                        'narration' => 'Vendor retention release #'.$retention->id,
                        'entries' => [
                            ['ledger_id' => $retentionLedgerId, 'debit' => $formatted, 'credit' => '0.00'],
                            ['ledger_id' => $bank->id, 'debit' => '0.00', 'credit' => $formatted],
                        ],
                    ],
                    true
                );
            } else {
                $voucher = $this->vouchers->save(
                    $company,
                    $branch,
                    $year,
                    $user,
                    VoucherType::Journal,
                    [
                        'voucher_date' => $date,
                        'narration' => 'Vendor retention release #'.$retention->id,
                        'entries' => [
                            ['ledger_id' => $retentionLedgerId, 'debit' => $formatted, 'credit' => '0.00'],
                            ['ledger_id' => $retention->supplier_ledger_id, 'debit' => '0.00', 'credit' => $formatted],
                        ],
                    ],
                    true
                );
            }

            $released = $already + $releaseCents;
            $retention->update([
                'released_amount' => Money::format($released),
                'status' => $released >= $held ? 'released' : 'partial',
                'release_voucher_id' => $voucher->id,
            ]);

            return $retention->fresh(['supplier', 'invoice', 'retentionLedger']);
        });
    }

    /**
     * @return array{0: Branch, 1: FinancialYear}
     */
    private function postingContext(Company $company, string $date): array
    {
        $year = FinancialYear::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first()
            ?? $company->financialYears()->where('is_active', true)->orderByDesc('start_date')->first();

        $branch = $company->branches()->where('is_active', true)->orderBy('id')->first();

        if (! $year || ! $branch) {
            throw ValidationException::withMessages([
                'retention' => 'Active branch and financial year are required.',
            ]);
        }

        return [$branch, $year];
    }
}
