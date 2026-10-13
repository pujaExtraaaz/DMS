<?php

namespace Tally\Payables;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Banking\ElectronicPaymentService;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\ApprovalRequest;
use Tally\Models\BankAccount;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Invoice;
use Tally\Models\PaymentBatch;

class PaymentBatchService
{
    public function __construct(
        private readonly VoucherEngine $vouchers,
        private readonly ElectronicPaymentService $electronicPayments,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, array $input, ?PaymentBatch $batch = null): PaymentBatch
    {
        return DB::transaction(function () use ($company, $input, $batch) {
            $total = 0.0;
            $lineRows = [];

            foreach ($input['lines'] ?? [] as $line) {
                if (! is_array($line) || empty($line['supplier_ledger_id'])) {
                    continue;
                }
                $amount = (float) ($line['amount'] ?? 0);
                $total += $amount;
                $lineRows[] = [
                    'supplier_ledger_id' => (int) $line['supplier_ledger_id'],
                    'bill_id' => ($line['bill_id'] ?? null) ?: null,
                    'amount' => round($amount, 2),
                    'tds_amount' => round((float) ($line['tds_amount'] ?? 0), 2),
                    'reference' => ($line['reference'] ?? null) ?: null,
                ];
            }

            $payload = [
                'branch_id' => ($input['branch_id'] ?? null) ?: null,
                'batch_date' => (string) ($input['batch_date'] ?? now()->toDateString()),
                'bank_ledger_id' => ($input['bank_ledger_id'] ?? null) ?: null,
                'total_amount' => round($total, 2),
                'status' => ($input['status'] ?? null) ?: ($batch?->status ?? 'draft'),
                'narration' => ($input['narration'] ?? null) ?: null,
            ];

            if ($batch) {
                abort_unless($batch->company_id === $company->id, 404);
                if (in_array($batch->status, ['approved', 'paid'], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'This payment batch cannot be changed.',
                    ]);
                }
                $batch->update($payload);
                $batch->lines()->delete();
            } else {
                $count = PaymentBatch::query()->where('company_id', $company->id)->count() + 1;
                $batch = PaymentBatch::query()->create($payload + [
                    'company_id' => $company->id,
                    'number' => 'PB-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT),
                ]);
            }

            if ($lineRows !== []) {
                $batch->lines()->createMany($lineRows);
            }

            return $batch->fresh(['lines.supplier', 'bankLedger']);
        });
    }

    /**
     * Prefill a draft payment batch from posted purchase bills.
     *
     * @param  list<int>  $invoiceIds
     */
    public function createFromBills(Company $company, array $invoiceIds, ?int $branchId = null, ?int $bankLedgerId = null): PaymentBatch
    {
        $invoices = Invoice::query()
            ->where('company_id', $company->id)
            ->where('kind', InvoiceKind::Purchase)
            ->where('status', VoucherStatus::Posted)
            ->whereIn('id', $invoiceIds)
            ->get();

        if ($invoices->isEmpty()) {
            throw ValidationException::withMessages([
                'invoice_ids' => 'Select at least one posted purchase bill.',
            ]);
        }

        $lines = $invoices->map(fn (Invoice $invoice) => [
            'supplier_ledger_id' => $invoice->party_ledger_id,
            'bill_id' => $invoice->id,
            'amount' => (string) $invoice->grand_total,
            'tds_amount' => '0',
            'reference' => $invoice->invoice_number,
        ])->all();

        $batchDate = now()->toDateString();
        $year = FinancialYear::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('start_date', '<=', $batchDate)
            ->where('end_date', '>=', $batchDate)
            ->first()
            ?? $company->financialYears()->where('is_active', true)->orderByDesc('start_date')->first();
        if ($year && ($batchDate < $year->start_date->toDateString() || $batchDate > $year->end_date->toDateString())) {
            $batchDate = $year->end_date->toDateString();
        }

        return $this->save($company, [
            'branch_id' => $branchId,
            'batch_date' => $batchDate,
            'bank_ledger_id' => $bankLedgerId,
            'narration' => 'Payment for '.$invoices->pluck('invoice_number')->implode(', '),
            'status' => 'draft',
            'lines' => $lines,
        ]);
    }

    public function submit(Company $company, PaymentBatch $batch): PaymentBatch
    {
        abort_unless($batch->company_id === $company->id, 404);
        if ($batch->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Only draft batches can be submitted.',
            ]);
        }
        $batch->update(['status' => 'pending_approval']);

        return $batch->fresh(['lines']);
    }

    public function approve(Company $company, PaymentBatch $batch, User $user): PaymentBatch
    {
        abort_unless($batch->company_id === $company->id, 404);
        if ($batch->status !== 'pending_approval') {
            throw ValidationException::withMessages([
                'status' => 'Only pending batches can be approved.',
            ]);
        }
        $batch->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        ApprovalRequest::query()
            ->where('company_id', $company->id)
            ->where('document_type', 'payment_batch')
            ->where('document_id', $batch->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'decided_by' => $user->id,
                'decided_at' => now(),
            ]);

        $batch = $batch->fresh(['lines']);

        // Auto-post payment vouchers when the batch is ready; otherwise leave as approved.
        if ($batch->bank_ledger_id && $batch->lines->isNotEmpty()) {
            return $this->pay($company, $batch, $user);
        }

        return $batch->fresh(['lines', 'approver']);
    }

    /**
     * Post one payment voucher per supplier line (Dr supplier, Cr bank) with bill allocation when present.
     */
    public function pay(Company $company, PaymentBatch $batch, User $user): PaymentBatch
    {
        abort_unless($batch->company_id === $company->id, 404);

        if (! in_array($batch->status, ['approved', 'pending_approval'], true) && $batch->status !== 'paid') {
            // allow pay from approved; if already paid, return
        }
        if ($batch->status === 'paid') {
            return $batch->fresh(['lines', 'approver']);
        }
        if ($batch->status !== 'approved') {
            throw ValidationException::withMessages([
                'status' => 'Approve the batch before paying.',
            ]);
        }
        if (! $batch->bank_ledger_id) {
            throw ValidationException::withMessages([
                'bank_ledger_id' => 'Select a bank ledger on the payment batch.',
            ]);
        }

        $batch->loadMissing(['lines.supplier']);
        if ($batch->lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'Add at least one payment line.',
            ]);
        }

        $branch = $batch->branch_id
            ? Branch::query()->where('company_id', $company->id)->whereKey($batch->branch_id)->first()
            : $company->branches()->where('is_active', true)->orderBy('id')->first();
        $year = FinancialYear::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('start_date', '<=', $batch->batch_date->toDateString())
            ->where('end_date', '>=', $batch->batch_date->toDateString())
            ->first()
            ?? $company->financialYears()->where('is_active', true)->orderByDesc('start_date')->first();

        if (! $branch || ! $year) {
            throw ValidationException::withMessages([
                'batch_date' => 'Active branch and financial year are required to post payments.',
            ]);
        }

        return DB::transaction(function () use ($company, $batch, $user, $branch, $year) {
            foreach ($batch->lines as $line) {
                $amount = Money::format(Money::cents((string) $line->amount));
                if (Money::cents($amount) <= 0) {
                    continue;
                }

                $allocations = [];
                if ($line->bill_id) {
                    $allocations[] = [
                        'bill_id' => $line->bill_id,
                        'amount' => $amount,
                    ];
                }

                $voucher = $this->vouchers->save(
                    $company,
                    $branch,
                    $year,
                    $user,
                    VoucherType::Payment,
                    [
                        'voucher_date' => $batch->batch_date->toDateString(),
                        'reference_number' => $batch->number.($line->reference ? '/'.$line->reference : ''),
                        'narration' => trim(($batch->narration ?: 'Payment batch '.$batch->number).' — supplier #'.$line->supplier_ledger_id),
                        'entries' => [
                            ['ledger_id' => $line->supplier_ledger_id, 'debit' => $amount, 'credit' => '0.00'],
                            ['ledger_id' => $batch->bank_ledger_id, 'debit' => '0.00', 'credit' => $amount],
                        ],
                        'allocations' => $allocations,
                    ],
                    true
                );

                $bankAccountId = BankAccount::query()
                    ->where('company_id', $company->id)
                    ->where('ledger_id', $batch->bank_ledger_id)
                    ->value('id');

                $this->electronicPayments->save($company, [
                    'bank_account_id' => $bankAccountId,
                    'voucher_id' => $voucher->id,
                    'payment_batch_id' => $batch->id,
                    'channel' => Money::cents($amount) >= 20000000 ? 'rtgs' : 'neft',
                    'reference' => $batch->number.($line->reference ? '/'.$line->reference : ''),
                    'payment_date' => $batch->batch_date->toDateString(),
                    'amount' => $amount,
                    'beneficiary' => $line->supplier?->name ?? ('Supplier #'.$line->supplier_ledger_id),
                    'status' => 'initiated',
                ]);
            }

            $batch->update(['status' => 'paid']);

            return $batch->fresh(['lines', 'approver']);
        });
    }
}
