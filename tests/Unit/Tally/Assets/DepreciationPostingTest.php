<?php

namespace Tests\Unit\Tally\Assets;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Assets\DepreciationService;
use Tally\Assets\FixedAssetService;
use Tally\Models\FixedAssetCategory;
use Tally\Models\Ledger;
use Tally\Models\Voucher;

class DepreciationPostingTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_run_for_period_posts_journal_and_sets_voucher_id(): void
    {
        $company = $this->makeCompany();
        $user = User::factory()->create();

        $fixedAssets = $company->accountGroups()->where('code', 'FIXED_ASSETS')->first();
        $expenses = $company->accountGroups()->where('code', 'INDIRECT_EXPENSES')->first();

        $assetLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $fixedAssets->id,
            'name' => 'Plant & Machinery',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);
        $accumLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $fixedAssets->id,
            'name' => 'Accumulated Depreciation',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);
        $expenseLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $expenses->id,
            'name' => 'Depreciation Expense',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        $category = FixedAssetCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'Machinery',
            'depreciation_method' => 'straight_line',
            'useful_life_years' => 5,
            'residual_percent' => 5,
            'asset_ledger_id' => $assetLedger->id,
            'accum_depr_ledger_id' => $accumLedger->id,
            'depr_expense_ledger_id' => $expenseLedger->id,
            'is_active' => true,
        ]);

        app(FixedAssetService::class)->create($company, [
            'category_id' => $category->id,
            'name' => 'Lathe',
            'acquisition_date' => $company->financial_year_start->toDateString(),
            'gross_value' => '120000.00',
        ]);

        $entries = app(DepreciationService::class)->runForPeriod($company, '2025-04', null, $user);

        $this->assertCount(1, $entries);
        $this->assertNotNull($entries[0]->voucher_id);

        $voucher = Voucher::query()->findOrFail($entries[0]->voucher_id);
        $this->assertTrue($voucher->isPosted());
        $this->assertSame('Depreciation '.$entries[0]->fixedAsset->asset_code.' 2025-04', $voucher->narration);
        $this->assertCount(2, $voucher->entries);
    }
}
