<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\SupplierPayable;
use App\Domains\Purchasing\Models\SupplierPayment;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\BooksPoster;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Models\Ledger;
use Tally\Models\Party;
use Tally\Models\Voucher;
use Tally\Models\VoucherEntry;

class SupplierPaymentSync
{
    public const KEY = 'supplier_payment';

    public function sync(Model $model): void
    {
        if ($model instanceof SupplierPayment) {
            $this->push($model);

            return;
        }

        if ($model instanceof Voucher && $model->voucher_type === VoucherType::Payment) {
            $this->pull($model);

            try {
                app(BankTransactionSync::class)->sync($model);
            } catch (Throwable $exception) {
                SyncFailureLogger::write(BankTransactionSync::KEY, 'books_to_dms', $model, $exception);
            }
        }
    }

    public function push(SupplierPayment $payment): void
    {
        $payment->loadMissing(['supplier', 'purchaseInvoice']);
        $supplier = $payment->supplier;
        $orgCompanyId = (int) ($payment->company_id ?: $supplier?->company_id);
        if (! $orgCompanyId || ! $supplier) {
            return;
        }

        if ($payment->status === 'cancelled') {
            $this->cancelBooksTwin($payment);

            return;
        }

        $company = BooksCompany::forOrganization($orgCompanyId);
        $date = $payment->payment_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId($orgCompanyId, $supplier->branch_id ? (int) $supplier->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Supplier payment '.$payment->payment_no.' has no books company, branch, or financial year.');
        }

        $voucher = Voucher::query()->find(SyncLinks::acctId(self::KEY, $payment));
        if ($voucher && $voucher->status !== VoucherStatus::Draft) {
            SyncLinks::store(self::KEY, $payment, $voucher, 'conflict', 'Books payment is '.$voucher->status->value.' and was left unchanged.');
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $payment, 'Books payment is '.$voucher->status->value.' and was left unchanged.');

            return;
        }

        app(CustomerSync::class)->push($supplier);
        $party = Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $supplier));
        if (! $party) {
            throw new \RuntimeException('Supplier '.$supplier->id.' did not sync before the payment.');
        }

        $amount = number_format((float) $payment->amount, 2, '.', '');
        $reference = $payment->purchaseInvoice?->invoice_no ?: $payment->reference_no;
        $narration = $payment->notes
            ?: ($reference ? 'Payment for '.$reference : 'Supplier payment '.$payment->payment_no);

        $moneyLedger = BooksCompany::receiptLedger($company, $payment->method ?? 'bank');

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'voucher_type' => VoucherType::Payment,
            'voucher_number' => mb_substr($payment->payment_no, 0, 30),
            'voucher_date' => $date,
            'reference_number' => $reference ? mb_substr((string) $reference, 0, 60) : null,
            'narration' => $narration,
            'status' => VoucherStatus::Draft,
            'total_debit' => $amount,
            'total_credit' => $amount,
        ];

        DB::transaction(function () use (&$voucher, $attributes, $payment, $party, $amount, $moneyLedger) {
            $voucher ? $voucher->update($attributes) : $voucher = Voucher::query()->create($attributes);
            $voucher->entries()->delete();
            // Payment: Dr Supplier, Cr Bank/Cash
            VoucherEntry::query()->create([
                'voucher_id' => $voucher->id,
                'ledger_id' => $party->ledger_id,
                'line_number' => 1,
                'debit' => $amount,
                'credit' => 0,
            ]);
            VoucherEntry::query()->create([
                'voucher_id' => $voucher->id,
                'ledger_id' => $moneyLedger->id,
                'line_number' => 2,
                'debit' => 0,
                'credit' => $amount,
            ]);
            SyncLinks::store(self::KEY, $payment, $voucher);
            if ($payment->status === 'completed') {
                app(BooksPoster::class)->voucher($voucher, self::KEY);
            }
        });
    }

    /**
     * Books Payment voucher → DMS supplier payment + AP credit.
     */
    public function pull(Voucher $voucher): void
    {
        if ($voucher->voucher_type !== VoucherType::Payment) {
            return;
        }

        if ($voucher->status === VoucherStatus::Cancelled) {
            $existing = SupplierPayment::query()->find(SyncLinks::dmsId(self::KEY, $voucher));
            if ($existing && $existing->status !== 'cancelled') {
                $existing->update(['status' => 'cancelled']);
            }

            return;
        }

        if (SyncLinks::dmsId(self::KEY, $voucher)) {
            return;
        }

        // Skip if this voucher is somehow also linked as a customer receipt payment.
        if (SyncLinks::dmsId(PaymentSync::KEY, $voucher)) {
            return;
        }

        $voucher->loadMissing(['entries.ledger.accountGroup']);
        $partyLedger = $voucher->entries
            ->first(function (VoucherEntry $entry) {
                if ((float) $entry->debit <= 0) {
                    return false;
                }
                $ledger = $entry->ledger;
                if (! $ledger) {
                    return false;
                }

                return method_exists($ledger, 'isSupplier') && $ledger->isSupplier();
            })
            ?->ledger;

        if (! $partyLedger) {
            // Soft skip — journals/contra-like payments without a creditor debit.
            return;
        }

        $party = Party::query()->where('ledger_id', $partyLedger->id)->first();
        if (! $party) {
            SyncFailureLogger::write(self::KEY, 'books_to_dms', $voucher, 'Payment '.$voucher->voucher_number.' has no party for supplier ledger.');

            return;
        }

        app(CustomerSync::class)->pull($party);
        $supplierId = SyncLinks::dmsId(CustomerSync::KEY, $party);
        if (! $supplierId) {
            SyncFailureLogger::write(self::KEY, 'books_to_dms', $voucher, 'Party '.$party->id.' did not sync to a DMS supplier.');

            return;
        }

        $organizationId = BooksCompany::organizationId((int) $voucher->company_id);
        $purchaseInvoiceId = null;
        if ($voucher->reference_number) {
            $purchaseInvoiceId = PurchaseInvoice::query()
                ->where('invoice_no', $voucher->reference_number)
                ->when($organizationId, fn ($q) => $q->where('company_id', $organizationId))
                ->value('id');
        }

        $amount = (float) ($voucher->total_debit ?: $voucher->total_credit);
        if ($amount <= 0) {
            return;
        }

        $method = $this->guessMethod($voucher);

        DB::transaction(function () use ($voucher, $supplierId, $organizationId, $purchaseInvoiceId, $amount, $method) {
            $payment = SupplierPayment::query()->create([
                'payment_no' => $this->paymentNumber($voucher),
                'company_id' => $organizationId,
                'supplier_id' => $supplierId,
                'purchase_invoice_id' => $purchaseInvoiceId,
                'payment_date' => $voucher->voucher_date?->toDateString() ?: now()->toDateString(),
                'amount' => $amount,
                'method' => $method,
                'reference_no' => $voucher->reference_number,
                'status' => $voucher->status === VoucherStatus::Posted ? 'completed' : 'draft',
                'notes' => $voucher->narration,
                'recorded_by' => null,
            ]);

            if ($payment->status === 'completed') {
                $previous = (float) (SupplierPayable::query()
                    ->where('supplier_id', $supplierId)
                    ->orderByDesc('id')
                    ->value('balance') ?? 0);

                SupplierPayable::create([
                    'supplier_id' => $supplierId,
                    'type' => 'payment',
                    'reference_type' => $payment->getMorphClass(),
                    'reference_id' => $payment->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'balance' => $previous - $amount,
                    'notes' => 'Books payment '.$voucher->voucher_number,
                ]);
            }

            SyncLinks::store(self::KEY, $payment, $voucher);
        });
    }

    public function cancelBooksTwin(SupplierPayment $payment): void
    {
        $voucher = Voucher::query()->find(SyncLinks::acctId(self::KEY, $payment));
        if (! $voucher) {
            return;
        }

        try {
            if ($voucher->status === VoucherStatus::Draft) {
                $voucher->entries()->delete();
                $voucher->delete();
                SyncLinks::forget(self::KEY, $payment);

                return;
            }

            if (method_exists($voucher, 'isCancelled') && $voucher->isCancelled()) {
                return;
            }

            app(\Tally\Accounting\VoucherEngine::class)->cancel($voucher->fresh());
        } catch (\Throwable $e) {
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $payment, $e);
        }
    }

    public function remove(Model $source): void
    {
        if ($source instanceof Voucher && $source->voucher_type === VoucherType::Payment) {
            try {
                app(BankTransactionSync::class)->remove($source);
            } catch (Throwable $exception) {
                SyncFailureLogger::write(BankTransactionSync::KEY, 'books_to_dms', $source, $exception);
            }

            $payment = SupplierPayment::query()->find(SyncLinks::dmsId(self::KEY, $source));
            if (! $payment) {
                SyncLinks::forget(self::KEY, $source);

                return;
            }
            if ($payment->status === 'completed') {
                SyncLinks::store(self::KEY, $payment, $source, 'conflict', 'DMS supplier payment is completed, so the delete was not copied.');
                SyncFailureLogger::write(self::KEY, 'books_to_dms', $source, 'DMS supplier payment is completed, so the delete was not copied.');

                return;
            }
            $payment->delete();
            SyncLinks::forget(self::KEY, $source);

            return;
        }

        if (! $source instanceof SupplierPayment) {
            return;
        }

        $voucher = Voucher::query()->find(SyncLinks::acctId(self::KEY, $source));
        if (! $voucher) {
            SyncLinks::forget(self::KEY, $source);

            return;
        }

        if ($voucher->status !== VoucherStatus::Draft) {
            SyncLinks::store(self::KEY, $source, $voucher, 'conflict', 'Books payment is posted, so the delete was not copied.');
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $source, 'Books payment is posted, so the delete was not copied.');

            return;
        }

        $voucher->entries()->delete();
        $voucher->delete();
        SyncLinks::forget(self::KEY, $source);
    }

    private function paymentNumber(Voucher $voucher): string
    {
        $preferred = mb_substr($voucher->voucher_number, 0, 30);
        $taken = SupplierPayment::query()->where('payment_no', $preferred)->exists();

        return $taken ? mb_substr($preferred, 0, 22).'-B'.$voucher->id : $preferred;
    }

    private function guessMethod(Voucher $voucher): string
    {
        $creditLedger = $voucher->entries
            ->first(fn (VoucherEntry $entry) => (float) $entry->credit > 0)
            ?->ledger;

        if (! $creditLedger instanceof Ledger) {
            return 'bank';
        }

        $name = mb_strtolower((string) $creditLedger->name);
        if (str_contains($name, 'cash')) {
            return 'cash';
        }
        if (str_contains($name, 'upi')) {
            return 'upi';
        }
        if (str_contains($name, 'cheque') || str_contains($name, 'check')) {
            return 'cheque';
        }

        return 'bank';
    }
}
