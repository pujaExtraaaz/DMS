<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentAllocation;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sync\Handlers\BankTransactionSync;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\BooksPoster;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Models\Party;
use Tally\Models\Voucher;
use Tally\Models\VoucherEntry;

class PaymentSync
{
    public const KEY = 'payment';

    public function __construct(private readonly OutstandingLedgerService $outstanding) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Payment) {
            $this->push($model);

            return;
        }

        if ($model instanceof Voucher && $model->voucher_type === VoucherType::Receipt) {
            $this->pull($model);
        }

        if ($model instanceof Voucher) {
            try {
                app(BankTransactionSync::class)->sync($model);
            } catch (Throwable $exception) {
                SyncFailureLogger::write(BankTransactionSync::KEY, 'books_to_dms', $model, $exception);
            }
        }
    }

    public function push(Payment $payment): void
    {
        $payment->loadMissing('invoice.customer');
        $customer = $payment->invoice?->customer;
        if (! $customer?->company_id || ! $payment->invoice) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $customer->company_id);
        $date = ($payment->paid_at ?? $payment->created_at ?? now())->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId((int) $customer->company_id, $customer->branch_id ? (int) $customer->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Payment '.$payment->payment_no.' has no books company, branch, or financial year.');
        }

        $voucher = Voucher::query()->find(SyncLinks::acctId(self::KEY, $payment));
        if ($voucher && $voucher->status !== VoucherStatus::Draft) {
            SyncLinks::store(self::KEY, $payment, $voucher, 'conflict', 'Books receipt is '.$voucher->status->value.' and was left unchanged.');
            SyncFailureLogger::write(self::KEY, 'dms_to_books', $payment, 'Books receipt is '.$voucher->status->value.' and was left unchanged.');

            return;
        }

        app(CustomerSync::class)->push($customer);
        $party = Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $customer));
        if (! $party) {
            throw new \RuntimeException('Customer '.$customer->id.' did not sync before the receipt.');
        }

        $amount = number_format((float) $payment->amount, 2, '.', '');
        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'voucher_type' => VoucherType::Receipt,
            'voucher_number' => mb_substr($payment->payment_no, 0, 30),
            'voucher_date' => $date,
            'reference_number' => $payment->invoice->invoice_no,
            'narration' => $payment->notes ?: ('Receipt for '.$payment->invoice->invoice_no),
            'status' => VoucherStatus::Draft,
            'total_debit' => $amount,
            'total_credit' => $amount,
        ];

        DB::transaction(function () use (&$voucher, $attributes, $payment, $company, $party, $amount) {
            $voucher ? $voucher->update($attributes) : $voucher = Voucher::query()->create($attributes);
            $voucher->entries()->delete();
            VoucherEntry::query()->create([
                'voucher_id' => $voucher->id,
                'ledger_id' => BooksCompany::cashLedger($company)->id,
                'line_number' => 1,
                'debit' => $amount,
                'credit' => 0,
            ]);
            VoucherEntry::query()->create([
                'voucher_id' => $voucher->id,
                'ledger_id' => $party->ledger_id,
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

    public function remove(Model $source): void
    {
        if ($source instanceof Voucher) {
            try {
                app(BankTransactionSync::class)->remove($source);
            } catch (Throwable $exception) {
                SyncFailureLogger::write(BankTransactionSync::KEY, 'books_to_dms', $source, $exception);
            }
        }

        if ($source instanceof Payment) {
            $voucher = Voucher::query()->find(SyncLinks::acctId(self::KEY, $source));
            if (! $voucher) {
                SyncLinks::forget(self::KEY, $source);

                return;
            }
            if ($voucher->status !== VoucherStatus::Draft) {
                SyncLinks::store(self::KEY, $source, $voucher, 'conflict', 'Books receipt is posted, so the delete was not copied.');
                SyncFailureLogger::write(self::KEY, 'dms_to_books', $source, 'Books receipt is posted, so the delete was not copied.');

                return;
            }
            $voucher->entries()->delete();
            $voucher->delete();
            SyncLinks::forget(self::KEY, $source);

            return;
        }

        if (! $source instanceof Voucher || $source->voucher_type !== VoucherType::Receipt) {
            return;
        }

        $payment = Payment::query()->find(SyncLinks::dmsId(self::KEY, $source));
        if (! $payment) {
            SyncLinks::forget(self::KEY, $source);

            return;
        }
        if ($payment->status === 'completed') {
            SyncLinks::store(self::KEY, $payment, $source, 'conflict', 'DMS payment is completed, so the delete was not copied.');
            SyncFailureLogger::write(self::KEY, 'books_to_dms', $source, 'DMS payment is completed, so the delete was not copied.');

            return;
        }
        $payment->delete();
        SyncLinks::forget(self::KEY, $source);
    }

    public function pull(Voucher $voucher): void
    {
        if ($voucher->voucher_type !== VoucherType::Receipt) {
            return;
        }

        $existing = Payment::query()->find(SyncLinks::dmsId(self::KEY, $voucher));
        if ($existing) {
            return;
        }

        $invoice = Invoice::query()->where('invoice_no', $voucher->reference_number)->first();
        if (! $invoice) {
            SyncFailureLogger::write(self::KEY, 'books_to_dms', $voucher, 'Receipt '.$voucher->voucher_number.' has no DMS invoice reference.');

            return;
        }

        $amount = (float) $voucher->total_credit;
        $method = 'cash';
        $payment = Payment::query()->create([
            'payment_no' => $this->paymentNumber($voucher),
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => $amount,
            'method' => $method,
            'status' => 'completed',
            'paid_at' => $voucher->voucher_date,
            'notes' => $voucher->narration,
        ]);
        PaymentAllocation::query()->create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
        ]);

        $newPaid = (float) $invoice->paid_amount + $amount;
        $invoice->update([
            'paid_amount' => $newPaid,
            'status' => $newPaid >= (float) $invoice->grand_total ? 'paid' : 'partial',
        ]);
        $this->outstanding->recordPayment($payment);
        SyncLinks::store(self::KEY, $payment, $voucher);
    }

    private function paymentNumber(Voucher $voucher): string
    {
        $preferred = mb_substr($voucher->voucher_number, 0, 30);
        $taken = Payment::query()->where('payment_no', $preferred)->exists();

        return $taken ? mb_substr($preferred, 0, 22).'-B'.$voucher->id : $preferred;
    }
}
