<?php

namespace Tests\Unit\Tally\Payables;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Models\Ledger;
use Tally\Models\Voucher;
use Tally\Payables\ExpenseClaimService;

class ExpenseClaimPostingTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_approve_posts_accrual_and_pay_posts_payment(): void
    {
        $company = $this->makeCompany();
        $year = $company->financialYears()->first();
        $branch = $company->branches()->first();
        $user = User::factory()->create();

        $expenses = $company->accountGroups()->where('code', 'INDIRECT_EXPENSES')->first();
        $liabilities = $company->accountGroups()->where('code', 'CURRENT_LIABILITIES')->first();
        $bankGroup = $company->accountGroups()->where('code', 'BANK')->first();

        $expenseLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $expenses->id,
            'name' => 'Travel Expense',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);
        $employeeLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $liabilities->id,
            'name' => 'Employee Reimbursement',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);
        $bankLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $bankGroup->id,
            'name' => 'HDFC Current',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        $service = app(ExpenseClaimService::class);
        $claim = $service->save($company, $branch, $year, $user, [
            'claim_date' => $year->start_date->toDateString(),
            'employee_ledger_id' => $employeeLedger->id,
            'status' => 'submitted',
            'lines' => [
                [
                    'expense_date' => $year->start_date->toDateString(),
                    'category' => 'Travel',
                    'expense_ledger_id' => $expenseLedger->id,
                    'description' => 'Taxi',
                    'amount' => '250.00',
                ],
                [
                    'expense_date' => $year->start_date->toDateString(),
                    'category' => 'Travel',
                    'expense_ledger_id' => $expenseLedger->id,
                    'description' => 'Meals',
                    'amount' => '150.00',
                ],
            ],
        ]);

        $this->assertSame('400.00', (string) $claim->amount);
        $this->assertSame('submitted', $claim->status);

        $approved = $service->approve($company, $claim, $user);

        $this->assertSame('approved', $approved->status);
        $this->assertSame($user->id, $approved->approved_by);
        $this->assertNotNull($approved->approved_at);
        $this->assertNotNull($approved->accrual_voucher_id);

        $accrual = Voucher::query()->findOrFail($approved->accrual_voucher_id);
        $this->assertTrue($accrual->isPosted());
        $this->assertSame('400.00', (string) $accrual->total_debit);
        $this->assertSame('400.00', (string) $accrual->total_credit);

        $paid = $service->pay($company, $approved, $user, (int) $bankLedger->id);

        $this->assertSame('paid', $paid->status);
        $this->assertNotNull($paid->payment_voucher_id);

        $payment = Voucher::query()->findOrFail($paid->payment_voucher_id);
        $this->assertTrue($payment->isPosted());
        $this->assertSame('payment', $payment->voucher_type->value);
        $this->assertSame('400.00', (string) $payment->total_debit);
    }
}
