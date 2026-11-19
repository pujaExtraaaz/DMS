<?php

namespace App\Domains\Payment\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\ChequeBook;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Models\ChequeBounce;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use App\Support\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class ChequeService
{
    public const BOUNCE_FREEZE_THRESHOLD = 3;

    public function __construct(
        protected AuditLogService $auditLogService,
        protected ?OutstandingLedgerService $outstandingLedgerService = null,
    ) {
        $this->outstandingLedgerService = $outstandingLedgerService ?? app(OutstandingLedgerService::class);
    }

    public function create(array $data, ?User $actor = null): Cheque
    {
        $actorId = $actor?->id ?? auth()->id();
        $isIssued = ($data['cheque_type'] ?? null) === Cheque::TYPE_ISSUED
            || ($data['direction'] ?? null) === 'issued_to_vendor';

        $chequeNo = trim((string) $data['cheque_no']);
        $companyId = $data['company_id'] ?? auth()->user()?->company_id;
        $bankAccountNo = isset($data['bank_account_no']) ? trim((string) $data['bank_account_no']) : null;
        $chequeBookId = $data['cheque_book_id'] ?? null;

        if ($isIssued) {
            if (empty($bankAccountNo)) {
                throw new InvalidArgumentException('Company bank account is required when issuing a cheque.');
            }

            // Find matching active cheque book
            $chequeBook = null;
            if ($chequeBookId) {
                $chequeBook = ChequeBook::query()
                    ->where('id', $chequeBookId)
                    ->where('bank_account_no', $bankAccountNo)
                    ->first();
            }

            if (! $chequeBook && $companyId) {
                $numeric = (int) $chequeNo;
                $chequeBook = ChequeBook::query()
                    ->where('company_id', $companyId)
                    ->where('bank_account_no', $bankAccountNo)
                    ->where('status', 'active')
                    ->where('from_numeric', '<=', $numeric)
                    ->where('to_numeric', '>=', $numeric)
                    ->first();
            }

            if (! $chequeBook && $companyId) {
                // If cheque books exist for this bank account, verify the number is in range
                $hasAnyBooks = ChequeBook::query()
                    ->where('company_id', $companyId)
                    ->where('bank_account_no', $bankAccountNo)
                    ->where('status', 'active')
                    ->exists();

                if ($hasAnyBooks) {
                    throw new InvalidArgumentException("Cheque number {$chequeNo} does not belong to any active cheque book for bank account {$bankAccountNo}.");
                }
            }

            if ($chequeBook) {
                $chequeBookId = $chequeBook->id;
                if (! $chequeBook->containsNumber($chequeNo)) {
                    throw new InvalidArgumentException("Cheque number {$chequeNo} is outside the range ({$chequeBook->from_number} - {$chequeBook->to_number}) of cheque book '{$chequeBook->cheque_book_name}'.");
                }

                if ($chequeBook->isNumberUsed($chequeNo)) {
                    throw new InvalidArgumentException("Cheque number {$chequeNo} has already been issued for this bank account.");
                }

                if ($chequeBook->isNumberCancelled($chequeNo)) {
                    throw new InvalidArgumentException("Cheque number {$chequeNo} has been cancelled and cannot be re-issued.");
                }
            } else {
                // Check general duplicate within same bank account
                $exists = Cheque::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->where('bank_account_no', $bankAccountNo)
                    ->where('cheque_no', $chequeNo)
                    ->where(function ($q) {
                        $q->where('cheque_type', Cheque::TYPE_ISSUED)
                            ->orWhere('direction', 'issued_to_vendor');
                    })
                    ->exists();

                if ($exists) {
                    throw new InvalidArgumentException("Cheque number {$chequeNo} has already been recorded for this bank account.");
                }
            }
        }

        $chequeType = $isIssued ? Cheque::TYPE_ISSUED : Cheque::TYPE_RECEIVED;
        $direction = $data['direction'] ?? ($isIssued ? 'issued_to_vendor' : 'received_from_client');
        $initialStatus = $data['status'] ?? ($isIssued ? Cheque::STATUS_ISSUED : Cheque::STATUS_RECEIVED);

        $cheque = Cheque::create([
            'company_id' => $companyId,
            'bank_account_no' => $bankAccountNo,
            'cheque_book_id' => $chequeBookId,
            'cheque_no' => $chequeNo,
            'customer_id' => $data['customer_id'] ?? null,
            'purpose' => $data['purpose'] ?? 'pdc',
            'direction' => $direction,
            'cheque_type' => $chequeType,
            'amount' => (float) $data['amount'],
            'bank_name' => $data['bank_name'] ?? ($chequeBook?->bank_name ?? null),
            'branch_name' => $data['branch_name'] ?? null,
            'cheque_date' => $data['cheque_date'] ?? now()->toDateString(),
            'deposit_date' => $data['deposit_date'] ?? null,
            'status' => $initialStatus,
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $actorId,
            'payment_id' => $data['payment_id'] ?? null,
            'reference_voucher' => $data['reference_voucher'] ?? null,
            'voucher_type' => $data['voucher_type'] ?? null,
            'voucher_id' => $data['voucher_id'] ?? null,
        ]);

        $this->auditLogService->record($cheque, 'created', null, [
            'cheque_no' => $cheque->cheque_no,
            'cheque_type' => $cheque->cheque_type,
            'amount' => $cheque->amount,
            'status' => $cheque->status,
        ]);

        return $cheque;
    }

    public function deposit(Cheque $cheque, ?string $depositDate = null): Cheque
    {
        if (in_array($cheque->status, [Cheque::STATUS_CLEARED, Cheque::STATUS_BOUNCED, Cheque::STATUS_CANCELLED], true)) {
            throw new InvalidArgumentException("Cheque cannot be marked as deposited from '{$cheque->status}' status.");
        }

        $cheque->update([
            'status' => Cheque::STATUS_DEPOSITED,
            'deposit_date' => $depositDate ?? now()->toDateString(),
        ]);

        $this->auditLogService->record($cheque, 'deposited');

        return $cheque->fresh();
    }

    public function clear(Cheque $cheque, ?string $clearanceDate = null): Cheque
    {
        return DB::transaction(function () use ($cheque, $clearanceDate) {
            $locked = Cheque::query()->whereKey($cheque->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [Cheque::STATUS_BOUNCED, Cheque::STATUS_CANCELLED, Cheque::STATUS_STALE], true)) {
                throw new InvalidArgumentException("Cheque cannot be cleared from its current status ('{$locked->status}').");
            }

            $clearDate = $clearanceDate ?? now()->toDateString();

            $locked->update([
                'status' => Cheque::STATUS_CLEARED,
                'clearance_date' => $clearDate,
            ]);

            // Update linked Payment with clearance timestamp and metadata
            $payment = $locked->payment_id
                ? Payment::query()->whereKey($locked->payment_id)->lockForUpdate()->first()
                : null;

            if ($payment) {
                $clearNote = "Cheque #{$locked->cheque_no} cleared on {$clearDate}.";
                $existingNotes = (string) $payment->notes;
                $newNotes = str_contains($existingNotes, "Cheque #{$locked->cheque_no} cleared")
                    ? $existingNotes
                    : trim($existingNotes."\n".$clearNote);

                $updateData = [
                    'notes' => $newNotes,
                    'status' => 'completed',
                ];
                if (Schema::hasColumn('payments', 'cleared_at')) {
                    $updateData['cleared_at'] = $clearDate;
                }
                $payment->update($updateData);
            }

            // Synchronize with Books Bank Reconciliation
            $this->syncBankReconciliation($locked, $clearDate);

            $this->auditLogService->record($locked, 'cleared', null, [
                'clearance_date' => $clearDate,
            ]);

            return $locked->fresh();
        });
    }

    public function bounce(Cheque $cheque, ?string $reason = null, float $charges = 0): ChequeBounce
    {
        return DB::transaction(function () use ($cheque, $reason, $charges) {
            $locked = Cheque::query()->whereKey($cheque->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [Cheque::STATUS_CLEARED, Cheque::STATUS_CANCELLED], true)) {
                throw new InvalidArgumentException('Cleared or cancelled cheques cannot bounce.');
            }

            $bounceNumber = (int) $locked->bounce_count + 1;
            $customer = $locked->customer_id ? Customer::query()->whereKey($locked->customer_id)->lockForUpdate()->first() : null;
            $partyBounceCount = $customer ? ((int) $customer->cheque_bounce_count + 1) : 0;
            $triggeredFreeze = $customer && ($partyBounceCount > self::BOUNCE_FREEZE_THRESHOLD);

            $locked->update([
                'status' => Cheque::STATUS_BOUNCED,
                'bounce_count' => $bounceNumber,
                'bounced_at' => now(),
                'bounce_reason' => $reason,
            ]);

            if ($customer) {
                $customer->update([
                    'cheque_bounce_count' => $partyBounceCount,
                    'risk_status' => $triggeredFreeze ? 'high' : ($partyBounceCount > 1 ? 'watch' : $customer->risk_status),
                    'credit_status' => $triggeredFreeze ? 'frozen' : $customer->credit_status,
                ]);
            }

            // Financial & Ledger Synchronization for linked Payment
            $payment = $locked->payment_id
                ? Payment::query()->with('allocations')->whereKey($locked->payment_id)->lockForUpdate()->first()
                : null;

            if ($payment && ! in_array($payment->status, ['bounced', 'cancelled'], true)) {
                $payment->update(['status' => 'bounced']);

                // Only received customer receipts affect sales invoices and party debtor ledgers
                if ($locked->isReceived()) {
                    if ($payment->allocations->isNotEmpty()) {
                        foreach ($payment->allocations as $alloc) {
                            $inv = Invoice::query()->whereKey($alloc->invoice_id)->lockForUpdate()->first();
                            if ($inv) {
                                $this->reverseInvoicePayment($inv, (float) $alloc->amount);
                            }
                        }
                    } elseif ($payment->invoice_id) {
                        $inv = Invoice::query()->whereKey($payment->invoice_id)->lockForUpdate()->first();
                        if ($inv) {
                            $this->reverseInvoicePayment($inv, (float) $payment->amount);
                        }
                    }

                    $customerId = $locked->customer_id ?: $payment->customer_id;
                    if ($customerId) {
                        $this->outstandingLedgerService->recordAdjustment(
                            customerId: (int) $customerId,
                            debit: (float) $locked->amount,
                            credit: 0.0,
                            notes: "Cheque bounce reversal: Cheque #{$locked->cheque_no}".($reason ? " ({$reason})" : '')
                        );
                    }
                }
            }

            // Reverse or cancel associated Books accounting voucher
            $this->cancelAssociatedBooksVoucher($locked, $payment);

            $bounce = ChequeBounce::create([
                'cheque_id' => $locked->id,
                'customer_id' => $customer?->id ?? 0,
                'bounced_on' => now()->toDateString(),
                'reason' => $reason,
                'charges' => $charges,
                'bounce_number' => $bounceNumber,
                'triggered_freeze' => $triggeredFreeze,
                'recorded_by' => auth()->id(),
            ]);

            $this->auditLogService->record($locked, 'bounced', null, [
                'bounce_number' => $bounceNumber,
                'party_bounce_count' => $partyBounceCount,
                'triggered_freeze' => $triggeredFreeze,
            ]);

            if ($triggeredFreeze && $customer) {
                $this->auditLogService->approval(
                    $customer,
                    'cheque_bounce_freeze',
                    'frozen',
                    $reason ?? 'Cheque bounce threshold exceeded',
                    [
                        'cheque_id' => $locked->id,
                        'bounce_count' => $partyBounceCount,
                        'threshold' => self::BOUNCE_FREEZE_THRESHOLD,
                    ]
                );
            }

            return $bounce;
        });
    }

    public function cancel(Cheque $cheque, ?string $reason = null): Cheque
    {
        return DB::transaction(function () use ($cheque, $reason) {
            $locked = Cheque::query()->whereKey($cheque->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Cheque::STATUS_CANCELLED) {
                return $locked;
            }

            if (in_array($locked->status, [Cheque::STATUS_CLEARED, Cheque::STATUS_BOUNCED], true)) {
                throw new InvalidArgumentException('Cleared or bounced cheques cannot be cancelled.');
            }

            $locked->update([
                'status' => Cheque::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_reason' => $reason,
                'notes' => trim(($locked->notes ? $locked->notes."\n" : '').($reason ? "Cancelled: {$reason}" : '')),
            ]);

            // Financial & Ledger Synchronization for linked Payment
            $payment = $locked->payment_id
                ? Payment::query()->with('allocations')->whereKey($locked->payment_id)->lockForUpdate()->first()
                : null;

            if ($payment && ! in_array($payment->status, ['cancelled', 'bounced'], true)) {
                $payment->update(['status' => 'cancelled']);

                // Only received customer receipts affect sales invoices and party debtor ledgers
                if ($locked->isReceived()) {
                    if ($payment->allocations->isNotEmpty()) {
                        foreach ($payment->allocations as $alloc) {
                            $inv = Invoice::query()->whereKey($alloc->invoice_id)->lockForUpdate()->first();
                            if ($inv) {
                                $this->reverseInvoicePayment($inv, (float) $alloc->amount);
                            }
                        }
                    } elseif ($payment->invoice_id) {
                        $inv = Invoice::query()->whereKey($payment->invoice_id)->lockForUpdate()->first();
                        if ($inv) {
                            $this->reverseInvoicePayment($inv, (float) $payment->amount);
                        }
                    }

                    $customerId = $locked->customer_id ?: $payment->customer_id;
                    if ($customerId) {
                        $this->outstandingLedgerService->recordAdjustment(
                            customerId: (int) $customerId,
                            debit: (float) $locked->amount,
                            credit: 0.0,
                            notes: "Cheque cancellation reversal: Cheque #{$locked->cheque_no}".($reason ? " ({$reason})" : '')
                        );
                    }
                }
            }

            // Reverse or cancel associated Books accounting voucher
            $this->cancelAssociatedBooksVoucher($locked, $payment);

            $this->auditLogService->record($locked, 'cancelled', null, ['reason' => $reason]);

            return $locked->fresh();
        });
    }

    /**
     * Recalculate invoice status and reverse paid amount.
     */
    protected function reverseInvoicePayment(Invoice $invoice, float $amount): void
    {
        $newPaid = max(0.0, (float) $invoice->paid_amount - $amount);
        $grandTotal = (float) $invoice->grand_total;

        $newStatus = match (true) {
            $newPaid >= $grandTotal && $grandTotal > 0 => 'paid',
            $newPaid > 0.0001 => 'partial',
            default => 'issued',
        };

        $invoice->update([
            'paid_amount' => $newPaid,
            'status' => $newStatus,
        ]);
    }

    /**
     * Cancel or reverse any associated Books accounting voucher and unreconcile its lines.
     */
    protected function cancelAssociatedBooksVoucher(Cheque $cheque, ?Payment $payment = null): void
    {
        if (! class_exists(\Tally\Models\Voucher::class) || ! Schema::hasTable('acct_vouchers')) {
            return;
        }

        $voucherId = null;
        if (class_exists(\App\Domains\Sync\Support\SyncLinks::class)) {
            $voucherId = \App\Domains\Sync\Support\SyncLinks::acctId(\App\Domains\Sync\Handlers\ChequeSync::KEY, $cheque)
                ?? ($payment ? \App\Domains\Sync\Support\SyncLinks::acctId(\App\Domains\Sync\Handlers\PaymentSync::KEY, $payment) : null);
        }

        if (! $voucherId && $cheque->voucher_id && in_array($cheque->voucher_type, ['Voucher', 'Payment', 'Receipt'], true)) {
            $voucherId = $cheque->voucher_id;
        }

        if (! $voucherId) {
            return;
        }

        $voucher = \Tally\Models\Voucher::query()->with('entries')->find($voucherId);
        if (! $voucher || $voucher->isCancelled()) {
            return;
        }

        if ($voucher->isPosted()) {
            try {
                if (class_exists(\Tally\Accounting\VoucherEngine::class)) {
                    app(\Tally\Accounting\VoucherEngine::class)->cancel($voucher, true);
                } else {
                    $voucher->update([
                        'status' => \Tally\Accounting\VoucherStatus::Cancelled,
                        'cancelled_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                $voucher->update([
                    'status' => \Tally\Accounting\VoucherStatus::Cancelled,
                    'cancelled_at' => now(),
                ]);
                if (class_exists(\Tally\Events\VoucherWritten::class)) {
                    \Tally\Events\VoucherWritten::dispatch($voucher);
                }
            }
        } else {
            $voucher->update([
                'status' => \Tally\Accounting\VoucherStatus::Cancelled,
                'cancelled_at' => now(),
            ]);
        }

        // Unreconcile any bank reconciliation records linked to entries of this voucher
        if (class_exists(\Tally\Models\BankReconciliation::class) && Schema::hasTable('acct_bank_reconciliations')) {
            \Tally\Models\BankReconciliation::query()
                ->whereIn('voucher_entry_id', $voucher->entries->pluck('id'))
                ->update([
                    'status' => \Tally\Banking\ReconciliationStatus::Unreconciled,
                    'reconciled_on' => null,
                ]);
        }
    }

    /**
     * Synchronize cleared cheque status with Books Bank Reconciliation.
     */
    protected function syncBankReconciliation(Cheque $cheque, string $clearDate): void
    {
        if (! class_exists(\Tally\Models\Voucher::class) || ! Schema::hasTable('acct_bank_reconciliations')) {
            return;
        }

        $payment = $cheque->payment_id ? Payment::query()->find($cheque->payment_id) : null;
        $voucherId = null;

        if (class_exists(\App\Domains\Sync\Support\SyncLinks::class)) {
            $voucherId = \App\Domains\Sync\Support\SyncLinks::acctId(\App\Domains\Sync\Handlers\ChequeSync::KEY, $cheque)
                ?? ($payment ? \App\Domains\Sync\Support\SyncLinks::acctId(\App\Domains\Sync\Handlers\PaymentSync::KEY, $payment) : null);
        }

        if (! $voucherId && $cheque->voucher_id && in_array($cheque->voucher_type, ['Voucher', 'Payment', 'Receipt'], true)) {
            $voucherId = $cheque->voucher_id;
        }

        if (! $voucherId) {
            try {
                if ($payment && class_exists(\App\Domains\Sync\Handlers\PaymentSync::class)) {
                    app(\App\Domains\Sync\Handlers\PaymentSync::class)->push($payment);
                    $voucherId = \App\Domains\Sync\Support\SyncLinks::acctId(\App\Domains\Sync\Handlers\PaymentSync::KEY, $payment);
                } elseif (class_exists(\App\Domains\Sync\Handlers\ChequeSync::class)) {
                    app(\App\Domains\Sync\Handlers\ChequeSync::class)->push($cheque);
                    $voucherId = \App\Domains\Sync\Support\SyncLinks::acctId(\App\Domains\Sync\Handlers\ChequeSync::KEY, $cheque);
                }
            } catch (\Throwable $e) {
                Log::warning('Cheque clearance Books sync warning: '.$e->getMessage());
            }
        }

        if (! $voucherId) {
            return;
        }

        $voucher = \Tally\Models\Voucher::query()->with(['entries.ledger', 'company'])->find($voucherId);
        if (! $voucher) {
            return;
        }

        $bankLedgerIds = [];
        if (class_exists(\Tally\Banking\BankBook::class) && $voucher->company) {
            $bankLedgerIds = app(\Tally\Banking\BankBook::class)->bankLedgerIds($voucher->company);
        }

        $bankEntry = $voucher->entries->first(function ($entry) use ($bankLedgerIds) {
            return in_array((int) $entry->ledger_id, $bankLedgerIds, true);
        }) ?? $voucher->entries->first(function ($entry) {
            return $entry->ledger?->bankAccount !== null;
        }) ?? $voucher->entries->first(function ($entry) use ($voucher) {
            return $voucher->voucher_type === \Tally\Accounting\VoucherType::Receipt
                ? (float) $entry->debit > 0
                : (float) $entry->credit > 0;
        }) ?? $voucher->entries->first();

        if (! $bankEntry) {
            return;
        }

        $amount = (float) ($bankEntry->debit > 0 ? $bankEntry->debit : $bankEntry->credit);

        \Tally\Models\BankReconciliation::query()->updateOrCreate(
            ['voucher_entry_id' => $bankEntry->id],
            [
                'company_id' => $voucher->company_id,
                'ledger_id' => $bankEntry->ledger_id,
                'reference' => 'CHQ-'.$cheque->cheque_no,
                'transaction_date' => $voucher->voucher_date,
                'book_amount' => $amount,
                'bank_amount' => $amount,
                'status' => \Tally\Banking\ReconciliationStatus::Reconciled,
                'reconciled_on' => $clearDate,
            ]
        );
    }

    public function markStale(Cheque $cheque, ?string $reason = null): Cheque
    {
        if (in_array($cheque->status, [Cheque::STATUS_CLEARED, Cheque::STATUS_BOUNCED, Cheque::STATUS_CANCELLED], true)) {
            throw new InvalidArgumentException("Cannot mark cheque as stale from '{$cheque->status}' status.");
        }

        $cheque->update([
            'status' => Cheque::STATUS_STALE,
            'stale_date' => now()->toDateString(),
            'notes' => trim(($cheque->notes ? $cheque->notes."\n" : '').($reason ?: 'Marked stale / expired (> 90 days validity).')),
        ]);

        $this->auditLogService->record($cheque, 'stale', null, ['reason' => $reason]);

        return $cheque->fresh();
    }

    public function getRegisterStatistics(?int $companyId = null, ?string $bankAccountNo = null): array
    {
        $base = Cheque::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when(filled($bankAccountNo), fn ($q) => $q->where('bank_account_no', trim((string) $bankAccountNo)));

        $issuedQuery = (clone $base)->where(function ($q) {
            $q->where('cheque_type', Cheque::TYPE_ISSUED)
                ->orWhere('direction', 'issued_to_vendor');
        });

        $receivedQuery = (clone $base)->where(function ($q) {
            $q->where('cheque_type', Cheque::TYPE_RECEIVED)
                ->orWhere('direction', '!=', 'issued_to_vendor');
        });

        return [
            'total_issued_count' => (int) (clone $issuedQuery)->count(),
            'total_issued_amount' => (float) (clone $issuedQuery)->sum('amount'),
            'total_received_count' => (int) (clone $receivedQuery)->count(),
            'total_received_amount' => (float) (clone $receivedQuery)->sum('amount'),
            'pending_count' => (int) (clone $base)->whereIn('status', ['pending', Cheque::STATUS_ISSUED, Cheque::STATUS_RECEIVED])->count(),
            'deposited_count' => (int) (clone $base)->where('status', Cheque::STATUS_DEPOSITED)->count(),
            'cleared_count' => (int) (clone $base)->where('status', Cheque::STATUS_CLEARED)->count(),
            'bounced_count' => (int) (clone $base)->where('status', Cheque::STATUS_BOUNCED)->count(),
            'cancelled_count' => (int) (clone $base)->where('status', Cheque::STATUS_CANCELLED)->count(),
            'stale_count' => (int) (clone $base)->whereIn('status', [Cheque::STATUS_STALE, 'expired'])->count(),
        ];
    }
}
