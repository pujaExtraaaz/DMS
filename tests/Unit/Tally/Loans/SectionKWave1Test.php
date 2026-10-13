<?php

namespace Tests\Unit\Tally\Loans;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Loans\LoanService;

class SectionKWave1Test extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_loan_schedule_installment_count_equals_tenure(): void
    {
        $company = $this->makeCompany();
        $tenure = 24;

        $loan = app(LoanService::class)->create($company, [
            'loan_number' => 'LN-001',
            'lender_name' => 'Test Bank',
            'loan_type' => 'term',
            'disbursement_date' => '2025-04-01',
            'principal' => '120000.00',
            'interest_rate' => 12,
            'tenure_months' => $tenure,
        ]);

        $this->assertCount($tenure, $loan->schedules);
        $this->assertSame($tenure, $loan->schedules()->count());
        $this->assertSame(1, (int) $loan->schedules->first()->installment_no);
        $this->assertSame($tenure, (int) $loan->schedules->last()->installment_no);
    }
}
