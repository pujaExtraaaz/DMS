<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\FinancialYear as BooksYear;

class FinancialYearSync
{
    public const KEY = 'financial_year';

    public function sync(Model $model): void
    {
        if ($model instanceof FinancialYear) {
            $this->push($model);
        }
    }

    public function push(FinancialYear $year): void
    {
        if (! $year->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $year->company_id);
        if (! $company) {
            return;
        }

        $books = BooksYear::query()->find(SyncLinks::acctId(self::KEY, $year));
        $name = $year->name ?: BooksYear::labelForPeriod($year->starts_on, $year->ends_on);

        if (! $books) {
            $books = BooksYear::query()
                ->where('company_id', $company->id)
                ->where(function ($q) use ($name, $year) {
                    $q->where('name', $name);
                    if ($year->starts_on && $year->ends_on) {
                        $q->orWhere(function ($inner) use ($year) {
                            $inner->whereDate('start_date', $year->starts_on->toDateString())
                                ->whereDate('end_date', $year->ends_on->toDateString());
                        });
                    }
                })
                ->first();
        }

        $attributes = [
            'company_id' => $company->id,
            'name' => $name,
            'start_date' => $year->starts_on?->toDateString(),
            'end_date' => $year->ends_on?->toDateString(),
            'is_active' => ! $year->is_closed,
        ];

        $books ? $books->update($attributes) : $books = BooksYear::query()->create($attributes);
        SyncLinks::store(self::KEY, $year, $books);
        BooksCompany::putYearMap((int) $year->company_id, (int) $year->id, (int) $books->id, (bool) $year->is_current);
    }

    public function remove(Model $source): void
    {
        if (! $source instanceof FinancialYear) {
            return;
        }
        SyncLinks::forget(self::KEY, $source);
    }
}
