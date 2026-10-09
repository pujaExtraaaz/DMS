<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Master\Models\Uom;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tally\Models\Company;
use Tally\Models\Unit;

class UomSync
{
    public function entityKey(int $acctCompanyId): string
    {
        return 'uom:'.$acctCompanyId;
    }

    public function sync(Model $model): void
    {
        if ($model instanceof Uom) {
            $this->push($model);

            return;
        }

        if ($model instanceof Unit) {
            $this->pull($model);
        }
    }

    public function push(Uom $uom): void
    {
        $companyIds = DB::table('accounting_company_links')->pluck('acct_company_id');
        foreach ($companyIds as $acctCompanyId) {
            $company = Company::query()->find($acctCompanyId);
            if ($company) {
                $this->pushToCompany($uom, $company);
            }
        }
    }

    public function pushToCompany(Uom $uom, Company $company): Unit
    {
        $key = $this->entityKey($company->id);
        $unit = Unit::query()->find(SyncLinks::acctId($key, $uom));
        $symbol = $this->symbol($company, $uom->code ?: $uom->name, $unit?->id);
        $name = SyncNames::unique(
            Unit::query()->where('company_id', $company->id),
            'name',
            $uom->name,
            $unit?->id,
            255
        );

        $attributes = [
            'company_id' => $company->id,
            'name' => $name,
            'symbol' => $symbol,
            'decimal_places' => 2,
            'is_active' => (bool) $uom->is_active,
        ];

        $unit ? $unit->update($attributes) : $unit = Unit::query()->create($attributes);
        SyncLinks::store($key, $uom, $unit);

        return $unit;
    }

    public function pull(Unit $unit): void
    {
        $key = $this->entityKey($unit->company_id);
        $uom = Uom::query()->find(SyncLinks::dmsId($key, $unit));
        $code = SyncNames::unique(
            Uom::query(),
            'code',
            SyncNames::code($unit->symbol ?: $unit->name, 20),
            $uom?->id,
            20
        );

        $attributes = [
            'name' => $unit->name,
            'code' => $code,
            'is_active' => (bool) $unit->is_active,
        ];

        $uom ? $uom->update($attributes) : $uom = Uom::query()->create($attributes);
        SyncLinks::store($key, $uom, $unit);
    }

    private function symbol(Company $company, string $raw, ?int $ignoreId): string
    {
        return SyncNames::unique(
            Unit::query()->where('company_id', $company->id),
            'symbol',
            SyncNames::code($raw, 16),
            $ignoreId,
            16
        );
    }
}
