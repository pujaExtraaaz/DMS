<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Deal\Models\ExpenseType;
use App\Domains\Organization\Models\Company;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use Illuminate\Database\Eloquent\Model;
use Tally\Accounting\OpeningBalanceType;
use Tally\Models\AccountGroup;
use Tally\Models\Ledger;

/** Deal expense types → Books expense ledgers (per linked company). */
class ExpenseTypeSync
{
    public const KEY = 'expense_type';

    public function sync(Model $model): void
    {
        if ($model instanceof ExpenseType) {
            $this->push($model);
        }
    }

    public function push(ExpenseType $type): void
    {
        Company::query()->orderBy('id')->each(function (Company $org) use ($type) {
            $company = BooksCompany::forOrganization((int) $org->id);
            if (! $company) {
                return;
            }

            $group = AccountGroup::query()
                ->where('company_id', $company->id)
                ->where('code', 'INDIRECT_EXPENSES')
                ->first()
                ?: AccountGroup::query()
                    ->where('company_id', $company->id)
                    ->where('code', 'DIRECT_EXPENSES')
                    ->first();
            if (! $group) {
                return;
            }

            $ledger = Ledger::query()->find(SyncLinks::acctId(self::KEY.'.'.$org->id, $type));
            $code = SyncNames::unique(
                Ledger::query()->where('company_id', $company->id),
                'code',
                SyncNames::code($type->code ?: ('EXP'.$type->id), 20),
                $ledger?->id,
                20
            );
            $name = SyncNames::unique(
                Ledger::query()->where('company_id', $company->id),
                'name',
                $type->name,
                $ledger?->id
            );

            $attributes = [
                'company_id' => $company->id,
                'account_group_id' => $group->id,
                'name' => $name,
                'code' => $code,
                'opening_balance' => 0,
                'opening_balance_type' => OpeningBalanceType::Debit,
                'is_active' => (bool) $type->is_active,
                'is_system' => false,
            ];

            $ledger ? $ledger->update($attributes) : $ledger = Ledger::query()->create($attributes);
            SyncLinks::store(self::KEY.'.'.$org->id, $type, $ledger);
        });
    }
}
