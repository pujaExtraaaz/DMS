<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Organization\Models\Company;
use Illuminate\Database\Eloquent\Model;

/** Auto-pushes DMS company bank profile into Books on every company save. */
class CompanySync
{
    public const KEY = 'company';

    public function __construct(private readonly CompanyBankSync $banks) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Company) {
            $this->banks->push($model);
        }
    }
}
