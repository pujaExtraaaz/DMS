<?php

namespace Tests\Support;

use Tally\Models\Company;

trait CreatesBooksCompany
{
    protected function makeCompany(array $overrides = []): Company
    {
        return Company::provision(array_merge([
            'name' => 'Test Co '.uniqid(),
            'country' => 'India',
            'financial_year_start' => '2025-04-01',
            'financial_year_end' => '2026-03-31',
            'is_active' => true,
            'accounting_basis' => 'accrual',
        ], $overrides));
    }
}
