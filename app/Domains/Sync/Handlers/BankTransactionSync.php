<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Banking\Models\BankAccountTransaction;
use App\Domains\Banking\Models\OdAccount;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Models\BankAccount;
use Tally\Models\Voucher;
use Tally\Models\VoucherEntry;

class BankTransactionSync
{
    public const KEY = 'bank_transaction';

    public function __construct(private readonly BankAccountSync $accounts) {}

    public function sync(Model $model): void
    {
        if ($model instanceof BankAccountTransaction) {
            $this->push($model);

            return;
        }

        if ($model instanceof Voucher) {
            $this->pull($model);
        }
    }

    public function push(BankAccountTransaction $transaction): void
    {
        $account = $transaction->odAccount ?: OdAccount::query()
            ->where('company_id', $transaction->company_id)
            ->where('account_number', $transaction->account_number)
            ->first();
        if (! $account) {
            return;
        }

        $this->accounts->push($account);
        $bank = BankAccount::query()->find(SyncLinks::acctId(BankAccountSync::KEY, $account));
        if (! $bank) {
            throw new \RuntimeException('Bank account '.$account->account_number.' did not sync before the transaction.');
        }

        $company = BooksCompany::forOrganization((int) $account->company_id);
        $date = $transaction->transaction_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId((int) $account->company_id, $account->branch_id ? (int) $account->branch_id : null) : null;
        if (! $company || ! $year || ! $branchId) {
            throw new \RuntimeException('Bank transaction '.$transaction->transaction_no.' has no books company, branch, or financial year.');
        }

        $voucher = Voucher::query()->find(SyncLinks::acctId(self::KEY, $transaction));
        if ($voucher && $voucher->status !== VoucherStatus::Draft) {
            $this->conflict($transaction, $voucher, 'dms_to_books', 'Books voucher is '.$voucher->status->value.' and was left unchanged.');

            return;
        }

        $netIn = round((float) $transaction->credit - (float) $transaction->debit, 2);
        if (abs($netIn) < 0.005) {
            return;
        }

        $amount = number_format(abs($netIn), 2, '.', '');
        $bankDebit = $netIn > 0 ? $amount : '0.00';
        $bankCredit = $netIn < 0 ? $amount : '0.00';
        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'voucher_type' => VoucherType::Journal,
            'voucher_number' => $this->booksNumber((string) $transaction->transaction_no, $company->id, $voucher?->id),
            'voucher_date' => $date,
            'narration' => $transaction->description,
            'status' => VoucherStatus::Draft,
            'total_debit' => $amount,
            'total_credit' => $amount,
        ];

        DB::transaction(function () use (&$voucher, $attributes, $transaction, $company, $bank, $amount, $bankDebit, $bankCredit) {
            $voucher ? $voucher->update($attributes) : $voucher = Voucher::query()->create($attributes);
            $voucher->entries()->delete();
            VoucherEntry::query()->create([
                'voucher_id' => $voucher->id,
                'ledger_id' => $bank->ledger_id,
                'line_number' => 1,
                'debit' => $bankDebit,
                'credit' => $bankCredit,
            ]);
            VoucherEntry::query()->create([
                'voucher_id' => $voucher->id,
                'ledger_id' => BooksCompany::clearingLedger($company)->id,
                'line_number' => 2,
                'debit' => $bankCredit,
                'credit' => $bankDebit,
            ]);
            SyncLinks::store(self::KEY, $transaction, $voucher);
        });
    }

    public function pull(Voucher $voucher): void
    {
        if (SyncLinks::dmsId(PaymentSync::KEY, $voucher)) {
            return;
        }

        $transaction = BankAccountTransaction::query()->find(SyncLinks::dmsId(self::KEY, $voucher));
        if ($voucher->status !== VoucherStatus::Draft && $transaction) {
            $this->conflict($transaction, $voucher, 'books_to_dms', 'Books voucher is '.$voucher->status->value.' and was left unchanged.');

            return;
        }

        $voucher->loadMissing('entries.ledger');
        $entry = $voucher->entries->first(fn (VoucherEntry $row) => $row->ledger?->isBank());
        if (! $entry) {
            return;
        }

        $bank = BankAccount::query()->where('ledger_id', $entry->ledger_id)->first();
        if (! $bank) {
            return;
        }
        $this->accounts->pull($bank);
        $account = OdAccount::query()->find(SyncLinks::dmsId(BankAccountSync::KEY, $bank));
        if (! $account) {
            return;
        }

        $moneyIn = round((float) $entry->debit, 2);
        $moneyOut = round((float) $entry->credit, 2);
        if ($moneyIn < 0.005 && $moneyOut < 0.005) {
            return;
        }

        $attributes = [
            'company_id' => $account->company_id,
            'account_number' => $account->account_number,
            'od_account_id' => $account->id,
            'transaction_date' => $voucher->voucher_date->toDateString(),
            'transaction_no' => $this->dmsNumber($voucher, $transaction),
            'description' => mb_substr((string) ($voucher->narration ?: 'Bank transaction'), 0, 255),
            'transaction_type' => $moneyIn >= $moneyOut ? 'credit' : 'debit',
            'debit' => $moneyOut,
            'credit' => $moneyIn,
            'created_by' => $transaction?->created_by ?: User::query()->value('id'),
        ];

        $transaction ? $transaction->update($attributes) : $transaction = BankAccountTransaction::query()->create($attributes);
        SyncLinks::store(self::KEY, $transaction, $voucher);
    }

    public function remove(Model $source): void
    {
        if ($source instanceof BankAccountTransaction) {
            $voucher = Voucher::query()->find(SyncLinks::acctId(self::KEY, $source));
            if (! $voucher) {
                SyncLinks::forget(self::KEY, $source);

                return;
            }
            if ($voucher->status !== VoucherStatus::Draft) {
                $this->conflict($source, $voucher, 'dms_to_books', 'Books voucher is posted, so the delete was not copied.');

                return;
            }
            $voucher->entries()->delete();
            $voucher->delete();
            SyncLinks::forget(self::KEY, $source);

            return;
        }

        if (! $source instanceof Voucher) {
            return;
        }

        $transaction = BankAccountTransaction::query()->find(SyncLinks::dmsId(self::KEY, $source));
        if (! $transaction) {
            return;
        }
        if ($source->status !== VoucherStatus::Draft) {
            $this->conflict($transaction, $source, 'books_to_dms', 'Books voucher is posted, so the delete was not copied.');

            return;
        }
        $transaction->delete();
        SyncLinks::forget(self::KEY, $source);
    }

    private function booksNumber(string $preferred, int $companyId, ?int $ignoreId): string
    {
        $preferred = mb_substr($preferred !== '' ? $preferred : 'BANK', 0, 30);
        $taken = Voucher::query()
            ->where('company_id', $companyId)
            ->where('voucher_number', $preferred)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 24).'-K'.($ignoreId ?: 'N') : $preferred;
    }

    private function dmsNumber(Voucher $voucher, ?BankAccountTransaction $transaction): string
    {
        $preferred = mb_substr((string) $voucher->voucher_number, 0, 40);
        $taken = BankAccountTransaction::query()
            ->where('transaction_no', $preferred)
            ->when($transaction, fn ($query) => $query->whereKeyNot($transaction->id))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 32).'-B'.$voucher->id : $preferred;
    }

    private function conflict(BankAccountTransaction $transaction, Voucher $voucher, string $direction, string $message): void
    {
        SyncLinks::store(self::KEY, $transaction, $voucher, 'conflict', $message);
        SyncFailureLogger::write(self::KEY, $direction, $direction === 'dms_to_books' ? $transaction : $voucher, $message);
    }
}
