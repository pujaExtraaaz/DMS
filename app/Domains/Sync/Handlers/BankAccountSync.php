<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Banking\Models\OdAccount;
use App\Domains\Organization\Models\Branch;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tally\Accounting\OpeningBalanceType;
use Tally\Accounting\VoucherStatus;
use Tally\Banking\BankAccountService;
use Tally\Models\AccountGroup;
use Tally\Models\BankAccount;
use Tally\Models\Ledger;
use Tally\Models\VoucherEntry;

class BankAccountSync
{
    public const KEY = 'bank_account';

    public function __construct(private readonly BankAccountService $banks) {}

    public function sync(Model $model): void
    {
        if ($model instanceof OdAccount) {
            $this->push($model);

            return;
        }

        if ($model instanceof BankAccount) {
            $this->pull($model);
        }
    }

    public function push(OdAccount $account): void
    {
        $name = trim((string) $account->bank_name);
        $number = trim((string) $account->account_number);
        if ($name === '' || $number === '' || ! $account->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $account->company_id);
        if (! $company) {
            return;
        }

        $bank = BankAccount::query()->find(SyncLinks::acctId(self::KEY, $account));
        $ledger = $bank?->ledger ?: $this->ledger($company->id, $name, $number, null);
        if ($bank && $ledger) {
            $ledger->update([
                'name' => SyncNames::unique(Ledger::query()->where('company_id', $company->id), 'name', $name, $ledger->id),
            ]);
        }

        $this->banks->sync($ledger, [
            'bank_name' => $name,
            'account_number' => $number,
            'ifsc' => $account->ifsc_code,
        ]);
        $bank = BankAccount::query()->where('ledger_id', $ledger->id)->first();
        if (! $bank) {
            throw new \RuntimeException('Bank account '.$number.' was not created in Books.');
        }

        SyncLinks::store(self::KEY, $account, $bank);
    }

    public function pull(BankAccount $bank): void
    {
        $organizationId = BooksCompany::organizationId($bank->company_id);
        if (! $organizationId || trim((string) $bank->bank_name) === '' || trim((string) $bank->account_number) === '') {
            return;
        }

        $account = OdAccount::query()->find(SyncLinks::dmsId(self::KEY, $bank));
        $number = $this->dmsNumber($bank, $account, $organizationId);
        $attributes = [
            'company_id' => $organizationId,
            'account_number' => $number,
            'bank_name' => $bank->bank_name,
            'ifsc_code' => $bank->ifsc,
        ];
        if (! $account) {
            $attributes += [
                'branch_id' => Branch::query()->where('company_id', $organizationId)->value('id'),
                'od_limit' => 0,
                'interest_rate' => 0,
                'interest_calculation_method' => 'daily_simple',
                'effective_from' => now()->toDateString(),
                'status' => 'active',
                'created_by' => User::query()->value('id'),
            ];
            $account = OdAccount::query()->create($attributes);
        } else {
            $account->update($attributes);
        }

        SyncLinks::store(self::KEY, $account, $bank);
    }

    public function remove(Model $source): void
    {
        if ($source instanceof OdAccount) {
            $bank = BankAccount::query()->find(SyncLinks::acctId(self::KEY, $source));
            if (! $bank) {
                SyncLinks::forget(self::KEY, $source);

                return;
            }
            if ($this->posted($bank->ledger_id)) {
                $this->conflict($source, $bank, 'dms_to_books', 'Books bank ledger has posted vouchers, so the delete was not copied.');

                return;
            }
            if ($this->entries($bank->ledger_id)) {
                $this->conflict($source, $bank, 'dms_to_books', 'Books bank ledger still has vouchers, so the delete was not copied.');

                return;
            }
            $ledgerId = $bank->ledger_id;
            $bank->delete();
            Ledger::query()->whereKey($ledgerId)->delete();
            SyncLinks::forget(self::KEY, $source);

            return;
        }

        if (! $source instanceof BankAccount) {
            return;
        }

        $account = OdAccount::query()->find(SyncLinks::dmsId(self::KEY, $source));
        if (! $account) {
            SyncLinks::forget(self::KEY, $source);

            return;
        }
        if ($this->posted($source->ledger_id)) {
            $this->conflict($account, $source, 'books_to_dms', 'Books bank ledger has posted vouchers, so the delete was not copied.');

            return;
        }
        $account->delete();
        SyncLinks::forget(self::KEY, $source);
    }

    private function ledger(int $companyId, string $name, string $number, ?int $ignoreId): Ledger
    {
        $group = AccountGroup::query()->where('company_id', $companyId)->where('code', 'BANK')->first();
        if (! $group) {
            throw new \RuntimeException('Bank Accounts group is missing.');
        }

        $code = mb_substr('BK'.SyncNames::code($number, 18), 0, 20);
        $suffix = 2;
        while (Ledger::query()->where('company_id', $companyId)->where('code', $code)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $tail = (string) $suffix++;
            $code = mb_substr('BK'.SyncNames::code($number, 16), 0, 20 - strlen($tail)).$tail;
        }

        return Ledger::query()->create([
            'company_id' => $companyId,
            'account_group_id' => $group->id,
            'name' => SyncNames::unique(Ledger::query()->where('company_id', $companyId), 'name', $name),
            'code' => $code,
            'opening_balance' => 0,
            'opening_balance_type' => OpeningBalanceType::Debit,
            'is_active' => true,
            'is_system' => false,
        ]);
    }

    private function dmsNumber(BankAccount $bank, ?OdAccount $account, int $organizationId): string
    {
        $preferred = mb_substr((string) $bank->account_number, 0, 64);
        $taken = OdAccount::query()
            ->where('company_id', $organizationId)
            ->where('account_number', $preferred)
            ->when($account, fn ($query) => $query->whereKeyNot($account->id))
            ->exists();

        return $taken ? mb_substr($preferred, 0, 50).'-B'.$bank->id : $preferred;
    }

    private function posted(int $ledgerId): bool
    {
        return VoucherEntry::query()
            ->where('ledger_id', $ledgerId)
            ->whereHas('voucher', fn ($query) => $query->where('status', VoucherStatus::Posted))
            ->exists();
    }

    private function entries(int $ledgerId): bool
    {
        return VoucherEntry::query()->where('ledger_id', $ledgerId)->exists();
    }

    private function conflict(OdAccount $account, BankAccount $bank, string $direction, string $message): void
    {
        SyncLinks::store(self::KEY, $account, $bank, 'conflict', $message);
        SyncFailureLogger::write(self::KEY, $direction, $direction === 'dms_to_books' ? $account : $bank, $message);
    }
}
