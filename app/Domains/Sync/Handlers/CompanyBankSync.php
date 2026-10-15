<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Banking\Models\OdAccount;
use App\Domains\Organization\Models\Company;
use App\Domains\Sync\Support\BooksCompany;

/**
 * Ensures the DMS company bank profile is present as OdAccount and pushed to Books BankAccount.
 */
class CompanyBankSync
{
    public const KEY = 'company_bank';

    public function __construct(private readonly BankAccountSync $banks) {}

    public function push(Company $company): ?OdAccount
    {
        $name = trim((string) $company->bank_name);
        $number = trim((string) $company->bank_account_no);
        if ($name === '' || $number === '') {
            return null;
        }

        $od = OdAccount::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'account_number' => $number,
            ],
            [
                'bank_name' => $name,
                'ifsc_code' => $company->bank_ifsc,
                'od_limit' => $company->od_limit ?? 0,
                'interest_rate' => $company->interest_rate ?? 0,
                'interest_calculation_method' => 'daily_simple',
                'effective_from' => now()->startOfMonth()->toDateString(),
                'status' => 'active',
            ],
        );

        // Ensure Books company link exists before bank push.
        if (! BooksCompany::forOrganization((int) $company->id)) {
            return $od;
        }

        $this->banks->push($od);

        return $od;
    }
}
