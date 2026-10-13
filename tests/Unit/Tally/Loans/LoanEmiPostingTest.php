<?php

namespace Tests\Unit\Tally\Loans;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Accounting\VoucherType;
use Tally\Loans\LoanService;
use Tally\Models\BankAccount;
use Tally\Models\Ledger;
use Tally\Models\Voucher;

class LoanEmiPostingTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_disburse_and_pay_installment_post_vouchers(): void
    {
        $company = $this->makeCompany();
        $user = User::factory()->create();

        $bankGroup = $company->accountGroups()->where('code', 'BANK')->first();
        $loansGroup = $company->accountGroups()->where('code', 'LOANS')->first();
        $expenses = $company->accountGroups()->where('code', 'INDIRECT_EXPENSES')->first();

        $bankLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $bankGroup->id,
            'name' => 'HDFC Current',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);
        $loanLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $loansGroup->id,
            'name' => 'Term Loan - Test Bank',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);
        $interestLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $expenses->id,
            'name' => 'Interest on Loan',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        $bankAccount = BankAccount::query()->create([
            'company_id' => $company->id,
            'ledger_id' => $bankLedger->id,
            'bank_name' => 'HDFC',
            'account_number' => '123',
            'ifsc' => 'HDFC0001',
        ]);

        $service = app(LoanService::class);
        $loan = $service->create($company, [
            'loan_number' => 'LN-EMI-1',
            'lender_name' => 'Test Bank',
            'loan_type' => 'term',
            'disbursement_date' => '2025-04-01',
            'principal' => '120000.00',
            'interest_rate' => 12,
            'tenure_months' => 12,
            'loan_ledger_id' => $loanLedger->id,
            'interest_ledger_id' => $interestLedger->id,
            'bank_account_id' => $bankAccount->id,
        ]);

        $disbursement = $service->disburse($loan->fresh(), $user);

        $this->assertTrue($disbursement->isPosted());
        $this->assertSame(VoucherType::Journal, $disbursement->voucher_type);
        $this->assertSame($disbursement->id, $loan->fresh()->disbursement_voucher_id);
        $this->assertCount(2, $disbursement->entries);

        $schedule = $loan->schedules()->orderBy('installment_no')->first();
        $this->assertFalse($schedule->is_paid);

        $payment = $service->payInstallment($schedule, $user);

        $this->assertTrue($payment->isPosted());
        $this->assertSame(VoucherType::Payment, $payment->voucher_type);
        $this->assertTrue($schedule->fresh()->is_paid);
        $this->assertSame($payment->id, $schedule->fresh()->payment_voucher_id);

        $principalPaid = (float) $schedule->principal_component;
        $outstanding = (float) $loan->fresh()->outstanding_principal;
        $this->assertEqualsWithDelta(120000 - $principalPaid, $outstanding, 0.02);

        $this->assertNotNull(Voucher::query()->find($payment->id));
        $this->assertGreaterThanOrEqual(2, $payment->entries()->count());
    }
}
