<?php

namespace Tests\Unit\Tally\Payables;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Models\Ledger;
use Tally\Models\Voucher;
use Tally\Payables\AccruedExpenseService;
use Tally\Payables\PrepaidExpenseService;

class PrepaidAccrualPostingTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_prepaid_recognize_posts_journal_and_updates_recognized_amount(): void
    {
        $company = $this->makeCompany();
        $user = User::factory()->create();
        $branch = $company->branches()->first();

        $currentAssets = $company->accountGroups()->where('code', 'CURRENT_ASSETS')->first();
        $expenses = $company->accountGroups()->where('code', 'INDIRECT_EXPENSES')->first();

        $prepaidLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $currentAssets->id,
            'name' => 'Prepaid Rent',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);
        $expenseLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $expenses->id,
            'name' => 'Rent Expense',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        $service = app(PrepaidExpenseService::class);
        $prepaid = $service->save($company, [
            'branch_id' => $branch->id,
            'name' => 'Office rent',
            'prepaid_ledger_id' => $prepaidLedger->id,
            'expense_ledger_id' => $expenseLedger->id,
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'total_amount' => '1200.00',
            'frequency' => 'monthly',
        ]);

        $done = $service->recognizeDue('2025-04-30', $user);

        $this->assertCount(1, $done);
        $this->assertSame('100.00', (string) $done[0]->recognized_amount);
        $this->assertSame('active', $done[0]->status);

        $voucher = Voucher::query()
            ->where('company_id', $company->id)
            ->where('narration', 'Prepaid recognition: Office rent')
            ->first();

        $this->assertNotNull($voucher);
        $this->assertTrue($voucher->isPosted());
        $this->assertCount(2, $voucher->entries);
    }

    public function test_accrued_expense_post_creates_journal(): void
    {
        $company = $this->makeCompany();
        $user = User::factory()->create();
        $branch = $company->branches()->first();

        $liabilities = $company->accountGroups()->where('code', 'CURRENT_LIABILITIES')->first();
        $expenses = $company->accountGroups()->where('code', 'INDIRECT_EXPENSES')->first();

        $expenseLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $expenses->id,
            'name' => 'Utilities Expense',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);
        $accrualLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $liabilities->id,
            'name' => 'Accrued Utilities',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $service = app(AccruedExpenseService::class);
        $accrual = $service->save($company, [
            'branch_id' => $branch->id,
            'name' => 'March power',
            'accrual_date' => '2025-04-30',
            'expense_ledger_id' => $expenseLedger->id,
            'accrual_ledger_id' => $accrualLedger->id,
            'amount' => '500.00',
        ]);

        $posted = $service->post($accrual, $user);

        $this->assertNotNull($posted->accrual_voucher_id);
        $voucher = Voucher::query()->findOrFail($posted->accrual_voucher_id);
        $this->assertTrue($voucher->isPosted());
    }
}
