<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Organization\Models\Company;
use App\Domains\Sync\Support\BooksCompany;
use Illuminate\Database\Eloquent\Model;

/** Creates/updates the Books company from DMS and then pushes the company bank profile. */
class CompanySync
{
    public const KEY = 'company';

    public function __construct(private readonly CompanyBankSync $banks) {}

    public function sync(Model $model): void
    {
        if (! $model instanceof Company) {
            return;
        }

        BooksCompany::ensureForOrganization($model);
        $this->banks->push($model);
    }
}