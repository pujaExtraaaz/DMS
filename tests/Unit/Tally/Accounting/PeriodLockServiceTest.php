<?php

namespace Tests\Unit\Tally\Accounting;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Accounting\PeriodLockService;
use Tally\Models\AccountingPeriod;
use Tally\Models\FinancialYear;

class PeriodLockServiceTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_ensure_monthly_periods_creates_months_for_fy(): void
    {
        $company = $this->makeCompany(['name' => 'Lock Co']);
        $year = FinancialYear::query()->create([
            'company_id' => $company->id,
            'name' => 'FY 2025-26',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
            'is_closed' => false,
        ]);

        app(PeriodLockService::class)->ensureMonthlyPeriods($company, $year);

        $this->assertSame(12, AccountingPeriod::query()->where('financial_year_id', $year->id)->count());
    }

    public function test_assert_date_open_rejects_locked_period(): void
    {
        $company = $this->makeCompany(['name' => 'Lock Co 2']);
        $year = FinancialYear::query()->create([
            'company_id' => $company->id,
            'name' => 'FY',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
            'is_closed' => false,
        ]);
        $user = User::factory()->create();
        $service = app(PeriodLockService::class);
        $service->ensureMonthlyPeriods($company, $year);
        $period = AccountingPeriod::query()->where('financial_year_id', $year->id)->where('name', 'Apr 2025')->firstOrFail();
        $service->lock($period, $user);

        $this->expectException(ValidationException::class);
        $service->assertDateOpen($company, '2025-04-15', $year);
    }
}
